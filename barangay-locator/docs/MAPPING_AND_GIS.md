# Mapping and GIS

## Library and tiles
Leaflet 1.9.4. The app uses `assets/vendor/leaflet/` if that folder exists; otherwise it loads from unpkg with SRI (hashes unverified — see KNOWN_ISSUES #3). To vendor: download Leaflet 1.9.4 from leafletjs.com and copy `leaflet.js`, `leaflet.css` and `images/` into `assets/vendor/leaflet/`.

Tile URL and attribution are settings (Settings → Map tiles). The default is the public OpenStreetMap tile server, which is **not** a free-for-all production host: its usage policy restricts heavy use and requires attribution. A visible warning appears until an admin ticks that the chosen provider permits production use. For a kiosk or busy site use a hosted provider with suitable terms, or self-host tiles.

## Coordinates
- Stored as decimal degrees (WGS 84): latitude -90..90, longitude -180..180, validated on input and by CHECK constraints.
- Blank means unknown. The system never geocodes or guesses.
- A point is plotted only when `coords_verified = 1`, unless an admin enables *plot unverified coordinates*. Unplotted records still appear in lists.

## Boundaries
No boundary ships with the app. An admin can paste GeoJSON Polygon/MultiPolygon (≤ 1 MB) and must name its source. The map shows it with a note that it was supplied by the named source; it is never presented as official unless the barangay confirmed it.

## Nearby and directions
- "Nearby" uses a bounding box prefilter plus haversine great-circle distance. It is **straight-line distance** and labelled that way; it is not walking or driving distance or time.
- Directions: the Directions page links to an external maps app using the template in Settings (`{from_lat} {from_lng} {to_lat} {to_lng}`). Routing inside the app (OSRM) is not implemented.
- Location permission is requested only when the visitor presses "Use my location" (or opens a link with `?locate=1`). Browsing, searching and the map work without it. Browsers require HTTPS for geolocation.

## Marker rules
Marker colour/icon come from the category. Sample and awaiting-verification records are labelled in popups. Emergency styling appears only for staff-verified emergency facilities.
