# Flujo de pruebas: merge ggh3 e inventario por mL

## 1. Preparacion

```powershell
php --version
php -m | Select-String -Pattern "pdo_sqlite|sqlite3"
php artisan optimize:clear
php artisan migrate:status
```

Las pruebas automatizadas usan SQLite en memoria. Deben aparecer `pdo_sqlite` y
`sqlite3`; la aplicacion local y produccion pueden continuar usando MySQL.

## 2. Validacion estatica

```powershell
php artisan route:list --except-vendor
php artisan view:clear
php artisan view:cache
npm.cmd run build
```

## 3. Pruebas unitarias rapidas

```powershell
php artisan test --testsuite=Unit
```

Este bloque cubre calculos, estados, coordenadas UTM, formato de catalogos,
facturacion y cobro oncologico por mg, mL o frasco.

## 4. Inventario por mL y remanentes

```powershell
php artisan test tests/Feature/MedicineRemainderServiceTest.php
php artisan test tests/Unit/InstitutionBillingPricingServiceTest.php
```

Casos cubiertos: apertura de frasco, remanente trazable, prioridad por
caducidad de estabilidad, descarte al vencer, devolucion al revertir y cobro
por volumen consumido.

## 5. Modulos incorporados desde ggh3

```powershell
php artisan test tests/Feature/DistributionCourierCatalogTest.php
php artisan test tests/Feature/PriceListWarehouseConfigurationTest.php
php artisan test tests/Feature/HospitalUtmCoordinatesTest.php
php artisan test tests/Feature/SuperAdministratorManagementTest.php
php artisan test tests/Unit/SolicitudOperativeStatusServiceTest.php
php artisan test tests/Unit/OncologyMixtureDeliveryScheduleServiceTest.php
```

## 6. Regresion completa

```powershell
php artisan test
```

El flujo se considera aprobado cuando no hay errores, fallos ni pruebas
interrumpidas por extensiones ausentes. Los `skipped` documentados pueden
revisarse por separado, pero no deben ocultar fallos.

## 7. Revision manual minima

1. Crear o editar una mezcla oncologica con cobro por mL.
2. Usar parcialmente un frasco y confirmar que aparece un remanente vigente.
3. Crear otra mezcla del mismo medicamento y confirmar que sugiere primero el remanente.
4. Verificar el movimiento de salida, el consumo de remanente y el saldo en mL.
5. Confirmar que un remanente vencido no puede seleccionarse.
6. Repetir el flujo nutricional con una presentacion que tenga estabilidad configurada.
7. Validar rutas de distribucion, mensajeros, almacenes y listas de precios desde un usuario autorizado.
