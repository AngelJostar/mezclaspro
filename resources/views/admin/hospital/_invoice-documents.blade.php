@foreach (['pdf', 'xml'] as $format)
    @php $path = $invoice['account']?->{$format.'_path'}; $available = $path && str_starts_with($path, 'hospital-invoices/') && !str_contains($path, '..') && !str_contains($path, '\\') && Storage::disk('local')->exists($path); @endphp
    @if ($available)
        <a class="ht-document ht-document-{{ $format }}" href="{{ route('admin.hospital.facturacion.documento', ['invoice' => $invoice['key'], 'format' => $format]) }}" title="Descargar {{ strtoupper($format) }}"><i data-tools-icon="file-text" aria-hidden="true"></i>{{ strtoupper($format) }}</a>
    @else
        <button class="ht-document ht-document-{{ $format }}" type="button" disabled title="{{ strtoupper($format) }} no disponible" aria-label="{{ strtoupper($format) }} no disponible"><i data-tools-icon="file-text" aria-hidden="true"></i>{{ strtoupper($format) }}</button>
    @endif
@endforeach
