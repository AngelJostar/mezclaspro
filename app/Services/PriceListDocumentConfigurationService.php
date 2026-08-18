<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PriceListDocumentConfigurationService
{
    public function syncSubdistributor(Model $priceList, Request $request, string $logoDirectory): void
    {
        $priceList->loadMissing('distributor');
        $distributor = $priceList->distributor;

        if (!$request->boolean('has_subdistributor')) {
            if ($distributor) {
                $this->deleteLogo($distributor->logo_path);
                $distributor->delete();
            }

            return;
        }

        $distributor ??= $priceList->distributor()->make();
        $distributor->fill([
            'nombre' => trim((string) $request->input('subdistributor_razon_social')),
            'rfc' => strtoupper(trim((string) $request->input('subdistributor_rfc'))),
            'direccion' => trim((string) $request->input('subdistributor_direccion')),
            'contacto' => trim((string) $request->input('subdistributor_contacto')),
            'informacion_adicional' => trim((string) $request->input('subdistributor_additional_information')),
        ]);

        if ($request->hasFile('subdistributor_logo')) {
            $this->deleteLogo($distributor->logo_path);
            $distributor->logo_path = $request->file('subdistributor_logo')->store($logoDirectory, 'public');
        }

        $distributor->save();
    }

    private function deleteLogo(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
