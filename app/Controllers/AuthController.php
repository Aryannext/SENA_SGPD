<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Auth;
use Core\Controller;
use App\Models\Usuario;

/**
 * Inicio y cierre de sesión.
 */
final class AuthController extends Controller
{
    public function login(): void
    {
        if (Auth::autenticado()) {
            $this->redirect('/dashboard');
            return;
        }

        $this->view('auth.login', [
            'pageTitle'  => 'Iniciar sesión',
            'error'      => $_SESSION['login_error'] ?? null,
            'usuario'    => $_SESSION['login_usuario'] ?? '',
            'sinUsuarios' => Usuario::contarTotal() === 0,
        ], 'layouts.blank');

        unset($_SESSION['login_error'], $_SESSION['login_usuario']);
    }

    public function loginPost(): void
    {
        $usuario = trim((string) ($_POST['usuario'] ?? ''));
        $clave   = (string) ($_POST['contrasena'] ?? '');

        $error = Auth::entrar($usuario, $clave);

        if ($error !== null) {
            // Se conserva el usuario escrito, nunca la contraseña.
            $_SESSION['login_error']   = $error;
            $_SESSION['login_usuario'] = $usuario;
            $this->redirect('/login');
            return;
        }

        $this->redirect(Auth::destinoTrasEntrar());
    }

    public function logout(): void
    {
        Auth::salir();
        header('Location: ' . \Core\App::url('/login'));
        exit;
    }
}
