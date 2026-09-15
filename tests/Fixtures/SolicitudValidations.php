<?php

namespace Tests\Fixtures;

use App\Models\User;
use App\View\Components\AdminLayout;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\Component;
use Spatie\Permission\Models\Role;

class SolicitudValidations extends Component
{
    public static function boot(): User
    {
        if (DB::getDriverName() !== 'sqlite' || DB::connection()->getDatabaseName() !== ':memory:') {
            throw new \RuntimeException('Validation fixtures require an in-memory database.');
        }
        (require database_path('migrations/2024_04_11_013606_create_permission_tables.php'))->up();
        $user = (new User)->forceFill(['id' => 1, 'name' => 'Usuario de prueba', 'is_active' => true]);
        $user->setRelation('roles', new Collection([
            (new Role)->forceFill(['name' => 'Super Admin', 'guard_name' => 'web']),
        ]));
        auth()->setUser($user);
        app()->bind(AdminLayout::class, fn () => new self);
        view()->share('errors', new ViewErrorBag);

        return $user;
    }

    public function render(): string
    {
        return '<main class="admin-page"><div class="admin-content">{{ $slot }}</div></main>';
    }
}
