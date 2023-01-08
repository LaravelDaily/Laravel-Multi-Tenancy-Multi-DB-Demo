<?php

namespace App\Http\Controllers\Auth;

use App\Models\Tenant;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'subdomain' => ['required', 'alpha', 'unique:domains,domain'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $tenant = Tenant::create([
            'name' => $request->name,
        ]);
        $tenant->domains()->create([
            'domain' => $request->subdomain . '.' . config('tenancy.central_domains')[0],
        ]);
        $user->tenants()->attach($tenant->id);

        event(new Registered($user));

        Auth::login($user);

        return redirect('http://' . $request->subdomain . '.'. config('tenancy.central_domains')[0] .'/dashboard');
    }
}
