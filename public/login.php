<?php
require_once __DIR__ . '/../config/config.php';
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Acceso - CercaMatch</title>
    <link rel="icon" type="image/png" href="/aplicacion/cercamatch/public/favicon.png">
    <link rel="stylesheet" href="/aplicacion/cercamatch/public/assets/css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        #mapa-registro { height: 300px; width: 100%; margin: 15px 0; border-radius: 8px; border: 1px solid #ddd; }
        .hidden { display: none; }
        .tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #eee; }
        .tab { padding: 10px 20px; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -2px; }
        .tab.active { border-color: #ff4b2b; color: #ff4b2b; font-weight: bold; }
    </style>
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <a href="/aplicacion/cercamatch/public/login.php" class="logo"><img src="/aplicacion/cercamatch/public/assets/img/logo.png" alt="CercaMatch" height="40"></a>
            
            <div class="tabs">
                <div class="tab active" onclick="switchTab('login')">Ingresar</div>
                <div class="tab" onclick="switchTab('registro')">Registrarse</div>
            </div>

            <!-- LOGIN FORM -->
            <form id="login-form" onsubmit="handleAuth(event, 'login')">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required placeholder="tu@email.com">
                </div>
                <div class="form-group">
                    <label>Contraseña</label>
                    <input type="password" name="password" required placeholder="••••••••">
                </div>
                <button type="submit" class="btn btn-primary">Entrar</button>
            </form>

            <!-- REGISTER FORM -->
            <form id="register-form" class="hidden" onsubmit="handleAuth(event, 'registro')">
                <div class="form-group">
                    <label>Nombre Completo</label>
                    <input type="text" name="nombre" required placeholder="Ej: Juan Pérez">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required placeholder="tu@email.com">
                </div>
                <div class="form-group">
                    <label>Contraseña</label>
                    <input type="password" name="password" required placeholder="Mínimo 6 caracteres">
                </div>
                <div class="form-group">
                    <label>¿Qué eres?</label>
                    <select name="rol" onchange="toggleProveedorFields(this.value)">
                        <option value="cliente">Soy Cliente (Busco servicios)</option>
                        <option value="proveedor">Soy Proveedor (Ofrezco servicios)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="tel" name="telefono" required>
                </div>
                <div class="form-group">
                    <label>Dirección aproximada</label>
                    <input type="text" name="direccion" required id="reg-direccion">
                </div>
                
                <p class="help-text">Arrastra el marcador a tu ubicación exacta en el mapa:</p>
                <div id="mapa-registro"></div>
                
                <input type="hidden" name="lat" id="reg-lat">
                <input type="hidden" name="lng" id="reg-lng">

                <button type="submit" class="btn btn-primary">Crear Cuenta</button>
            </form>
            
            <div id="auth-msg" style="margin-top: 15px; text-align: center;"></div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="/aplicacion/cercamatch/public/assets/js/mapa.js"></script>
    <script>
        let isGettingLocation = false;
        const BASE_URL = '/aplicacion/cercamatch/public';

        function switchTab(tab) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            const tabElement = document.querySelector(`.tab[onclick="switchTab('${tab}')"]`);
            if (tabElement) tabElement.classList.add('active');
            
            if(tab === 'login') {
                document.getElementById('login-form').classList.remove('hidden');
                document.getElementById('register-form').classList.add('hidden');
            } else {
                document.getElementById('login-form').classList.add('hidden');
                document.getElementById('register-form').classList.remove('hidden');
                initMapaRegistro();
            }
        }

        async function handleAuth(e, action) {
            e.preventDefault();
            const form = e.target;
            const submitBtn = form.querySelector('button[type="submit"]');
            const authMsg = document.getElementById('auth-msg');

            if (action === 'registro' && !form.dataset.geoObtained) {
                authMsg.innerHTML = '<span style="color:blue">Obteniendo ubicación precisa...</span>';
                submitBtn.disabled = true;
                
                solicitarUbicacion(
                    (data) => {
                        document.getElementById('reg-lat').value = data.lat;
                        document.getElementById('reg-lng').value = data.lng;
                        document.getElementById('reg-direccion').value = data.direccion;
                        actualizarMapa(data.lat, data.lng);
                        
                        form.dataset.geoObtained = "true";
                        authMsg.innerHTML = '<span style="color:green">Ubicación obtenida. Procesando registro...</span>';
                        submitBtn.disabled = false;
                        handleAuth(e, action); // Re-enviar ahora con coordenadas
                    },
                    (error) => {
                        authMsg.innerHTML = `<span style="color:orange">${error} Por favor, ingresa tu dirección manualmente y ajusta el marcador en el mapa.</span>`;
                        submitBtn.disabled = false;
                        form.dataset.geoObtained = "manual"; // Permitir manual si falla o deniega
                    }
                );
                return;
            }

            // Convertir FormData a un objeto simple para enviarlo como JSON
            const formData = new FormData(form);
            const dataObj = { accion: action };
            formData.forEach((value, key) => {
                dataObj[key] = value;
            });

            try {
                const res = await fetch(`${BASE_URL}/recibe_datos.php`, { 
                    method: 'POST', 
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(dataObj) 
                });
                const data = await res.json();
                
                if(data.status === 'success') {
                    if(action === 'login') {
                        window.location.href = data.rol === 'admin' ? 'dashboard-admin.php' : 
                                              (data.rol === 'proveedor' ? 'dashboard-proveedor.php' : 'index.php');
                    } else {
                        authMsg.innerHTML = '<span style="color:green">Registro exitoso. ¡Ya puedes ingresar!</span>';
                        setTimeout(() => switchTab('login'), 2000);
                    }
                } else {
                    authMsg.innerHTML = `<span style="color:red">${data.message}</span>`;
                }
            } catch (err) {
                console.error(err);
                authMsg.innerHTML = '<span style="color:red">Error de conexión con el servidor.</span>';
            }
        }
    </script>
</body>
</html>
