<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/Cservicios.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$svc = new Cservicios($conn);
$solicitudes = $svc->listarSolicitudes($_SESSION['user_id'], $_SESSION['rol']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Mis Solicitudes - CercaMatch</title>
    <link rel="icon" type="image/png" href="/aplicacion/cercamatch/public/favicon.png">
    <link rel="stylesheet" href="/aplicacion/cercamatch/public/assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="/aplicacion/cercamatch/public/<?php
                if (empty($_SESSION['user_id'])) echo 'login.php';
                elseif ($_SESSION['rol'] === 'proveedor') echo 'dashboard-proveedor.php';
                elseif ($_SESSION['rol'] === 'admin') echo 'dashboard-admin.php';
                else echo 'index.php';
            ?>" class="logo"><img src="/aplicacion/cercamatch/public/assets/img/logo.png" alt="CercaMatch" height="40"></a>
            <div class="nav-links">
                <a href="index.php">Buscar</a>
                <a href="mis-solicitudes.php" class="active">Mis Solicitudes</a>
                <a href="perfil.php">Perfil</a>
                <a href="logout.php" class="btn-logout">Salir</a>
            </div>
        </div>
    </nav>

    <div class="solicitudes-container">
        <div class="solicitudes-header">
            <h2>Historial de Solicitudes</h2>
            <p>Consulta el estado de tus servicios y califica a los proveedores</p>
        </div>

        <div class="solicitudes-grid">
            <?php if(empty($solicitudes)): ?>
                <div class="sin-resultados">
                    <p>Aun no has realizado ninguna solicitud.</p>
                    <a href="index.php" class="btn btn-primary">Explorar servicios</a>
                </div>
            <?php else: ?>
                <?php foreach ($solicitudes as $solicitud):
                    $proveedor_foto = $solicitud['proveedor_foto'] ?? $solicitud['foto'] ?? '';
                    $proveedor_nombre = $solicitud['proveedor_nombre'] ?? $solicitud['nombre'] ?? 'Proveedor';
                    $proveedor_telefono = $solicitud['proveedor_telefono'] ?? $solicitud['telefono'] ?? '';
                    $status_map = [
                        'pendiente' => 'Pendiente',
                        'aceptada' => 'Aceptada',
                        'en_camino' => 'En camino',
                        'completada' => 'Completada',
                        'rechazada' => 'Rechazada',
                        'cancelada' => 'Cancelada'
                    ];
                ?>
                <div class="solicitud-card">
                    <div class="card-status status-<?php echo strtolower($solicitud['estado']); ?>">
                        <span class="status-text">
                            <?php echo $status_map[$solicitud['estado']] ?? ucfirst($solicitud['estado']); ?>
                        </span>
                    </div>

                    <div class="card-provider">
                        <div class="provider-avatar">
                            <?php if (!empty($proveedor_foto) && file_exists('../uploads/' . $proveedor_foto)): ?>
                                <img src="/aplicacion/cercamatch/uploads/<?php echo $proveedor_foto; ?>" alt="Proveedor">
                            <?php else: ?>
                                <div class="avatar-placeholder">P</div>
                            <?php endif; ?>
                        </div>
                        <div class="provider-info">
                            <h3><?php echo htmlspecialchars($proveedor_nombre); ?></h3>
                            <div class="provider-contact">
                                <span class="phone"><?php echo htmlspecialchars($proveedor_telefono); ?></span>
                                <?php
                                $telefono_limpio = preg_replace('/[^0-9]/', '', $proveedor_telefono);
                                $estado_activo = in_array($solicitud['estado'], ['pendiente', 'aceptada', 'en_camino']);
                                if (!empty($telefono_limpio) && $estado_activo):
                                ?>
                                    <a href="tel:<?php echo $telefono_limpio; ?>" class="call-link">Llamar</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="card-service">
                        <h4><?php echo htmlspecialchars($solicitud['servicio_titulo'] ?? 'Servicio'); ?></h4>
                        <?php if (!empty($solicitud['mensaje'])): ?>
                            <div class="service-message">
                                <span class="message-label">Tu mensaje</span>
                                <p><?php echo nl2br(htmlspecialchars($solicitud['mensaje'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="card-details">
                        <div class="detail-row">
                            <span class="detail-label">Fecha de solicitud</span>
                            <span class="detail-value"><?php echo date('d/m/Y', strtotime($solicitud['fecha_solicitud'] ?? 'now')); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Precio final</span>
                            <span class="detail-value price">$<?php echo number_format($solicitud['precio_final'] ?? 0, 0, ',', '.'); ?></span>
                        </div>
                    </div>

                    <div class="card-actions">
                        <?php if ($solicitud['estado'] === 'pendiente'): ?>
                            <button onclick="cancelarSolicitud(<?php echo $solicitud['id']; ?>)" class="btn-outline">Cancelar solicitud</button>
                        <?php endif; ?>

                        <?php if ($solicitud['estado'] === 'completada' && is_null($solicitud['calificacion'] ?? null)): ?>
                            <div class="rating-section">
                                <div class="rating-label">Califica este servicio</div>
                                <div class="star-rating" data-solicitud-id="<?php echo $solicitud['id']; ?>">
                                    <span class="star" data-value="1">★</span>
                                    <span class="star" data-value="2">★</span>
                                    <span class="star" data-value="3">★</span>
                                    <span class="star" data-value="4">★</span>
                                    <span class="star" data-value="5">★</span>
                                </div>
                                <textarea id="comentario_<?php echo $solicitud['id']; ?>" placeholder="Escribe un comentario (opcional)" rows="2"></textarea>
                                <button onclick="enviarCalificacion(<?php echo $solicitud['id']; ?>)" class="btn-primary">Enviar calificacion</button>
                            </div>
                        <?php elseif ($solicitud['estado'] === 'completada' && !is_null($solicitud['calificacion'] ?? null)): ?>
                            <div class="rating-completed">
                                <div class="rating-stars"><?php echo str_repeat('★', (int)$solicitud['calificacion']) . str_repeat('☆', 5 - (int)$solicitud['calificacion']); ?></div>
                                <?php if (!empty($solicitud['comentario_calificacion'])): ?>
                                    <p class="rating-comment">"<?php echo htmlspecialchars($solicitud['comentario_calificacion']); ?>"</p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="/aplicacion/cercamatch/public/assets/js/app.js"></script>
    <script>
    function enviarCalificacion(solicitudId) { 
        const ratingContainer = document.querySelector(`.star-rating[data-solicitud-id="${solicitudId}"]`);
        const activeStar = ratingContainer.querySelector('.star.active-last');
        
        if (!activeStar) { 
            alert('Selecciona una calificación de 1 a 5 estrellas'); 
            return; 
        } 
        
        const calificacion = activeStar.getAttribute('data-value'); 
        const comentario = document.getElementById(`comentario_${solicitudId}`).value; 
        
        fetch('/aplicacion/cercamatch/public/recibe_datos.php', { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' }, 
            body: JSON.stringify({ 
                accion: 'calificar', 
                id: solicitudId, 
                estrellas: calificacion, 
                comentario: comentario 
            }) 
        }) 
        .then(response => response.json()) 
        .then(data => { 
            if (data.status === 'success') { 
                alert('¡Gracias por calificar!'); 
                location.reload(); 
            } else { 
                alert('Error: ' + (data.message || 'No se pudo guardar la calificación')); 
            } 
        }) 
        .catch(error => { 
            console.error('Error:', error); 
            alert('Error de conexión'); 
        }); 
    } 
    
    // Sistema de estrellas interactivo 
    document.querySelectorAll('.star-rating').forEach(rating => { 
        const stars = rating.querySelectorAll('.star'); 
        stars.forEach(star => { 
            star.addEventListener('click', function() { 
                const value = parseInt(this.getAttribute('data-value')); 
                stars.forEach(s => {
                    s.classList.remove('active');
                    s.classList.remove('active-last');
                });
                for (let i = 0; i < value; i++) { 
                    stars[i].classList.add('active'); 
                    if (i === value - 1) stars[i].classList.add('active-last');
                } 
            }); 
        }); 
    });
    </script>
</body>
</html>
