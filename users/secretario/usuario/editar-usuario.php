<?php
include('./../../../conexion.php');
$conn = conectar_bd();

// valores de un formulario POST
$id_usuario = $_POST['id_usuario'];
$ci_usuario = $_POST['ci_usuario'];
$nombre_usuario = $_POST['nombre_usuario'];
$apellido_usuario = $_POST['apellido_usuario'];
$gmail_usuario = $_POST['gmail_usuario'];
$telefono_usuario = $_POST['telefono_usuario'];
$cargo_usuario = $_POST['cargo_usuario'];
$contrasenia_usuario = $_POST['contrasenia_usuario']; //if '' ? null : hash

// --- Validar duplicados (sin incluirse a sí mismo)
$sql_dup = "SELECT id_usuario, ci_usuario, gmail_usuario, telefono_usuario FROM usuario 
            WHERE (ci_usuario = ? OR gmail_usuario = ? OR telefono_usuario = ?) 
            AND id_usuario <> ?";
$stmt_dup = $conn->prepare($sql_dup);
$stmt_dup->bind_param("sssi", $ci_usuario, $gmail_usuario, $telefono_usuario, $id_usuario);
$stmt_dup->execute();
$res = $stmt_dup->get_result();
$dup = $res->fetch_assoc();

if ($dup) {
    $campo = '';
    if ($dup['ci_usuario'] == $ci_usuario) $campo = 'la cédula';
    elseif ($dup['gmail_usuario'] == $gmail_usuario) $campo = 'el email';
    elseif ($dup['telefono_usuario'] == $telefono_usuario) $campo = 'el teléfono';
    echo "<script>
        window.onload = function() {
          Swal.fire({icon:'error',title:'Dato duplicado',text:'Ya existe un usuario con $campo ingresado.',confirmButtonColor:'#d33'});
        }
      </script>";
    exit;
}

$hashed_password = password_hash($contrasenia_usuario, PASSWORD_BCRYPT);

if(empty($contrasenia_usuario)) {
    // Si está vacía, mantener la contraseña actual
    $sql_actual = "SELECT contrasenia_usuario FROM usuario WHERE id_usuario = ?";
    $stmt_actual = $conn->prepare($sql_actual);
    $stmt_actual->bind_param("i", $id_usuario);
    $stmt_actual->execute();
    $resultado = $stmt_actual->get_result();
    $usuario_actual = $resultado->fetch_assoc();
    $hashed_password = $usuario_actual['contrasenia_usuario'];
} else {
    // Si NO está vacía, hashear la nueva contraseña
    $hashed_password = password_hash($contrasenia_usuario, PASSWORD_BCRYPT);
}

// Traemos el cargo anterior antes de actualizar
$sql_prev = "SELECT cargo_usuario FROM usuario WHERE id_usuario = ?";
$stmt_prev = $conn->prepare($sql_prev);
$stmt_prev->bind_param("i", $id_usuario);
$stmt_prev->execute();
$result_prev = $stmt_prev->get_result();
$prev = $result_prev->fetch_assoc();
$cargo_anterior = $prev['cargo_usuario'] ?? null;
$stmt_prev->close();

//Actualizamos la tabla usuario
$stmt = $conn->prepare("UPDATE usuario
    SET ci_usuario = ?,
        nombre_usuario = ?,
        apellido_usuario = ?,
        gmail_usuario = ?,
        telefono_usuario = ?,
        cargo_usuario = ?,
        contrasenia_usuario = ?
    WHERE id_usuario = ?");
$stmt->bind_param("issssssi", $ci_usuario, $nombre_usuario, $apellido_usuario, $gmail_usuario, $telefono_usuario, $cargo_usuario, $hashed_password, $id_usuario);
$stmt->execute();
$stmt->close();

//Si cambió de cargo, eliminamos de la tabla anterior e insertamos en la nueva
if ($cargo_anterior !== $cargo_usuario) {
    // Eliminamos de la tabla anterior
    switch ($cargo_anterior) {
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

    // Insertamos en la nueva tabla
    switch ($cargo_usuario) {
        case "Docente":
            $conn->query("INSERT INTO docente (id_usuario) VALUES ($id_usuario)");
            break;
        case "Adscripto":
            $conn->query("INSERT INTO adscripto (id_usuario) VALUES ($id_usuario)");
            break;
        case "Secretario":
            $conn->query("INSERT INTO secretario (id_usuario) VALUES ($id_usuario)");
            break;
    }
}

// Redirigir
header("Location: ./secretario-usuario.php");
exit;
?>
