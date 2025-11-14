<?php
header("Content-Type: application/json");

// Load DB + sessions
include("../../includes/db.php");
session_start();

// ======= Load API Key From .env ======= //
$envPath = $_SERVER['DOCUMENT_ROOT'] . "/env/.env";

if (!file_exists($envPath)) {
    echo json_encode(["error" => "Environment file not found"]);
    exit;
}

$env = parse_ini_file($envPath);
$API_KEY = $env["GEMINI_API_KEY"] ?? null;

if (!$API_KEY) {
    echo json_encode(["error" => "Gemini API key missing in .env"]);
    exit;
}

// ======= Read User Message ======= //
$input = json_decode(file_get_contents("php://input"), true);
$userMessage = trim($input["message"] ?? "");

if ($userMessage === "") {
    echo json_encode(["error" => "Empty message"]);
    exit;
}

// Logged-in landlord email
$landlordEmail = $_SESSION["user_email"] ?? null;

if (!$landlordEmail) {
    echo json_encode(["error" => "No landlord session"]);
    exit;
}

// ====================================================================================
// 1. FETCH RELEVANT LANDLORD DATA
// ====================================================================================

// -------- PROPERTIES -------- //
$properties = [];
$stmt = $conn->prepare("
    SELECT PropertyID, Address
    FROM Property
    JOIN Landlord ON Property.LandlordID = Landlord.LandlordID
    WHERE Landlord.Email = ?
");
$stmt->bind_param("s", $landlordEmail);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) $properties[] = $row;


// -------- TENANTS + LEASES -------- //
$tenants = [];
$stmt = $conn->prepare("
    SELECT 
        t.TenantID,
        t.Name,
        p.Address,
        ls.RentPrice,
        ls.EndDate
    FROM Tenants t
    LEFT JOIN Property p ON t.PropertyID = p.PropertyID
    LEFT JOIN Lease ls ON ls.TenantID = t.TenantID
    JOIN Landlord l ON p.LandlordID = l.LandlordID
    WHERE l.Email = ?
");
$stmt->bind_param("s", $landlordEmail);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) $tenants[] = $row;


// -------- PAYMENTS (Last 30 days) -------- //
$payments = [];
$stmt = $conn->prepare("
    SELECT 
        pmt.LeaseNum,
        p.Address,
        pmt.Amount,
        pmt.Status,
        pmt.PaymentDate
    FROM Payments pmt
    JOIN Lease ls ON pmt.LeaseNum = ls.LeaseNum
    JOIN Property p ON ls.PropertyID = p.PropertyID
    JOIN Landlord l ON p.LandlordID = l.LandlordID
    WHERE l.Email = ?
      AND pmt.PaymentDate >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
");
$stmt->bind_param("s", $landlordEmail);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) $payments[] = $row;


// -------- MAINTENANCE REQUESTS (Last 30 days) -------- //
$requests = [];
$stmt = $conn->prepare("
    SELECT 
        mr.RequestNUM,
        t.Name AS TenantName,
        p.Address AS PropertyAddress,
        mr.Issue,
        mr.Cost,
        mr.Current_Status,
        mr.RequestDate
    FROM MaintenanceRequest mr
    JOIN Tenants t ON mr.TenantID = t.TenantID
    JOIN Property p ON t.PropertyID = p.PropertyID
    JOIN Landlord l ON p.LandlordID = l.LandlordID
    WHERE l.Email = ?
      AND mr.RequestDate >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ORDER BY mr.RequestDate DESC
");
$stmt->bind_param("s", $landlordEmail);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) $requests[] = $row;


// ====================================================================================
// 2. BUILD AI PROMPT
// ====================================================================================

$context = "
You are an expert AI assistant for a rental property management dashboard.
You analyze financial performance, maintenance issues, tenants, property conditions, and profitability.

Here is the landlord's current data from the database:

=== PROPERTIES ===
" . json_encode($properties, JSON_PRETTY_PRINT) . "

=== TENANTS + LEASES ===
" . json_encode($tenants, JSON_PRETTY_PRINT) . "

=== PAYMENTS (last 30 days) ===
" . json_encode($payments, JSON_PRETTY_PRINT) . "

=== MAINTENANCE REQUESTS (last 30 days) ===
" . json_encode($requests, JSON_PRETTY_PRINT) . "

Using this data, provide helpful, accurate answers for the landlord.
DO NOT hallucinate — if data is missing, mention it clearly.
Provide reasoning and actionable insights.
";

$fullPrompt = $context . "\n\nLandlord Question: " . $userMessage;


// ====================================================================================
// 3. SEND REQUEST TO GEMINI API
// ====================================================================================
$systemPrompt = <<<PROMPT
You are a rental property assistant.
Keep responses short, clean and professional.
Avoid markdown formatting like **bold**, lists, or long paragraphs.
Prefer concise 1–3 sentence answers unless the user asks for more detail.
Never include unnecessary symbols or formatting.
PROMPT;


$payload = [
    "contents" => [
        [
            "role" => "user",
            "parts" => [
                ["text" => $fullPrompt]
            ]
        ]
    ]
];


$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=$API_KEY",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
    CURLOPT_POSTFIELDS => json_encode($payload)
]);

$response = curl_exec($curl);
$httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($httpStatus !== 200) {
    echo json_encode([
        "error" => "Gemini API error",
        "status" => $httpStatus,
        "raw" => $response
    ]);
    exit;
}

$json = json_decode($response, true);

// =======================
// UNIVERSAL GEMINI PARSER
// =======================
$reply = null;

// Older Gemini format: parts[].text
if (isset($json["candidates"][0]["content"]["parts"][0]["text"])) {
    $reply = $json["candidates"][0]["content"]["parts"][0]["text"];
}
// Newer Gemini format: parts[].content
elseif (isset($json["candidates"][0]["content"]["parts"][0]["content"])) {
    $reply = $json["candidates"][0]["content"]["parts"][0]["content"];
}
// Some models respond with "output_text"
elseif (isset($json["candidates"][0]["output_text"])) {
    $reply = $json["candidates"][0]["output_text"];
}
// Gemini sometimes responds directly with top-level "text"
elseif (isset($json["text"])) {
    $reply = $json["text"];
}
// Gemini error message
elseif (isset($json["error"]["message"])) {
    $reply = "AI Error: " . $json["error"]["message"];
}
// Fallback
else {
    $reply = "Sorry, I couldn't understand the AI response.";
}


// ====================================================================================
// 4. RETURN AI REPLY
// ====================================================================================

ob_clean(); //clear any accidental output
echo json_encode(["reply" => $reply]);
exit;
?>
