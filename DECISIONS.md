# DECISIONS.md — Plataforma de Due Diligence / Background Check

> **Propósito de este archivo.** Es el puente de contexto para cualquier agente de IA (Claude o Gemini) que trabaje este proyecto en Antigravity. El agente **no tiene memoria del chat** donde se tomaron estas decisiones: su contexto es el **código del repo + el plan + este documento**. Léelo completo antes de proponer o ejecutar cambios. Acompaña al prompt de trabajo (`PLAN.md` / prompt de Antigravity v5).

_Última actualización: handoff inicial._

---

## 1. Qué es el proyecto

SaaS **multitenant** de due diligence / background check para México. Permite a despachos e investigadores ejecutar consultas sobre un **sujeto** (persona física o moral) contra múltiples fuentes oficiales, consolidar los hallazgos en un **expediente** y exportar un **reporte PDF**. Se opera con varios clientes independientes de forma aislada y segura.

Principio rector: los resultados son **indicios para análisis humano, nunca veredictos automáticos**.

---

## 2. Stack (decidido — no sustituir sin acuerdo)

- **Backend:** PHP 8.3 + **Laravel 12**
- **DB:** **MySQL / MariaDB**, con columnas `JSON` para los payloads crudos de cada conector
- **Colas/Jobs:** Laravel Queues, driver `database` para el MVP (migrable a Redis)
- **Auth + RBAC:** Breeze/Fortify + `spatie/laravel-permission` **solo si la plantilla no trae ya su propio sistema de roles**
- **Multitenancy:** shared-DB con discriminador `tenant_id` + Global Scope a nivel Eloquent (NO bases separadas por tenant en el MVP)
- **Plantilla / Frontend:** la **variante `laravel-12` del pack premium** ya instalada en la carpeta del proyecto (`Laravel/saas`). Es un **proyecto Laravel 12 completo** (tiene `artisan`, `composer.json`, `vite.config`, `lang/`, `.rtlcsssrc`), Blade + **Vite** (no Mix). Reutilizar su shell (sidebar, topbar, cards, DataTables). **Blade server-rendered**, NO usar variantes Vue/React/Inertia/Next.
- **Reportes PDF:** `barryvdh/laravel-dompdf` o equivalente
- **Bitácora de actividad:** `spatie/laravel-activitylog`
- **Entorno de desarrollo:** **Laragon en Windows** (PHP, MariaDB/MySQL, Composer, Node ya provistos). **Sin Docker en local.** DB en `127.0.0.1:3306`.
- **Entorno de producción:** Ubuntu 22.04+, `docker-compose.yml` opcional.

### Decisiones de plantilla descartadas
- **Riho (ThemeForest, $19): RECHAZADA.** La variante Laravel usa Bootstrap 4 + jQuery + Laravel Mix (legacy). La plantilla actual (Laravel 12 + Bootstrap 5 + Vite) es superior y ya se pagó.
- AdminLTE 4 quedó descartado en favor de la plantilla premium ya instalada.
- **Pendiente:** verificar que la **licencia** de la plantilla permita uso en un SaaS multitenant revendido a terceros.

---

## 3. Arquitectura clave: patrón de conectores

Todas las fuentes viven detrás de una interfaz uniforme. El dashboard nunca sabe el origen de los datos.

```
interface SourceConnector {
    key(): string;                 // 'nufi_rfc', 'inegi_denue', ...
    label(): string;
    appliesTo(Subject $s): bool;   // decide si aplica según datos del sujeto
    query(Subject $s): SourceResult;  // normaliza salida + guarda raw_payload
}
```

`BaseSourceConnector` → subclases. `InvestigationRunner` recibe un `Subject`, descubre conectores aplicables, **despacha un Job en cola por cada uno** (async), y el dashboard se actualiza conforme llegan resultados. Agregar una fuente nueva = una clase nueva, sin tocar el runner.

**Esta abstracción es también la defensa de independencia de proveedor (ver §7).**

---

## 4. Modelo de datos (mínimo)

- `tenants` — clientes independientes (`nombre`, `plan`, `limite_consultas_mensual`, `limite_por_servicio` opcional, `activo`)
- `users` — pertenecen a un tenant; `tenant_id` nullable solo para `super_admin`
- Roles (spatie): `super_admin`, `tenant_admin`, `investigador`
- `projects` — proyectos de investigación (por tenant)
- `subjects` — `tipo` (`persona_fisica|persona_moral`), nombre/razón social, RFC, domicilio, consentimiento (`consentimiento_otorgado`, `fecha`, `base_legal`)
- `source_queries` — **1 fila = 1 petición de API** = unidad de conteo y de cobro (estado, fuente, fecha)
- `source_results` — resultado normalizado + `raw_payload` (JSON)
- `api_usage` — contador agregado por `tenant_id` / `user_id` / `servicio` / `periodo` (año-mes), con `conteo`, `costo_estimado`, `ingreso_estimado`
- `activity_logs` — bitácora general (login/logout, altas/cambios/bajas, ejecutar investigación, exportar reporte, cambios de cuota). Implementar con `spatie/laravel-activitylog`
- `audit_logs` — registro inmutable específico de consultas a fuentes

Todo lo de negocio lleva `tenant_id` y está bajo Global Scope. **Un tenant nunca ve datos de otro** (probar con test automatizado).

---

## 5. Alcance de FASE 1 (lo único que se construye ahora)

Plataforma multitenant completa + **5 consultas, todas vía la API de NuFi**:

1. `nufi_rfc` — Validación de RFC
2. `nufi_csd` — Obtención de Certificados de Sello Digital (CSD) por RFC ("Recuperación de Certificados")
3. `nufi_siger` — Consulta SIGER / Registro Público de Comercio (socio / razón social)
4. `nufi_sat_69_69b` — Listas SAT 69 y 69B (EFOS/EDOS)
5. `nufi_marcas` — Registro de Marcas IMPI

Más los dos controles administrativos:
- **Conteo y cuotas de API** (por tenant/usuario/servicio, tablero de consumo, bloqueo/alerta al alcanzar cuota)
- **Bitácora de actividad** de usuarios y clientes

En Fase 1 **NO** hay scraping, **NI** importadores bulk, **NI** Google Maps. Las listas 69/69B vienen por NuFi (no se necesita descargador de archivos en Fase 1).

### Milestones (orden estricto)
- **M0** — Puesta en marcha sobre el proyecto existente (Laragon, `composer install`, `npm install`, `.env` → DB Laragon, migraciones). **NO correr `laravel new`.**
- **M1** — Multitenancy + RBAC (aislamiento por tenant, roles)
- **M2** — Panel Super Admin + conteo/cuotas de API + tablero de consumo
- **M2b** — Bitácora de actividad (spatie/activitylog) + vistas filtrables
- **M3** — Panel Tenant (proyectos, sujetos, usuarios, consentimiento)
- **M4** — Framework de conectores + 5 conectores NuFi (verificar cuota antes de despachar; incrementar `api_usage` al ejecutar)
- **M5** — Motor de investigación + expediente + reporte PDF + bitácora
- **M6** — QA (incl. aislamiento entre tenants) + despliegue Ubuntu + documentación de handoff

### Paso CERO obligatorio del agente
1. **El proyecto ya existe** en `Laravel/saas`. Inventariar lo que trae la plantilla (auth, roles, layouts, componentes) y construir **encima**; no sobrescribir el tema.
2. Leer la doc viva de NuFi (**https://docs.nufi.mx**) para los 5 servicios. **No inventar endpoints/payloads.**
3. Producir un Artifact con el inventario + los 5 endpoints antes de generar el plan.

---

## 6. NuFi

- **Costo confirmado: $3 MXN por consulta** (para los 5 servicios de Fase 1).
- API keys de **prueba** en proceso de entrega; entran en M4.
- **Pendiente de verificar:** que NuFi exponga **CSD ("Recuperación de Certificados")** como endpoint propio; es el único de los 5 que no estaba explícito en su catálogo público. El agente lo confirma al leer la doc en el Paso CERO.

---

## 7. Riesgo de proveedor e independencia (importante)

NuFi (y competidores como Nubarium, Verifik) son **capas de estabilización sobre fuentes públicas oficiales** (SAT, Secretaría de Economía/RPC, IMPI). No dependes de NuFi para el *dato*, sino para estabilidad, normalización, manejo de captchas y mantenimiento cuando los portales de gobierno cambian.

Mitigaciones (de más barata a más cara), a documentar/implementar:
1. **Abstracción de conector** (ya está): NuFi detrás de la interfaz → sustituible sin rehacer la plataforma.
2. **Guardar `raw_payload`** (ya está): el histórico sobrevive aunque NuFi desaparezca.
3. **Pre-registrar un proveedor de respaldo** (Nubarium / Verifik): swap en días.
4. **Auto-alojar SAT 69/69B**: el SAT publica esas listas como archivos descargables gratis → conector propio con costo casi cero. Primer paso de independencia.
5. **Degradación elegante** (ya está): si una fuente cae, el expediente la marca "no disponible" sin tumbar la investigación.
6. **Contrato con NuFi:** SLA de uptime, estabilidad de precio, derecho a exportar datos.

**No replicar todo ahora.** Replicar = convertirte en el que mantiene scrapers contra sitios de gobierno que cambian seguido (el negocio completo de NuFi). A $3/consulta, NuFi sale más barato que tu mantenimiento salvo a volúmenes altos.

---

## 8. Decisiones PENDIENTES

- **INEGI DENUE** (fuente oficial gratuita, **$0/consulta**; existencia, domicilio, actividad SCIAN, tamaño, geo, contacto):
  - ¿Fase 1 (6º conector `inegi_denue`, el build sube de ~$157k a ~$167k) o Fase 2?
  - ¿Cómo se cobran esas consultas al cliente? (costo $0 → margen ~100% si se cobran, o valor agregado gratis)
  - Es un movimiento de **independencia de proveedor** (fuente directa oficial, sin intermediario). Requiere token gratuito de INEGI.

---

## 9. Roadmap de FASE 2 (diferido — dejar puntos de extensión, no implementar)

Fuentes adicionales sobre la misma plataforma, cotizadas por separado:
- Google Places (domicilio) — *nota:* DENUE lo cubre mejor para empresas.
- Adverse media / noticias relevantes
- Panama Papers (ICIJ) — importador bulk con índice local
- ComprasMX / CompraNet
- Plataforma Nacional de Transparencia
- QuiénEsQuién.wiki
- Servidores Públicos Sancionados (SPF)
- INEGI DENUE (si no entra en Fase 1)
- **Presencia en línea / huella digital** (OSINT) — ver §10

---

## 10. OSINT de presencia en línea (Fase 2) — reglas firmes

**APROBADO con salvaguardas:** `Sherlock` (licencia MIT, apto para SaaS) y `social-analyzer` (**verificar su licencia antes de redistribuir**; busca por nombre, mejor para nuestro caso). Como conector `presencia_en_linea` detrás del `BaseSourceConnector`.

Gobernanza obligatoria (escribir en diseño y en términos):
- Solo **handles/enlaces públicos**, no volcados completos de perfil.
- **No** capturar ni usar atributos sensibles (religión, política, salud, orientación).
- Marcar todo como **indicio con revisión humana**; ruta ARCO de rectificación.
- **Retención corta**; finalidad acotada por contrato a due diligence lícita.
- Consulta **dirigida por investigación**, no barridos masivos.
- Base legal = **interés legítimo** (el sujeto casi nunca consiente), no consentimiento.
- Tratar **empresas** (bajo riesgo) distinto de **personas físicas** (sensible).
- **Política de uso aceptable por tenant** + atestación de propósito + monitoreo de abuso (la bitácora de actividad ayuda).
- Evaluar un **API comercial de OSINT** con garantías de cumplimiento vs. auto-alojar (mismo trade-off que NuFi: confiabilidad + ToS/bloqueo + tercero que asume parte del cumplimiento).

**RECHAZADO (no integrar):** `GhostTrack` / `Seeker` y cualquier herramienta de **rastreo activo, geolocalización o engaño** para extraer datos privados. Sin licencia usable, opera por engaño, y en modelo multitenant no se puede verificar autorización real → riesgo de acecho. El consentimiento firmado **no** rescata este tipo de herramienta.

---

## 11. Cumplimiento (LFPDPPP)

- Aviso de privacidad, finalidad declarada, minimización, derechos ARCO.
- **Bitácora de auditoría inmutable** de consultas + **bitácora de actividad** de acciones.
- Captura de **consentimiento del sujeto** donde aplique; para dato público de terceros, base de **interés legítimo** bien fundamentada.
- Disclaimer en todo reporte: indicios para análisis humano, no decisión automatizada.
- El régimen de protección de datos en México **cambió de autoridad en 2025** → conviene revisión legal antes de producción.
- Credenciales externas solo en `.env`.

---

## 12. Notas de handoff a Antigravity

- El **Claude/Gemini de Antigravity es una sesión nueva**: no recuerda el chat original. Su contexto es **código + `PLAN.md` + este `DECISIONS.md` + artifacts** de Antigravity.
- Cambiar de modelo (Gemini ↔ Claude) **no pierde el trabajo**: el código y los artifacts viven en el workspace, no en el modelo.
- En Windows/Laragon: verificar que la terminal de Antigravity vea `php`, `composer`, `node`, `npm` (agregar Laragon al PATH).
- Flujo: abrir `Laravel/saas` como workspace → seleccionar modelo → pegar el prompt → **Planning Mode primero** → aprobar plan → construir. No aprobar el plan sin el Artifact de inventario del Paso CERO.
