FROM python:3.11-slim

WORKDIR /app

RUN apt-get update && apt-get install -y --no-install-recommends \
    git tor curl ca-certificates \
    && rm -rf /var/lib/apt/lists/*

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Clonar 6abd/horus, apurvsinghgautam/robin y antoniaci/blackbird en rutas aisladas
RUN git clone --depth 1 https://github.com/6abd/horus /opt/horus || true
RUN git clone --depth 1 https://github.com/apurvsinghgautam/robin /opt/robin || true
RUN git clone --depth 1 https://github.com/antoniaci/blackbird /opt/blackbird || true
RUN if [ -f /opt/robin/requirements.txt ]; then pip install --no-cache-dir -r /opt/robin/requirements.txt || true; fi
RUN if [ -f /opt/blackbird/requirements.txt ]; then pip install --no-cache-dir -r /opt/blackbird/requirements.txt || true; fi

COPY app.py .

ENV OSINT_HOST=0.0.0.0 \
    OSINT_PORT=5001 \
    HORUS_PATH=/opt/horus \
    ROBIN_PATH=/opt/robin \
    BLACKBIRD_PATH=/opt/blackbird \
    TOR_SOCKS_PROXY=socks5h://127.0.0.1:9050

EXPOSE 5001

# Arrancar Tor en background y luego Gunicorn
CMD service tor start && gunicorn -w 2 -b 0.0.0.0:5001 --timeout 240 app:app
