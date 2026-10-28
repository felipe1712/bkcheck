# Guía Técnica — Cómo Agregar una Nueva Fuente al Catálogo Global y una Regla de Disparo al Motor PLD

Esta guía documenta el flujo técnico para integrar un nuevo conector en el **Módulo de Expedientes PLD/EBR de AvalID** manteniendo la regla de determinismo (100% código PHP reproducible, cero llamadas a LLM y validación pre-entrega de 14 puntos).

---

## 1. Arquitectura del Motor PLD/EBR

El flujo completo de evaluación y versionado está compuesto por:
1. **Catálogo Global (`pld_fuente_catalogo` / `App\Models\PldFuenteCatalogo`):** Define la `categoria` (`IDENTIDAD`, `PLD`, `CONSISTENCIA`, `REPUTACIONAL`) y `criticidad` (`ALTA`, `MEDIA`, `BAJA`) de cada conector (`fuente_key`).
2. **Política por Tenant (`pld_scoring_policies` / `App\Models\PldScoringPolicy`):** Almacena pesos y `triggers_config` por categoría para cada `tenant_id`.
3. **Motor Determinístico (`App\Services\Pld\PLDScoringService`):** Evalúa las entradas crudas contra el catálogo global y las reglas de disparo, calcula el cruce de consistencia, construye el bloque EBR y ejecuta el gate bloqueante de 14 puntos (`verifyPreDeliveryChecklist`).
4. **Formateador (`App\Services\Pld\PLDExpedienteFormatterService`) y Plantilla PDF (`resources/views/tenant/reports/pld_expediente_pdf.blade.php`):** Renderizan las 12 secciones exactas del expediente.
5. **Versionado y Cadena de Hashes (`App\Services\Pld\PLDVersioningService` + `JcsCanonicalizer`):** Serializa bajo RFC 8785 (JCS), encadena `hash_previo` $\rightarrow$ `hash_version`, arrastra fuentes caídas en reconsulta (`NO_RECONSULTADA`) y calcula el diff estructurado.

---

## 2. Paso a Paso para Agregar una Fuente Nueva

### Paso 1: Registrar la fuente en `config/pld.php` y en el Catálogo Global
Edite `config/pld.php` dentro del arreglo `'catalogo_default'`:
```php
[
    'fuente_key' => 'mi_nueva_fuente',
    'nombre'     => 'Nombre Oficial de la Fuente',
    'categoria'  => 'PLD',       // IDENTIDAD | PLD | CONSISTENCIA | REPUTACIONAL
    'criticidad' => 'ALTA',      // ALTA | MEDIA | BAJA
    'activo'     => true,
],
```
> **Importante (Regla §3):** Si una fuente enviada en el input no existe o no está activa en `pld_fuente_catalogo`, `PLDScoringService` la excluye automáticamente del cálculo y la registra en `fuentes_no_catalogadas` con la leyenda `"fuente no catalogada, requiere clasificación manual"`.

### Paso 2: Declarar el Trigger en `config/pld.php` (`policy_defaults`)
Si la nueva fuente introduce una regla de tope o descuento configurable por tenant, agréguela en `config/pld.php` bajo la categoría correspondiente:
```php
'mi_nuevo_trigger' => [
    'etiqueta' => 'Descripción clara del disparador',
    'tipo'     => 'tope_categoria', // 'tope_categoria' o 'descuento_puntos'
    'valor'    => 35,
    'activo'   => true,
],
```

### Paso 3: Implementar la evaluación determinística en `PLDScoringService.php`
En `App\Services\Pld\PLDScoringService::evaluate()`:
1. Verifique que la fuente tenga datos evaluables:
   ```php
   if (!empty($fuentesCatalogadas['mi_nueva_fuente']['tiene_datos_evaluables'])) {
       $d = $fuentesCatalogadas['mi_nueva_fuente']['resultado_crudo'];
       // Evaluar campos exactos del JSON de entrada
   }
   ```
2. Cuando el disparador se active:
   - Obtenga el valor configurado para el tenant con `$this->getTriggerValue($policies, 'PLD', 'mi_nuevo_trigger', 35)` y verifique `$this->isTriggerActive($policies, 'PLD', 'mi_nuevo_trigger')`.
   - Aplique el tope (`$scores['PLD'] = min($scores['PLD'], $tope)`) o descuento (`$scores['PLD'] = max(0, $scores['PLD'] - $descuento)`).
   - Agregue el registro a `$hallazgos[]` indicando **obligatoriamente** `'fuente' => 'mi_nueva_fuente'` y `'campo' => 'nombre_exacto_del_campo'` (requerido por el Punto #1 del Checklist Pre-Entrega).
3. Agregue el resumen de salida en `PLDScoringService::summarizeSourceResult()`.

### Paso 4: Respetar las Reglas Madre de Fraseología (Gate Bloqueante)
Todo texto que agregue en `descripcion` o `summarizeSourceResult()` será validado automáticamente por `PLDScoringService::assertNoForbiddenPhrases()`:
- **PROHIBIDO** escribir `"sin antecedentes"`, `"está limpio"`, `"expediente limpio"`, `"sin sentencia"`.
- **OBLIGATORIO** escribir `"Sin coincidencia en [nombre de la fuente]"` o `"No verificable con las fuentes configuradas"`.
- **PROHIBIDO** mencionar `"aviso"`, `"aviso de 24 horas"` o `"requerimiento"` (*LFPIORPI Art. 63 fr. II*).

### Paso 5: Agregar Prueba Automatizada
Agregue un caso de prueba en `tests/Feature/PldModuleAcceptanceTest.php` y ejecute:
```bash
php artisan test --filter=PldModuleAcceptanceTest
```
