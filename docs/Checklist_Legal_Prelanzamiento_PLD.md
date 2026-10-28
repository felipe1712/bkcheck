# Checklist Legal Pre-Lanzamiento — Módulo de Expedientes PLD/EBR de AvalID

Este documento compila todos los puntos legales y regulatorios marcados como **"pendientes de validar con el abogado"** en los documentos rectores (`Manual_Operativo_PLD_LFPIORPI_2026.md` y `AvalID_Spec_Modulo_Reconsulta_y_Versionado.md`). Debe revisarse y firmarse por el área jurídica antes de habilitar el módulo comercialmente para sujetos obligados.

---

## 1. Marco Regulatorio y Entrada en Vigor (Documento 1 §1 y §12)

- [ ] **1.1 Vacatio legis y plazos escalonados del Acuerdo 115/2026 (RCG):**
  - El Acuerdo 115/2026 (DOF 31/03/2026) entra en vigor el **1 de junio de 2026**, con plazos escalonados en sus artículos transitorios (cuatrimestrales por fracción del Art. 17) para el alta/actualización en el Portal PLD, diseño de metodología EBR y mecanismos automatizados.
  - **Validar con abogado:** Confirmar la fecha exacta exigible para cada giro (fracciones I a XVI del Art. 17 de la LFPIORPI) según los transitorios cuarto a décimo del Acuerdo 115/2026.
- [ ] **1.2 Guías específicas de la UIF pendientes de publicación:**
  - Verificar el estado de publicación de las guías oficiales de la UIF sobre:
    - Personas Políticamente Expuestas (PEP) y su listado oficial.
    - Programas de capacitación anual (*Ley Art. 18 fr. VIII*).
    - Auditoría interna / externa (*Ley Art. 18 fr. IX*).
    - Metodología de Evaluación Basada en Riesgos (EBR).

---

## 2. Alcance de Asimilados a PEP y Listas Oficiales (Documento 1 §5 y §12)

- [ ] **2.1 Asimilados a PEP (cónyuge, parientes por consanguinidad o afinidad, socios):**
  - El sistema declara por diseño `Asimilados a PEP: no evaluado (sin datos de vínculos familiares o societarios en las fuentes consultadas)` salvo que la fuente de listas devuelva un vínculo explícito.
  - **Validar con abogado:** Confirmar hasta qué grado de parentesco y qué tipo de vínculo societario exige la guía de la UIF vigente, y bajo qué base legal puede recabarse declaración del cliente sin vulnerar la LFPDPPP.
- [ ] **2.2 Listas oficiales reconocidas por la UIF (RCG Art. 38):**
  - **Validar con abogado:** Confirmar el catálogo exacto de listas nacionales e internacionales reconocidas oficialmente por la SHCP/UIF y verificar que el proveedor de listas (`sanciones`) las cubra todas contractualmente.

---

## 3. Selección de Personal — RCG Art. 39 Bis 2 (Documento 1 §10 y §12)

- [ ] **3.1 Corroboración de ausencia de sentencia por delitos patrimoniales e inhabilitación:**
  - Conforme a RCG Art. 39 Bis 2, el sujeto obligado debe recabar declaración firmada del candidato y corroborar información dentro de los 6 meses previos a la contratación.
  - Actualmente AvalID declara expresamente: *"No verificable con las fuentes configuradas (AvalID no afirma ausencia de sentencia firme ni de inhabilitación administrativa sin fuente oficial explícita — RCG Art. 39 Bis 2)"*.
  - **Validar con abogado:** Determinar qué medio o constancia oficial puede usarse lícitamente en México para corroborar este requisito en selección de personal, considerando las restricciones de derechos humanos y protección de datos personales sobre cartas de antecedentes no penales.

---

## 4. Privacidad, Consentimiento y Retención — LFPDPPP vs. LFPIORPI (Documento 2 §6 y §9)

- [ ] **4.1 Redacción de la Carta de Consentimiento para Reconsulta Periódica:**
  - El monitoreo periódico (`REEVALUACION_PERIODICA` cada 3 o 6 meses y `REEVALUACION_ANUAL_IDENTIDAD`) requiere que el titular haya otorgado consentimiento expreso para la finalidad (`alta_cliente` vs. `seleccion_personal`), la periodicidad de reconsulta, su duración y el mecanismo de revocación.
  - **Validar con abogado:** Aprobar el texto legal de la cláusula de consentimiento para reconsulta periódica que los tenants deben recabar de sus clientes/candidatos.
- [ ] **4.2 Plazo de retención en servidores de AvalID vs. obligación de 10 años del Sujeto Obligado:**
  - La obligación legal de conservar la documentación por **10 años** (*Ley Art. 18 fr. IV, Reglamento Art. 20*) recae sobre el **sujeto obligado** (el tenant), no sobre AvalID.
  - **Validar con abogado:** Definir en el contrato de prestación de servicios con el tenant el plazo de retención de las versiones firmadas en la base de datos de AvalID por finalidad, equilibrando el soporte probatorio para el cliente con el principio de limitación del plazo de conservación de la LFPDPPP (*Ley Art. 4 fr. V*).
- [ ] **4.3 Procedimiento de atención de Derechos ARCO (Acceso y Cancelación) bajo reserva PLD:**
  - El Art. 63 fr. II de la LFPIORPI sanciona penalmente revelar información sobre avisos o requerimientos. Aunque AvalID por arquitectura **jamás** almacena ni conoce si un sujeto obligado presentó un aviso, debe definirse el protocolo de respuesta ante solicitudes ARCO recibidas directamente por AvalID (como encargado) vs. por el tenant (como responsable).

---

## 5. Valor Probatorio y Sellado de Tiempo (Documento 2 §2.2 y §9)

- [ ] **5.1 Constancia de Conservación de Mensajes de Datos (NOM-151-SCFI-2016):**
  - El módulo implementa integridad criptográfica mediante canonicalización **RFC 8785 (JCS)** y cadena de hashes **SHA-256** (`hash_previo` $\rightarrow$ `hash_version`).
  - **Validar con abogado:** Confirmar si para efectos de auditoría de la UIF o litigio fiscal/administrativo se recomienda integrar opcionalmente el sellado de cada `hash_version` ante un Prestador de Servicios de Certificación (PSC) acreditado por la Secretaría de Economía bajo la NOM-151 vigente.

---

## 6. Revisión de Material Comercial y Contratos (Documento 1 §11)

- [ ] **6.1 Verificación de comunicación comercial:**
  - Confirmar que ningún folleto, landing page, propuesta comercial o contrato utilice las frases prohibidas:
    - ❌ *"AvalID cumple la LFPIORPI por ti"* / *"AvalID cumple el Art. 41"*
    - ❌ *"AvalID calcula el Grado de Riesgo de tu cliente"*
    - ❌ *"Certificado de persona sin antecedentes"*
  - Y que siempre se describa como:
    - ✅ *"Insumo estructurado, puntuado y trazable para la integración del expediente único y la evaluación basada en riesgos (EBR) del sujeto obligado"*.
