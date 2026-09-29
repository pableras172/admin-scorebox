# ScoreBox Admin - Panel de Administración & Backoffice

[![GitHub Repository](https://img.shields.io/badge/GitHub-pableras172%2Fadmin--scorebox-181717?style=flat&logo=github)](https://github.com/pableras172/admin-scorebox)
[![Laravel Version](https://img.shields.io/badge/Laravel-13.x-FF2D20?style=flat&logo=laravel)](https://laravel.com)
[![Filament Version](https://img.shields.io/badge/Filament-5.x-F59E0B?style=flat&logo=filament)](https://filamentphp.com)
[![PHP Version](https://img.shields.io/badge/PHP-%5E8.3-777BB4?style=flat&logo=php)](https://php.net)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

Panel de administración web y backoffice para el ecosistema **ScoreBox** ([MyMusicalScores](https://github.com/pableras172/admin-scorebox)). Proporciona una interfaz centralizada y ágil para la gestión operativa, supervisión de métricas, soporte y ejecución de estrategias de marketing sobre la base de datos de producción de la aplicación móvil.

---

## 🚀 Características Principales

- **👥 Gestión de Usuarios Móviles (`ScoreBoxUsers`)**:
  - Consulta y administración de usuarios almacenados en **Google Cloud Firestore**.
  - Filtrado por estado de suscripción (*Free* vs. *Premium*), actividad y fecha de registro.
  - Interacción segura mediante DTOs y una capa tipada de servicios para preservar la integridad de datos de los clientes Android.

- **🎟️ Gestión de Códigos Promocionales**:
  - Creación, seguimiento y control de códigos promocionales.
  - Importación masiva de códigos mediante importadores dedicados.

- **📢 Campañas de Marketing & Emailing**:
  - Segmentación de audiencias móviles a través de `CampaignAudienceResolver`.
  - Configuración y despacho de campañas dirigidas a segmentos específicos de usuarios.
  - Registro y trazabilidad de envíos.

- **🛡️ Gestión de Bajas (`Email Unsubscribes`)**:
  - Listas de exclusión y cumplimiento normativo para la gestión de solicitudes de baja de correo.

- **📊 Métricas y Dashboard en Tiempo Real**:
  - Widgets interactivos con estadísticas de uso de ScoreBox, distribución de planes y últimos usuarios registrados.

---

## 🏛️ Arquitectura & Principios de Integración

1. **Modelo Dual de Usuarios**:
   - **`Users` (Laravel)**: Administradores y operadores autorizados para autenticarse en el panel administrativo (almacenados en la base de datos relacional de la aplicación).
   - **`ScoreBoxUsers` (Firestore)**: Usuarios finales de la aplicación móvil publicados en Play Store (almacenados en Google Cloud Firestore).
2. **Aislamiento de Servicios (Service Layer Isolation)**:
   - Los componentes visuales de Filament (recursos, páginas y widgets) nunca interactúan directamente con el SDK de Firestore. Todas las operaciones pasan por `App\Services\Firestore\*`.
3. **No Invasión de Clientes Móviles**:
   - Este proyecto se limita estrictamente a la gestión backoffice y respeta en todo momento los esquemas y contratos de datos utilizados por la app Android.

---

## 📋 Requisitos del Sistema

- **PHP**: `^8.3`
- **Extensiones PHP**: `curl`, `mbstring`, `openssl`, `pdo`, `tokenizer`, `xml`, `bcmath` (opcionalmente `grpc` para optimizar conexiones con Firestore).
- **Composer**: `2.x`
- **Node.js**: `>= 18.x` y **NPM**
- **Base de Datos Local**: SQLite (por defecto), MySQL o PostgreSQL
- **Cuenta de Servicio de Firebase / Google Cloud**: Archivo JSON de credenciales con acceso al proyecto de Firestore de ScoreBox.

---

## 🛠️ Instalación y Puesta en Marcha

### 1. Clonar el Repositorio

```bash
git clone https://github.com/pableras172/admin-scorebox.git
cd admin-scorebox
```

### 2. Instalar Dependencias de Backend

```bash
composer install
```

### 3. Configurar el Entorno

Copia el archivo de ejemplo y genera la clave de aplicación:

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configurar Credenciales de Firebase / Firestore

Añade las credenciales de tu proyecto en el archivo `.env`:

```dotenv
FIREBASE_PROJECT_ID=tu-firebase-project-id
FIREBASE_CREDENTIALS=storage/app/firebase/service-account.json
```

> ⚠️ **Importante**: Asegúrate de que el archivo JSON de credenciales nunca se incluya en el control de versiones (Git).

### 5. Ejecutar Migraciones y Crear Usuario Administrador

```bash
# Crear base de datos local y tablas necesarias
php artisan migrate

# Crear usuario para acceder al panel de Filament
php artisan make:filament-user
```

### 6. Instalar Dependencias de Frontend y Compilar Assets

```bash
npm install
npm run build
```

### 7. Iniciar el Servidor de Desarrollo

Puedes iniciar el entorno completo de desarrollo con:

```bash
composer run dev
```

O bien mediante los procesos individuales:

```bash
# Terminal 1: Servidor web PHP
php artisan serve

# Terminal 2: Servidor de assets Vite
npm run dev
```

Accede al panel administrativo navegando a: [http://localhost:8000/admin](http://localhost:8000/admin)

---

## 🚀 Checklist de Despliegue en Producción (Plesk / Servidor Web)

### 📌 A. Configuración Inicial (Solo la primera vez)

1. [ ] **DNS**: Añadir registro tipo `A` en el proveedor del dominio (`GoDaddy` / proveedor DNS) para `admin.scorebox.pro` apuntando a la IP pública del servidor.
2. [ ] **Document Root**: Configurar en Plesk que la raíz del documento apunte a `/public` (ej. `/var/www/vhosts/scorebox.pro/admin.scorebox.pro/public`).
3. [ ] **Certificado SSL**: Emitir e instalar el certificado SSL (Let's Encrypt) en Plesk para `admin.scorebox.pro`.
4. [ ] **Variables de Entorno (`.env`)**:
   - `APP_ENV=production` y `APP_DEBUG=false`
   - `APP_URL=https://admin.scorebox.pro`
   - Clave de aplicación generada (`php artisan key:generate`)
   - `DB_CONNECTION=sqlite`
   - Configuración SMTP de **Brevo** (`MAIL_HOST=smtp-relay.brevo.com`, puerto `587`, credenciales y remitente validado).
   - Variables de **Firebase / Firestore** (`FIREBASE_PROJECT_ID` y ruta a `FIREBASE_CREDENTIALS`).
5. [ ] **Credenciales de Firebase**: Subir el archivo JSON de la cuenta de servicio a una ruta segura fuera del acceso público (ej. `storage/app/firebase/service-account.json`).
6. [ ] **Base de Datos SQLite & Permisos**:
   ```bash
   touch database/database.sqlite
   chmod -R 775 storage bootstrap/cache database
   chmod 664 database/database.sqlite
   ```
7. [ ] **Migraciones Iniciales**:
   ```bash
   php artisan migrate --force
   ```
8. [ ] **Crear Usuario Administrador de Filament**:
   ```bash
   php artisan make:filament-user --name="Admin" --email="info@scorebox.pro" --password="TuPasswordSeguro"
   ```
9. [ ] **Cron / Tarea Programada en Plesk**:
   - Añadir en *Tareas Programadas* (*Scheduled Tasks*) cada minuto:
     ```bash
     cd /ruta-a-tu-proyecto && php artisan schedule:run >> /dev/null 2>&1
     ```

---

### 🔄 B. Checklist Post-Despliegue (En cada actualización / `git pull`)

Cada vez que se suban cambios al repositorio y se despliegue una nueva versión en producción, ejecutar en orden:

```bash
# 1. Obtener la última versión del código
git pull origin main

# 2. Instalar / actualizar dependencias de Composer sin paquetes de desarrollo
composer install --no-dev --optimize-autoloader

# 3. Ejecutar nuevas migraciones de base de datos
php artisan migrate --force

# 4. Actualizar assets publicados de Filament
php artisan filament:upgrade

# 5. Compilar assets de frontend (si hubo cambios en estilos, iconos o vistas)
npm run build

# 6. Limpiar y re-generar cachés de configuración, rutas y vistas optimizadas
php artisan optimize:clear
php artisan optimize

# 7. Reiniciar colas de trabajo (si hay procesos en segundo plano)
php artisan queue:restart

# 8. Asegurar permisos de carpetas críticas
chmod -R 775 storage bootstrap/cache database
chmod 664 database/database.sqlite
```

> 💡 **Tip para Plesk**: En la pestaña **Despliegue** (*Deployment*) de Plesk Laravel Toolkit, puedes automatizar esta secuencia para que se ejecute con un solo clic en cada actualización.

---

## 🧪 Pruebas y Calidad de Código

Ejecutar la suite de pruebas automatizadas:

```bash
php artisan test
```

Aplicar el formato de código estandarizado con Laravel Pint:

```bash
vendor/bin/pint --format agent
```

---

## 📄 Licencia

Este proyecto está bajo la licencia [MIT](LICENSE).
