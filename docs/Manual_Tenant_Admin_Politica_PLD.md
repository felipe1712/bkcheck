# Manual para Tenant Admin — Configuración de Política de Puntuación y Monitoreo PLD/EBR

Este manual describe cómo el rol `tenant_admin` configura los parámetros de puntuación, topes y reevaluación periódica dentro del **Módulo de Expedientes PLD/EBR de AvalID** (`/tenant/pld-policy`).

---

## 1. Distinción Fundamental: Norma Legal (No Editable) vs. Política de Organización (Editable)

AvalID opera como proveedor de evidencia trazable e **insumo** para la metodología de Evaluación Basada en Riesgos (EBR) de su organización. En la interfaz de configuración (`/tenant/pld-policy`) encontrará dos bloques claramente diferenciados:

### 1.1 Parámetros Fijos por Norma (No Editables)
Estos valores provienen directamente de la reforma a la **LFPIORPI (DOF 16/07/2025)**, su Reglamento y el **Acuerdo 115/2026 (RCG)**, por lo que el sistema los aplica de forma obligatoria:
1. **Clasificación mínima de riesgo:** Al menos 3 niveles (`BAJO`, `MEDIO`, `ALTO`) — *RCG Art. 23 Bis 2, fr. II*.
   - *Nota importante:* AvalID calcula el **Nivel de Riesgo de Persona (Insumo EBR)**. El **Grado de Riesgo** de la relación comercial (que integra además factores transaccionales y canales) lo determina exclusivamente el sujeto obligado.
2. **Techo máximo del intervalo de reevaluación:** Mínimo cada **6 meses** (*RCG Art. 23 Bis 1*) y revisión anual del expediente de identificación (*RCG Art. 21*).
3. **Plazo de conservación documental:** **10 años** (*LFPIORPI Art. 18 fr. IV; Reglamento Art. 20*).
4. **Orden de prelación del Beneficiario Controlador (Personas Morales):**
   - **(I)** Persona(s) física(s) con $\ge 25\%$ de composición accionaria (*RCG Art. 23 Quinquies, fr. I*) $\rightarrow$ **(II)** Control efectivo por otros medios (*fr. II*) $\rightarrow$ **(III)** Funcionario de mayor jerarquía / alta dirección (*fr. III*).

### 1.2 Parámetros de Política de AvalID / Tenant (Editables)
Estos parámetros reflejan el apetito de riesgo de su organización y **no** son valores impuestos por la ley:
1. **Pesos porcentuales por categoría (deben sumar exactamente 100%):**
   - `IDENTIDAD` (Default: `40%`)
   - `PLD` — Cumplimiento PLD, Listas y Fiscal (Default: `30%`)
   - `CONSISTENCIA` — Consistencia de Datos y Cruce Documental (Default: `15%`)
   - `REPUTACIONAL` — Señales Reputacionales, Judiciales y OSINT (Default: `15%`)
2. **Topes duros (`tope_categoria`) y penalizaciones (`descuento_puntos`) por regla de disparo:**
   - A diferencia de un promedio simple que diluiría un hallazgo crítico, cuando una regla con `tope_categoria` se activa (por ejemplo, coincidencia en listas OFAC/69-B con tope `0`, o PEP confirmado con tope `40`), la puntuación de esa categoría **no puede superar dicho tope**.
3. **Intervalos de reevaluación periódica (1 a 6 meses):**
   - **Intervalo para Nivel BAJO y MEDIO:** Default `6` meses (máximo permitido por RCG Art. 23 Bis 1).
   - **Intervalo para Nivel ALTO:** Default `3` meses (política preventiva para perfiles con PEP, EDD o hallazgos relevantes).

---

## 2. Cómo Ajustar la Política desde el Panel

1. Inicie sesión con una cuenta con rol `tenant_admin`.
2. Diríjase a `/tenant/pld-policy`.
3. Ajuste los **Pesos Porcentuales** de las 4 categorías verificando que su suma sea `100%`.
4. Ajuste los **Intervalos de Reevaluación** entre `1` y `6` meses.
5. En la tabla **Topes y Penalizaciones por Regla de Disparo**, puede activar/desactivar reglas individuales o ajustar su valor numérico (`0` a `100`).
6. Haga clic en **Guardar Política PLD/EBR del Tenant**. Los cambios se aplicarán de forma determinística a partir de la siguiente versión de expediente generada (`v{n}`). Las versiones anteriores (`v1..v{n-1}`) permanecen inmutables con su firma SHA-256 canónica (JCS RFC 8785).

---

## 3. Configuración de API Key y Webhooks

- En la misma pantalla (`/tenant/pld-policy`) puede consultar o rotar su **API Key de Tenant** (`X-Tenant-Api-Key`).
- Para registrar un endpoint de Webhook que reciba notificaciones firmadas con HMAC-SHA256 (`HALLAZGO_CRITICO`, `NIVEL_CAMBIO`, `RECOMENDACION_CAMBIO`, `FUENTE_CAIDA_PERSISTENTE`, `REEVALUACION_VENCIDA`), envíe una petición `POST /api/webhooks`:
  ```json
  {
    "url": "https://su-dominio.com/webhooks/avalid-pld",
    "secreto": "whsec_su_llave_secreta_de_al_menos_16_caracteres",
    "eventos_suscritos": ["HALLAZGO_CRITICO", "NIVEL_CAMBIO", "RECOMENDACION_CAMBIO"],
    "incluir_sin_cambios": false
  }
  ```
- Por diseño de privacidad y confidencialidad (*LFPIORPI Art. 63 fr. II*), los mensajes de webhook **nunca** incluyen datos personales en claro (nombre, RFC, CURP) ni referencias a avisos o requerimientos; contienen únicamente el `folio`, `persona_ref` opaco, `nivel_previo`, `nivel_actual` y `url_expediente`.
