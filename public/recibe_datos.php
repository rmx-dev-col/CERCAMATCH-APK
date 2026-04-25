<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/Cservicios.php';

header('Content-Type: application/json');

// Leer entrada JSON si existe
$input = json_decode(file_get_contents('php://input'), true);
if ($input) {
    $_POST = array_merge($_POST, $input);
}

$svc = new Cservicios($conn);
$response = ['status' => 'error', 'message' => 'Acción no válida'];

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

try {
    switch ($accion) {
        case 'login':
            $email = $_POST['email'] ?? '';
            $pass = $_POST['password'] ?? '';
            $user = $svc->login($email, $pass);
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nombre'] = $user['nombre'];
                $_SESSION['rol'] = $user['rol'];
                $_SESSION['lat'] = $user['latitud'];
                $_SESSION['lng'] = $user['longitud'];
                $response = ['status' => 'success', 'rol' => $user['rol']];
            } else {
                $response = ['status' => 'error', 'message' => 'Credenciales incorrectas'];
            }
            break;

        case 'registro':
            $nombre = $_POST['nombre'] ?? '';
            $email = $_POST['email'] ?? '';
            $pass = $_POST['password'] ?? '';
            $rol = $_POST['rol'] ?? 'cliente';
            $tel = $_POST['telefono'] ?? '';
            $dir = $_POST['direccion'] ?? '';
            $lat = (float)($_POST['lat'] ?? 0);
            $lng = (float)($_POST['lng'] ?? 0);
            
            if ($svc->registrarUsuario($nombre, $email, $pass, $rol, $tel, $dir, $lat, $lng)) {
                $response = ['status' => 'success', 'message' => 'Usuario registrado'];
            } else {
                $response = ['status' => 'error', 'message' => 'Error al registrar usuario'];
            }
            break;

        case 'crear_solicitud':
            if (empty($_SESSION['user_id'])) throw new Exception("No autorizado");
            $servicio_id = (int)$_POST['servicio_id'];
            $mensaje = $_POST['mensaje'] ?? '';
            $res = $svc->crearSolicitud($_SESSION['user_id'], $servicio_id, $mensaje, $_SESSION['lat'], $_SESSION['lng']);
            if ($res) {
                $response = ['status' => 'success', 'data' => $res];
            }
            break;

        case 'responder_solicitud':
            if (empty($_SESSION['user_id']) || $_SESSION['rol'] !== 'proveedor') throw new Exception("No autorizado");
            $id = (int)($input['id'] ?? $_POST['id']);
            $estado = $input['estado'] ?? $_POST['estado'];
            // Pasar también el ID del proveedor (se obtiene dentro del método) 
            if ($svc->responderSolicitud($id, $estado)) {
                $response = ['status' => 'success'];
            } else {
                $response = ['status' => 'error', 'message' => 'No se pudo actualizar'];
            }
            break;

        case 'cambiar_estado':
            if (empty($_SESSION['user_id']) || $_SESSION['rol'] !== 'proveedor') throw new Exception("No autorizado");
            $id = (int)($input['id'] ?? $_POST['id']);
            $nuevo_estado = $input['estado'] ?? $_POST['estado'];
            $res = false;
            if ($nuevo_estado === 'en_camino') {
                $res = $svc->marcarEnCamino($id);
            } elseif ($nuevo_estado === 'completada') {
                $res = $svc->completarSolicitud($id);
            }
            $response = $res ? ['status' => 'success'] : ['status' => 'error', 'message' => 'Error al cambiar estado'];
            break;

        case 'calificar':
            if (empty($_SESSION['user_id'])) throw new Exception("No autorizado");
            $id = (int)($input['id'] ?? $_POST['id'] ?? 0);
            $stars = (int)($input['estrellas'] ?? $_POST['estrellas'] ?? 0);
            $comentario = $input['comentario'] ?? $_POST['comentario'] ?? '';

            if ($stars < 1 || $stars > 5) {
                $response = ['status' => 'error', 'message' => 'La calificación debe ser entre 1 y 5 estrellas'];
                break;
            }

            // Verificar que la solicitud pertenece al cliente y está completada
            $check = "SELECT id FROM solicitudes WHERE id = ? AND cliente_id = ? AND estado = 'completada'";
            $stmt = mysqli_prepare($conn, $check);
            mysqli_stmt_bind_param($stmt, "ii", $id, $_SESSION['user_id']);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            if (mysqli_fetch_assoc($res) === null) {
                mysqli_stmt_close($stmt);
                $response = ['status' => 'error', 'message' => 'No puedes calificar esta solicitud'];
                break;
            }
            mysqli_stmt_close($stmt);

            if ($svc->calificarSolicitud($id, $stars, $comentario)) {
                $response = ['status' => 'success'];
            } else {
                $response = ['status' => 'error', 'message' => 'Error al guardar calificación'];
            }
            break;

        case 'guardar_servicio':
            if (empty($_SESSION['user_id']) || $_SESSION['rol'] !== 'proveedor') throw new Exception("No autorizado");
            $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
            $cat_id = (int)$_POST['categoria_id'];
            $titulo = $_POST['titulo'];
            $desc = $_POST['descripcion'];
            $precio = (float)$_POST['precio_base'];
            $tiempo = $_POST['tiempo_estimado'];
            $costo_km = (float)$_POST['costo_por_km'];
            
            if ($svc->guardarServicio($id, $_SESSION['user_id'], $cat_id, $titulo, $desc, $precio, $tiempo, $costo_km)) {
                $response = ['status' => 'success'];
            }
            break;

        case 'eliminar_servicio':
            if (empty($_SESSION['user_id']) || $_SESSION['rol'] !== 'proveedor') throw new Exception("No autorizado");
            $id = (int)$_POST['id'];
            if ($svc->eliminarServicio($id, $_SESSION['user_id'])) {
                $response = ['status' => 'success'];
            }
            break;

        case 'cambiar_estado_usuario':
            if (empty($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') throw new Exception("No autorizado");
            $id = (int)$_POST['id'];
            $activo = (int)$_POST['activo'];
            if ($svc->cambiarEstadoUsuario($id, $activo)) {
                $response = ['status' => 'success'];
            }
            break;

        case 'actualizar_perfil':
            if (empty($_SESSION['user_id'])) throw new Exception("No autorizado");
            $nombre = $_POST['nombre'];
            $tel = $_POST['telefono'];
            $dir = $_POST['direccion'];
            $lat = (float)$_POST['lat'];
            $lng = (float)$_POST['lng'];
            
            $extra = [];
            if ($_SESSION['rol'] === 'proveedor') {
                $extra['descripcion'] = $_POST['descripcion'] ?? '';
                $extra['radio_maximo_km'] = (float)$_POST['radio_maximo_km'];
            }
            
            if ($svc->actualizarPerfil($_SESSION['user_id'], $nombre, $tel, $dir, $lat, $lng, $extra)) {
                $_SESSION['nombre'] = $nombre;
                $_SESSION['lat'] = $lat;
                $_SESSION['lng'] = $lng;
                $response = ['status' => 'success'];
            }
            break;

        case 'subir_foto':
            if (empty($_SESSION['user_id'])) throw new Exception("No autorizado");
            
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/../uploads/';
                if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                
                $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
                $filename = 'user_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
                $target = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $target)) {
                    // Actualizar en BD
                    $stmt = mysqli_prepare($conn, "UPDATE usuarios SET foto_perfil = ? WHERE id = ?");
                    mysqli_stmt_bind_param($stmt, "si", $filename, $_SESSION['user_id']);
                    mysqli_stmt_execute($stmt);
                    
                    $response = ['status' => 'success', 'filename' => $filename];
                } else {
                    $response = ['status' => 'error', 'message' => 'Error al subir archivo'];
                }
            } else {
                $response = ['status' => 'error', 'message' => 'No se recibió archivo'];
            }
            break;

        case 'guardar_ubicacion':
            $lat = (float)($_POST['lat'] ?? 0);
            $lng = (float)($_POST['lng'] ?? 0);
            
            $_SESSION['lat'] = $lat;
            $_SESSION['lng'] = $lng;

            if (!empty($_SESSION['user_id'])) {
                // Si el usuario está logueado, actualizamos su ubicación en la DB
                $sql = "UPDATE usuarios SET latitud = ?, longitud = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ddi", $lat, $lng, $_SESSION['user_id']);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
            
            $response = ['status' => 'success'];
            break;

        case 'obtener_ubicacion':
            $response = [
                'status' => 'success',
                'data' => [
                    'lat' => $_SESSION['lat'] ?? null,
                    'lng' => $_SESSION['lng'] ?? null
                ]
            ];
            break;
    }
} catch (Exception $e) {
    $response = ['status' => 'error', 'message' => $e->getMessage()];
}

echo json_encode($response);
?>
