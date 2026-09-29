# Soporte quimico y clinico de solicitudes

## Estado de entrega

Integracion implementada, pero no habilitada para uso clinico en produccion.
El agente queda disponible bajo demanda, sin ejecuciones programadas. El 29/09/2026
se verificaron la autenticacion, el chat y la validacion con el modelo gpt-4.1-mini
y la clave configurada por el propietario, usando datos sinteticos. Eso no verifica
la exactitud clinica ni sustituye la revision del responsable sanitario.

Los formularios incluyen campos complementarios de contexto clinico. El adaptador
registra los campos ausentes y el servidor impide que una respuesta optimista del
modelo habilite el envio. **No basta con activar el agente para habilitar Enviar
Solicitud**: hacen falta contexto suficiente, evidencia vigente revisada y una
respuesta completa sin bloqueos. Llenar campos no certifica su suficiencia clinica.
No hay una excepcion administrativa para omitir bloqueos tecnicos o falta de evidencia.
Existe un registro de autorizacion medica solo para desviaciones de recomendaciones
de dosis expresamente permitidas por un protocolo revisado (ver politica de captura).

## Instalacion y configuracion

1. Aplicar las migraciones `2026_09_29_000001_create_clinical_support.php` y
   `2026_09_29_000002_add_context_to_clinical_reviews.php`. Para el chat, aplicar
   `2026_09_29_000003_create_clinical_agent_conversations.php`.
2. Ejecutar `php artisan clinical:import-manual "ruta/al/manual.docx"`.
   La importacion es idempotente y no aprueba ni corrige el documento.
3. En Superadministrador > Centro de agentes > Configurar OpenAI, guardar la clave
   API del proyecto y un modelo compatible con Responses y Structured Outputs.
   Se reutiliza el almacenamiento cifrado existente, exclusivamente en servidor.
4. Revisar las contradicciones del manual y registrar fuentes institucionales
   revisadas por categoria, con referencia, extracto, responsable y vigencia.
5. Capturar en Datos para la revision clinica las alergias, antecedentes pertinentes,
   funcion renal/hepatica, laboratorios con fecha/unidades, medicacion concomitante,
   protocolo y condiciones de preparacion/conservacion, segun aplicabilidad revisada
   por el profesional. Son campos acotados de texto, no extractores de las notas.
6. Ejecutar evaluaciones clinicas independientes, revisar privacidad y aprobar
   el despliegue antes de utilizar el agente con casos reales.

La cuenta de ChatGPT no se vincula automaticamente. La clave pertenece al proyecto
API del propietario y sus consultas usan la facturacion/cuota de ese proyecto.
No colocar claves en JavaScript, Git, mensajes o capturas de pantalla.

## Flujos

- Los formularios nutricional y oncologico/antibiotico presentan Validar y Continuar.
- El preflight devuelve observaciones, campos afectados, fuentes y calculos. No
  sobrescribe notas ni modifica dosis. Las carencias de contexto/evidencia bloquean.
- Solo una revision completa sin bloqueos ni incertidumbres habilita Enviar Solicitud;
  requiere confirmacion del mismo usuario que solicito la revision. Ese usuario
  decide y captura los cambios sugeridos, vuelve a validar y confirma antes de
  enviar. No se aplican cambios automaticos. Enviar no autoriza la preparacion.
- El recibo es de un solo uso, caduca a los 15 minutos y esta ligado a usuario,
  sesion, contenido completo, catalogo, configuracion y fuentes. Cambiar cualquiera
  de ellos exige volver a validar. El consumo ocurre en la transaccion de guardado.
- Desde Mensajes se revisan los parametros almacenados de esa mezcla con los mismos
  permisos por hospital y categoria. La IA aparece en un panel separado; no suplanta
  participantes ni envia mensajes humanos. No interpreta texto libre del chat. El
  contexto complementario de la solicitud enviada se conserva cifrado para esta
  revision, indicando su fecha; no se infiere contexto para solicitudes anteriores.
- Las consultas de una conversacion se reutilizan durante 15 minutos si no cambian
  los parametros o fuentes; los mensajes humanos siguen funcionando si falla la IA.
- Una vez registrado el agente, el servidor exige la validacion para las nuevas
  solicitudes, incluso si el agente esta inactivo o el cliente omite JavaScript.
  Configuracion incompleta significa envio bloqueado, no validacion aprobada.

## Evidencia y limites

El manual revision 3 proporcionado se refiere a nutricion parenteral. Se detectaron
contradicciones de combinaciones, umbrales de aminoacidos y unidades en secciones
1, 6, 7 y 9. No se codificaron correcciones clinicas inferidas ni limites de dosis.
El responsable debe resolver expresamente estas diferencias mediante una fuente
revisada. Oncologia y antibioticos necesitan protocolos y fichas propias.

No hay busqueda bibliografica automatica: el agente consulta los extractos cargados,
vigentes y aprobados. Los identificadores de cita deben corresponder a esas fuentes.
La fecha de vigencia la establece el revisor, no certifica vigencia cientifica por si
sola. Las pruebas de software no demuestran exactitud ni validacion clinica.

Las conversiones deterministas reproducen los factores del catalogo existente,
no certifican esos factores. No se infieren conversiones entre mEq y mmol ni
compatibilidad/estabilidad por ausencia de informacion. No se equipara estabilidad
fisicoquimica con seguridad microbiologica.

## Datos y seguridad

Los usuarios con rol Cliente o Institucion reciben solo el resultado operativo:
estado, observaciones, calculos puntuales del hallazgo y sugerencias de correccion.
Las secciones Calculos y supuestos, Estado de la evidencia y del servicio, Fuentes
utilizadas y la nota interna final se reservan para personal del centro. El servidor
excluye esos datos, cobertura, modelo y referencias internas de la respuesta al
cliente, tambien cuando reutiliza una revision en cache. La interfaz exige un
permiso explicito de la respuesta para mostrarlos; no depende de ocultarlos con CSS.
Los registros cifrados completos permanecen intactos para auditoria. No cambian los
bloqueos, las autorizaciones medicas requeridas ni la identificacion del soporte como IA.

En la validacion se transmiten a `https://api.openai.com/v1/responses` edad en dias, peso, sexo,
componentes del catalogo, cantidades, unidades, vias, formulacion, calculos, fuentes
y los campos complementarios de revision clinica capturados expresamente. Los
campos generales de identificadores, nombre, fecha de nacimiento, diagnostico, notas
y conversaciones no se copian al payload. El usuario debe excluir identificadores
del contexto complementario: no hay anonimizado automatico fiable de texto libre.
Este subconjunto sigue siendo informacion clinica y requiere evaluar obligaciones
y acuerdos de tratamiento de datos.

`store: false` evita almacenar la respuesta para recuperacion en Responses, pero
**no garantiza retencion cero**. Revisar los controles de la organizacion y los
requisitos de datos sanitarios antes de conectar casos reales. Los resultados se
cifran en la base local. Guardar copias seguras de APP_KEY y definir retencion de
revisiones y fuentes; no se elimina historia automaticamente.

Los limites actuales son 10 consultas/minuto, tiempo de respuesta API de 60 segundos,
5000 tokens de salida, 20 fuentes y 250 KB de evidencia por revision. Errores, rechazos,
salida incompleta, referencias desconocidas y cobertura incompleta no habilitan envio.
Las fuentes, catalogo y perfil editable se procesan como datos, no como instrucciones
con capacidad de autorizar acciones. El agente no tiene herramientas de escritura.

## Verificacion

`tests/Feature/ClinicalSupportTest.php`: privacidad del payload, aritmetica, fuentes,
fallos, bloqueo por contexto faltante, recibos, permisos e importacion. Los casos
positivos usan contexto sintetico completo; no son recetas ni validacion clinica.
`tests/Browser/clinical-review.test.cjs`: estados, notas intactas, XSS, edicion durante
la consulta, fallos y confirmacion en escritorio/movil. `mixture-messages` cubre la
integracion del panel y las regresiones de mensajeria. No se realizan llamadas reales
a OpenAI ni se crean solicitudes clinicas en estas pruebas.

Verificacion de esta entrega: 88 pruebas de servidor (1239 aserciones), dos suites
de navegador con varios escenarios en escritorio/movil y compilacion Vite correctas.
Dos pruebas heredadas de `NutritionRequestFlowTest` no pudieron ejecutarse en la
base aislada en memoria: requieren tablas preexistentes y no crean su esquema
(`no such table: users`). No se ejecutaron contra la base real para suplir esa falta.
El flujo clinico completo y la exactitud clinica siguen pendientes de verificar.

## Conversacion en Superadministrador

El agente clinico incluye un chat de texto libre, separado de solicitudes y recibos
de validacion. Solo Super Admin puede enviar y leer su propio historial. Los mensajes
se cifran en `clinical_agent_conversations`; no se incorporan al perfil del agente ni
se aplican como cambios a mezclas, fuentes o autorizaciones. Nueva conversacion
conserva el historial anterior, accesible en el selector de conversaciones.

Al enviar, se transmiten expresamente la pregunta, las ultimas seis consultas y
respuestas, el perfil y los extractos institucionales aplicables, con `store: false`.
El texto libre no tiene anonimizado automatico: no incluir nombres ni expedientes.
No se consultan registros de pacientes. Los extractos no revisados se identifican
como pendientes; no se realiza busqueda externa ni se aprueban fuentes desde el chat.

Limites: 4000 caracteres por mensaje, 20 consultas por conversacion, 10 consultas
por minuto por usuario, 200 KB de contexto, 3000 tokens de salida y 60 segundos de
espera API. Un bloqueo por usuario/agente evita envios concurrentes; una revision
de historial impide sobrescribir cambios desde otra ventana. Los errores conservan
el borrador sin persistir respuestas incompletas ni revelar cuerpos del proveedor.

`ClinicalAgentChatTest` y el escenario de chat en `tests/Browser/agent-center.test.cjs`
cubren permisos, cifrado, fuentes, XSS, duplicados, historial, fallos y escritorio/movil.
Usan datos sinteticos y respuestas simuladas, sin acceso a la base de produccion.
La primera prueba real autorizada del chat (29/09/2026) recibio HTTP 429 por saldo
agotado. Despues de la recarga, el chat respondio HTTP 200 en 3.5 segundos usando
el perfil y las fuentes institucionales, sin datos de pacientes ni persistencia
de mensajes.

## Prueba real de validacion y consumo (29/09/2026)

Se ejecuto el controlador de Validar y Continuar con una solicitud nutricional
completamente sintetica. No se consultaron expedientes ni se enviaron mezclas.
Los registros temporales de revision se revirtieron mediante transacciones.

- Volumen declarado de 1000 mL frente a 1350 mL calculados: estado blocked,
  can_submit=false. OpenAI respondio HTTP 200.
- Volumen ajustado a 1500 mL, contexto incompleto y manual sin aprobar: estado
  needs_review, can_submit=false. La respuesta final fue aceptada por el sistema.
- Dos consultas intermedias fueron rechazadas por la verificacion de la respuesta;
  el diagnostico reprodujo identificadores de fuentes mezclados con secciones o
  campos. Se restringio el esquema a IDs de fuentes y campos exactos, conservando
  la verificacion del servidor. Sin fuentes, las listas de referencias deben quedar
  vacias. Las 18 pruebas ClinicalSupportTest pasaron (94 aserciones), en SQLite
  en memoria y con HTTP simulado.

Consumo reportado por OpenAI, modelo gpt-4.1-mini-2025-04-14, sin tokens en cache:

| Consulta | Entrada | Salida | Total tokens | Costo estimado USD |
| --- | ---: | ---: | ---: | ---: |
| Volumen inconsistente | 8740 | 542 | 9282 | 0.0043632 |
| Primera revision del volumen ajustado, rechazada | 8707 | 1044 | 9751 | 0.0051532 |
| Diagnostico de referencias, rechazado | 8707 | 545 | 9252 | 0.0043548 |
| Volumen ajustado, formato corregido | 8891 | 903 | 9794 | 0.0050012 |
| Total de esta prueba | 35045 | 3034 | 38079 | 0.0188724 |

Estimacion con tarifa estandar de USD 0.40 por millon de tokens de entrada y
USD 1.60 por millon de salida; entrada en cache USD 0.10 por millon cuando aplique.
No incluye impuestos ni condiciones particulares de facturacion. Las respuestas
rechazadas por la aplicacion tambien consumieron tokens. Una muestra no establece
un costo fijo: varia con el contexto, las fuentes, los componentes y la respuesta.
Este resultado tecnico no certifica la exactitud clinica del manual ni del agente.

## Politica de captura y autorizacion medica (30/09/2026)

- Nutricion: peso positivo y fecha de nacimiento obligatorios. Oncologia y
  antibioticos: tambien talla en cm y superficie corporal en m2, capturadas por
  el usuario. No se calculan ni sustituyen automaticamente. Se conservan en la
  solicitud. Formularios, preflight y envio validan estos datos; errores en rojo.
- Ya no se agregan listas repetitivas de "Requiere aclaracion profesional" por
  antecedentes ausentes. El prompt clasifica esas ausencias y el servidor las
  excluye de observaciones/resumen, conservando respuesta completa cifrada para
  auditoria. Riesgos reales conocidos no se ocultan. Las incidencias de fuentes
  o servicio se muestran separadas en Estado de la evidencia y del servicio.
- Ocultar avisos de antecedentes no equivale a afirmar seguridad: contexto
  insuficiente y cobertura/evidencia incompleta mantienen el envio bloqueado.
  No se modifican las aprobaciones del manual ni se inventan limites clinicos.
- Una fuente revisada debe marcar explicitamente allows_medical_authorization
  y describir excepciones de dosis permitidas. Esa marca empieza desactivada.
  El hallazgo debe referir un campo de dosis, citar esa fuente y tener categoria
  dose_recommendation/severidad warning (normalizada a authorization). Todos los dominios deben estar
  revisados. Incertidumbres, calculos incorrectos, incompatibilidad y riesgos
  de seguridad nunca se pueden exceptuar mediante captura de un medico.
- El aviso de excepcion pide unicamente nombre del medico y cedula profesional.
  Ya no pide referencia, fecha/hora de autorizacion, justificacion ni casilla de
  confirmacion adicional. El usuario sigue necesitando la autorizacion medica real.
  Es un registro de una autorizacion recibida, no una firma electronica ni una
  verificacion automatica de identidad o licencia. El envio sigue requiriendo
  aprobacion profesional posterior para preparar la mezcla.
- La autorizacion se guarda cifrada, junto con usuario, fecha y hallazgos de la
  revision, en la misma transaccion del envio. No se transmite a OpenAI. No
  altera dosis. Cambiar parametros, fuentes, usuario o sesion invalida el recibo;
  el formulario borra la captura de autorizacion al cambiar la mezcla.
- Aplicar 2026_09_30_000001_add_medical_authorization_and_anthropometry.php.
  Los registros existentes quedan con campos nuevos nulos; no se inventan datos.
- Aplicar tambien 2026_09_30_000003_simplify_clinical_authorization_capture.php
  para sincronizar la instruccion de captura del agente existente. Se conserva
  el historial previo completo. Los registros nuevos solo capturan nombre y cedula;
  usuario, momento de captura, revision y hallazgos se registran en el servidor.
  El momento de captura no se presenta como fecha de autorizacion del medico.
  La politica v4 invalida los recibos anteriores. La revision general del usuario
  y los bloqueos por rechazo o evidencia insuficiente siguen vigentes.

## Advertencia y rechazo

- ADVERTENCIA: desviacion de limites recomendados con excepcion expresamente
  permitida por una fuente vigente revisada. Toda severidad warning o authorization
  exige registro de autorizacion medica; nunca habilita envio directo, aunque el
  modelo devuelva no_blockers. Las notas sin riesgos usan information, no warning.
- RECHAZO: severity blocking. Se muestra SOLICITUD RECHAZADA, se marcan los campos
  en rojo y se exige corregir y revalidar. Prevalece sobre cualquier advertencia;
  capturar datos de un medico no lo omite. Tampoco se omiten errores locales.
- Las advertencias quimicas usan chemical_recommendation. Requieren una fuente
  revisada con allows_chemical_medical_authorization, desactivado por defecto e
  independiente del permiso previo para dosis. No se aprueban incompatibilidades,
  inestabilidad ni limites absolutos de seguridad. No se cambian umbrales clinicos.
- Sin contexto/evidencia suficiente se conserva Revision incompleta y el bloqueo.
  El manual revision 3 sigue pendiente de revision; sus contradicciones no se
  resuelven mediante una autorizacion medica ni con esta actualizacion de software.
- La migracion 2026_09_30_000002_update_clinical_observation_policy.php agrega la
  politica al recuadro Instrucciones del agente existente, sin duplicarla, borrar
  su perfil personalizado, cambiar su activacion ni aprobar fuentes. Agentes nuevos
  la incluyen desde su importacion. La politica v3 invalida recibos anteriores.

La separacion entre revision de formulacion y aprobacion para preparacion sigue
el criterio de revisar compatibilidad/estabilidad y discutir discrepancias con
el prescriptor, sin reemplazar el juicio profesional:
https://nutritioncare.org/wp-content/uploads/2024/12/PN-Checklist-02-Order-Review.pdf

## Sugerencias de correccion

- Las sugerencias incluyen solo cambios concretos sustentados: campo, valor actual,
  valor/rango o condicion requerida, unidades y fuente/seccion aplicable del manual
  o protocolo revisado. No se agregan avisos genericos de consultar al profesional,
  confirmar fuentes o verificar evidencia. Se conservan en la
  revision cifrada y se muestra tambien en Mensajes y en el historial del agente.
- En el rechazo local por volumen se calcula la diferencia exacta entre la suma
  de componentes y el volumen capturado. Se pide cotejar la orden medica: si los
  componentes son correctos, el area medica debe confirmar un volumen que los
  contenga; si se mantiene el volumen prescrito, debe revisar la formulacion.
  No se propone reducir dosis ni agregar agua automaticamente. La consistencia
  aritmetica no equivale a seguridad clinica o fisicoquimica.
- El modelo devuelve `suggestion` en el esquema estructurado. El campo debe existir
  y ser texto, pero queda vacio cuando no hay un ajuste especifico sustentado. Si
  las fuentes citadas no estan revisadas o faltan, el servidor elimina la sugerencia
  sin sustituirla por un mensaje generico ni inventar valores. El hallazgo, su
  severidad y el bloqueo permanecen. Un texto vacio no equivale a revision aprobada.
- Las respuestas e historial visible tambien omiten las sugerencias antiguas basadas
  en fuentes sin revisar; los registros cifrados de auditoria no se reescriben.
- No se modifican campos ni se quitan bloqueos al mostrar una sugerencia. Siempre
  se exige corregir y revalidar; las fuentes deben sustentar cualquier rango clinico.
- Aplicar `2026_09_30_000004_add_clinical_correction_suggestions.php` sincroniza
  las instrucciones del agente existente sin cambiar su activacion, perfil previo
  ni fuentes. La politica v5 invalida los recibos anteriores.
- Aplicar `2026_09_30_000005_refine_clinical_suggestions.php` reemplaza la instruccion
  anterior por la politica de ajustes concretos, conservando instrucciones propias,
  activacion y revision de fuentes. La politica v6 invalida recibos y consultas en
  cache anteriores. Las contradicciones del manual siguen pendientes de aclaracion.
- Las observaciones y sugerencias de validacion se limitan a los componentes y
  campos de la solicitud evaluada, tanto al crearla como desde Mensajes. Se omiten
  saludos, consejos generales, confirmaciones de parametros correctos y comentarios
  operativos. Los riesgos conocidos que afectan a esa mezcla se mantienen.
- El esquema clasifica notas internas como `internal_comment`; junto con
  `information` y `missing_clinical_context`, quedan fuera de las observaciones
  visibles, incluso al leer registros anteriores. La decision de envio se calcula
  antes del filtro y la respuesta completa se conserva cifrada en auditoria.
  Filtrar comentarios no elimina rechazos, advertencias ni revision incompleta.
- Aplicar `2026_09_30_000006_scope_clinical_feedback_to_mixture.php` agrega esta
  regla al recuadro de instrucciones, sin reemplazar el perfil ni aprobar fuentes.
  La politica v7 invalida recibos y consultas en cache de versiones previas.

Documentacion oficial consultada:
- https://developers.openai.com/api/docs/guides/structured-outputs
- https://developers.openai.com/api/docs/guides/your-data
- https://developers.openai.com/api/docs/models/gpt-4.1-mini
