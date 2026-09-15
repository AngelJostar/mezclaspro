# Centro de agentes

## Instalacion

- Ejecutar la migracion `2026_09_14_000005_add_agent_execution.php`.
- Ejecutar `php artisan db:seed --class=PromesaAiAgentsSeeder` para completar los perfiles existentes sin reemplazar configuraciones personalizadas ni activar agentes.
- Compilar con `npm run build`.
- Los agentes requieren sus tablas de dominio. Abasto usa las migraciones `2026_09_03_000001` y `2026_09_04_000002`; las confirmaciones de entrega usan `2026_09_02_000001`.

## Ejecucion

Cada agente requiere objetivo, reglas, fuentes autorizadas, alcance y responsable. Solo Super Admin puede configurar, ejecutar o revisar hallazgos. Un perfil sin alcance no ejecuta consultas. La activacion ON no sustituye la configuracion.

El motor calcula hallazgos reales en modo de solo lectura. Solo escribe en las tablas de agentes, ejecuciones, hallazgos y seguimiento. No modifica registros operativos. Los permisos son una lista cerrada de herramientas; las instrucciones nunca conceden permisos. La configuracion y el texto del perfil quedan registrados en cada ejecucion.

La fecha de corte, registros revisados, limites y errores aparecen en Seguimiento. Las reglas tienen cobertura concreta, mostrada en la pantalla: no equivalen a una auditoria clinica, a un calculo completo de rentabilidad ni a una conciliacion bancaria. Las fuentes no disponibles producen resultados parciales o errores, nunca registros inventados.

El alcance se aplica en las consultas, intersectando instituciones, centrales y almacenes. Una fuente sin la dimension solicitada queda excluida y se informa. El resumen de direccion requiere alcance global porque consolida hallazgos de varias fuentes. Limites: 3000 registros recientes por consulta y 500 hallazgos guardados por ejecucion; los recortes quedan marcados como parciales.

Los hallazgos repetidos actualizan su evidencia sin duplicarse ni reabrir una resolucion humana. Todo cambio de estado exige justificacion y registra usuario, fecha y estado resultante en el historial de eventos (el primer estado es Nueva). Los registros operativos de origen no cambian.

## OpenAI

En el engranaje de Centro de agentes se configura la clave API y el modelo. La clave se cifra con Laravel Crypt y APP_KEY; no se devuelve en HTML, propiedades Livewire, JSON ni registros de error. Se requiere HTTPS fuera de localhost. Proteger y respaldar APP_KEY por separado de la base de datos. Tambien se admiten OPENAI_API_KEY y OPENAI_AGENT_MODEL en el entorno del servidor; una clave cifrada configurada en el panel tiene preferencia. Eliminar la clave del panel no elimina una clave del entorno.

Seleccionar **Reglas + interpretacion con OpenAI** en cada agente que deba usarla. Se envia el objetivo, instrucciones del perfil y un maximo de 30 hallazgos calculados. No se envian filas originales, expedientes, pacientes, claves, lotes ni archivos. El administrador debe evitar datos personales en el texto del perfil. No se habilitan herramientas externas ni cambios operativos.

La API usa un destino HTTPS fijo, sin redirecciones, Responses API, salida JSON estructurada y `store: false`. Esto desactiva el almacenamiento de estado de aplicacion de la respuesta, no constituye una garantia de retencion cero. Revisar las politicas y acuerdos de datos de la cuenta antes de usar informacion sensible. Las interpretaciones son propuestas para revision humana, separadas de la evidencia local. Una falla de clave, cuota, red, respuesta incompleta o esquema invalido conserva la auditoria local y marca la ejecucion como parcial. Limites por llamada: 25 segundos y 2200 tokens de salida; limite local de 20 llamadas/minuto.

Documentacion oficial: https://developers.openai.com/api/docs/guides/structured-outputs y https://developers.openai.com/api/docs/guides/your-data.

## Programador

`php artisan agents:run-due` procesa agentes activos con configuracion autorizada por un Super Admin que conserve su rol. `hourly` y `daily` programan desde la finalizacion; `changes` compara las fuentes autorizadas cada minuto y ejecuta solo ante cambios. Para alertas dependientes del paso del tiempo usar periodicidad horaria/diaria. Los agentes manuales y apagados no se ejecutan solos.

Laravel registra el comando cada minuto en Kernel. En un servidor con scheduler existente, mantener `php artisan schedule:run` cada minuto. En desarrollo se puede mantener **solo** el motor de agentes con `php artisan agents:work`, sin ejecutar otras tareas del sistema. No usar ambos trabajadores a la vez. El panel muestra el ultimo latido del programador; sin proceso activo no hay automatizacion. El trabajador local debe reiniciarse despues de reiniciar el equipo. En produccion usar un servicio administrado y un cache con bloqueos compartidos si hay varios servidores.

## Verificacion

`php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Feature/AgentExecutionTest.php tests/Feature/AgentCenterTest.php tests/Feature/PromesaAiAgentsTest.php`

`node --test tests/Browser/agent-center.test.cjs` (Playwright con Edge en Windows).

Las pruebas de OpenAI usan respuestas simuladas: no consumen API ni envian datos. Se requiere una clave real para verificar acceso y cuota del proveedor.
