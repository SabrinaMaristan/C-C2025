<?php
// ============================================================
//      Controlar la sesión y cerrar si hay inactividad
// ============================================================

// Iniciamos o reanudamos la sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ------------------------------------------------------------
// CONFIGURACIÓN: tiempo máximo de inactividad (en segundos)
// ------------------------------------------------------------
$tiempoMaxInactividad = 15 * 60; // 15 minutos = 900 segundos

// ------------------------------------------------------------
// 1. Si no existe la marca de actividad, la creamos
// ------------------------------------------------------------
if (!isset($_SESSION['last_activity'])) {
    $_SESSION['last_activity'] = time();
}

// ------------------------------------------------------------
// 2. Verificamos si el usuario superó el tiempo máximo
// ------------------------------------------------------------
if (time() - $_SESSION['last_activity'] > $tiempoMaxInactividad) {
    // Limpiar variables de sesión
    $_SESSION = [];

    // Eliminar cookie de sesión si existe
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Destruir la sesión
    session_unset();
    session_destroy();

    // Redirigir al index con parámetro indicando expiración
    header("Location: /CoffeeAndCode/C-C2025/index.php?timeout=1");
    exit;
}

// ------------------------------------------------------------
// 3. Actualizamos la marca de tiempo de última actividad
// ------------------------------------------------------------
$_SESSION['last_activity'] = time();
?>
