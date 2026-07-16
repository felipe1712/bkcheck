# BkCheck — OSINT Microservice

Servicio Python liviano que envuelve **Sherlock** y **Social Analyzer** y los expone como una API REST interna. El conector Laravel (`PresenciaEnLineaConnector`) llama a este servicio; ninguna de las dos herramientas OSINT se importa directamente en el código PHP.

## Por qué microservicio separado

| Herramienta | Licencia | Razón del aislamiento |
|---|---|---|
| Sherlock | MIT ✅ | Podría integrarse directo; se mantiene aquí por consistencia |
| Social Analyzer | **AGPL v3** ⚠️ | La AGPL obliga a publicar el código fuente de toda app que lo use en red. Corre como proceso externo para mantener el código de la plataforma privado. |

## Instalación

```bash
cd osint-service
pip install -r requirements.txt
```

## Arranque

### Desarrollo (Windows / Laragon)
```bash
python app.py
```
El servicio escucha en `http://127.0.0.1:5001`.

### Producción (Ubuntu)
```bash
pip install gunicorn
gunicorn -w 2 -b 127.0.0.1:5001 app:app
```
Configura Supervisor para mantenerlo activo:
```ini
[program:bkcheck-osint]
command=gunicorn -w 2 -b 127.0.0.1:5001 app:app
directory=/var/www/bkcheck/osint-service
autostart=true
autorestart=true
```

## Variables de Entorno

| Variable | Default | Descripción |
|---|---|---|
| `OSINT_HOST` | `127.0.0.1` | Host de escucha |
| `OSINT_PORT` | `5001` | Puerto |
| `OSINT_SECRET` | `bkcheck-osint-dev-secret` | Token secreto (cambia en producción) |
| `SHERLOCK_TIMEOUT` | `10` | Timeout por plataforma (segundos) |
| `OSINT_DEBUG` | `false` | Modo debug Flask |

## Configuración en Laravel (.env)

```env
OSINT_SERVICE_URL=http://127.0.0.1:5001
OSINT_SERVICE_ENABLED=false
OSINT_SERVICE_SECRET=bkcheck-osint-dev-secret
```

Cuando `OSINT_SERVICE_ENABLED=false` (por defecto), el conector usa **mock data** y no requiere que el servicio esté corriendo.

## Endpoints

### `GET /health`
Health check — verifica que el servicio está activo.

### `POST /osint/search`
Ejecuta la búsqueda OSINT.

**Headers:**
```
Content-Type: application/json
X-OSINT-Secret: {tu-secret}
```

**Body:**
```json
{
  "nombre":   "Juan Pérez García",
  "username": "jperez"
}
```

**Response:**
```json
{
  "username_buscado": "jperez",
  "nombre_buscado": "Juan Pérez García",
  "plataformas_encontradas": [
    { "plataforma": "Twitter", "url": "https://twitter.com/jperez", "fuente": "sherlock", "confianza": "alta" }
  ],
  "perfiles_correlacionados": [
    { "plataforma": "LinkedIn", "url": "https://linkedin.com/in/jperez", "fuente": "social_analyzer", "confianza": "media", "nombre_detectado": "Juan Pérez" }
  ],
  "total_coincidencias": 2,
  "nivel_exposicion": "bajo",
  "disclaimer": "Esta información proviene de fuentes públicas..."
}
```

## Gobernanza y Privacidad

Cumplimiento con `DECISIONS.md §10`:
- ✅ Solo handles/URLs públicos — sin volcados de perfil completo
- ✅ Plataformas sensibles excluidas (salud, religión, orientación) — ver `EXCLUDED_PLATFORMS` en `app.py`
- ✅ Disclaimer obligatorio en toda respuesta
- ✅ Consultas dirigidas por investigación (nunca barridos masivos)
- ✅ Base legal: interés legítimo (no consentimiento del investigado)
- ✅ Toda ejecución queda en la bitácora de actividad de Laravel
