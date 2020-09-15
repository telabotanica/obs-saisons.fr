export function createMap(
    elementIdAttr,
    lat = 47.0504,
    lng = 2.2347,
    zoom = 6,
    hasZoomControl = true,
    isDraggable = true,
    hasMarker = false,
) {
    const map = L.map(elementIdAttr, {zoomControl: hasZoomControl}).setView([lat, lng], zoom);
    map.markers = [];

    L.tileLayer(
    'https://osm.tela-botanica.org/tuiles/osmfr/{z}/{x}/{y}.png', {
        attribution: 'Data © <a href="http://osm.org/copyright">OpenStreetMap</a>',
        maxZoom: 18
    }).addTo(map);

    map.addLayer(new L.FeatureGroup());

    if (hasMarker) {
        let marker = new L.Marker(
            {
                'lat': lat,
                'lng': lng
            },
            {
                draggable: isDraggable,
            }
        );
        map.addLayer(marker);
        map.markers.push(marker);
    }

    return map;
}
