<?php

namespace App\Exports\Instituciones;

use App\Models\Hospital;
use App\Models\Institucion;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InstitucionHospitalesRelacionadosExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(private int $institucionId) {}

    public function headings(): array
    {
        return [
            'Institucion',
            'ID hospital',
            'Hospital',
            'Nombre corto',
            'Clave interna',
            'Tipo de unidad',
            'Nivel de atencion',
            'Estatus hospital',
            'Estatus acceso',
            'Razon social fiscal',
            'RFC',
            'Regimen fiscal',
            'Uso CFDI',
            'Correo facturacion',
            'Telefono facturacion',
            'CLUES',
            'Texto libre',
            'Pais',
            'Estado',
            'Municipio o alcaldia',
            'Codigo postal',
            'Colonia',
            'Calle y numero',
            'Direccion completa',
            'Link Google Maps',
            'Coordenadas',
            'Responsable',
            'Cargo',
            'Telefono contacto',
            'Correo contacto',
            'Horario recepcion',
            'Dias operacion',
            'Lineas de servicio',
            'Central de mezclas',
            'Usuarios software',
            'Contrasenas software',
            'Usuarios capacitacion',
            'Contrasenas capacitacion',
            'Creado',
            'Actualizado',
        ];
    }

    public function array(): array
    {
        $institucion = Institucion::query()
            ->with([
                'hospitals' => fn ($query) => $query
                    ->with([
                        'laboratory:id,nombre',
                        'users' => fn ($userQuery) => $userQuery
                            ->select([
                                'id',
                                'hospital_id',
                                'username',
                                'credential_password',
                                'training_username',
                                'training_credential_password',
                                'is_active',
                            ])
                            ->whereHas('roles', fn ($roleQuery) => $roleQuery
                                ->whereIn('name', ['Cliente', 'Institucion']))
                            ->orderByDesc('is_active')
                            ->orderBy('username'),
                    ])
                    ->orderBy('hospitals.name'),
            ])
            ->findOrFail($this->institucionId);

        return $institucion->hospitals
            ->map(fn (Hospital $hospital): array => $this->hospitalRow($institucion, $hospital))
            ->values()
            ->all();
    }

    public function styles(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        $sheet->getStyle("A1:{$highestColumn}1")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F3C88'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        if ($highestRow > 1) {
            $sheet->getStyle("A2:{$highestColumn}{$highestRow}")
                ->getAlignment()
                ->setVertical(Alignment::VERTICAL_TOP)
                ->setWrapText(true);
        }

        $sheet->freezePane('A2');

        return [];
    }

    private function hospitalRow(Institucion $institucion, Hospital $hospital): array
    {
        $users = $hospital->users;
        $operationDays = is_array($hospital->operation_days)
            ? implode(', ', $hospital->operation_days)
            : (string) $hospital->operation_days;

        return [
            $institucion->nombre,
            $hospital->id,
            $hospital->name,
            $hospital->short_name,
            $hospital->internal_key,
            $hospital->unit_type,
            $hospital->care_level,
            $hospital->is_active ? 'Activo' : 'Inactivo',
            $hospital->access_is_active ? 'Activo' : 'Bloqueado',
            $hospital->fiscal_name,
            $hospital->rfc,
            $hospital->fiscal_regime,
            $hospital->cfdi_use,
            $hospital->billing_email,
            $hospital->billing_phone,
            $hospital->clues,
            $hospital->free_text,
            $hospital->country,
            $hospital->state,
            $hospital->municipality,
            $hospital->postal_code,
            $hospital->neighborhood,
            $hospital->street_number,
            $hospital->adress,
            $hospital->google_maps_url,
            $this->coordinates($hospital),
            $hospital->contact_name,
            $hospital->contact_position,
            $hospital->phone,
            $hospital->email,
            $hospital->reception_hours,
            $operationDays,
            $this->services($hospital),
            $hospital->laboratory?->nombre,
            $users->pluck('username')->filter()->implode("\n"),
            $users->map(fn ($user) => $user->credential_password ?: 'Sin contrasena')->implode("\n"),
            $users->pluck('training_username')->filter()->implode("\n"),
            $users->map(fn ($user) => $user->training_credential_password ?: 'Sin contrasena')->implode("\n"),
            optional($hospital->created_at)->format('d/m/Y H:i'),
            optional($hospital->updated_at)->format('d/m/Y H:i'),
        ];
    }

    private function services(Hospital $hospital): string
    {
        return collect([
            $hospital->service_oncology || $hospital->onco_medicine_list_id ? 'Oncologicos' : null,
            $hospital->service_antibiotics ? 'Antibioticos' : null,
            $hospital->service_nutrition || $hospital->nutri_medicine_list_id ? 'Nutricionales' : null,
        ])->filter()->implode(', ');
    }

    private function coordinates(Hospital $hospital): string
    {
        if ($hospital->latitude === null || $hospital->longitude === null) {
            return '';
        }

        return $hospital->latitude.', '.$hospital->longitude;
    }
}
