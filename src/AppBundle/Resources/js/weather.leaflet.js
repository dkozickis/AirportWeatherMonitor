var icons = {};

for (var i = 0; i < 4; i++) {
    for (var a = 0; a < 4; a++) {
        icons[i + '_' + a] = new L.Icon({
            iconUrl: '../img/' + i + '.png',
            iconSize: [20, 20],
            shadowUrl: '../img/' + a + '.png',
            shadowSize: [30, 30]
        });
    }
}

/*
 Leaflet Init
 */
var map = L.map('map').setView([24, 56], 4);

/*
 Function to attach popups to markers
 */
function airportMarker(feature, layer) {
    var popupContent = [];
    if (feature.properties.metarStatus == 0) {
        popupContent.push('<span class="red">METAR NOT PROCESSED</span>');
    }
    popupContent.push(feature.properties.colorizedMetar);
    popupContent.push("");
    if (feature.properties.tafStatus == 0) {
        popupContent.push('<span class="red">TAF NOT PROCESSED</span>');
    }
    popupContent.push(feature.properties.colorizedTaf);
    layer.bindPopup(popupContent.join("</br>"));
    layer.setIcon(icons[feature.properties.metarStatus + '_' + feature.properties.tafStatus]);
}

/*
 Base Map for our leaflet map
 */
var baseMap = L.tileLayer('http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: 'Created & Maintained by Deniss Kozickis @ EY',
    maxZoom: 18
}).addTo(map);

/*
 Destination airports layers
 */
var airportsDest = new L.GeoJSON.AJAX(destinationWeather, {
    onEachFeature: airportMarker
});

airportsDest.addTo(map);

airportsDest.on('data:loaded', function () {
    destBounds = airportsDest.getBounds();
    this.map.fitBounds(destBounds);
}.bind());

/*
 Alternate airports layer
 */
var airportsAltn = new L.GeoJSON.AJAX(alternateWeather, {
    onEachFeature: airportMarker
});

/*
 Array with Destination/Alternate airports layers
 */
var airports = {
    "Destinations": airportsDest,
    "Alternates": airportsAltn
};

/*
 Adding control to map
 */
L.control.layers(null, airports).addTo(map);

/*
 Adding box for outdated WX display
 */
var outdatedWeatherBox = L.control({position: 'bottomright'});

outdatedWeatherBox.onAdd = function (map) {
    var div = L.DomUtil.create('div', 'info legend');
    div.innerHTML = "<strong>Outdated METARs</strong>";
    return div;
};

outdatedWeatherBox.addTo(map);


