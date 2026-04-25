<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/Cservicios.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$svc = new Cservicios($conn);
// Obtener datos frescos del usuario
$sql = "SELECT * FROM usuarios WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

// Si es proveedor, obtener detalle
$detalle = null;
if ($user['rol'] === 'proveedor') {
    $sql = "SELECT * FROM proveedores_detalle WHERE usuario_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $detalle = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Mi Perfil - CercaMatch</title>
    <link rel="icon" type="image/png" href="/aplicacion/cercamatch/public/favicon.png">
    <link rel="stylesheet" href="/aplicacion/cercamatch/public/assets/css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
</head>
<body>
    <nav class="navbar">
        <div class="container nav-container"> 
            <a href="/aplicacion/cercamatch/public/<?php
                if (empty($_SESSION['user_id'])) echo 'login.php';
                elseif ($_SESSION['rol'] === 'proveedor') echo 'dashboard-proveedor.php';
                elseif ($_SESSION['rol'] === 'admin') echo 'dashboard-admin.php';
                else echo 'index.php';
            ?>" class="logo"><img src="/aplicacion/cercamatch/public/assets/img/logo.png" alt="CercaMatch" height="40"></a>
            <div class="menu-icon">☰</div> 
            <div class="nav-links">
                <?php if($_SESSION['rol'] === 'proveedor'): ?>
                    <a href="dashboard-proveedor.php">Dashboard</a>
                <?php elseif($_SESSION['rol'] === 'admin'): ?>
                    <a href="dashboard-admin.php">Admin</a>
                <?php else: ?>
                    <a href="index.php">Buscar</a>
                <?php endif; ?>
                <a href="perfil.php" class="active">Perfil</a>
                <a href="logout.php" class="btn-logout">Salir</a>
            </div>
        </div>
    </nav>

    <main class="container profile-container">
        <div class="profile-card">
            <header class="profile-header">
                <!-- Sección de foto de perfil -->
                <div class="profile-section">
                    <div class="profile-avatar-wrapper" style="text-align: center; margin-bottom: 1.5rem;">
                        <?php 
                        $foto_perfil = !empty($user['foto_perfil']) ? $user['foto_perfil'] : 'default-avatar.png';
                        $foto_path = '/aplicacion/cercamatch/uploads/' . $foto_perfil;
                        // Si no existe el archivo físico, usar el default de assets
                        if ($foto_perfil === 'default-avatar.png') {
                            $foto_path = '/aplicacion/cercamatch/public/assets/img/default-avatar.png';
                        }
                        ?>
                        <img src="<?php echo $foto_path; ?>" alt="Avatar" class="profile-avatar" id="avatar-img">
                        <div class="avatar-upload" style="margin-top: 1rem;">
                            <input type="file" id="foto_perfil" name="foto_perfil" accept="image/jpeg,image/png,image/jpg" style="display: none;">
                            <label for="foto_perfil" class="btn btn-secondary">📷 Cambiar foto</label>
                            <div id="avatar-preview-info" class="avatar-preview"></div>
                        </div>
                    </div>
                </div>
                <h1><?= htmlspecialchars($user['nombre']) ?></h1>
                <span class="badge badge-<?= $user['rol'] ?>"><?= ucfirst($user['rol']) ?></span>
            </header>

            <form id="form-perfil" onsubmit="actualizarPerfil(event)">
                <input type="hidden" name="accion" value="actualizar_perfil">
                
                <div class="form-section">
                    <h3>Datos Personales</h3>
                    <div class="form-group">
                        <label>Nombre Completo:</label>
                        <input type="text" name="nombre" value="<?= htmlspecialchars($user['nombre']) ?>" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Teléfono:</label>
                            <input type="tel" name="telefono" value="<?= htmlspecialchars($user['telefono']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Email (No editable):</label>
                            <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                        </div>
                    </div>
                </div>

                <?php if($user['rol'] === 'proveedor'): ?>
                    <div class="form-section">
                        <h3>Detalle Profesional</h3>
                        <div class="form-group">
                            <label>Descripción:</label>
                            <textarea name="descripcion"><?= htmlspecialchars($detalle['descripcion'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Radio de Cobertura (KM):</label>
                            <input type="number" name="radio_maximo_km" value="<?= $detalle['radio_maximo_km'] ?? 10 ?>" min="1">
                        </div>
                    </div>
                <?php endif; ?>

                <div class="form-section">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h3>Ubicación</h3>
                        <button type="button" class="btn btn-outline btn-sm" onclick="pedirUbicacionPerfil()">📍 Usar mi ubicación actual</button>
                    </div>
                    <div class="form-group">
                        <label>Dirección:</label>
                        <input type="text" name="direccion" id="perfil-direccion" value="<?= htmlspecialchars($user['direccion']) ?>">
                    </div>
                    <p class="help-text">Arrastra el marcador para actualizar tu ubicación exacta:</p>
                    <div id="mapa-perfil" style="height: 300px; border-radius: 8px; margin-bottom: 1rem;"></div>
                    <input type="hidden" name="lat" id="perfil-lat" value="<?= $user['latitud'] ?>">
                    <input type="hidden" name="lng" id="perfil-lng" value="<?= $user['longitud'] ?>">
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-block">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </main>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="/aplicacion/cercamatch/public/assets/js/mapa.js"></script>
    <script src="/aplicacion/cercamatch/public/assets/js/app.js"></script>
    <script>
        // Previsualización y subida de foto de perfil
        document.getElementById('foto_perfil').addEventListener('change', async function(e) {
            const file = e.target.files[0];
            if (file) {
                // Previsualización local
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('avatar-img').src = event.target.result;
                    document.getElementById('avatar-preview-info').innerHTML = `<small>📷 Nueva foto seleccionada: ${file.name}</small>`;
                };
                reader.readAsDataURL(file);

                // Subida automática al servidor
                const formData = new FormData();
                formData.append('accion', 'subir_foto');
                formData.append('foto', file);

                try {
                    const response = await fetch('/aplicacion/cercamatch/public/recibe_datos.php', {
                        method: 'POST',
                        body: formData // Nota: FormData no requiere Content-Type header manual
                    });
                    const data = await response.json();
                    if (data.status === 'success') {
                        alert('Foto de perfil actualizada con éxito');
                    } else {
                        alert('Error al subir la foto: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error de conexión al subir la foto');
                }
            }
        });

        // Inicializar mapa de perfil
        document.addEventListener('DOMContentLoaded', () => {
            initMapaPerfil(<?= (float)$user['latitud'] ?>, <?= (float)$user['longitud'] ?>);
        });

        function pedirUbicacionPerfil() {
            const btn = document.querySelector('button[onclick="pedirUbicacionPerfil()"]');
            btn.disabled = true;
            btn.innerText = "Obteniendo...";

            solicitarUbicacion(
                (data) => {
                    document.getElementById('perfil-lat').value = data.lat;
                    document.getElementById('perfil-lng').value = data.lng;
                    document.getElementById('perfil-direccion').value = data.direccion;
                    actualizarMapa(data.lat, data.lng);
                    
                    btn.disabled = false;
                    btn.innerText = "📍 Ubicación actualizada";
                    setTimeout(() => btn.innerText = "📍 Usar mi ubicación actual", 3000);
                },
                (error) => {
                    alert(error);
                    btn.disabled = false;
                    btn.innerText = "📍 Reintentar";
                }
            );
        }
    </script>
</body>
</html>
