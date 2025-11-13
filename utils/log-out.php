<?php
session_start(); //inicia o reanuda sesion existente
session_unset(); //elimina variables almacenadas en $_SESSION
session_destroy();//elimina completamente la sesión del servidor.

// Construir ruta completa a la raíz del proyecto
$host = $_SERVER['HTTP_HOST']; // dbitsp.tailff9876.ts.net (Detectar el dominio / servidor)
$path = dirname($_SERVER['PHP_SELF']); // /CoffeeAndCode/C-C2025/users/docente/reserva (Detectar la carpeta donde está el archivo actual)
// Subir hasta la raíz del proyecto
$basePath = '/CoffeeAndCode/C-C2025/';

// Redirigir correctamente
header("Location: https://$host$basePath" . "index.php");
exit(); // se detiene la ejecucion
?>
