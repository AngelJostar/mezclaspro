<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $users = User::select('id', 'name', 'lastname', 'username', 'is_active', 'hospital_id')
            ->with(['roles:name', 'hospital:id,name'])
            ->paginate(10);

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $hospitals = Hospital::all();
        $roles = Role::all();

        // ❌ ya no se selecciona lista por usuario
        return view('admin.users.create', compact('hospitals', 'roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'lastname'   => 'string|max:255',
            'username'   => 'required|string|max:255|unique:users,username',
            'password'   => 'required|string|confirmed',
            'hospital_id'=> 'required|exists:hospitals,id',
            'roles'      => 'nullable|array',
        ]);

        $user = User::create([
            'name'       => $request->name,
            'lastname'   => $request->lastname,
            'username'   => $request->username,
            'hospital_id'=> $request->hospital_id,
            'password'   => Hash::make($request->password),
            'is_active'  => $request->input('is_active', 1),
        ]);

        $user->roles()->sync($request->roles);

        session()->flash('swal', [
            'title' => "¡Bien hecho!",
            'text'  => "El usuario se ha creado con éxito.",
            'icon'  => "success"
        ]);

        return redirect()->route('admin.users.index');
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        $hospitals = Hospital::all();

        $authenticatedUser = Auth::user();
        $userRoleName = $authenticatedUser->roles->pluck('name')->first();

        // ❌ ya no se manda medicineLists
        return view('admin.users.edit', compact('user', 'hospitals', 'roles', 'userRoleName'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'       => 'string|max:255',
            'lastname'   => 'string|max:255',
            'username'   => 'string|max:255|unique:users,username,' . $user->id,
            'password'   => 'nullable|string|confirmed',
            'hospital_id'=> 'exists:hospitals,id',
            'roles'      => 'nullable|array',
        ]);

        $user->name = $request->name;
        $user->lastname = $request->lastname;
        $user->username = $request->username;
        $user->hospital_id = $request->hospital_id;


        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        $user->roles()->sync($request->roles);

        session()->flash('swal', [
            'title' => "¡Bien hecho!",
            'text'  => "El usuario se ha editado con éxito.",
            'icon'  => "success"
        ]);

        return redirect()->route('admin.users.index');
    }

    public function destroy(User $user)
    {
        //
    }
}
