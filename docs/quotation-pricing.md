# Nueva cotizacion

Las nuevas cotizaciones usan el flujo comercial: categoria, hospital y mezclas,
revision de precios y guardado como borrador o envio a revision. No crean solicitudes
de preparacion ni movimientos de inventario. Los registros clinicos anteriores
conservan su formulario de edicion.

## Listas base

Configurar los IDs de las listas existentes en el entorno de la instalacion:

```dotenv
QUOTATION_BASE_NUTRITION_LIST_ID=
QUOTATION_BASE_ONCOLOGY_LIST_ID=
QUOTATION_BASE_ANTIBIOTIC_LIST_ID=
```

Nutricionales referencia `nutri_medicine_lists`; oncologicos y antibioticos
referencian `medicine_lists` con su respectivo `catalog_category`.
Despues de cambiar el entorno, actualizar la cache de configuracion si se utiliza.
No se elige una lista por nombre ni se reutiliza implicitamente la de otro hospital.
Si falta la configuracion, la cotizacion sin relacion comercial queda bloqueada
con un mensaje de validacion. La lista nutricional debe estar activa.

## Reglas de precio

- La institucion debe pertenecer al hospital seleccionado; se mantienen los permisos
  de solicitudes y la restriccion de hospital para clientes e instituciones.
- Con relacion comercial se usa la lista asignada al hospital, incluyendo sus
  preferencias por medicamento. Si hay modalidades distintas se muestra cobro
  segun medicamento, sin permitir modificaciones manuales.
- Sin relacion comercial se usa exclusivamente la lista base configurada. Se permite
  elegir por unidad (mL para nutricionales, mg para las otras categorias) o por frasco.
- Con cobro por frasco se captura una cantidad entera de frascos (`bottle_count`),
  que se multiplica directamente por su precio unitario. Con cobro por mg/mL se
  captura `concentration` en la unidad de la categoria. El servidor exige el campo
  correspondiente a la modalidad de cada medicamento, incluso en listas mixtas.
  Al cambiar la modalidad se limpia la cantidad; los borradores comerciales
  anteriores recuperan los frascos de su desglose cotizado al abrir el editor.
- Las tarifas nutricionales se almacenan por mL: al cotizar frascos se multiplican
  por el volumen del frasco. Las tarifas legadas por mL de oncologia/antibioticos
  se convierten a mg usando el contenido y volumen registrados.
- Las mezclas se numeran desde 1. Cada una tiene su propia tabla de medicamentos,
  cantidades, ajustes de precio y subtotal. El total estimado suma todas las mezclas.
- El cargo de preparacion y los demas cargos de la lista se aplican una vez por
  mezcla, no por medicamento ni una sola vez por cotizacion. El IVA sigue las
  reglas existentes; cada mezcla conserva sus cargos en el desglose.
- La captura guarda `mixture_count` e `items[].mixture_number`. Todas las mezclas
  deben contener medicamentos. Se permite repetir una presentacion en mezclas
  diferentes, no dentro de la misma mezcla. El limite es 50 medicamentos en total.
  Los registros anteriores sin estos campos conservan su calculo original:
  una mezcla nutricional o una por medicamento oncologico/antibiotico.
- El servidor recalcula precios y disponibilidad en revision y guardado. El token
  de revision detecta cambios en precios, productos y cargos antes de persistir.
  El cliente no puede proporcionar importes finales ni cambiar la modalidad del hospital.
- El lapiz de precio unitario permite un `unit_price_override` por medicamento,
  solo para esa cotizacion comercial. Acepta valores no negativos con hasta cuatro
  decimales, mantiene los impuestos y cargos de la lista y permite restablecer la
  tarifa original. No actualiza ninguna lista de precios.
- El desglose registra `list_unit_price`, `price_adjusted` y `price_adjusted_by`
  junto al precio efectivo. Los ajustes se restauran al editar borradores y se
  limpian al cambiar de hospital, lista o modalidad. Cualquier cambio de precio
  invalida la revision previa; cotizaciones enviadas o autorizadas no se editan.
- El desglose cotizado queda congelado en `pricing_snapshot`; los cambios posteriores
  de catalogo no modifican cotizaciones ya guardadas.
- Al enviar a preparacion, el precio efectivo pasa a `quotation_pricing_snapshot`.
  Preparacion, remisiones y facturacion utilizan ese desglose sin recalcularlo con
  la lista vigente, tanto por frasco como por mg/mL.
- El envio a preparacion conserva la agrupacion cotizada. Para nutricionales se
  crea una solicitud por mezcla, todas vinculadas a la misma cotizacion; para
  oncologicos y antibioticos se conserva una solicitud con sus mezclas. El envio
  es transaccional y no puede repetirse para generar solicitudes duplicadas.

## Envio en PDF

- La descarga autenticada `solicitudes/cotizacion/{quotation}/pdf` y el correo usan
  `RequestQuotationPdf`, con el archivo nombrado por folio, por ejemplo
  `COT-000001.pdf`. El documento utiliza los importes guardados, no los del catalogo actual.
- La vista previa y el PDF muestran el logotipo PROMESA y los datos de emisor de
  `config/prodifem.php`: razon social, RFC, domicilio, telefono y correo. El PDF
  incorpora la imagen local sin depender de descargas externas.
- El PDF conserva las unidades, los impuestos registrados,
  el total en letras y encabezados de tabla repetidos en documentos de varias paginas.
  No incluye datos clinicos, pacientes ni firmas privadas.
- El correo adjunta el PDF directamente y requiere un transporte de correo real.
  Una falla al generar el documento impide el envio; las pruebas no envian correos reales.
- La integracion actual de WhatsApp es `wa.me`, no WhatsApp Business API.
  Se descarga el PDF y se abre el chat con el mensaje opcional y el folio;
  el usuario debe adjuntar el archivo y confirmar el envio en WhatsApp.
  No se publican enlaces al PDF ni se modifica el estado de la cotizacion al abrir el chat.

Para revision visual con datos sinteticos, ejecutar
`php tests/Browser/fixtures/quotation-pdf.php <directorio-existente>`.
