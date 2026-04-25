<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/Cservicios.php';

if (empty($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$svc = new Cservicios($conn);
$stats = $svc->obtenerEstadisticasAdmin();
$usuarios = $svc->listarUsuarios();
$categorias = $svc->listarCategorias();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Panel de Administración - CercaMatch</title>
    <link rel="icon" type="image/png" href="/aplicacion/cercamatch/public/favicon.png">
    <link rel="stylesheet" href="/aplicacion/cercamatch/public/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="admin-panel">
    <nav class="navbar admin-navbar">
        <div class="container">
            <a href="/aplicacion/cercamatch/public/<?php
                if (empty($_SESSION['user_id'])) echo 'login.php';
                elseif ($_SESSION['rol'] === 'proveedor') echo 'dashboard-proveedor.php';
                elseif ($_SESSION['rol'] === 'admin') echo 'dashboard-admin.php';
                else echo 'index.php';
            ?>" class="logo"><img src="/aplicacion/cercamatch/public/assets/img/logo.png" alt="CercaMatch" height="40"></a>
            <div class="nav-links">
                <a href="#stats">Estadísticas</a>
                <a href="#usuarios">Usuarios</a>
                <a href="#categorias">Categorías</a>
                <a href="logout.php" class="btn-logout">Salir</a>
            </div>
        </div>
    </nav>

    <main class="container">
        <section id="stats" class="admin-section">
            <h2>Resumen del Sistema</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="value"><?= $stats['total_usuarios'] ?? 0 ?></span>
                    <span class="label">Usuarios Registrados</span>
                </div>
                <div class="stat-card">
                    <span class="value"><?= $stats['total_servicios'] ?? 0 ?></span>
                    <span class="label">Servicios Activos</span>
                </div>
                <div class="stat-card">
                    <span class="value"><?= $stats['total_solicitudes'] ?? 0 ?></span>
                    <span class="label">Solicitudes Totales</span>
                </div>
                <div class="stat-card">
                    <span class="value">$<?= number_format($stats['ingresos_totales'] ?? 0, 0) ?></span>
                    <span class="label">Volumen de Negocio</span>
                </div>
            </div>
            
            <div class="charts-row">
                <div class="chart-container">
                    <canvas id="chartSolicitudes"></canvas>
                </div>
            </div>
        </section>

        <section id="usuarios" class="admin-section">
            <header class="section-header">
                <h2>Gestión de Usuarios</h2>
            </header>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($usuarios as $u): ?>
                            <tr>
                                <td><?= htmlspecialchars($u['nombre']) ?></td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td><span class="badge badge-<?= $u['rol'] ?>"><?= ucfirst($u['rol']) ?></span></td>
                                <td>
                                    <span class="status-dot <?= $u['activo'] ? 'online' : 'offline' ?>"></span>
                                    <?= $u['activo'] ? 'Activo' : 'Inactivo' ?>
                                </td>
                                <td>
                                    <?php if($u['id'] != $_SESSION['user_id']): ?>
                                        <button class="btn btn-sm <?= $u['activo'] ? 'btn-outline-danger' : 'btn-outline-success' ?>" 
                                                onclick="toggleUsuario(<?= $u['id'] ?>, <?= $u['activo'] ? 0 : 1 ?>)">
                                            <?= $u['activo'] ? 'Desactivar' : 'Activar' ?>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="categorias" class="admin-section">
            <header class="section-header">
                <h2>Categorías</h2>
                <button class="btn btn-primary btn-sm">+ Nueva Categoría</button>
            </header>
            <div class="categories-grid">
                <?php foreach($categorias as $cat): ?>
                    <div class="category-item">
                        <span class="cat-icon"><?= $cat['icono'] ?: '🛠️' ?></span>
                        <span class="cat-name"><?= htmlspecialchars($cat['nombre']) ?></span>
                        <div class="cat-actions">
                            <button class="btn-icon">✏️</button>
                            <button class="btn-icon">🗑️</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <script src="/aplicacion/cercamatch/public/assets/js/app.js"></script>
    <script>
        // Configuración básica de Chart.js
        const ctx = document.getElementById('chartSolicitudes').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Pendientes', 'Aceptadas', 'En Camino', 'Completadas', 'Canceladas'],
                datasets: [{
                    label: 'Solicitudes por Estado',
                    data: [
                        <?= $stats['solicitudes_pendientes'] ?? 0 ?>,
                        <?= $stats['solicitudes_aceptadas'] ?? 0 ?>,
                        <?= $stats['solicitudes_en_camino'] ?? 0 ?>,
                        <?= $stats['solicitudes_completadas'] ?? 0 ?>,
                        <?= $stats['solicitudes_canceladas'] ?? 0 ?>
                    ],
                    backgroundColor: ['#f1c40f', '#3498db', '#9b59b6', '#2ecc71', '#e74c3c']
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });

        async function toggleUsuario(id, nuevoEstado) {
            if(!confirm('¿Estás seguro de cambiar el estado de este usuario?')) return;
            
            try {
                const data = await fetchJSON('recibe_datos.php', {
                    accion: 'cambiar_estado_usuario',
                    id: id,
                    activo: nuevoEstado
                });
                if(data.status === 'success') location.reload();
                else alert(data.message || 'Error al actualizar usuario');
            } catch (err) {
                console.error(err);
                alert('Error de conexión');
            }
        }
    </script>
</body>
</html>
