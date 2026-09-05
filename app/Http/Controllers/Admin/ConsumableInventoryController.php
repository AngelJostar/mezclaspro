<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\ConsumableItem;
use App\Models\ConsumableLot;
use App\Models\ConsumableCatalogPresentation;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class ConsumableInventoryController extends Controller
{
    public function index(Warehouse $warehouse) { return view('admin.warehouses.consumables', ['warehouse'=>$warehouse->load('laboratory'), 'lots'=>ConsumableLot::with('item')->where('warehouse_id',$warehouse->id)->orderBy('expires_at')->paginate(40)]); }
    public function create(Warehouse $warehouse) { return view('admin.warehouses.consumable-form', ['warehouse' => $warehouse, 'presentations' => ConsumableCatalogPresentation::with('item')->where('is_active', true)->orderBy('presentation')->get()]); }
    public function store(Request $request, Warehouse $warehouse) {
        $data=$request->validate(['catalog_presentation_id'=>['required','exists:consumable_catalog_presentations,id'],'lot'=>['required','string','max:255'],'expires_at'=>['nullable','date'],'received_at'=>['required','date'],'stock_actual'=>['required','numeric','gt:0']]);
        $presentation = ConsumableCatalogPresentation::with('item')->findOrFail($data['catalog_presentation_id']);
        $existing = ConsumableLot::where(['consumable_item_id'=>$presentation->consumable_item_id,'catalog_presentation_id'=>$presentation->id,'warehouse_id'=>$warehouse->id,'lot'=>$data['lot']])->first();
        if ($existing) { $existing->update(['stock_actual'=>(float)$existing->stock_actual + (float)$data['stock_actual'], 'expires_at'=>$data['expires_at'], 'received_at'=>$data['received_at'], 'is_active'=>true]); }
        else { ConsumableLot::create(['consumable_item_id'=>$presentation->consumable_item_id,'catalog_presentation_id'=>$presentation->id,'warehouse_id'=>$warehouse->id,'presentation'=>$presentation->presentation,'brand'=>$presentation->commercial_name,'manufacturer'=>$presentation->manufacturer,'lot'=>$data['lot'],'expires_at'=>$data['expires_at'],'received_at'=>$data['received_at'],'stock_actual'=>$data['stock_actual'],'is_active'=>true]); }
        return redirect()->route('admin.warehouses.consumables.index',$warehouse)->with('success','Lote de consumible guardado.');
    }
}
