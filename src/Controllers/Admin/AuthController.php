<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Http;
use App\Services\Auth;
use App\Services\Csrf;
use App\Services\RateLimiter;
use App\View;

final class AuthController
{
    private const MAX_INTENTOS = 5;
    private const VENTANA = 900; // 15 minutos

    public function form(?string $error = null): string
    {
        header('X-Robots-Tag: noindex, nofollow');
        if (Auth::estaLogueado()) {
            Http::redirigir('/admin');
        }
        Auth::iniciarSesion();
        return View::render('admin/login', ['titulo' => 'Ingresar', 'error' => $error, 'email' => Http::post('email') ?? ''], 'admin/layout');
    }

    public function login(): string
    {
        Csrf::verificar();
        $clave = 'login:' . Http::ip();
        if (RateLimiter::excedido($clave, self::MAX_INTENTOS)) {
            http_response_code(429);
            return $this->form('Demasiados intentos. Esperá 15 minutos.');
        }
        if (!Auth::login(Http::post('email') ?? '', $_POST['password'] ?? '')) {
            RateLimiter::registrar($clave, self::VENTANA);
            return $this->form('Email o contraseña incorrectos.');
        }
        RateLimiter::limpiar($clave);
        Http::redirigir('/admin');
    }

    public function logout(): string
    {
        Csrf::verificar();
        Auth::logout();
        Http::redirigir('/admin/login');
    }
}
