<?php
// 1. Authenticate and connect to DB
include('../../includes/auth.php');
include('../../includes/db.php');

// 2. Get user data from session
$user_name = $_SESSION['user_name'];
$role = $_SESSION['role'];

// 3. --- LANDLORD ONLY ---
if ($role !== 'landlord') {
    header('Location: ../../dashboard.php'); 
    exit;
}

// 4. Get this landlord's property addresses from the view
$landlordEmail = $_SESSION['user_email'] ?? null;
$properties = [];

if ($landlordEmail) {
    $stmt = $conn->prepare("
        SELECT full_property_address 
        FROM LandlordAndPropertyAddresses 
        WHERE landlord_email = ? AND full_property_address IS NOT NULL
    ");
    $stmt->bind_param("s", $landlordEmail);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $properties[] = $row['full_property_address']; 
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Property Map</title>
    
    <!-- Leaflet.js CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <!-- CSS for this page ONLY -->
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; 
            margin: 0; 
            padding: 0;
            background-color: #f9f9f9; 
        }
        .container {
            max-width: 900px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #0077cc;
            text-decoration: none;
            font-weight: 600;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        #map { 
            height: 500px; 
            width: 100%; 
            margin-top: 15px; 
            border: 1px solid #ccc;
            border-radius: 8px;
        }
        #property-list {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #eee;
            background: #fdfdfd;
            padding: 10px;
            border-radius: 8px;
        }
        #property-list ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        #property-list li a {
            display: block;
            padding: 8px 12px;
            text-decoration: none;
            color: #0077cc;
            border-radius: 5px;
        }
        #property-list li a:hover {
            background-color: #f0f0f0;
            cursor: pointer;
        }
        #status {
            margin-top: 10px;
            font-style: italic;
            color: #555;
        }
    </style>
</head>
<body>

    <div class="container">
        <!-- Link to go back to the main dashboard -->
        <a href="../../dashboard.php" class="back-link">Back to Dashboard</a>

        <h2>My Property Map</h2>
        <p>Click on an address from your list to see it on the map.</p>

        <h3>My Property List</h3>
        
        <div id="property-list">
            <?php if (!empty($properties)): ?>
                <ul>
                    <?php 
                    // This is the correct loop variable
                    foreach ($properties as $address): 
                    ?>
                        <li>
                            <!-- 
                                FIX 1: Use $address, not $full_address 
                                (and use ENT_QUOTES for safety)
                            -->
                            <a href="#" onclick="showOnMap('<?php echo htmlspecialchars($address, ENT_QUOTES); ?>'); return false;">
                                <!-- FIX 2: Use $address here as well -->
                                <?php echo htmlspecialchars($address); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>You have no properties with registered addresses.</p>
            <?php endif; ?>
            <!-- FIX 3: Removed the stray 's' here -->
        </div> 
        <!-- FIX 4: Closed the 'property-list' div *before* the map and status -->

        <div id="status"></div>
        <div id="map"></div>
    </div>

</body>

<!-- JAVASCRIPT -->
<script>
    // --- A. Initialize the Map ---
    const map = L.map('map').setView([43.7, -79.2], 10);

    // Add the OpenStreetMap tiles
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);

    let currentMarker = null;

    // --- B. The Search Function ---
    // FIX 5: Renamed function to 'showOnMap' to match the onclick
    async function showOnMap(address) {
        const statusDiv = document.getElementById('status');
        
        if (!address) return;

        statusDiv.innerHTML = `Searching for <b>${address}</b>...`;

        // FIX 6: ADDED USER-AGENT (CRITICAL)
        // YOU MUST CHANGE THIS to your real email or app name
        const userAgent = "RentalApp/1.0 (your-email@example.com)";

        const url = `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(address)}&format=jsonv2&limit=1`;

        try {
            // Pass the User-Agent in the fetch headers
            const response = await fetch(url, {
                headers: { 'User-Agent': userAgent }
            });
            const data = await response.json();

            if (data.length === 0) {
                statusDiv.innerHTML = `Location not found for <b>${address}</b>`;
                return;
            }

            const place = data[0];
            const lat = place.lat;
            const lon = place.lon;

            statusDiv.innerHTML = `Found: <b>${place.display_name}</b>`;

            // --- C. Update the Map ---
            map.setView([lat, lon], 16); 

            if (currentMarker) {
                map.removeLayer(currentMarker);
            }

            currentMarker = L.marker([lat, lon]).addTo(map)
                .bindPopup(place.display_name)
                .openPopup();

        } catch (error) {
            statusDiv.innerHTML = "Error: " + error.message;
        }
    }
</script>
</html>