<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class SsoController extends Controller
{
    public function index()
    {
        return view('auth.login');
    }

    public function getLogin(Request $request): RedirectResponse
    {
        $request->session()->put('state', $state = Str::random(40));

        $query = http_build_query([
            'client_id' => config('sso.client_id'),
            'redirect_uri' => config('sso.redirect_uri'),
            'response_type' => 'code',
            'scope' => 'view-user',
            'state' => $state,
            // 'prompt' => '', // "none", "consent", or "login"
        ]);

        return redirect(config('sso.base_url') . '/oauth/authorize?' . $query);
    }

    /**
     * @throws Throwable
     */
    public function getCallback(Request $request): RedirectResponse
    {
        $state = $request->session()->pull('state');

        throw_unless(
            strlen($state) > 0 && $state === $request->state,
            InvalidArgumentException::class,
            'Invalid state value.'
        );

        $response = Http::asForm()->post(config('sso.base_url') . '/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => config('sso.client_id'),
            'client_secret' => config('sso.client_secret'),
            'redirect_uri' => config('sso.redirect_uri'),
            'code' => $request->code,
        ]);
        if (isset($response->json()['access_token'])) {
            return redirect(route('connect'))->with('access_token', $response->json()['access_token']);
        } else {
            return redirect(route('login'));
        }
    }

    public function getConnect(Request $request): RedirectResponse
    {
        $access_token = $request->session()->get('access_token');
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $access_token,
        ])->get(config('sso.base_url') . '/api/user');
        $user = User::updateOrCreate([
            'sso_bps_id' => $response->json()['nip_bps'],
        ], [
            'name' => $response->json()['name'],
            'email' => $response->json()['email'],
        ]);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}