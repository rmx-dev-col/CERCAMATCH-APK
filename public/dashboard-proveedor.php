<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/Cservicios.php';

if (empty($_SESSION['user_id']) || $_SESSION['rol'] !== 'proveedor') {
    header('Location: login.php');
    exit;
}

$svc = new Cservicios($conn);
$solicitudes = $svc->listarSolicitudes($_SESSION['user_id'], 'proveedor');

// Estadísticas rápidas
$pendientes = count(array_filter($solicitudes, fn($s) => $s['estado'] === 'pendiente'));
$activas = count(array_filter($solicitudes, fn($s) => in_array($s['estado'], ['aceptada', 'en_camino'])));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Dashboard Proveedor - CercaMatch</title>
    <link rel="icon" type="image/png" href="/aplicacion/cercamatch/public/favicon.png">
    <link rel="stylesheet" href="/aplicacion/cercamatch/public/assets/css/style.css">
</head>
<body class="bg-light">
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
                <a href="dashboard-proveedor.php" class="active">Solicitudes</a>
                <a href="gestion-servicios.php">Mis Servicios</a>
                <a href="perfil.php">Perfil</a>
                <a href="logout.php" class="btn-logout">Salir</a>
            </div>
        </div>
    </nav>

    <main class="container">
        <header class="dashboard-header">
            <h1>Bienvenido, <?= htmlspecialchars($_SESSION['nombre']) ?></h1>
            <div class="stats-grid"> 
                <div class="stat-card"> 
                    <div class="stat-number"><?php echo $pendientes; ?></div> 
                    <div class="stat-label">Nuevas Solicitudes</div> 
                </div> 
                <div class="stat-card"> 
                    <div class="stat-number"><?php echo $activas; ?></div> 
                    <div class="stat-label">Servicios en Curso</div> 
                </div> 
                <div class="stat-card"> 
                    <?php
                    $total_calif = 0;
                    $count_calif = 0;
                    foreach($solicitudes as $s) {
                        $c = $s['calificacion'] ?? $s['calificacion_promedio'] ?? 0;
                        if($c > 0) {
                            $total_calif += $c;
                            $count_calif++;
                        }
                    }
                    $promedio = $count_calif > 0 ? $total_calif / $count_calif : 0;
                    ?>
                    <div class="stat-number">⭐ <?php echo number_format($promedio, 1); ?></div> 
                    <div class="stat-label">Calificación</div> 
                </div> 
            </div> 
        </header>

        <section class="solicitudes-section">
            <h2>Gestión de Solicitudes</h2>
            
            <?php if(empty($solicitudes)): ?>
                <div class="empty-state">
                    <p>No tienes solicitudes registradas aún.</p>
                </div>
            <?php else: ?>
                <div class="solicitudes-grid">
                    <?php foreach($solicitudes as $sol): ?>
                        <div class="sol-card status-<?= $sol['estado'] ?>">
                            <div class="sol-card-header">
                                <span class="badge badge-<?= $sol['estado'] ?>"><?= ucfirst($sol['estado']) ?></span>
                                <span class="sol-id">#<?= $sol['id'] ?></span>
                            </div>
                            
                            <div class="sol-card-body">
                                <h3><?= htmlspecialchars($sol['servicio_titulo']) ?></h3>
                                <div class="cliente-info"> 
                                    <?php if (!empty($sol['cliente_foto']) && file_exists('../uploads/' . $sol['cliente_foto'])): ?> 
                                        <img src="/aplicacion/cercamatch/uploads/<?php echo $sol['cliente_foto']; ?>" alt="Cliente" class="avatar"> 
                                    <?php else: ?> 
                                        <div class="avatar default">👤</div> 
                                    <?php endif; ?> 
                                    <div class="cliente-detalle"> 
                                        <strong><?php echo htmlspecialchars($sol['cliente_nombre'] ?? 'Cliente'); ?></strong> 
                                        <small><?php echo htmlspecialchars($sol['cliente_telefono'] ?? ''); ?></small> 
                                    </div> 
                                </div>
                                <div class="client-location-info">
                                    <p>📍 <?= htmlspecialchars($sol['cliente_direccion'] ?? $sol['direccion'] ?? 'Sin dirección') ?> (<?= round($sol['distancia_km'], 1) ?> km)</p>
                                    <p class="message">"<?= htmlspecialchars($sol['mensaje']) ?>"</p>
                                </div>
                                <?php if(in_array($sol['estado'], ['aceptada', 'en_camino'])): ?>
                                    <div class="contact-info mt-2">
                                        <a href="https://wa.me/57<?php echo preg_replace('/[^0-9]/', '', $sol['cliente_telefono'] ?? ''); ?>" target="_blank" class="btn-whatsapp"> 
                                            📱 WhatsApp 
                                        </a> 
                                    </div>
                                <?php endif; ?>
                                <div class="price-info">
                                    <span>Ganancia estimada:</span>
                                    <span class="amount">$<?= number_format($sol['precio_final'], 0) ?></span>
                                </div>
                            </div>

                            <div class="sol-card-footer">
                                <?php if($sol['estado'] === 'pendiente'): ?>
                                    <button class="btn btn-success" onclick="responderSolicitud(<?= $sol['id'] ?>, 'aceptada')">Aceptar</button>
                                    <button class="btn btn-danger" onclick="responderSolicitud(<?= $sol['id'] ?>, 'rechazada')">Rechazar</button>
                                <?php elseif($sol['estado'] === 'aceptada'): ?>
                                    <button class="btn btn-primary" onclick="cambiarEstadoSolicitud(<?= $sol['id'] ?>, 'en_camino')">🚚 En camino</button>
                                    <button class="btn btn-success" onclick="cambiarEstadoSolicitud(<?= $sol['id'] ?>, 'completada')">✅ Completar</button>
                                <?php elseif($sol['estado'] === 'en_camino'): ?>
                                    <button class="btn btn-success" onclick="cambiarEstadoSolicitud(<?= $sol['id'] ?>, 'completada')">✅ Completar Servicio</button>
                                <?php elseif($sol['estado'] === 'completada'): ?>
                                    <div class="rating-display">
                                        <?php if(($sol['calificacion'] ?? false)): ?>
                                            <span>Rating: <?= str_repeat('⭐', (int)$sol['calificacion']) ?></span>
                                        <?php else: ?>
                                            <span>Esperando calificación...</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <script src="/aplicacion/cercamatch/public/assets/js/app.js"></script>
    <script> 
    function responderSolicitud(id, accion) { 
        if (!confirm(`¿Estás seguro de ${accion === 'aceptada' ? 'ACEPTAR' : 'RECHAZAR'} esta solicitud?`)) return; 
        
        fetch('/aplicacion/cercamatch/public/recibe_datos.php', { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' }, 
            body: JSON.stringify({ 
                accion: 'responder_solicitud', 
                id: id, 
                estado: accion 
            }) 
        }) 
        .then(response => response.json()) 
        .then(data => { 
            if (data.status === 'success') { 
                alert('Solicitud ' + (accion === 'aceptada' ? 'aceptada' : 'rechazada') + ' correctamente'); 
                location.reload(); 
            } else { 
                alert('Error: ' + (data.message || 'No se pudo procesar')); 
            } 
        }) 
        .catch(error => { 
            console.error('Error:', error); 
            alert('Error de conexión con el servidor'); 
        }); 
    } 
    
    function cambiarEstadoSolicitud(id, estado) { 
        if (!confirm(`¿Marcar como ${estado === 'en_camino' ? 'EN CAMINO' : 'COMPLETADA'}?`)) return; 
        
        fetch('/aplicacion/cercamatch/public/recibe_datos.php', { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' }, 
            body: JSON.stringify({ 
                accion: 'cambiar_estado', 
                id: id, 
                estado: estado 
            }) 
        }) 
        .then(response => response.json()) 
        .then(data => { 
            if (data.status === 'success') { 
                location.reload(); 
            } else { 
                alert('Error: ' + (data.message || 'No se pudo cambiar el estado')); 
            } 
        }) 
        .catch(error => { 
            console.error('Error:', error); 
            alert('Error de conexión'); 
        }); 
    } 
    </script> 
</body>
</html>
