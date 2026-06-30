# Plataforma de Due Diligence / Background Check (MVP — FASE 1)

> \*\*Cómo usar este prompt:\*\* Pega todo el bloque en Antigravity con el modelo \*\*Claude (Sonnet 4.6 u Opus)\*\* seleccionado. Trabaja en \*\*Planning Mode\*\* primero. No dejes que el agente escriba código antes de aprobar el plan. Cada tarea trae un checklist de aceptación: no avances a la siguiente hasta que la actual cumpla \*\*todos\*\* los puntos.

\---

## ROL Y CONTEXTO

Eres un ingeniero full-stack senior. Vas a construir el **MVP (Fase 1) de una plataforma SaaS multitenant de due diligence / background check** para el mercado mexicano. La plataforma permite a despachos e investigadores ejecutar consultas sobre un *sujeto* (persona física o moral) y consolidar los hallazgos en un expediente con reporte exportable.

**ALCANCE DE FASE 1 (esto es lo único que se implementa ahora):** la plataforma multitenant completa + **5 consultas, todas vía la API de NuFi**:

1. **Validación de RFC**
2. **Obtención de CSD** (Certificados de Sello Digital del SAT por RFC — "Recuperación de Certificados")
3. **Consulta SIGER** (Registro Público de Comercio) por nombre de socio / razón social
4. **Listas SAT 69 y 69B** (EFOS/EDOS — vía el servicio de listas de NuFi)
5. **Registro de Marcas IMPI**

Las 5 salen por una sola integración con NuFi (una API key, una clase base). En Fase 1 **NO** hay scraping, **NO** hay importadores bulk y **NO** hay Google Maps.

**FASE 2 (NO la implementes ahora; solo deja puntos de extensión):** Google Places (domicilio), adverse media / noticias, Panama Papers (bulk), ComprasMX, Plataforma Nacional de Transparencia, QuiénEsQuién, Servidores Públicos Sancionados.

**No-goals (NO implementar):** biometría, OCR, validación INE/CURP en vivo, billing/pagos, microservicios separados. Déjalos como interfaces/TODO.

### Paso CERO obligatorio (antes de cualquier plan)

1. **El proyecto Laravel 12 YA EXISTE** en la carpeta de trabajo (`Laravel/saas`, plantilla premium pre-instalada con Vite). **NO ejecutes `laravel new` ni regeneres el scaffold.** Primero **inventaría** lo que la plantilla ya trae: sistema de auth, roles/permisos existentes, layouts Blade, componentes, rutas, modelos y migraciones. Construye **encima** de lo existente y **reutiliza el tema y sus componentes** — nunca los sobrescribas. Si la plantilla ya incluye un sistema de roles/permisos propio, úsalo en vez de instalar `spatie/laravel-permission`; solo agrégalo si no existe.
2. Lee y entiende la documentación viva de NuFi en **https://docs.nufi.mx** para los 5 servicios de Fase 1: **Validación de RFC, Obtención/Recuperación de Certificados (CSD), Registro Público de Comercio (SIGER), Listas SAT 69/69B y Registro de Marcas (IMPI)**. **No inventes endpoints ni payloads**: implementa contra el contrato documentado y deja todo configurable por `.env`/config. Si un endpoint no está claro, deja un TODO y pregunta; no adivines.
3. Resume en un Artifact (a) el inventario de la plantilla existente y (b) los 5 endpoints NuFi con sus parámetros de entrada y campos de salida relevantes. Solo entonces genera el plan de implementación.

\---

## STACK TECNOLÓGICO (pineado — no sustituir)

* **Backend:** PHP 8.3 + **Laravel 12**
* **DB:** **MySQL/MariaDB** (sistema de registro) con columnas `JSON` para payloads crudos de conectores
* **Cola/Jobs:** Laravel Queues con driver `database` para el MVP (migrable a Redis)
* **Auth + RBAC:** Laravel Breeze (o Fortify) + paquete de roles/permisos (`spatie/laravel-permission`)
* **Multitenancy:** shared-DB con discriminador `tenant\_id` + Global Scope a nivel Eloquent (NO usar bases separadas por tenant en el MVP)
* **Frontend / Plantilla:** Usar la **variante `laravel-12` del pack de plantilla premium provisto por el usuario** (Blade, server-rendered). Integrar ese tema tal cual; reutilizar su shell (sidebar, topbar, cards, tablas/DataTables) y NO construir el layout desde cero. **NO** usar las variantes Vue, React, Inertia ni Next — el stack es Blade puro.
* **Reportes:** exportación a PDF del expediente (`barryvdh/laravel-dompdf` o similar)
* **HTTP client:** Laravel HTTP (Guzzle) para conectores
* **Entorno de desarrollo:** **Laragon en Windows** (PHP, MariaDB/MySQL, Composer, Node ya provistos). NO usar Docker en local. DB en `127.0.0.1:3306`.
* **Entorno de producción:** Ubuntu 22.04+. Entregar `docker-compose.yml` (app + mysql) **opcional** para paridad/despliegue, más instrucciones de despliegue nativo en Ubuntu.

\---

## MODELO DE DATOS (mínimo)

* `tenants` — clientes independientes (id, nombre, plan, `limite\_consultas\_mensual`, `limite\_por\_servicio` opcional, activo)
* `users` — pertenecen a un tenant; campo `tenant\_id` nullable solo para `super\_admin`
* Roles (spatie): `super\_admin`, `tenant\_admin`, `investigador`
* `projects` — proyectos de investigación (pertenecen a un tenant)
* `subjects` — sujeto investigado: tipo (`persona\_fisica`|`persona\_moral`), nombre/razón social, RFC, domicilio, datos de consentimiento (`consentimiento\_otorgado`, `fecha`, `base\_legal`)
* `source\_queries` — una ejecución de un conector contra un subject (estado: `pendiente`|`corriendo`|`ok`|`error`, fuente, fecha). **Cada fila = 1 petición de API** = unidad de conteo y de cobro.
* `source\_results` — resultado normalizado + columna JSON `raw\_payload`
* `api\_usage` — contador agregado por `tenant\_id`, `user\_id`, `servicio` y `periodo` (año-mes), con `conteo`, `costo\_estimado` (conteo × costo NuFi) e `ingreso\_estimado` (conteo × precio cliente). Se incrementa al ejecutar cada conector.
* `activity\_logs` — bitácora general de actividad: `user\_id`, `tenant\_id`, `accion`, `entidad`, `entidad\_id`, `ip`, `metadata` (JSON), `created\_at`. Implementar con `spatie/laravel-activitylog`. Registra login/logout, altas/cambios/bajas, ejecutar investigación, exportar reporte, cambios de cuota, etc.
* `audit\_logs` — registro inmutable específico de consultas a fuentes (quién consultó qué sujeto, qué fuente, cuándo, IP).

Todas las tablas con datos de negocio llevan `tenant\_id` y están protegidas por el Global Scope de tenant.

\---

## PATRÓN DE CONECTORES (núcleo de la arquitectura)

Crea una interfaz/clase base `BaseSourceConnector` con contrato uniforme:

```
interface SourceConnector {
    public function key(): string;            // 'nufi\_rfc', 'google\_places', etc.
    public function label(): string;          // nombre legible
    public function appliesTo(Subject $s): bool; // si la fuente aplica al tipo de sujeto / datos disponibles
    public function query(Subject $s): SourceResult; // normaliza salida + guarda raw\_payload
}
```

Cada conector es una clase derivada. El motor de investigación (`InvestigationRunner`) recibe un `Subject`, descubre los conectores aplicables, despacha un **Job en cola por cada uno** (async), y el dashboard se actualiza conforme llegan resultados. El front **nunca** sabe el origen de los datos.

Conectores a implementar en **Fase 1** (una sola clase base `NufiConnector` con API key y base URL en config; cada servicio una subclase):

* `nufi\_rfc` — Validación de RFC
* `nufi\_csd` — Obtención de Certificados de Sello Digital (por RFC)
* `nufi\_siger` — Consulta SIGER / Registro Público de Comercio (socio / razón social)
* `nufi\_sat\_69\_69b` — Listas SAT 69 y 69B
* `nufi\_marcas` — Registro de Marcas IMPI

`appliesTo()` decide qué conector corre según los datos del sujeto (p. ej. RFC presente → RFC, CSD, 69/69B; razón social → SIGER, IMPI).

**Fase 2 (NO implementar, solo dejar el punto de extensión):** registrar la interfaz de forma que agregar `google\_places`, `nufi\_noticias`, `panama\_papers`, etc., sea una clase nueva sin tocar el runner. Documenta en la guía técnica cómo hacerlo.

\---

## REGLAS PARA EL AGENTE (Antigravity)

1. **Planning Mode primero.** Genera un plan de implementación como Artifact con las tareas de abajo desglosadas. Espera aprobación.
2. Trabaja **una tarea a la vez**. Al terminar cada una, genera un Artifact de verificación con el checklist marcado y capturas de pantalla del UI cuando aplique.
3. No declares una tarea "lista" sin cumplir **todo** el checklist de aceptación.
4. Después de cada tarea: corre migraciones y seeders, levanta el servidor y verifica que arranca sin error.
5. Código limpio y comentado en español donde ayude al handoff. Commits atómicos por tarea.

\---

## TAREAS POR MILESTONE

### M0 — Puesta en marcha sobre el proyecto existente

**Partir de la carpeta `Laravel/saas` (proyecto Laravel 12 con plantilla premium ya instalada y Vite).** NO crear proyecto nuevo. Pasos: `composer install`, `npm install`, configurar el `.env` existente apuntando a la DB de **Laragon** (`127.0.0.1:3306`), `php artisan key:generate`, crear la base de datos en Laragon y correr `php artisan migrate`. Instalar `spatie/laravel-permission` solo si la plantilla no trae roles propios. Generar `docker-compose.yml` opcional solo para producción.

* \[ ] `composer install` y `npm install` corren sin error en Laragon
* \[ ] El `.env` apunta a la DB de Laragon y `php artisan migrate` corre limpio
* \[ ] `npm run dev` compila los assets de la plantilla con Vite sin errores
* \[ ] La app levanta y se ve el layout/login **original de la plantilla** (sin reconstruirlo)
* \[ ] Artifact con el inventario de lo que ya trae la plantilla (auth, roles, layouts, componentes)
* \[ ] `.env.example` documenta las credenciales externas nuevas (NuFi API key)
* \[ ] README con arranque en **Laragon (Windows)** y despliegue en **Ubuntu** (nativo + docker-compose opcional)

### M1 — Multitenancy + RBAC

Modelo `Tenant`, columna `tenant\_id`, Global Scope de tenant en todos los modelos de negocio, middleware de tenant, roles `super\_admin`/`tenant\_admin`/`investigador` con permisos.

* \[ ] Un usuario de Tenant A no puede leer ni por URL datos de Tenant B (probado)
* \[ ] `super\_admin` ve todos los tenants; `tenant\_admin` solo el suyo
* \[ ] Seeder crea 1 super\_admin, 2 tenants demo con 1 admin y 1 investigador c/u
* \[ ] Test automatizado que verifica el aislamiento entre tenants

### M2 — Panel Super Admin + conteo de API + bitácora de actividad

CRUD de tenants (clientes independientes), planes y **límites de consultas**, alta de tenant\_admins. Además, los dos controles administrativos de esta etapa:

**(a) Conteo y cuotas de peticiones de API:** contar cada consulta por `tenant`, por `usuario` y por `servicio` NuFi (RFC, CSD, SIGER, 69/69B, Marcas), acumulado por periodo (mensual). Tablero de consumo con consultas usadas vs. límite, costo estimado e ingreso estimado. Cuota por tenant con bloqueo o alerta al acercarse/superar el límite.

**(b) Bitácora de actividad (usuarios y clientes):** registro de todas las acciones (login/logout, altas/cambios/bajas, ejecutar investigación, exportar reporte, cambios de cuota) con `spatie/laravel-activitylog`, vista filtrable por usuario, cliente, acción y fecha.

* \[ ] Crear/editar/suspender tenant; crear su `tenant\_admin` inicial
* \[ ] Definir `limite\_consultas\_mensual` por tenant y verlo aplicado
* \[ ] Contador de consultas por tenant / usuario / servicio (tabla `api\_usage`), incrementado al ejecutar cada conector
* \[ ] Tablero de consumo: usadas vs. límite, costo e ingreso estimados; `super\_admin` global, `tenant\_admin` solo su tenant
* \[ ] Bloqueo o alerta cuando un tenant alcanza su cuota (configurable)
* \[ ] `activity\_logs` registra las acciones clave; vista filtrable respetando el aislamiento por tenant
* \[ ] Todo lo de conteo y bitácora respeta el Global Scope (un tenant nunca ve datos de otro)

### M3 — Panel Tenant (proyectos, sujetos, usuarios)

El `tenant\_admin` administra usuarios de su tenant; cualquier usuario crea proyectos y da de alta sujetos.

* \[ ] CRUD de usuarios del tenant (solo dentro del tenant)
* \[ ] CRUD de proyectos de investigación
* \[ ] CRUD de sujetos (persona física/moral) con captura obligatoria de consentimiento y base legal
* \[ ] Validación de RFC y campos mínimos según tipo de sujeto

### M4 — Framework de conectores + 5 conectores NuFi

Interfaz `SourceConnector`, `BaseSourceConnector`, clase base `NufiConnector`, `InvestigationRunner`, Jobs en cola, modelo de resultado normalizado + `raw\_payload` JSON. Implementar los 5 servicios: `nufi\_rfc`, `nufi\_csd`, `nufi\_siger`, `nufi\_sat\_69\_69b`, `nufi\_marcas`.

* \[ ] Los 5 conectores implementados contra la doc viva de NuFi, con API key (de prueba) en config
* \[ ] `appliesTo()` elige conectores correctos según los datos del sujeto (RFC vs razón social)
* \[ ] Ejecutar una consulta despacha un Job por conector aplicable (async)
* \[ ] Cada resultado se normaliza y se guarda el `raw\_payload` crudo
* \[ ] Manejo de error/timeout por conector sin tumbar la investigación completa
* \[ ] Antes de despachar, verifica la cuota del tenant; al ejecutar, incrementa `api\_usage` (tenant/usuario/servicio)
* \[ ] Se registra cada consulta en `audit\_logs` y genera evento en `activity\_logs`
* \[ ] Interfaz lista para agregar conectores de Fase 2 sin tocar el runner

### M5 — Motor de investigación + expediente + reporte

UI para lanzar una investigación sobre un sujeto, ver el dashboard consolidado de hallazgos por fuente conforme llegan, marcar indicios, y exportar el expediente a PDF. Bitácora visible.

* \[ ] Botón "Ejecutar investigación" dispara todos los conectores aplicables
* \[ ] Dashboard muestra estado por fuente (pendiente/corriendo/ok/error) y resultados en vivo
* \[ ] Expediente consolidado con una sección por consulta (RFC, CSD, SIGER, 69/69B, IMPI)
* \[ ] Exportación a PDF con disclaimer ("indicios para análisis humano, no veredicto")
* \[ ] `audit\_logs` consultable por el `tenant\_admin` de su propio tenant

### M6 — QA, despliegue y documentación de handoff

QA (incl. aislamiento entre tenants), despliegue en Ubuntu y documentación, generada como Artifacts.

* \[ ] Suite de pruebas pasa, incluido el test de aislamiento entre tenants
* \[ ] Despliegue en Ubuntu documentado y probado (nativo + docker-compose opcional)
* \[ ] Manual de `super\_admin`: alta de clientes (tenants), planes, límites
* \[ ] Manual de `tenant\_admin`: usuarios, proyectos, sujetos, ejecutar investigaciones, leer expediente
* \[ ] Guía técnica: arquitectura, **cómo agregar un conector nuevo (Fase 2) paso a paso con ejemplo**, variables de entorno
* \[ ] Runbook de mantenimiento (backups MySQL, colas/worker, rotación de logs, rotación de API key NuFi de prueba → producción)

\---

## DESIGN CONSTRAINTS

* Usar el **tema de la plantilla premium (variante `laravel-12`)**: respetar su sidebar, topbar y sistema de componentes. Tablas con DataTables, tarjetas (cards) para los resultados de cada fuente.
* Estado de cada conector con badge de color (gris=pendiente, azul=corriendo, verde=ok, rojo=error).
* Expediente del sujeto = una vista por pestañas o acordeón, una sección por fuente.
* Español en toda la UI.

## SEGURIDAD Y CUMPLIMIENTO (obligatorio en el MVP)

* Aislamiento estricto por tenant (probado con tests).
* Captura de **consentimiento del sujeto** y base legal antes de permitir consultas.
* **Bitácora de auditoría inmutable** de toda consulta (usuario, sujeto, fuente, fecha, IP) y **bitácora de actividad** de todas las acciones de usuarios y clientes.
* Aviso de privacidad incluido en la plataforma.
* Disclaimer en todo reporte: los hallazgos son indicios para análisis humano, no decisiones automatizadas.
* Credenciales externas solo en `.env`, nunca en código.

\---

## ORDEN DE EJECUCIÓN

M0 → M1 → M2 → M3 → M4 → M5 → M6. No saltes milestones. Pide aprobación del plan antes de M0.

