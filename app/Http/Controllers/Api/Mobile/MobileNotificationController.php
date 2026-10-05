<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\DistributionDeliveryConfirmation;
use App\Models\DistributionRoute;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MobileNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $messenger */
        $messenger = $request->user();
        abort_unless($messenger->is_active && !$messenger->isBlockedByOrganization(), 403);
        if ($messenger->hasAnyRole(['Cliente', 'Institucion'])) {
            abort_unless($messenger->hospital_id, 403);
            $this->synchronizeHospitalNotifications($messenger);
        } elseif (!$messenger->isSalesperson()) {
            $this->synchronizeOperationalNotifications($messenger);
        }

        $notifications = $messenger->notifications()->latest()->limit(50)->get()->map(fn ($notification) => [
            'id' => $notification->id,
            'title' => (string) data_get($notification->data, 'title', 'Notificación'),
            'message' => (string) data_get($notification->data, 'message', ''),
            'kind' => (string) data_get($notification->data, 'kind', 'info'),
            'quotation_id' => data_get($notification->data, 'quotation_id'),
            'status' => data_get($notification->data, 'status'),
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
            'relative_time' => $notification->created_at?->locale('es')->diffForHumans(short: true) ?? '',
        ]);

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $messenger->unreadNotifications()->count(),
        ]);
    }

    public function readAll(Request $request): JsonResponse
    {
        abort_unless($request->user()->is_active && !$request->user()->isBlockedByOrganization(), 403);
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['message' => 'Notificaciones marcadas como leídas.']);
    }

    private function synchronizeHospitalNotifications(User $user): void
    {
        \App\Models\RequestQuotation::where('hospital_id', $user->hospital_id)->where('status', '<>', 'borrador')
            ->latest('id')->limit(30)->get()->each(function ($q) use ($user) {
                if ($user->notifications()->where('data->quotation_id', $q->id)->exists()) return;
                $this->createOnce($user, 'hospital-quote-'.$q->id, [
                    'quotation_id' => $q->id, 'status' => $q->status,
                    'title' => 'Cotización disponible', 'message' => 'La cotización '.$q->folio.' está disponible. Consulta Cotizaciones recibidas.', 'kind' => 'quotation_updated',
                ], $q->sent_at ?? $q->created_at);
            });
        DistributionDeliveryConfirmation::whereHas('schedule', fn ($q) => $q->where('hospital_id', $user->hospital_id))
            ->latest('delivered_at')->limit(30)->get()->each(fn ($c) => $this->createOnce($user, 'hospital-delivery-'.$c->id, [
                'title' => 'Entrega confirmada', 'message' => 'El mensajero confirmó la entrega en tu hospital. Consulta el detalle en Entregas.', 'kind' => 'delivery_completed',
            ], $c->delivered_at));
    }

    private function synchronizeOperationalNotifications(User $messenger): void
    {
        $routes = DistributionRoute::query()
            ->whereHas('messengers', fn ($query) => $query->whereKey($messenger->id))
            ->withCount(['deliverySchedules as pending_deliveries_count' => fn ($query) => $query
                ->where('status', 'sent')
                ->whereDoesntHave('confirmation')])
            ->get();

        foreach ($routes as $route) {
            $this->createOnce($messenger, "route-assigned-{$route->id}", [
                'title' => 'Ruta asignada',
                'message' => "Tomaste la ruta {$route->code} · {$route->name}.",
                'kind' => 'route_assigned',
            ], $route->updated_at);

            if ($route->pending_deliveries_count > 0) {
                $this->createOnce($messenger, "route-pending-{$route->id}-".now()->toDateString(), [
                    'title' => 'Entregas pendientes',
                    'message' => "La ruta {$route->code} tiene {$route->pending_deliveries_count} entrega(s) pendiente(s) por confirmar.",
                    'kind' => 'reminder',
                ]);
            }
        }

        DistributionDeliveryConfirmation::query()
            ->where('messenger_id', $messenger->id)
            ->with(['schedule:id,hospital_id,distribution_route_id', 'schedule.hospital:id,name', 'schedule.route:id,code'])
            ->latest('delivered_at')
            ->limit(30)
            ->get()
            ->each(function (DistributionDeliveryConfirmation $confirmation) use ($messenger): void {
                $hospital = $confirmation->schedule?->hospital?->name ?? 'el hospital';
                $route = $confirmation->schedule?->route?->code ?? 'la ruta';
                $this->createOnce($messenger, "delivery-completed-{$confirmation->id}", [
                    'title' => 'Entrega completada',
                    'message' => "Entrega en {$hospital} registrada correctamente para {$route}.",
                    'kind' => 'delivery_completed',
                ], $confirmation->delivered_at);
            });
    }

    private function createOnce(User $user, string $eventKey, array $data, $createdAt = null): void
    {
        if ($user->notifications()->where('data->event_key', $eventKey)->exists()) {
            return;
        }

        $timestamp = $createdAt ?: now();
        $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'mobile_operational',
            'data' => $data + ['event_key' => $eventKey],
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }
}
