<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/Cservicios.php';

if (empty($_SESSION['user_id']) || $_SESSION['rol'] !== 'proveedor') {
    header('Location: login.php');
    exit;
}

$svc = new Cservicios($conn);
$servicios = $svc->listarServiciosPorProveedor($_SESSION['user_id']);
$categorias = $svc->listarCategorias();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Gestionar Servicios - CercaMatch</title>
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
                <a href="dashboard-proveedor.php">Solicitudes</a>
                <a href="gestion-servicios.php" class="active">Mis Servicios</a>
                <a href="perfil.php">Perfil</a>
                <a href="logout.php" class="btn-logout">Salir</a>
            </div>
        </div>
    </nav>

    <main class="container">
        <header class="section-header">
            <h1>Mis Servicios Ofertados</h1>
            <button class="btn btn-primary" onclick="abrirModalServicio()">+ Agregar Nuevo Servicio</button>
        </header>

        <div class="services-list">
            <?php if(empty($servicios)): ?>
                <div class="empty-state">
                    <p>Aún no has creado ningún servicio. ¡Comienza ahora para recibir solicitudes!</p>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Categoría</th>
                            <th>Precio Base</th>
                            <th>Costo x KM</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($servicios as $s): ?>
                            <tr>
                                <td><b><?= htmlspecialchars($s['titulo']) ?></b></td>
                                <td><?= htmlspecialchars($s['categoria_nombre']) ?></td>
                                <td>$<?= number_format($s['precio_base'], 0) ?></td>
                                <td>$<?= number_format($s['costo_por_km'], 0) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline" onclick='editarServicio(<?= json_encode($s) ?>)'>Editar</button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="eliminarServicio(<?= $s['id'] ?>)">Eliminar</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>

    <!-- MODAL SERVICIO -->
    <div id="modal-servicio" class="modal hidden">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal()">&times;</span>
            <h2 id="modal-titulo">Servicio</h2>
            <form id="form-servicio" onsubmit="guardarServicio(event)">
                <input type="hidden" name="id" id="serv-id">
                
                <div class="form-group">
                    <label>Título del Servicio:</label>
                    <input type="text" name="titulo" id="serv-titulo" required placeholder="Ej: Reparación de lavadoras">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Categoría:</label>
                        <select name="categoria_id" id="serv-cat" required>
                            <?php foreach($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tiempo Estimado:</label>
                        <input type="text" name="tiempo_estimado" id="serv-tiempo" placeholder="Ej: 2 horas">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Precio Base ($):</label>
                        <input type="number" name="precio_base" id="serv-precio" required min="0">
                    </div>
                    <div class="form-group">
                        <label>Costo por KM ($):</label>
                        <input type="number" name="costo_por_km" id="serv-costo-km" required min="0">
                    </div>
                </div>

                <div class="form-group">
                    <label>Descripción:</label>
                    <textarea name="descripcion" id="serv-desc" required placeholder="Describe lo que incluye tu servicio..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Guardar Servicio</button>
            </form>
        </div>
    </div>

    <script src="/aplicacion/cercamatch/public/assets/js/app.js"></script>
</body>
</html>
