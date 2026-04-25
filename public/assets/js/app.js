/**
 * Lógica global de la aplicación CercaMatch
 */

const BASE_URL = '/aplicacion/cercamatch/public';

document.addEventListener('DOMContentLoaded', function() { 
    const menuIcon = document.querySelector('.menu-icon'); 
    const navLinks = document.querySelector('.nav-links'); 
    
    if (menuIcon && navLinks) { 
        menuIcon.addEventListener('click', function() { 
            navLinks.classList.toggle('active'); 
        }); 
    } 
}); 

// Helper para peticiones fetch con JSON
async function fetchJSON(endpoint, data) {
    const res = await fetch(`${BASE_URL}/${endpoint}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });
    return await res.json();
}

// --- MODALES ---
function abrirModalSolicitud(id, titulo, precioBase) {
    document.getElementById('sol-servicio-id').value = id;
    document.getElementById('modal-titulo').innerText = `Solicitar: ${titulo}`;
    document.getElementById('modal-info').innerText = `Precio base: $${precioBase.toLocaleString()}`;
    document.getElementById('modal-solicitud').classList.remove('hidden');
}

function abrirModalCalificar(id, titulo) {
    document.getElementById('cal-solicitud-id').value = id;
    document.getElementById('cal-servicio-titulo').innerText = titulo;
    document.getElementById('modal-calificar').classList.remove('hidden');
}

function abrirModalServicio() {
    document.getElementById('form-servicio').reset();
    document.getElementById('serv-id').value = '';
    document.getElementById('modal-titulo').innerText = 'Nuevo Servicio';
    document.getElementById('modal-servicio').classList.remove('hidden');
}

function cerrarModal() {
    document.querySelectorAll('.modal').forEach(m => m.classList.add('hidden'));
}

// --- AJAX ACCIONES ---
async function enviarSolicitud(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const dataObj = { accion: 'crear_solicitud' };
    formData.forEach((value, key) => dataObj[key] = value);

    try {
        const data = await fetchJSON('recibe_datos.php', dataObj);
        if (data.status === 'success') {
            alert('¡Solicitud enviada con éxito!');
            location.href = 'mis-solicitudes.php';
        } else {
            alert(data.message || 'Error al enviar solicitud');
        }
    } catch (err) {
        console.error(err);
        alert('Error de conexión');
    }
}

async function cancelarSolicitud(id) {
    if (!confirm('¿Estás seguro de que deseas cancelar esta solicitud?')) return;
    
    try {
        const data = await fetchJSON('recibe_datos.php', {
            accion: 'responder_solicitud',
            id: id,
            estado: 'cancelada'
        });
        if (data.status === 'success') location.reload();
    } catch (err) {
        console.error(err);
        alert('Error de conexión');
    }
}

async function enviarCalificacion(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const dataObj = { accion: 'calificar' };
    formData.forEach((value, key) => dataObj[key] = value);
    dataObj.id = dataObj.solicitud_id;

    try {
        const data = await fetchJSON('recibe_datos.php', dataObj);
        if (data.status === 'success') location.reload();
    } catch (err) {
        console.error(err);
        alert('Error de conexión');
    }
}

async function responderSolicitud(id, estado) {
    const msg = estado === 'aceptada' ? '¿Aceptar esta solicitud?' : '¿Rechazar esta solicitud?';
    if (!confirm(msg)) return;

    try {
        const data = await fetchJSON('recibe_datos.php', {
            accion: 'responder_solicitud',
            id: id,
            estado: estado
        });
        if (data.status === 'success') location.reload();
    } catch (err) {
        console.error(err);
        alert('Error de conexión');
    }
}

async function cambiarEstado(id, estado) {
    try {
        const data = await fetchJSON('recibe_datos.php', {
            accion: 'cambiar_estado',
            id: id,
            estado: estado
        });
        if (data.status === 'success') location.reload();
    } catch (err) {
        console.error(err);
        alert('Error de conexión');
    }
}

// --- GESTIÓN DE SERVICIOS (PROVEEDOR) ---
function editarServicio(s) {
    document.getElementById('serv-id').value = s.id;
    document.getElementById('serv-titulo').value = s.titulo;
    document.getElementById('serv-cat').value = s.categoria_id;
    document.getElementById('serv-tiempo').value = s.tiempo_estimado;
    document.getElementById('serv-precio').value = s.precio_base;
    document.getElementById('serv-costo-km').value = s.costo_por_km;
    document.getElementById('serv-desc').value = s.descripcion;
    document.getElementById('modal-titulo').innerText = 'Editar Servicio';
    document.getElementById('modal-servicio').classList.remove('hidden');
}

async function guardarServicio(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const dataObj = { accion: 'guardar_servicio' };
    formData.forEach((value, key) => dataObj[key] = value);

    try {
        const data = await fetchJSON('recibe_datos.php', dataObj);
        if (data.status === 'success') location.reload();
        else alert(data.message || 'Error al guardar');
    } catch (err) {
        console.error(err);
        alert('Error de conexión');
    }
}

async function eliminarServicio(id) {
    if (!confirm('¿Eliminar este servicio definitivamente?')) return;
    
    try {
        const data = await fetchJSON('recibe_datos.php', {
            accion: 'eliminar_servicio',
            id: id
        });
        if (data.status === 'success') location.reload();
    } catch (err) {
        console.error(err);
        alert('Error de conexión');
    }
}

// --- BÚSQUEDA AJAX ---
async function buscarServicios() {
    const radio = document.getElementById('radio_km') ? document.getElementById('radio_km').value : 10;
    const categoria = document.querySelector('select[name="categoria"]')?.value || '';
    const texto = document.querySelector('input[name="texto_busqueda"]')?.value || '';

    const url = `${BASE_URL}/index.php?radio_km=${encodeURIComponent(radio)}&categoria=${encodeURIComponent(categoria)}&texto_busqueda=${encodeURIComponent(texto)}`;

    try {
        const res = await fetch(url);
        const html = await res.text();
        location.href = url;
    } catch (err) {
        console.error('Error al buscar servicios:', err);
    }
}

function mostrarResultados(tieneResultados) {
    const resultados = document.getElementById('resultados');
    const sinResultados = document.getElementById('sin-resultados');
    if (!resultados || !sinResultados) return;
    if (tieneResultados) {
        resultados.style.display = 'grid';
        sinResultados.style.display = 'none';
    } else {
        resultados.style.display = 'none';
        sinResultados.style.display = 'block';
    }
}

// --- PERFIL ---
async function actualizarPerfil(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const dataObj = {};
    formData.forEach((value, key) => dataObj[key] = value);

    try {
        const data = await fetchJSON('recibe_datos.php', dataObj);
        if (data.status === 'success') alert('Perfil actualizado correctamente');
        else alert(data.message || 'Error al actualizar');
    } catch (err) {
        console.error(err);
        alert('Error de conexión');
    }
}
