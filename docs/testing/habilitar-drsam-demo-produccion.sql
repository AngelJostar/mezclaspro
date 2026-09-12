-- Ejecutar en la base de datos de MezclasPro/CBTA.
-- Objetivo: asociar DRSAM-DEMO a la central que ya posee inventario.
-- La consulta es idempotente: puede ejecutarse de nuevo sin duplicar existencias.

START TRANSACTION;

-- Estado previo. Debe devolver una sola fila.
SELECT
    id,
    external_code,
    name,
    laboratory_id,
    nutri_medicine_list_id,
    onco_medicine_list_id,
    is_active,
    access_is_active
FROM hospitals
WHERE external_code = 'DRSAM-DEMO';

-- El respaldo muestra que el inventario vigente está en laboratory_id = 1
-- y que las listas de prueba son las número 1.
UPDATE hospitals
SET
    laboratory_id = 1,
    nutri_medicine_list_id = 1,
    onco_medicine_list_id = 1,
    is_active = 1,
    access_is_active = 1,
    updated_at = CURRENT_TIMESTAMP
WHERE external_code = 'DRSAM-DEMO';

-- Debe devolver 1. Si devuelve 0, ejecutar ROLLBACK en vez de COMMIT.
SELECT ROW_COUNT() AS filas_actualizadas;

COMMIT;

-- Verificación del hospital ya enlazado.
SELECT
    external_code,
    laboratory_id,
    nutri_medicine_list_id,
    onco_medicine_list_id
FROM hospitals
WHERE external_code = 'DRSAM-DEMO';

-- Verificación del producto usado en la prueba (PRIMENE).
SELECT
    p.external_code AS presentation_code,
    SUM(s.stock_ml_actual) AS disponible_ml,
    SUM(s.frascos_actuales) AS frascos_disponibles
FROM hospitals h
JOIN medicine_laboratory_stocks s
    ON s.laboratory_id = h.laboratory_id
JOIN nutrition_medicine_presentations p
    ON p.id = s.nutrition_medicine_presentation_id
WHERE h.external_code = 'DRSAM-DEMO'
  AND p.external_code = 'NPT-PRES-000002'
  AND s.is_active = 1
  AND s.caducidad >= CURRENT_DATE
GROUP BY p.external_code;

-- Verificación de bolsas EVA de la lista NPT asignada.
SELECT
    p.external_code AS presentation_code,
    c.denominacion_generica,
    SUM(s.frascos_actuales) AS unidades_disponibles
FROM hospitals h
JOIN nutri_medicine_list_items li
    ON li.nutri_medicine_list_id = h.nutri_medicine_list_id
JOIN nutrition_medicine_presentations p
    ON p.id = li.nutrition_medicine_presentation_id
JOIN nutrition_medicines_catalog c
    ON c.id = p.nutrition_medicine_catalog_id
JOIN medicine_laboratory_stocks s
    ON s.nutrition_medicine_presentation_id = p.id
   AND s.laboratory_id = h.laboratory_id
WHERE h.external_code = 'DRSAM-DEMO'
  AND c.category_id = 6
  AND s.is_active = 1
  AND s.caducidad >= CURRENT_DATE
GROUP BY p.external_code, c.denominacion_generica
ORDER BY p.external_code;

-- Reversión manual, únicamente si se necesita deshacer este enlace:
-- UPDATE hospitals
-- SET laboratory_id = NULL, updated_at = CURRENT_TIMESTAMP
-- WHERE external_code = 'DRSAM-DEMO' AND laboratory_id = 1;
