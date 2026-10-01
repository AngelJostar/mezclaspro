# Manual institucional

`manual-v4.docx` es una copia sin cambios de `Manual Maestro de Validacion V4.docx`
proporcionado por el propietario. SHA-256 del archivo:
`494490a682185fc5b09266847dfa2564b163b700f85b5f24d5e1344e9492678d`.

La migracion de instalacion y el despliegue lo importan con
`clinical:import-manual resources/clinical/manual-v4.docx --manual-version=4`.
La importacion es idempotente, comprueba el archivo y archiva las versiones
anteriores sin borrar sus registros ni aprobar la nueva evidencia.
`manual-revision3.docx` se conserva unicamente como historico.

No contiene credenciales de OpenAI. No incluir expedientes, datos de pacientes
ni claves en esta carpeta. Mantener el repositorio y sus accesos de acuerdo con
la politica institucional para este documento.

La V4 sigue pendiente de revision profesional: conserva discrepancias entre
8 y 16 combinaciones, rangos de aminoacidos, unidades y limites de calcio/fosfato
en los criterios 3, 4 y 6 del Supuesto 1, y limites llamados absolutos pero
autorizables en el Supuesto 2. No se corrige el original ni se inventan equivalencias.
Las aclaraciones profesionales se vinculan al SHA-256 textual de esta version:
una aprobacion de la revision 3 no resuelve automaticamente la V4.
El panel permite descargar el original solo al Super Admin. Las revisiones
previas quedan invalidadas por el cambio de fuentes e instrucciones.
