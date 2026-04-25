<?php
declare(strict_types=1);

/**
 * Capa de servicios: Llamadas a procedimientos almacenados usando mysqli
 */
final class Cservicios {
    private $conn;

    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }

    // --- USUARIOS ---
    public function registrarUsuario($nombre, $email, $pass, $rol, $telefono, $direccion, $lat, $lng): bool {
        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $sql = "CALL SP_RegistrarUsuario(?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssssdd", $nombre, $email, $hash, $rol, $telefono, $direccion, $lat, $lng);
        $res = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $res;
    }

    public function login($email, $pass): ?array {
        $sql = "SELECT id, nombre, password, rol, latitud, longitud FROM usuarios WHERE email = ? AND activo = 1";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if ($user && password_verify($pass, $user['password'])) {
            unset($user['password']);
            return $user;
        }
        return null;
    }

    // --- SERVICIOS ---
    public function buscarServiciosCercanos($lat, $lng, $radio, $categoria_id = null, $busqueda = ''): array {
        $sql = "CALL SP_BuscarServiciosCercanos(?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "dddis", $lat, $lng, $radio, $categoria_id, $busqueda);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $servicios = mysqli_fetch_all($res, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
        return $servicios;
    }

    public function listarServiciosPorProveedor($proveedor_id): array {
        $sql = "SELECT s.*, c.nombre as categoria_nombre 
                FROM servicios s 
                JOIN categorias c ON s.categoria_id = c.id 
                WHERE s.proveedor_id = ? AND s.activo = 1";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $proveedor_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $servicios = mysqli_fetch_all($res, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
        return $servicios;
    }

    public function guardarServicio($id, $proveedor_id, $cat_id, $titulo, $desc, $precio, $tiempo, $costo_km): bool {
        if ($id) {
            $sql = "UPDATE servicios SET categoria_id = ?, titulo = ?, descripcion = ?, precio_base = ?, tiempo_estimado = ?, costo_por_km = ? WHERE id = ? AND proveedor_id = ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "isssdidi", $cat_id, $titulo, $desc, $precio, $tiempo, $costo_km, $id, $proveedor_id);
        } else {
            $sql = "INSERT INTO servicios (proveedor_id, categoria_id, titulo, descripcion, precio_base, tiempo_estimado, costo_por_km) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "iisssdi", $proveedor_id, $cat_id, $titulo, $desc, $precio, $tiempo, $costo_km);
        }
        $res = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $res;
    }

    public function eliminarServicio($id, $proveedor_id): bool {
        $sql = "UPDATE servicios SET activo = 0 WHERE id = ? AND proveedor_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $id, $proveedor_id);
        $res = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $res;
    }

    // --- SOLICITUDES ---
    public function crearSolicitud($cliente_id, $servicio_id, $mensaje, $lat, $lng): ?array {
        $sql = "CALL SP_CrearSolicitud(?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "iisdd", $cliente_id, $servicio_id, $mensaje, $lat, $lng);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $solicitud = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);
        return $solicitud;
    }

    public function listarSolicitudes($usuario_id, $rol): array {
        $sql = "CALL SP_ListarSolicitudesPorUsuario(?, ?)";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "is", $usuario_id, $rol);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $solicitudes = mysqli_fetch_all($res, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
        return $solicitudes;
    }

    public function responderSolicitud($solicitud_id, $estado): bool {
        // El SP espera: (solicitud_id, estado, proveedor_id) 
        $sql = "CALL SP_ResponderSolicitud(?, ?, ?)";
        $stmt = mysqli_prepare($this->conn, $sql);
        $proveedor_id = $_SESSION['user_id'];
        mysqli_stmt_bind_param($stmt, "isi", $solicitud_id, $estado, $proveedor_id);
        $res = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $res;
    }

    public function marcarEnCamino($solicitud_id): bool {
        $sql = "CALL SP_MarcarEnCamino(?, ?)";
        $stmt = mysqli_prepare($this->conn, $sql);
        $proveedor_id = $_SESSION['user_id'];
        mysqli_stmt_bind_param($stmt, "ii", $solicitud_id, $proveedor_id);
        $res = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $res;
    }

    public function completarSolicitud($solicitud_id): bool {
        $sql = "CALL SP_CompletarSolicitud(?, ?)";
        $stmt = mysqli_prepare($this->conn, $sql);
        $proveedor_id = $_SESSION['user_id'];
        mysqli_stmt_bind_param($stmt, "ii", $solicitud_id, $proveedor_id);
        $res = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $res;
    }

    public function calificarSolicitud($solicitud_id, $calificacion, $comentario): bool {
        $cliente_id = $_SESSION['user_id'];
        $sql = "CALL SP_CalificarSolicitud(?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "iiis", $solicitud_id, $cliente_id, $calificacion, $comentario);
        $res = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $res;
    }

    // --- ADMIN ---
    public function obtenerEstadisticasAdmin(): array {
        $sql = "CALL SP_EstadisticasAdmin()";
        $res = mysqli_query($this->conn, $sql);
        $stats = mysqli_fetch_assoc($res);
        mysqli_free_result($res);
        // Algunos SPs retornan múltiples result sets, si es necesario manejarlos:
        // while(mysqli_next_result($this->conn)) { ... }
        return $stats ?: [];
    }

    public function listarUsuarios(): array {
        $sql = "SELECT id, nombre, email, rol, activo, fecha_registro FROM usuarios ORDER BY fecha_registro DESC";
        $res = mysqli_query($this->conn, $sql);
        return mysqli_fetch_all($res, MYSQLI_ASSOC);
    }

    public function cambiarEstadoUsuario($id, $activo): bool {
        $sql = "UPDATE usuarios SET activo = ? WHERE id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $activo, $id);
        $res = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $res;
    }

    public function actualizarPerfil($id, $nombre, $tel, $dir, $lat, $lng, $extra = []): bool {
        mysqli_begin_transaction($this->conn);
        try {
            $sql = "UPDATE usuarios SET nombre = ?, telefono = ?, direccion = ?, latitud = ?, longitud = ? WHERE id = ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "sssddi", $nombre, $tel, $dir, $lat, $lng, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            if (isset($extra['radio_maximo_km'])) {
                $sql = "UPDATE proveedores_detalle SET descripcion = ?, radio_maximo_km = ? WHERE usuario_id = ?";
                $stmt = mysqli_prepare($this->conn, $sql);
                mysqli_stmt_bind_param($stmt, "sdi", $extra['descripcion'], $extra['radio_maximo_km'], $id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }

            mysqli_commit($this->conn);
            return true;
        } catch (Exception $e) {
            mysqli_rollback($this->conn);
            return false;
        }
    }

    // --- CATEGORIAS ---
    public function listarCategorias(): array {
        $sql = "SELECT * FROM categorias WHERE activo = 1";
        $res = mysqli_query($this->conn, $sql);
        return mysqli_fetch_all($res, MYSQLI_ASSOC);
    }
}
?>
