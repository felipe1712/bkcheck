# BkCheck - Plataforma SaaS de Background Checks y OSINT

Este proyecto es una plataforma multitenant para due diligence e investigaciones de antecedentes en México. Integra 5 consultas clave mediante la API de NuFi (Validación de RFC, Certificados CSD/FIEL del SAT, Registro Público de Comercio SIGER, Listas SAT 69/69B de EFOS/EDOS y Búsqueda de Marcas del IMPI).

---

## 🚀 Requisitos e Instalación Local (Windows con Laragon)

Laragon incluye PHP, Composer, Node.js y MySQL listos para usar.

1. **Clonar e instalar dependencias**:
   ```bash
   composer install
   npm install
   ```

2. **Configurar el archivo de entorno**:
   Copia el archivo `.env.example` a `.env` y configura tus credenciales de base de datos.
   *(La base de datos `common_admin` se crea automáticamente o puedes crearla en MySQL/MariaDB).*
   ```bash
   copy .env.example .env
   php artisan key:generate
   ```

3. **Ejecutar migraciones y semilla de datos (Seeders)**:
   ```bash
   php artisan migrate:fresh --seed
   ```

4. **Compilar assets con Vite**:
   ```bash
   npm run build
   ```

---

## 🖥️ Cómo Iniciar el Entorno de Desarrollo

Para utilizar la plataforma localmente, abre dos terminales en la carpeta del proyecto y ejecuta:

### Terminal 1: Servidor Web Laravel
```bash
php artisan serve --port=8001
```
El panel estará disponible en: **`http://127.0.0.1:8001`**

### Terminal 2: Procesamiento de Consultas (Worker de Colas)
Las consultas a conectores e integraciones de API se realizan de manera asíncrona mediante queues. Inicia el worker con:
```bash
php artisan queue:work
```

---

## 🔑 Credenciales de Acceso (Entorno de Desarrollo)

### 1. Panel de Administración Global (Super Admin)
* **URL:** `http://127.0.0.1:8001/superadmin/dashboard`
* **Usuario:** `superadmin@atlas.com`
* **Contraseña:** `password`
* *Funcionalidades:* Administración de Tenants (Clientes), límites mensuales de consultas, registro inmutable de auditoría, bitácoras de llamadas a la API de NuFi.

### 2. Panel del Tenant A (Consultoría Alfa)
* **URL:** `http://127.0.0.1:8001/tenant/dashboard`
* **Administrador:** `admin@alfa.com` / `password`
* **Investigador:** `investigador@alfa.com` / `password`
* *Funcionalidades:* Administración de usuarios, creación de proyectos y sujetos de investigación, consulta de antecedentes y descarga de PDF.

### 3. Panel del Tenant B (Investigaciones Beta)
* **Administrador:** `admin@beta.com` / `password`
* **Investigador:** `investigador@beta.com` / `password`

---

## 🔌 Integración con NuFi y Modo Mock

Para fines de prueba, el conector está configurado por defecto en **modo simulado (Mock)**, lo cual permite probar la UI y el comportamiento de las consultas sin consumir créditos reales ni requerir una API Key de NuFi activa.

* Si deseas conectar con la API real de NuFi, edita tu `.env`:
  ```env
  NUFI_MOCK=false
  NUFI_API_KEY=tu_api_key_aqui
  NUFI_BASE_URL=https://nufi.azure-api.net
  ```

---

## 🐳 Despliegue en Producción (Ubuntu / Docker)

### Requisitos Mínimos (Ubuntu 22.04+)
1. Instalar PHP 8.3+, extensiones comunes, MySQL, Nginx y Supervisor para mantener el comando `php artisan queue:work` activo.
2. Configurar Nginx apuntando al directorio `public/` del proyecto.
3. Asegurar que las variables `.env` apunten a producción (`APP_ENV=production`, `APP_DEBUG=false`).

### Despliegue con Docker (Opcional)
Se puede utilizar el archivo `docker-compose.yml` provisto para levantar la infraestructura de manera empaquetada:
```bash
docker-compose up -d --build
```
Esto levantará el servidor web PHP-FPM, el servidor web Nginx y el worker de colas configurado automáticamente.