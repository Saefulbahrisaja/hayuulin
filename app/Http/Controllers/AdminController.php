<?php

namespace App\Http\Controllers;

use App\Models\Accommodation;
use App\Models\Category;
use App\Models\Destination;
use App\Models\TransportOption;
use App\Models\TransportRoute;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    public function index()
    {
        $stats = [
            'destinations' => Destination::count(),
            'categories' => Category::count(),
            'transport_options' => TransportOption::count(),
            'transport_routes' => TransportRoute::count(),
            'accommodations' => Accommodation::count(),
            'users' => User::count(),
            'trips' => Trip::count(),
        ];
        $users = User::query()->latest()->paginate(12, ['*'], 'users_page');
        $recentTrips = Trip::with('user')->latest()->limit(8)->get();

        return view('admin.dashboard', compact('stats', 'users', 'recentTrips'));
    }

    public function createUser()
    {
        return view('admin.user-form', ['user' => null]);
    }

    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
            'is_admin' => ['sometimes', 'boolean'],
        ]);
        $user = User::create(collect($validated)->only(['name', 'email', 'password'])->all());
        $user->is_admin = $request->boolean('is_admin');
        $user->save();

        return redirect()->route('admin.dashboard')->with('status', 'Pengguna berhasil dibuat.');
    }

    public function editUser(User $user)
    {
        return view('admin.user-form', compact('user'));
    }

    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:12', 'confirmed'],
            'is_admin' => ['sometimes', 'boolean'],
        ]);
        $isAdmin = $request->boolean('is_admin');
        if ($user->is_admin && ! $isAdmin && User::where('is_admin', true)->count() <= 1) {
            throw ValidationException::withMessages(['is_admin' => 'Administrator terakhir tidak dapat dicabut aksesnya.']);
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->is_admin = $isAdmin;
        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }
        $user->save();

        return redirect()->route('admin.dashboard')->with('status', 'Data pengguna berhasil diperbarui.');
    }

    public function toggleAdmin(User $user)
    {
        if ($user->is_admin && User::where('is_admin', true)->count() <= 1) {
            throw ValidationException::withMessages(['user' => 'Administrator terakhir tidak dapat dicabut aksesnya.']);
        }

        $user->is_admin = ! $user->is_admin;
        $user->save();

        return back()->with('status', 'Akses administrator diperbarui.');
    }

    public function destroyUser(Request $request, User $user)
    {
        abort_if($request->user()->is($user), 422, 'Akun yang sedang digunakan tidak dapat dihapus.');

        if ($user->is_admin && User::where('is_admin', true)->count() <= 1) {
            throw ValidationException::withMessages(['user' => 'Administrator terakhir tidak dapat dihapus.']);
        }

        $user->delete();

        return back()->with('status', 'Pengguna dan itinerary miliknya berhasil dihapus.');
    }
}
