<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/Cservicios.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$svc = new Cservicios($conn);
$categorias = $svc->listarCategorias();

// Parámetros de búsqueda
$radio = (int)($_GET['radio_km'] ?? 10);
$cat_id = !empty($_GET['categoria']) ? (int)$_GET['categoria'] : null;
$texto_busqueda = $_GET['texto_busqueda'] ?? '';

$servicios = $svc->buscarServiciosCercanos(
    (float)$_SESSION['lat'],
    (float)$_SESSION['lng'],
    $radio,
    $cat_id,
    $texto_busqueda
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Explorar - CercaMatch</title>
    <link rel="icon" type="image/png" href="/aplicacion/cercamatch/public/favicon.png">
    <link rel="stylesheet" href="/aplicacion/cercamatch/public/assets/css/style.css">
</head>
<body class="main-page">
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
                <a href="index.php" class="active">Buscar</a>
                <a href="mis-solicitudes.php">Mis Solicitudes</a>
                <a href="perfil.php">Perfil</a>
                <a href="logout.php" class="btn-logout">Salir</a>
            </div>
        </div>
    </nav>

    <div class="search-hero">
        <div class="container">
            <h1>  Encuentra servicios cerca de ti</h1>
            <p>Conectamos con los mejores profesionales de tu zona</p>
        </div>
    </div>

    <div class="container">
        <div class="search-card">
            <div class="search-grid">
                <div class="search-group">
                    <label> ¿Qué necesitas?</label>
                    <input type="text" id="texto_busqueda" name="texto_busqueda" value="<?= htmlspecialchars($texto_busqueda) ?>" placeholder="Ej: reparación de nevera, pintura...">
                </div>
                <div class="search-group">
                    <label>📂 Categoría</label>
                    <select id="categoria" name="categoria">
                        <option value="">Todas</option>
                        <?php foreach($categorias as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $cat_id == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="search-group">
                    <label> Radio (km)</label>
                    <select id="radio_km" name="radio_km">
                        <option value="5" <?= $radio == 5 ? 'selected' : '' ?>>5 km</option>
                        <option value="10" <?= $radio == 10 ? 'selected' : '' ?>>10 km</option>
                        <option value="15" <?= $radio == 15 ? 'selected' : '' ?>>15 km</option>
                        <option value="20" <?= $radio == 20 ? 'selected' : '' ?>>20 km</option>
                        <option value="25" <?= $radio == 25 ? 'selected' : '' ?>>25 km</option>
                        <option value="30" <?= $radio == 30 ? 'selected' : '' ?>>30 km</option>
                        <option value="35" <?= $radio == 35 ? 'selected' : '' ?>>35 km</option>
                        <option value="40" <?= $radio == 40 ? 'selected' : '' ?>>40 km</option>
                    </select>
                </div>
                <button class="btn-buscar" onclick="buscarServicios()">🔍 Buscar</button>
            </div>
        </div>
    </div>

    <main class="container" style="padding-top: 2rem; padding-bottom: 3rem;">
        <section id="resultados" class="services-grid">
            <?php if(empty($servicios)): ?>
                <div class="no-results">
                    <p>😕 No encontramos servicios con esos filtros.</p>
                    <p>💡 Sugerencias:</p>
                    <ul>
                        <li>Amplía el radio de búsqueda (prueba 15km o más)</li>
                        <li>Selecciona otra categoría</li>
                        <li>Escribe menos palabras o más generales</li>
                    </ul>
                </div>
            <?php else: ?>
                <?php
                $servicios_vistos = [];
                foreach ($servicios as $servicio):
                    if (in_array($servicio['servicio_id'] ?? $servicio['id'] ?? 0, $servicios_vistos)) continue;
                    $servicios_vistos[] = $servicio['servicio_id'] ?? $servicio['id'] ?? 0;
                    $categoria = $servicio['categoria'] ?? $servicio['categoria_nombre'] ?? 'General';
                    $servicioId = $servicio['servicio_id'] ?? $servicio['id'] ?? 0;
                    $distancia = $servicio['distancia_km'] ?? $servicio['distancia'] ?? 0;
                    $precio = $servicio['precio_base'] ?? $servicio['precio_estimado'] ?? 0;
                    $titulo = $servicio['titulo'] ?? 'Sin título';
                    $proveedor = $servicio['proveedor_nombre'] ?? $servicio['nombre'] ?? 'Desconocido';
                    $calificacion = $servicio['calificacion_promedio'] ?? 0;
                ?>
                    <div class="service-card">
                        <div class="service-card-header">
                            <h3><?php echo htmlspecialchars($titulo); ?></h3>
                            <span class="service-category"><?php echo htmlspecialchars($categoria); ?></span>
                        </div>
                        <div class="service-card-body">
                            <div class="provider-info">
                                <?php if (!empty($servicio['foto_perfil']) && file_exists('../uploads/' . $servicio['foto_perfil'])): ?>
                                    <img src="/aplicacion/cercamatch/uploads/<?php echo $servicio['foto_perfil']; ?>" alt="Avatar" class="provider-avatar">
                                <?php else: ?>
                                    <div class="provider-avatar default">👤</div>
                                <?php endif; ?>
                                <div class="provider-name"><?php echo htmlspecialchars($proveedor); ?></div>
                                <div class="provider-rating">⭐ <?php echo number_format($calificacion, 1); ?></div>
                            </div>
                            <div class="service-location">
                                📍 <?php echo number_format($distancia, 1); ?> km
                            </div>
                            <div class="service-price">
                                $<?php echo number_format($precio, 0, ',', '.'); ?>
                            </div>
                            <button onclick="abrirModalSolicitud(<?php echo $servicioId; ?>, '<?php echo addslashes($titulo); ?>', <?php echo $precio; ?>)" class="btn-solicitar">
                                Solicitar
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
        <div id="sin-resultados" class="no-results" style="display:none;">
            <p>😕 No encontramos servicios con esos filtros.</p>
            <p>💡 Prueba ampliando el radio de búsqueda o seleccionando otra categoría.</p>
        </div>
    </main>

    <!-- MODAL SOLICITUD -->
    <div id="modal-solicitud" class="modal hidden">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal()">&times;</span>
            <h2 id="modal-titulo">Solicitar Servicio</h2>
            <p id="modal-info"></p>
            <form id="form-solicitud" onsubmit="enviarSolicitud(event)">
                <input type="hidden" name="servicio_id" id="sol-servicio-id">
                <div class="form-group">
                    <label>Cuéntanos un poco más sobre lo que necesitas:</label>
                    <textarea name="mensaje" required placeholder="Ej: Mi nevera no enfría la parte de abajo..."></textarea>
                </div>
                <div id="calculo-precio" class="price-box hidden">
                    <!-- Se llenará con AJAX -->
                </div>
                <button type="submit" class="btn btn-primary">Confirmar Solicitud</button>
            </form>
        </div>
    </div>

    <script src="/aplicacion/cercamatch/public/assets/js/mapa.js"></script>
    <script src="/aplicacion/cercamatch/public/assets/js/app.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Verificar si tenemos ubicación en sesión (PHP ya la puso en $_SESSION['lat'])
            const lat = <?= json_encode($_SESSION['lat'] ?? null) ?>;
            const lng = <?= json_encode($_SESSION['lng'] ?? null) ?>;

            if (!lat || !lng) {
                mostrarAvisoUbicacion();
            }
        });

        function mostrarAvisoUbicacion() {
            const container = document.querySelector('main.container');
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-warning';
            alertDiv.innerHTML = `
                <p>Para ver servicios cerca de ti, necesitamos tu ubicación.</p>
                <button class="btn btn-primary btn-sm" onclick="pedirUbicacionIndex()">Permitir Acceso</button>
            `;
            container.prepend(alertDiv);
        }

        function pedirUbicacionIndex() {
            solicitarUbicacion(
                async (data) => {
                    // Guardar en backend
                    try {
                        const result = await fetchJSON('recibe_datos.php', {
                            accion: 'guardar_ubicacion',
                            lat: data.lat,
                            lng: data.lng
                        });
                        if (result.status === 'success') location.reload();
                    } catch (err) {
                        console.error(err);
                        alert('Error al guardar ubicación');
                    }
                },
                (error) => {
                    alert(error);
                }
            );
        }
    </script>
</body>
</html>
