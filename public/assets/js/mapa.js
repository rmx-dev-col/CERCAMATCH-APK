/**
 * Gestión de mapas y geolocalización con Leaflet.js y Browser API
 */

let map, marker;

/**
 * Solicita permiso de ubicación al navegador de forma obligatoria
 */
function solicitarUbicacion(callbackExito, callbackError) {
    if (!navigator.geolocation) {
        callbackError("Tu navegador no soporta geolocalización.");
        return;
    }

    const options = {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0
    };

    navigator.geolocation.getCurrentPosition(
        async (position) => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            
            try {
                // Geocodificación inversa con Nominatim (OpenStreetMap)
                const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`);
                const data = await res.json();
                const direccion = data.display_name || "Ubicación detectada";
                
                callbackExito({ lat, lng, direccion });
            } catch (err) {
                // Si falla Nominatim, devolvemos solo coordenadas
                callbackExito({ lat, lng, direccion: "Coordenadas detectadas" });
            }
        },
        (error) => {
            let msg = "Error al obtener ubicación.";
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    msg = "Permiso de ubicación denegado. Por favor, actívalo en tu navegador.";
                    break;
                case error.POSITION_UNAVAILABLE:
                    msg = "La información de ubicación no está disponible.";
                    break;
                case error.TIMEOUT:
                    msg = "Tiempo de espera agotado al obtener la ubicación.";
                    break;
            }
            callbackError(msg);
        },
        options
    );
}

function initMapaRegistro() {
    const lat = 8.751;
    const lng = -75.881;

    map = L.map('mapa-registro').setView([lat, lng], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

    marker = L.marker([lat, lng], { draggable: true }).addTo(map);

    marker.on('dragend', function (e) {
        const position = marker.getLatLng();
        document.getElementById('reg-lat').value = position.lat;
        document.getElementById('reg-lng').value = position.lng;
    });
}

function initMapaPerfil(lat, lng) {
    if (map) map.remove();
    
    map = L.map('mapa-perfil').setView([lat, lng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

    marker = L.marker([lat, lng], { draggable: true }).addTo(map);

    marker.on('dragend', function (e) {
        const position = marker.getLatLng();
        document.getElementById('perfil-lat').value = position.lat;
        document.getElementById('perfil-lng').value = position.lng;
    });
}

/**
 * Actualiza la vista del mapa y el marcador
 */
function actualizarMapa(lat, lng) {
    if (map && marker) {
        map.setView([lat, lng], 15);
        marker.setLatLng([lat, lng]);
    }
}
