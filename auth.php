<?php
/**
 * auth.php
 * Autenticación sencilla con usuarios PREDEFINIDOS (sin registro).
 *
 * Hay dos cuentas fijas para entrar al panel de pedidos:
 *   Usuario: admin      Clave: admin123   (rol: admin — puede todo)
 *   Usuario: vendedor   Clave: vende123   (rol: vendedor — solo ver pedidos)
 *
 * Para CAMBIAR las claves de forma segura, define la constante APP_USERS en tu
 * config.php (así no quedan en el repositorio). Genera el hash de una clave con:
 *   php -r "echo password_hash('tu-clave', PASSWORD_DEFAULT);"
 *
 * Ejemplo dentro de config.php:
 *   define('APP_USERS', [
 *     'admin' => ['pass' => '$2y$...', 'rol' => 'admin', 'nombre' => 'Administrador'],
 *   ]);
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Devuelve la lista de usuarios. Usa APP_USERS de config.php si existe;
 * de lo contrario, las cuentas por defecto (admin / vendedor).
 */
function app_users(): array
{
    if (defined('APP_USERS') && is_array(APP_USERS)) {
        return APP_USERS;
    }

    return [
        'admin' => [
            // clave: admin123
            'pass'   => '$2y$12$yAntUgHNFweu5ad5sCYTIes/Gd.Y8ltcgGVQoobpW9YDRXGIPhC.y',
            'rol'    => 'admin',
            'nombre' => 'Administrador',
        ],
        'vendedor' => [
            // clave: vende123
            'pass'   => '$2y$12$bzmPhJ3wR2uCvdxcyuG4y.gwHiGye0PQNwPusT08LMC.LZ5WgLY8a',
            'rol'    => 'vendedor',
            'nombre' => 'Vendedor',
        ],
    ];
}

/**
 * Intenta iniciar sesión. Devuelve true si las credenciales son válidas.
 */
function intentar_login(string $usuario, string $clave): bool
{
    $usuario = strtolower(trim($usuario));
    $usuarios = app_users();

    if (!isset($usuarios[$usuario])) {
        return false;
    }

    $registro = $usuarios[$usuario];
    $hash = $registro['pass'] ?? '';

    // Soporta hash (recomendado) o, por comodidad en local, texto plano.
    $ok = str_starts_with($hash, '$2y$') || str_starts_with($hash, '$argon')
        ? password_verify($clave, $hash)
        : hash_equals($hash, $clave);

    if (!$ok) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['auth'] = [
        'usuario' => $usuario,
        'nombre'  => $registro['nombre'] ?? $usuario,
        'rol'     => $registro['rol'] ?? 'vendedor',
    ];
    return true;
}

/** ¿Hay una sesión iniciada? */
function esta_autenticado(): bool
{
    return isset($_SESSION['auth']);
}

/** Datos del usuario actual (o null). */
function usuario_actual(): ?array
{
    return $_SESSION['auth'] ?? null;
}

/** ¿El usuario actual es admin? */
function es_admin(): bool
{
    return (usuario_actual()['rol'] ?? '') === 'admin';
}

/** Obliga a estar logueado; si no, redirige al login. */
function requiere_login(): void
{
    if (!esta_autenticado()) {
        header('Location: login.php');
        exit;
    }
}

/** Cierra la sesión. */
function cerrar_sesion(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
