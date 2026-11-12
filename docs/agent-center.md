# Centro de agentes

## Instalacion

- Ejecutar la migracion `2026_09_14_000005_add_agent_execution.php`.
- Ejecutar `php artisan db:seed --class=PromesaAiAgentsSeeder` para completar los perfiles existentes sin reemplazar configuraciones personalizadas ni activar agentes.
- Compilar con `npm run build`.
- Los agentes requieren sus tablas de dominio. Abasto usa las migraciones `2026_09_03_000001` y `2026_09_04_000002`; las confirmaciones de entrega usan `2026_09_02_000001`.

## Ejecucion

Cada agente de auditoría requiere objetivo, reglas, fuentes autorizadas, alcance y responsable. Super Admin administra activación, configuración y accesos; puede conceder consulta, ejecución y edición de información a usuarios, roles y puestos desde Autorizaciones. Un perfil sin alcance no ejecuta consultas. La activacion ON no sustituye la configuracion.

El motor de auditoría calcula hallazgos en modo de solo lectura. Solo escribe en las tablas de agentes, ejecuciones, hallazgos y seguimiento. El agente operativo de conciliaciones descrito abajo sí crea y envía periodos. Los permisos son una lista cerrada de herramientas; las instrucciones nunca conceden permisos. La configuracion y el texto del perfil quedan registrados en cada ejecucion.

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

## Orden personal y agentes operativos (noviembre de 2026)

Ejecutar la migración `2026_11_12_000001_create_operational_agent_tables.php`, el seeder idempotente `OperationalAgentsSeeder` y `npm run build`. El seeder agrega dos agentes apagados sin modificar los existentes. No usan servicios externos de IA: ejecutan procedimientos y cálculos locales verificables.

Super Admin puede arrastrar las tarjetas o usar Alt + flecha izquierda/derecha. El orden se guarda por usuario; Todos y Crear nuevo conservan sus posiciones. Se rechazan listas incompletas o con agentes desconocidos.

### Envío automático de conciliaciones

- Solo Super Admin guarda la programación y activa el agente. Los usuarios con permiso de ejecución pueden iniciar un envío manual con esa configuración, limitado a sus hospitales autorizados. Predeterminado: mensual, día 1 a las 09:00 de Ciudad de México, con remisiones del mes calendario anterior. También admite día o semana anterior.
- Elegir hospitales activos, o Seleccionar todos, y guardar. No se generan periodos vacíos. Cada hospital recibe exclusivamente sus remisiones; los periodos y envíos se crean mediante el mismo servicio transaccional que la pantalla de conciliaciones.
- Se excluyen remisiones ya asignadas a periodos o enviadas previamente. Un identificador único por agente/institución/hospital/fechas evita repetir entregas. Si hay precios inválidos, se revierte únicamente ese hospital y se muestra el motivo.
- El programador conserva el ciclo pendiente tras un error y reintenta después de 15 minutos. Las entregas ya completadas se conservan. Al recuperarse procesa los ciclos atrasados, uno por agente y pasada, sin saltar al mes actual.
- El seguimiento muestra folio, fecha, respuesta pendiente/aceptada/con cambios, responsable y motivos reportados por el hospital.
- Correo OFF por defecto. Con ON se requieren el correo del hospital y de la institución; se sugieren los del catálogo/cuenta institucional y se pueden ajustar antes de guardar. El aviso contiene folio, periodo y enlace de acceso, sin datos de pacientes ni adjuntos clínicos.
- Se necesita un transporte real configurado en Laravel Mail. `log` y `array` no cuentan como envío. La bandeja registra destinatario, fecha y error; reintenta cada 15 minutos hasta cinco intentos, con opción manual Reintentar correo. Apagar el agente o las notificaciones detiene los correos pendientes; los enviados no se repiten.

### Comparativo de nutriciones por paciente

Seleccionar hospital y expediente; alternativamente, nombre completo y fecha de nacimiento. El rango incluye solo solicitudes entregadas/finalizadas del hospital elegido. Un expediente asociado a identidades distintas se rechaza. Los usuarios hospitalarios e institucionales solo consultan hospitales de su alcance, tanto en el panel como en la descarga PDF.

El informe conserva una copia de los resultados: peso registrado, cambios contra la nutrición anterior, cantidades prescritas, volumen y concentración por 100 mL. Para las categorías pediátricas por kg se usa el peso de cada solicitud. No se suman recetas del mismo día ni se convierten ausencias en ceros. Las unidades provienen del catálogo al generar el informe. Son comparaciones descriptivas, sin diagnósticos ni recomendaciones de tratamiento.

El resultado queda en Centro de agentes con PDF descargable: resumen, matriz por día/nutriente y detalle de cada nutrición. Máximo 1,000 nutriciones por informe; el usuario debe acotar fechas si lo supera. Se conservan los permisos consultar/ejecutar/editar; editar modifica descripción e instrucciones, nunca las fórmulas, la programación ni los accesos.

Pruebas adicionales: `tests/Feature/OperationalAgentsTest.php` y `tests/Browser/agent-operations.test.cjs`. Usan SQLite y pacientes ficticios, transporte simulado y un navegador con datos aislados. El PDF sintético opcional se genera al definir `AGENT_PDF_SAMPLE=1`.
