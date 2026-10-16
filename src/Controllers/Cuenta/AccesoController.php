<?php

declare(strict_types=1);

namespace App\Controllers\Cuenta;

use App\Db;
use App\Http;
use App\Seo\Meta;
use App\Services\Bloqueos;
use App\Services\Captcha;
use App\Services\CuentaAuth;
use App\Services\Csrf;
use App\Services\Cuentas;
use App\Services\FormToken;
use App\Services\RateLimiter;
use App\View;

/**
 * Registro, ingreso con email y contraseña, y recuperación con link por email.
 * Para no revelar qué emails tienen cuenta, los mensajes de error y de envío son siempre iguales.
 */
final class AccesoController
{
    public function ingresarForm(): string
    {
        if (CuentaAuth::cuenta() !== null) {
            Http::redirigir('/cuenta');
        }
        return $this->render('cuenta/ingresar', 'Entrar a tu cuenta', ['v' => [], 'err' => []]);
    }

    public function ingresar(): string
    {
        $v = ['email' => Http::post('email')];
        if (!$this->envioValido(1)) {
            return $this->render('cuenta/ingresar', 'Entrar a tu cuenta', ['v' => $v, 'err' => ['general' => 'El formulario venció. Probá de nuevo.']]);
        }
        $email = mb_strtolower(trim((string) Http::post('email')));
        $claveIp = 'login-cuenta:' . Http::ip();
        $claveEmail = 'login-cuenta-email:' . $email;
        if (RateLimiter::excedido($claveIp, 10) || RateLimiter::excedido($claveEmail, 10)) {
            http_response_code(429);
            return $this->render('cuenta/ingresar', 'Entrar a tu cuenta', ['v' => $v, 'err' => ['general' => 'Demasiados intentos. Esperá 15 minutos o recuperá tu contraseña.']]);
        }

        $resultado = CuentaAuth::loginConClave($email, (string) ($_POST['clave'] ?? ''));
        if ($resultado === CuentaAuth::LOGIN_INCORRECTO) {
            RateLimiter::registrar($claveIp, 900);
            RateLimiter::registrar($claveEmail, 900);
            return $this->render('cuenta/ingresar', 'Entrar a tu cuenta', ['v' => $v, 'err' => ['general' => 'Email o contraseña incorrectos. Si nunca elegiste una contraseña, usá «¿Olvidaste tu contraseña?».']]);
        }
        if ($resultado === CuentaAuth::LOGIN_SIN_CONFIRMAR) {
            return $this->enviado($email, 'confirmar');
        }
        RateLimiter::limpiar($claveIp);
        RateLimiter::limpiar($claveEmail);
        Http::redirigir('/cuenta', 303);
    }

    public function recuperarForm(): string
    {
        return $this->render('cuenta/recuperar', 'Recuperar contraseña', ['v' => [], 'err' => []]);
    }

    /** Manda un link de un solo uso para entrar y elegir una contraseña nueva. */
    public function recuperar(): string
    {
        if (!$this->envioValido(1)) {
            return $this->render('cuenta/recuperar', 'Recuperar contraseña', ['v' => $_POST, 'err' => ['general' => 'El formulario venció. Probá de nuevo.']]);
        }
        $email = mb_strtolower(trim((string) Http::post('email')));
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return $this->render('cuenta/recuperar', 'Recuperar contraseña', ['v' => $_POST, 'err' => ['email' => 'Revisá el email.']]);
        }
        if ($this->limiteExcedido('cuenta-link:' . Http::ip(), 10, 3600)) {
            return $this->render('cuenta/recuperar', 'Recuperar contraseña', ['v' => $_POST, 'err' => ['general' => 'Pediste muchos links seguidos. Esperá un rato.']]);
        }
        $id = Db::valor("SELECT id FROM cuentas WHERE email = ? AND estado = 'activa'", [$email]);
        if ($id !== null) {
            CuentaAuth::enviarLink((int) $id);
        }
        return $this->enviado($email, 'recuperar');
    }

    public function registroForm(): string
    {
        if (CuentaAuth::cuenta() !== null) {
            Http::redirigir('/cuenta');
        }
        return $this->render('cuenta/registro', 'Crear una cuenta', ['v' => ['tipo' => Http::query('tipo') === 'inmobiliaria' ? 'inmobiliaria' : 'particular'], 'err' => []]);
    }

    public function registro(): string
    {
        if (!$this->envioValido(3)) {
            return $this->render('cuenta/registro', 'Crear una cuenta', ['v' => $_POST, 'err' => ['general' => 'El formulario venció o se envió demasiado rápido. Probá de nuevo.']]);
        }
        if (!Captcha::valido()) {
            return $this->render('cuenta/registro', 'Crear una cuenta', ['v' => $_POST, 'err' => ['general' => 'Completá la verificación de seguridad y volvé a enviar.']]);
        }
        [$datos, $err] = Cuentas::validarRegistro($_POST);
        if ($err) {
            http_response_code(422);
            return $this->render('cuenta/registro', 'Crear una cuenta', ['v' => $_POST, 'err' => $err]);
        }
        if ($this->limiteExcedido('registro:' . Http::ip(), 5, 86400)) {
            return $this->render('cuenta/registro', 'Crear una cuenta', ['v' => $_POST, 'err' => ['general' => 'Se crearon muchas cuentas desde tu conexión. Probá mañana.']]);
        }
        if (Bloqueos::coincide($datos['whatsapp'], $datos['email'], Http::ip())) {
            return $this->enviado($datos['email'], 'confirmar'); // se finge éxito
        }
        $existente = Db::fila('SELECT id, estado FROM cuentas WHERE email = ?', [$datos['email']]);
        if ($existente === null) {
            CuentaAuth::enviarLink(Cuentas::crear($datos, Http::ip()), true);
        } elseif ($existente['estado'] === 'activa') {
            CuentaAuth::enviarLink((int) $existente['id']); // ya tenía cuenta: no se toca su contraseña, recibe el link para entrar
        }
        return $this->enviado($datos['email'], 'confirmar');
    }

    public function entrarForm(string $token): string
    {
        $fila = CuentaAuth::tokenVigente($token);
        if ($fila === null) {
            http_response_code(404);
        }
        return $this->render('cuenta/entrar', 'Entrar', ['valido' => $fila !== null, 'nombre' => $fila['nombre'] ?? null]);
    }

    /** El GET solo muestra un botón: los filtros de correo abren los links y gastarían el token. */
    public function entrar(string $token): string
    {
        if (!CuentaAuth::entrar($token)) {
            http_response_code(404);
            return $this->render('cuenta/entrar', 'Entrar', ['valido' => false, 'nombre' => null]);
        }
        $cuenta = CuentaAuth::cuenta();
        // Recuperación de contraseña o cuenta vieja sin contraseña: a elegirla.
        if ($cuenta !== null && $cuenta['password_hash'] === null) {
            \App\Services\Flash::ok('Elegí una contraseña para entrar la próxima vez sin esperar un email.');
            Http::redirigir('/cuenta#clave', 303);
        }
        // Si todavía no publicó nada (vino a publicar), directo al formulario.
        $sinAvisos = $cuenta !== null && (int) Db::valor('SELECT COUNT(*) FROM avisos WHERE cuenta_id = ?', [$cuenta['id']]) === 0;
        if (!$sinAvisos) {
            \App\Services\Flash::ok('Entraste con el link del email. Si te olvidaste la contraseña, elegí una nueva en «Contraseña».');
        }
        Http::redirigir($sinAvisos && Cuentas::puedePublicar($cuenta)[0] ? '/cuenta/avisos/nuevo' : '/cuenta', 303);
    }

    public function salir(): string
    {
        Csrf::verificar();
        CuentaAuth::salir();
        Http::redirigir('/', 303);
    }

    /** @param string $motivo confirmar | recuperar */
    private function enviado(string $email, string $motivo): string
    {
        return $this->render('cuenta/enviado', 'Revisá tu email', ['email' => $email, 'motivo' => $motivo, 'minutos' => CuentaAuth::minutosLink()]);
    }

    private function envioValido(int $segundosMinimos): bool
    {
        return (string) Http::post('sitio_web') === ''
            && FormToken::valido((string) Http::post('_t'), 'cuenta', $segundosMinimos, 3 * 3600);
    }

    private function limiteExcedido(string $clave, int $maximo, int $ventana): bool
    {
        if (RateLimiter::excedido($clave, $maximo)) {
            http_response_code(429);
            return true;
        }
        RateLimiter::registrar($clave, $ventana);
        return false;
    }

    /** @param array<string, mixed> $datos */
    private function render(string $plantilla, string $titulo, array $datos): string
    {
        header('Cache-Control: private, no-store');
        header('X-Robots-Tag: noindex, nofollow');
        return View::render($plantilla, $datos + [
            'meta' => new Meta(title: $titulo, description: 'Cuenta de anunciante en ' . app_nombre() . '.', indexar: false),
            'token' => FormToken::generar('cuenta'),
        ]);
    }
}
