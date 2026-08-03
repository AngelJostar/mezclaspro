<?php

namespace App\Exports;

use App\Models\Nutricionales\Solicitud;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\Exportable;

class SolicitudesExport implements FromQuery, WithMapping, WithHeadings, WithChunkReading, ShouldAutoSize
{
    use Exportable;

    protected $user;

    public function __construct()
    {
        $this->user = Auth::user();
    }

    public function query()
    {
        $query = Solicitud::with([
            'user.hospital',
            'solicitud_patient',
            'solicitud_detail',
            'input',
        ])->latest();

        if (!in_array($this->user->roles[0]->name, ['Admin', 'Super Admin'])) {
            $query->where('user_id', $this->user->id);
        }

        return $query;
    }

    public function map($s): array
    {
        $inputs = $s->input->keyBy('input_id');

        $getMl = fn($id) => isset($inputs[$id]) ? $inputs[$id]->valor_ml . ' mL' : '';

        return [
            $s->id,
            $s->remision ?? '',
            $s->lote ?? '',
            $s->user->hospital->name ?? '',
            trim(($s->solicitud_patient->nombre_paciente ?? '') . ' ' . ($s->solicitud_patient->apellidos_paciente ?? '')),
            $s->solicitud_patient->servicio ?? '',
            $s->solicitud_patient->registro ?? '',
            $s->solicitud_patient->diagnostico ?? '',
            $s->solicitud_patient->edad ?? '',
            $s->solicitud_patient->sexo ?? '',
            isset($s->solicitud_patient->peso) ? $s->solicitud_patient->peso . ' Kg' : '',
            $s->solicitud_patient->cama ?? '',
            $s->solicitud_detail->sobrellenado_ml ?? '',
            $s->solicitud_detail->volumen_total ?? '',
            $s->solicitud_detail->npt ?? '',
            optional($s->created_at)->format('d/m/Y'),
            $s->solicitud_detail->nombre_medico ?? '',
            $s->solicitud_detail->cedula ?? '',
            $s->solicitud_detail->observaciones ?? '',
            $s->is_aprobada ?? '',
            $getMl(5),  $getMl(36), $getMl(8),  $getMl(46), $getMl(9),
            $getMl(10), $getMl(11), $getMl(12), $getMl(13), $getMl(14),
            $getMl(15), $getMl(16), $getMl(17), $getMl(18), $getMl(19),
            $getMl(20), $getMl(21), $getMl(22), $getMl(23), $getMl(24),
            $getMl(25), $getMl(26), $getMl(27), $getMl(44), $getMl(29), $getMl(47),
            $getMl(30), $getMl(31), $getMl(32), $getMl(33), $getMl(34),
            $getMl(35),
        ];
    }

    public function headings(): array
    {
        return [
            'ID', 'Remisión', 'Lote', 'Hospital', 'Paciente', 'Servicio', 'Registro',
            'Diagnóstico', 'Edad', 'Sexo', 'Peso', 'Cama', 'Sobrellenado', 'Volumen total', 'NPT',
            'Fecha de solicitud', 'Nombre del médico', 'Cédula profesional',
            'Observaciones', 'Estatus',
            'Aminoácidos pediátricos 10%', 'Cloruro de Sodio 0.9%', 'Dextrosa 50%',
            'MCT/LCT 10%', 'MCT/LCT 20%', 'SMOF 20%', 'Cloruro de Sodio (3 mEq/mL Na)',
            'Acetato de Sodio (4 mEq/mL)', 'Fosfato de Sodio (4 mEq/mL)',
            'Sulfato de Magnesio (0.81 mEq/mL)', 'Cloruro de Potasio (4 mEq/mL)',
            'Acetato de Potasio (2 mEq/mL)', 'Fosfato de Potasio (2 mEq/mL)',
            'Gluconato de Calcio (0.465 mEq/mL)', 'Ácidos Grasos Omega 3 10%',
            'Albúmina 25% (0.25 g/mL)', 'Albúmina 20% (0.20 g/mL)', 'Glutamina 20%',
            'Cromo (4 mcg/mL)', 'Heparina (1000 UI/mL)',
            'L-Carnitina', 'Insulina', 'Manganeso (100 mcg/mL)', 'MVI pediátrico',
            'Oligoelementos Nulanza', 'Oligoelementos Tracefusin',
            'Ácido Folínico (12.5 mg/mL)', 'Selenio (40 mcg/mL)',
            'Vitamina C (100 mg/mL)', 'Vitamina K (10 mg/mL)', 'Zinc (1 mg/mL)',
            'L-Cisteina (50mg/mL)',
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
