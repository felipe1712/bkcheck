"""
BkCheck — Microservicio OSINT
=============================
Wrapper Flask que ejecuta Sherlock (MIT) y Social Analyzer (AGPL v3)
como proceso aislado. Laravel llama a este servicio por HTTP interno;
nunca se importa directamente en el código PHP.

Arquitectura de aislamiento:
  - Sherlock: MIT, puede integrarse directamente (aquí llamado por CLI/API)
  - Social Analyzer: AGPL v3, DEBE correr como proceso separado.
    Este archivo nunca forma parte del código fuente de la plataforma Laravel.

Arranque:
  python app.py              # desarrollo (puerto 5001)
  gunicorn -w 2 app:app      # producción

Variables de entorno:
  OSINT_PORT      Puerto de escucha (default: 5001)
  OSINT_HOST      Host de escucha (default: 127.0.0.1)
  OSINT_SECRET    Token secreto para validar peticiones desde Laravel
  SHERLOCK_TIMEOUT  Timeout por plataforma en segundos (default: 10)
"""

import os
import json
import logging
import subprocess
import threading
from flask import Flask, request, jsonify, abort

# ─── Configuración de logging ────────────────────────────────────────────────
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s"
)
log = logging.getLogger(__name__)

app = Flask(__name__)

# ─── Constantes ──────────────────────────────────────────────────────────────
OSINT_SECRET     = os.environ.get("OSINT_SECRET", "bkcheck-osint-dev-secret")
SHERLOCK_TIMEOUT = int(os.environ.get("SHERLOCK_TIMEOUT", 10))

# Plataformas excluidas por política de privacidad (DECISIONS.md §10):
# Sitios de contenido sensible, político, de salud u orientación sexual.
EXCLUDED_PLATFORMS = {
    "Pornhub", "RedTube", "XVideos", "OnlyFans",
    "Grindr", "HER", "scruff", "recon",
    "Stormfront", "4chan",
}

# Nivel de exposición según número de coincidencias
def calcular_nivel_exposicion(total: int) -> str:
    if total == 0:
        return "ninguno"
    elif total <= 5:
        return "bajo"
    elif total <= 15:
        return "medio"
    else:
        return "alto"


# ─── Middleware de autenticación ──────────────────────────────────────────────
def verificar_secret():
    """Valida el token secreto en el header X-OSINT-Secret."""
    secret = request.headers.get("X-OSINT-Secret", "")
    if secret != OSINT_SECRET:
        log.warning(f"Petición rechazada: secret inválido desde {request.remote_addr}")
        abort(401, description="Token de acceso inválido.")


# ─── Sherlock ─────────────────────────────────────────────────────────────────
def run_sherlock(username: str, timeout: int = SHERLOCK_TIMEOUT) -> list[dict]:
    """
    Ejecuta Sherlock en un subproceso y retorna la lista de plataformas
    donde se encontró el username.
    Sherlock es MIT — puede llamarse directamente.
    """
    if not username or len(username.strip()) < 2:
        return []

    try:
        result = subprocess.run(
            [
                "python", "-m", "sherlock",
                username.strip(),
                "--print-found",
                "--timeout", str(timeout),
                "--output", "/dev/null",   # no guardar archivos
            ],
            capture_output=True,
            text=True,
            timeout=timeout * 50,   # timeout global conservador
            encoding="utf-8",
            errors="replace",
        )
        encontradas = []
        for line in result.stdout.splitlines():
            line = line.strip()
            # Sherlock imprime líneas tipo: "[+] Twitter: https://twitter.com/user"
            if line.startswith("[+]") and "http" in line:
                partes = line[3:].strip().split(":", 1)
                if len(partes) == 2:
                    plataforma = partes[0].strip()
                    url = partes[1].strip().lstrip("/")
                    # Reconstruir URL completa
                    url_full = ("https:" + partes[1].strip()
                                if partes[1].strip().startswith("//")
                                else partes[1].strip())
                    if plataforma not in EXCLUDED_PLATFORMS:
                        encontradas.append({
                            "plataforma": plataforma,
                            "url": url_full,
                            "fuente": "sherlock",
                            "confianza": "alta",
                        })
        return encontradas

    except subprocess.TimeoutExpired:
        log.warning(f"Sherlock timeout para username: {username}")
        return []
    except Exception as e:
        log.error(f"Error ejecutando Sherlock: {e}")
        return []


# ─── Social Analyzer ─────────────────────────────────────────────────────────
def run_social_analyzer(nombre: str, username: str = "") -> list[dict]:
    """
    Llama a social-analyzer CLI para buscar perfiles por nombre.
    Social Analyzer es AGPL v3 — se ejecuta en subproceso separado,
    nunca importado directamente en el código fuente de la plataforma.
    """
    if not nombre or len(nombre.strip()) < 3:
        return []

    query = nombre.strip()

    try:
        cmd = [
            "python", "-m", "social_analyzer",
            "--cli",
            "--metadata",
            "--top", "20",
            "--output", "json",
        ]
        # Buscar por nombre o username según disponibilidad
        if username:
            cmd += ["--username", username.strip()]
        else:
            cmd += ["--username", query.replace(" ", "").lower()]

        result = subprocess.run(
            cmd,
            capture_output=True,
            text=True,
            timeout=90,
            encoding="utf-8",
            errors="replace",
        )

        perfiles = []
        try:
            data = json.loads(result.stdout)
            found = data.get("found", []) or data.get("profiles", [])
            for perfil in found:
                plataforma = perfil.get("website", perfil.get("name", "Desconocida"))
                url = perfil.get("url", perfil.get("link", ""))
                if url and plataforma not in EXCLUDED_PLATFORMS:
                    perfiles.append({
                        "plataforma": plataforma,
                        "url": url,
                        "fuente": "social_analyzer",
                        "confianza": "media",
                        "nombre_detectado": perfil.get("found_name", nombre),
                    })
        except (json.JSONDecodeError, AttributeError):
            # Si el output no es JSON parseable, intentar parsear líneas
            for line in result.stdout.splitlines():
                if "http" in line and "://" in line:
                    partes = line.strip().split(" ")
                    for p in partes:
                        if p.startswith("http"):
                            perfiles.append({
                                "plataforma": "Desconocida",
                                "url": p,
                                "fuente": "social_analyzer",
                                "confianza": "baja",
                                "nombre_detectado": nombre,
                            })

        return perfiles

    except subprocess.TimeoutExpired:
        log.warning(f"Social Analyzer timeout para: {nombre}")
        return []
    except Exception as e:
        log.error(f"Error ejecutando Social Analyzer: {e}")
        return []


# ─── Endpoints ────────────────────────────────────────────────────────────────

@app.route("/health", methods=["GET"])
def health():
    """Health check para que Laravel verifique si el servicio está activo."""
    return jsonify({"status": "ok", "service": "bkcheck-osint", "version": "1.0.0"})


@app.route("/osint/search", methods=["POST"])
def osint_search():
    """
    Ejecuta la búsqueda OSINT combinada (Sherlock + Social Analyzer).

    Body JSON:
      {
        "nombre":   "Juan Pérez García",   # obligatorio
        "username": "jperez",              # opcional — mejora resultados de Sherlock
      }

    Response:
      {
        "username_buscado":     "jperez",
        "nombre_buscado":       "Juan Pérez García",
        "plataformas_encontradas": [...],
        "perfiles_correlacionados": [...],
        "total_coincidencias":  12,
        "nivel_exposicion":     "medio",
        "disclaimer":           "...",
      }
    """
    verificar_secret()

    data = request.get_json(silent=True) or {}
    nombre   = (data.get("nombre")   or "").strip()
    username = (data.get("username") or "").strip()

    if not nombre and not username:
        return jsonify({"error": "Se requiere al menos 'nombre' o 'username'."}), 422

    log.info(f"OSINT search — nombre='{nombre}' username='{username}'")

    # Ejecutar ambas herramientas en paralelo para reducir latencia
    sherlock_results   = []
    social_results     = []
    sherlock_error     = None
    social_error       = None

    def run_sh():
        nonlocal sherlock_results, sherlock_error
        try:
            sherlock_results = run_sherlock(username) if username else []
        except Exception as e:
            sherlock_error = str(e)

    def run_sa():
        nonlocal social_results, social_error
        try:
            social_results = run_social_analyzer(nombre, username)
        except Exception as e:
            social_error = str(e)

    t1 = threading.Thread(target=run_sh)
    t2 = threading.Thread(target=run_sa)
    t1.start(); t2.start()
    t1.join(); t2.join()

    # Deduplicar por URL
    urls_vistas = set()
    plataformas  = []
    for item in sherlock_results:
        if item["url"] not in urls_vistas:
            urls_vistas.add(item["url"])
            plataformas.append(item)

    perfiles = []
    for item in social_results:
        if item["url"] not in urls_vistas:
            urls_vistas.add(item["url"])
            perfiles.append(item)

    total = len(plataformas) + len(perfiles)

    respuesta = {
        "username_buscado":        username or None,
        "nombre_buscado":          nombre,
        "plataformas_encontradas": plataformas,
        "perfiles_correlacionados": perfiles,
        "total_coincidencias":     total,
        "nivel_exposicion":        calcular_nivel_exposicion(total),
        "disclaimer": (
            "Esta información proviene de fuentes públicas y es de carácter "
            "indicativo. Requiere revisión humana antes de utilizarse en "
            "cualquier decisión. No se incluyen atributos sensibles."
        ),
    }

    if sherlock_error:
        respuesta["sherlock_error"] = sherlock_error
    if social_error:
        respuesta["social_analyzer_error"] = social_error

    log.info(f"OSINT completado — {total} coincidencias — nivel: {respuesta['nivel_exposicion']}")
    return jsonify(respuesta)


# ─── Main ─────────────────────────────────────────────────────────────────────
if __name__ == "__main__":
    host = os.environ.get("OSINT_HOST", "127.0.0.1")
    port = int(os.environ.get("OSINT_PORT", 5001))
    debug = os.environ.get("OSINT_DEBUG", "false").lower() == "true"
    log.info(f"BkCheck OSINT Service arrancando en http://{host}:{port}")
    app.run(host=host, port=port, debug=debug)
