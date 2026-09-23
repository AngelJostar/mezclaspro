# Ordenes automaticas por stock minimo

- El punto de reorden es `minimum_stock`. Se genera solo cuando el stock fisico de lotes activos de la central es estrictamente inferior.
- Cantidad: `ceil(maximum_stock - current_stock)`, en piezas. Las reservas no se descuentan otra vez.
- Se crea una orden persistida por central/presentacion, con folio `OC-AUTO-...` y estado `pendiente_revision`. No se envia por correo ni a proveedores.
- El proveedor predeterminado activo se copia como snapshot. Si falta, permanece pendiente. El almacen se asigna solo cuando hay un unico almacen activo en la central.
- Precios, impuestos y facturacion no se inventan: se muestran pendientes de cotizar/revisar. Se bloquea el PDF comercial mientras falta cotizar.
- La misma orden pendiente se actualiza cuando cambia el deficit o su configuracion. La recuperacion hasta el punto de reorden, desactivacion o eliminacion de limites cancela la pendiente conservando el historial. Una caida posterior inicia otra orden.
- Las ordenes que ya salieron de revision no se modifican automaticamente ni se duplican mientras conservan su clave abierta. El posterior flujo de aprobacion/envio/recepcion debe liberar esa clave solo al cerrar la reposicion.

## Ejecucion

`StockReorderMonitor` detecta escrituras del sistema en inventario/catalogo/configuracion, tanto Eloquent como Query Builder. Concilia al terminar la peticion o comando y despues de trabajos de cola, fuera de la transaccion de inventario. Si falla, conserva el inventario confirmado y registra el error.

`php artisan inventory:reorder` permite conciliar todas las centrales; `--laboratory=ID` limita una central. Es idempotente, serializa por central y tiene una restriccion unica para la orden abierta.

El programador Laravel incluye una conciliacion cada minuto como recuperacion y para cambios externos. En servidores que importen directamente a la base de datos debe estar activo `php artisan schedule:run` cada minuto. Los cambios realizados desde la aplicacion no dependen de ese programador.

Aplicar exclusivamente la migracion `2026_09_22_000006_add_automatic_reorders_to_purchase_orders.php` sobre instalaciones que ya tienen Stock minimo; despues ejecutar una conciliacion inicial. Las consultas GET de listas/detalles no generan ordenes.
