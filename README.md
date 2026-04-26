
# CercaMatch - Marketplace de Servicios Locales

[![PHP Version](https://img.shields.io/badge/PHP-8.2-blue.svg)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-orange.svg)](https://mysql.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

## 📱 Descripción

**CercaMatch** es una plataforma que conecta a clientes con proveedores de servicios locales (plomeros, electricistas, jardineros, etc.) utilizando **geolocalización** para mostrar profesionales cercanos.

### 🎯 Problema que resuelve
Encontrar un servicio técnico rápido, cerca de tu ubicación y con buenas referencias es difícil. CercaMatch permite:
- Buscar servicios por categoría y distancia
- Solicitar cotizaciones con costo de desplazamiento automático
- Calificar a los proveedores
- Comunicación directa por WhatsApp

---

## 🚀 Características principales

| Módulo | Funcionalidad |
|:---|:---|
| **🔐 Autenticación** | Registro/login con roles (cliente/proveedor/admin) |
| **📍 Geolocalización** | Busca servicios cercanos usando Leaflet y cálculo de distancias |
| **🛠️ Gestión de servicios** | Proveedores crean/editan servicios con precio y costo por km |
| **📝 Solicitudes** | Clientes solicitan servicios, proveedores aceptan/rechazan |
| **🚚 Estado en tiempo real** | Pendiente → Aceptada → En camino → Completada |
| **⭐ Calificaciones** | Sistema de 5 estrellas con comentarios |
| **💬 WhatsApp** | Contacto directo entre cliente y proveedor |
| **📸 Fotos de perfil** | Subida de avatar para usuarios |
| **📱 PWA** | Instalable en Android como app nativa |

---

## 🛠️ Tecnologías utilizadas

| Capa | Tecnologías |
|:---|:---|
| **Backend** | PHP 8.2, MySQL (Aiven.io), mysqli |
| **Frontend** | HTML5, CSS3, JavaScript, Leaflet (mapas), Tailwind CSS |
| **Autenticación** | Sesiones PHP, JWT (para API) |
| **Geolocalización** | Leaflet.js, Nominatim API |
| **Base de datos** | MySQL con procedimientos almacenados |
| **Despliegue** | Render / Railway |
| **App Android** | PWA + WebViewGold (APK) |

---

## 📂 Estructura del proyecto

```
CERCAMATCH-APK/
├── config/                 # Configuración de base de datos
│   ├── config.php          # Conexión (usa variables de entorno)
│   └── ca.pem              # Certificado SSL (no se sube)
├── public/                 # Archivos públicos (raíz web)
│   ├── assets/             # CSS, JS, imágenes
│   ├── manifest.json       # Configuración PWA
│   ├── sw.js               # Service Worker
│   ├── index.php           # Búsqueda de servicios
│   ├── login.php           # Login/registro
│   ├── mis-solicitudes.php # Historial del cliente
│   ├── perfil.php          # Editar perfil
│   ├── dashboard-proveedor.php
│   ├── dashboard-admin.php
│   └── gestion-servicios.php
├── services/               # Lógica de negocio
│   └── Cservicios.php      # Funciones para SPs
├── uploads/                # Fotos de perfil
├── Dockerfile              # Configuración para Render
├── .htaccess               # Redirección a public/
├── .gitignore              # Archivos excluidos
├── .env.example            # Plantilla de variables de entorno
└── README.md               # Este archivo
```

---

## ⚙️ Instalación local (para desarrollo)

### Requisitos previos
- PHP 8.2 o superior
- MySQL 5.7 o superior
- Composer (opcional)
- Git

### Pasos

```bash
# 1. Clonar el repositorio
git clone https://github.com/rmx-dev-col/CERCAMATCH-APK.git
cd CERCAMATCH-APK

# 2. Configurar variables de entorno
cp .env.example .env
# Editar .env con tus credenciales de base de datos

# 3. Crear base de datos y ejecutar el script SQL
mysql -u root -p < database.sql

# 4. Iniciar servidor local
php -S localhost:8000 -t public
```

### Variables de entorno (`.env`)

```env
DB_HOST=localhost
DB_PORT=3306
DB_USER=root
DB_PASS=
DB_NAME=cercamatch_db
```

---

## 🌐 Despliegue en producción

### Render (recomendado)

1. Conectar repositorio de GitHub
2. Agregar variables de entorno:
   ```env
   DB_HOST=mysql-3946982-yeinerenrique2007-cd2e.i.aivencloud.com
   DB_PORT=27520
   DB_USER=avnadmin
   DB_PASS=AVNS_D2Hom75qqeQ2uwrMicb
   DB_NAME=cercamatch_db
   ```
3. Desplegar

### Railway

La aplicación ya incluye `railway.json`. Solo conectar y agregar variables de entorno.

---

## 📱 App Android (APK)

### Opción 1: Desde navegador (PWA)
- Abrir `https://cercamatch.onrender.com` en **Chrome** en Android
- Tocar ⋮ → **"Instalar aplicación"**

### Opción 2: APK personalizada
Usa **WebViewGold** o **iappyxOS** para generar una APK con branding personalizado.


## 📄 Licencia

MIT © [Yeiner Medina](https://github.com/rmx-dev-col)

---

## 👨‍💻 Autor

**Yeiner Enrique Medina Aguas**
- GitHub: [@rmx-dev-col](https://github.com/rmx-dev-col)
- Portafolio: [tradingvisionai.online](https://tradingvisionai.online)
- Email: yeinerenrique2007@gmail.com

---

## 🙏 Agradecimientos

- [Leaflet](https://leafletjs.com/) para mapas interactivos
- [Nominatim](https://nominatim.openstreetmap.org/) para geocodificación
- [Render](https://render.com) para hosting
- [Aiven](https://aiven.io) para base de datos MySQL


