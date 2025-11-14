<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nominatim Search with Map</title>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        body { font-family: sans-serif; padding: 20px; }
        
        /* 2. The map MUST have a defined height */
        #map { 
            height: 400px; 
            width: 100%; 
            margin-top: 20px; 
            border: 1px solid #ccc;
        }
        .controls { margin-bottom: 10px; }
    </style>
</head>
<body>

    <h3>Address Map Search</h3>
    
    <div class="controls">
        <input type="text" id="query" placeholder="Enter address " size="40">
        <button onclick="searchAndMap()">Show on Map</button>
    </div>
    <div id="status"></div>

    <div id="map"></div>

    <script>
        // --- A. Initialize the Map ---
        // Start centered on a default location (London)
        const map = L.map('map').setView([51.505, -0.09], 13);

        // Add the OpenStreetMap tiles
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        // Create a variable to hold the current marker so we can remove it later
        let currentMarker = null;

        // --- B. The Search Function ---
        async function searchAndMap() {
            const query = document.getElementById('query').value;
            const statusDiv = document.getElementById('status');
            
            if (!query) return;

            statusDiv.innerHTML = "Searching...";

            const url = `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(query)}&format=jsonv2&limit=1`;

            try {
                const response = await fetch(url);
                const data = await response.json();

                if (data.length === 0) {
                    statusDiv.innerHTML = "Location not found.";
                    return;
                }

                // Get the first result
                const place = data[0];
                const lat = place.lat;
                const lon = place.lon;

                // Update Status
                statusDiv.innerHTML = `Found: <b>${place.display_name}</b>`;

                // --- C. Update the Map ---
                
                // 1. Move the map view to the new coordinates
                map.setView([lat, lon], 16); // 16 is the zoom level

                // 2. Remove previous marker if it exists
                if (currentMarker) {
                    map.removeLayer(currentMarker);
                }

                // 3. Add a new marker
                currentMarker = L.marker([lat, lon]).addTo(map)
                    .bindPopup(place.display_name)
                    .openPopup();

            } catch (error) {
                statusDiv.innerHTML = "Error: " + error.message;
            }
        }
    </script>

</body>
</html>