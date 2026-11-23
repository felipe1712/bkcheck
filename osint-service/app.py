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
import re
import json
import hashlib
import logging
import subprocess
import threading
import glob
import tempfile
import unicodedata
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
    nombre     = (data.get("nombre")     or "").strip()
    username   = (data.get("username")   or "").strip()
    email      = (data.get("email")      or "").strip().lower()
    aliases    = data.get("aliases") or []
    birth_year = (data.get("birth_year") or "").strip()

    if not nombre and not username and not email and not aliases:
        return jsonify({"error": "Se requiere al menos 'nombre', 'username', 'email' o 'aliases'."}), 422

    # Construir lista de usernames a escanear con Sherlock completo
    usernames_to_scan = []
    if username:
        usernames_to_scan.append(username)
    for alias in aliases:
        a = str(alias).strip().lstrip("@")
        if a and a not in usernames_to_scan:
            usernames_to_scan.append(a)
    if not usernames_to_scan and nombre:
        usernames_to_scan = generate_username_variants(nombre, birth_year)[:5]

    log.info(f"OSINT search — nombre='{nombre}' usernames={usernames_to_scan} email='{email}'")

    # Ejecutar Sherlock, Blackbird y Social Analyzer en paralelo
    sherlock_results   = []
    blackbird_results  = []
    social_results     = []
    sherlock_error     = None
    blackbird_error    = None
    social_error       = None

    def run_sh():
        nonlocal sherlock_results, sherlock_error
        try:
            for u in usernames_to_scan:
                found = run_sherlock(u)
                for item in found:
                    item["username_consultado"] = u
                sherlock_results.extend(found)
        except Exception as e:
            sherlock_error = str(e)

    def run_bb():
        nonlocal blackbird_results, blackbird_error
        try:
            blackbird_results = run_blackbird(usernames_to_scan[:2], email)
        except Exception as e:
            blackbird_error = str(e)

    def run_sa():
        nonlocal social_results, social_error
        try:
            primary_user = usernames_to_scan[0] if usernames_to_scan else ""
            social_results = run_social_analyzer(nombre, primary_user)
        except Exception as e:
            social_error = str(e)

    t1 = threading.Thread(target=run_sh)
    t2 = threading.Thread(target=run_bb)
    t3 = threading.Thread(target=run_sa)
    t1.start(); t2.start(); t3.start()
    t1.join(); t2.join(); t3.join()

    # Deduplicar por URL
    urls_vistas = set()
    plataformas  = []
    for item in sherlock_results + blackbird_results:
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
    if blackbird_error:
        respuesta["blackbird_error"] = blackbird_error
    if social_error:
        respuesta["social_analyzer_error"] = social_error

    respuesta["fuentes_ejecutadas"] = ["sherlock", "blackbird", "social_analyzer"]
    log.info(f"OSINT completado — {total} coincidencias — nivel: {respuesta['nivel_exposicion']}")
    respuesta["usernames_escaneados"] = usernames_to_scan
    return jsonify(respuesta)


def run_blackbird(usernames: list[str], email: str = "") -> list[dict]:
    """
    Ejecuta Blackbird (antoniaci/blackbird) para búsqueda por username y correo electrónico.
    """
    blackbird_path = os.environ.get("BLACKBIRD_PATH", "/opt/blackbird")
    script = os.path.join(blackbird_path, "blackbird.py")
    if not os.path.isfile(script):
        return []

    encontradas = []
    targets = []
    for u in usernames:
        if u and len(u.strip()) >= 2:
            targets.append(("--username", u.strip()))
    if email and "@" in email:
        targets.append(("--email", email.strip()))

    for flag, val in targets:
        try:
            with tempfile.TemporaryDirectory() as tmpdir:
                cmd = ["python", script, flag, val, "--json"]
                subprocess.run(
                    cmd,
                    cwd=tmpdir,
                    capture_output=True,
                    text=True,
                    timeout=90,
                    encoding="utf-8",
                    errors="replace",
                )
                for json_file in glob.glob(os.path.join(tmpdir, "**", "*.json"), recursive=True):
                    try:
                        with open(json_file, "r", encoding="utf-8", errors="replace") as fh:
                            payload = json.load(fh)
                        items = payload if isinstance(payload, list) else payload.get("results", payload.get("sites", []))
                        for it in (items or []):
                            if not isinstance(it, dict):
                                continue
                            status = str(it.get("status", "")).upper()
                            url = it.get("url") or it.get("uri_check") or ""
                            site = it.get("name") or it.get("app") or it.get("site") or "Desconocida"
                            if (status in ("FOUND", "VALID", "TRUE", "200") or it.get("found")) and url and site not in EXCLUDED_PLATFORMS:
                                encontradas.append({
                                    "plataforma": site,
                                    "url": url,
                                    "fuente": "blackbird",
                                    "confianza": "alta",
                                    "vector": "email" if flag == "--email" else "username",
                                })
                    except Exception:
                        continue
        except Exception as e:
            log.warning(f"Blackbird error ({flag}={val}): {e}")

    return encontradas


def _slug_ascii(text: str) -> str:
    nfkd = unicodedata.normalize("NFKD", text)
    only_ascii = "".join(c for c in nfkd if not unicodedata.combining(c))
    return re.sub(r"[^a-z0-9]", "", only_ascii.lower())


def generate_username_variants(nombre: str, birth_year: str = "") -> list[str]:
    """Genera variantes probables de username a partir del nombre y año de nacimiento."""
    parts = [_slug_ascii(p) for p in nombre.split() if len(_slug_ascii(p)) >= 2]
    if not parts:
        return []
    first = parts[0]
    last = parts[1] if len(parts) > 1 else ""
    variants = []
    if last:
        variants.extend([
            f"{first}{last}",
            f"{first}.{last}",
            f"{first}_{last}",
            f"{first[0]}{last}",
            f"{last}.{first}",
        ])
        if birth_year and len(birth_year) == 4:
            variants.append(f"{first}{last}{birth_year[2:]}")
            variants.append(f"{first[0]}{last}{birth_year[2:]}")
    else:
        variants.append(first)
    # Deduplicar manteniendo orden
    seen = set()
    return [v for v in variants if v and not (v in seen or seen.add(v))]


# ─── Horus (6abd/horus) ───────────────────────────────────────────────────────
@app.route("/osint/horus", methods=["POST"])
def osint_horus():
    """
    Ejecuta enriquecimiento OSINT de teléfono, correo electrónico y dominio
    integrando capacidades de 6abd/horus (GPL-3.0, aislado en este servicio).
    """
    verificar_secret()
    data = request.get_json(silent=True) or {}
    phone = (data.get("phone") or "").strip()
    email = (data.get("email") or "").strip().lower()
    nombre = (data.get("nombre") or "").strip()

    if not phone and not email:
        return jsonify({"error": "Se requiere 'phone' o 'email' para Horus."}), 422

    resultado = {
        "fuente": "horus",
        "phone_intel": None,
        "email_intel": None,
        "horus_cli_output": None,
    }

    # 1. Inteligencia telefónica (phonenumbers + Horus numlook si está disponible)
    if phone:
        phone_info = {"raw": phone, "valido": False}
        try:
            import phonenumbers
            from phonenumbers import geocoder, carrier, timezone as pn_tz
            parsed = phonenumbers.parse(phone, "MX")
            phone_info["valido"] = phonenumbers.is_valid_number(parsed)
            phone_info["e164"] = phonenumbers.format_number(parsed, phonenumbers.PhoneNumberFormat.E164)
            phone_info["region"] = geocoder.description_for_number(parsed, "es") or None
            phone_info["carrier"] = carrier.name_for_number(parsed, "es") or None
            phone_info["timezones"] = list(pn_tz.time_zones_for_number(parsed))
        except Exception as e:
            phone_info["error"] = str(e)
        resultado["phone_intel"] = phone_info

    # 2. Inteligencia de correo y dominio (MX, Gravatar, dominio)
    if email and "@" in email:
        domain = email.split("@", 1)[1]
        email_info = {
            "email": email,
            "dominio": domain,
            "mx_valido": False,
            "mx_records": [],
            "gravatar_url": None,
            "es_corporativo": domain not in {
                "gmail.com", "hotmail.com", "outlook.com", "yahoo.com", "icloud.com", "live.com"
            },
        }
        try:
            import dns.resolver
            answers = dns.resolver.resolve(domain, "MX", lifetime=5)
            email_info["mx_records"] = [str(r.exchange).rstrip(".") for r in answers]
            email_info["mx_valido"] = len(email_info["mx_records"]) > 0
        except Exception:
            pass

        try:
            import requests as req
            email_hash = hashlib.md5(email.encode("utf-8")).hexdigest()
            grav_url = f"https://www.gravatar.com/avatar/{email_hash}?d=404"
            resp = req.head(grav_url, timeout=4)
            if resp.status_code == 200:
                email_info["gravatar_url"] = f"https://www.gravatar.com/avatar/{email_hash}"
        except Exception:
            pass

        resultado["email_intel"] = email_info

    # 3. Invocar CLI de 6abd/horus si está instalado en HORUS_PATH
    horus_path = os.environ.get("HORUS_PATH", "/opt/horus")
    if os.path.isdir(horus_path) and os.path.isfile(os.path.join(horus_path, "horus.py")):
        try:
            cmd = ["python", os.path.join(horus_path, "horus.py"), "--non-interactive"]
            if phone:
                cmd += ["--phone", phone]
            if email:
                cmd += ["--email", email]
            proc = subprocess.run(cmd, capture_output=True, text=True, timeout=45, encoding="utf-8", errors="replace")
            if proc.stdout:
                resultado["horus_cli_output"] = proc.stdout[:4000]
        except Exception as e:
            log.warning(f"Horus CLI fallback: {e}")

    return jsonify(resultado)


# ─── Robin (apurvsinghgautam/robin — Dark Web OSINT con Anthropic + Tor) ──────
@app.route("/osint/robin", methods=["POST"])
def osint_robin():
    """
    Ejecuta búsqueda Dark Web OSINT con apurvsinghgautam/robin usando Anthropic
    como proveedor LLM y Tor como proxy SOCKS5.
    """
    verificar_secret()
    data = request.get_json(silent=True) or {}
    query = (data.get("query") or "").strip()
    model = (data.get("model") or os.environ.get("ROBIN_ANTHROPIC_MODEL", "claude-sonnet-4-20250514")).strip()

    if not query:
        return jsonify({"error": "Se requiere 'query' para Robin."}), 422

    robin_path = os.environ.get("ROBIN_PATH", "/opt/robin")
    anthropic_key = os.environ.get("ANTHROPIC_API_KEY", "")
    tor_proxy = os.environ.get("TOR_SOCKS_PROXY", "socks5h://127.0.0.1:9050")

    if not anthropic_key:
        return jsonify({
            "fuente": "robin",
            "status": "unavailable",
            "mensaje": "ANTHROPIC_API_KEY no configurada en osint-service.",
            "hallazgos": [],
        }), 200

    if not os.path.isdir(robin_path):
        return jsonify({
            "fuente": "robin",
            "status": "unavailable",
            "mensaje": f"Repositorio apurvsinghgautam/robin no encontrado en {robin_path}.",
            "hallazgos": [],
        }), 200

    try:
        env = os.environ.copy()
        env["ANTHROPIC_API_KEY"] = anthropic_key
        env["TOR_PROXY"] = tor_proxy

        cmd = [
            "python", os.path.join(robin_path, "main.py"),
            "cli",
            "-m", model,
            "-q", query,
            "-t", "4",
        ]
        proc = subprocess.run(cmd, env=env, capture_output=True, text=True, timeout=180, encoding="utf-8", errors="replace")

        return jsonify({
            "fuente": "robin",
            "status": "completed" if proc.returncode == 0 else "partial",
            "modelo_llm": model,
            "query": query,
            "resumen": (proc.stdout or "")[:6000],
            "hallazgos": [],
        })
    except subprocess.TimeoutExpired:
        return jsonify({"fuente": "robin", "status": "timeout", "mensaje": "Robin excedió el tiempo límite.", "hallazgos": []}), 200
    except Exception as e:
        log.error(f"Error ejecutando Robin: {e}")
        return jsonify({"fuente": "robin", "status": "error", "mensaje": str(e), "hallazgos": []}), 200


# ─── Main ─────────────────────────────────────────────────────────────────────
if __name__ == "__main__":
    host = os.environ.get("OSINT_HOST", "127.0.0.1")
    port = int(os.environ.get("OSINT_PORT", 5001))
    debug = os.environ.get("OSINT_DEBUG", "false").lower() == "true"
    log.info(f"BkCheck OSINT Service arrancando en http://{host}:{port}")
    app.run(host=host, port=port, debug=debug)
