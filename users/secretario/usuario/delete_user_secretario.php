<?php
include('./../../../conexion.php');
$conn = conectar_bd();

session_start();

// Verificamos que haya sesión activa
if (!isset($_SESSION['id_usuario'])) {
  header('Location: ./../../../index.php');
  exit;
}

// ID del usuario logueado (secretario)
$id_usuario_sesion = $_SESSION['id_usuario'];

// ID del usuario que se va a consultar (por GET)
$id_usuario_get = $_GET['id_usuario'] ?? null;
if (!$id_usuario_get) {
  echo "No se proporcionó un ID de usuario.";
  exit;
}

// -----------------------------------------------------------------------------
//  Eliminar registros relacionados según el cargo
// -----------------------------------------------------------------------------
$cargoQuery = $conn->prepare("SELECT cargo_usuario FROM usuario WHERE id_usuario = ?");
$cargoQuery->bind_param("i", $id_usuario);
$cargoQuery->execute();
$resCargo = $cargoQuery->get_result();
$cargo = $resCargo->fetch_assoc()['cargo_usuario'] ?? null;
$cargoQuery->close();

if ($cargo) {
    switch ($cargo) {
        case "Docente":
            $conn->query("DELETE FROM docente WHERE id_usuario = $id_usuario");
            break;
        case "Adscripto":
            $conn->query("DELETE FROM adscripto WHERE id_usuario = $id_usuario");
            break;
        case "Secretario":
            $conn->query("DELETE FROM secretario WHERE id_usuario = $id_usuario");
            break;
    }
}

// -----------------------------------------------------------------------------
//  Eliminar el usuario principal
// -----------------------------------------------------------------------------
$sql = "DELETE FROM usuario WHERE id_usuario = ?";
$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $id_usuario);
    $execute = mysqli_stmt_execute($stmt);

    if ($execute) {
        // Eliminación exitosa → redirige con mensaje
        header("Location: ./secretario-usuario.php?msg=EliminacionExitosa");
        exit();
    } else {
        echo "Error al eliminar el usuario: " . mysqli_error($conn);
    }

    mysqli_stmt_close($stmt);
} else {
    echo "Error al preparar la consulta: " . mysqli_error($conn);
}

mysqli_close($conn);
?>