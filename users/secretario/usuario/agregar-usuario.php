<?php
include('./../../../conexion.php');
$conn = conectar_bd();

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = $_POST['id_usuario'] ?? null;
    $ci_usuario = $_POST['ci_usuario'];
    $nombre_usuario = $_POST['nombre_usuario'];
    $apellido_usuario = $_POST['apellido_usuario'];
    $gmail_usuario = $_POST['gmail_usuario'];
    $telefono_usuario = $_POST['telefono_usuario'];
    $cargo_usuario = $_POST['cargo_usuario'];
    $contrasenia_usuario = trim($_POST['contrasenia_usuario']);

    $validacion_result = validaciones($conn, $ci_usuario, $nombre_usuario, $apellido_usuario, $gmail_usuario,
                                        $telefono_usuario, $contrasenia_usuario, $cargo_usuario);

    if ($validacion_result === true) {
        // Hashear la contraseña
        $hashed_password = password_hash($contrasenia_usuario, PASSWORD_BCRYPT);

        $sql = "INSERT INTO usuario
            (ci_usuario, nombre_usuario, apellido_usuario, gmail_usuario, telefono_usuario, cargo_usuario, contrasenia_usuario)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssssss", $ci_usuario, $nombre_usuario, $apellido_usuario, $gmail_usuario, $telefono_usuario, $cargo_usuario, $hashed_password);
        $success = mysqli_stmt_execute($stmt);
        $idUsuario = $conn->insert_id;

        if ($success) {
            switch ($cargo_usuario) {
                case "Docente":
                    $stmt_doc = $conn->prepare("INSERT INTO docente (id_usuario) VALUES (?)");
                    $stmt_doc->bind_param("i", $idUsuario);
                    $stmt_doc->execute();
                    break;
                case "Adscripto":
                    $stmt_ads = $conn->prepare("INSERT INTO adscripto (id_usuario) VALUES (?)");
                    $stmt_ads->bind_param("i", $idUsuario);
                    $stmt_ads->execute();
                    break;
                case "Secretario":
                    $stmt_sec = $conn->prepare("INSERT INTO secretario (id_usuario) VALUES (?)");
                    $stmt_sec->bind_param("i", $idUsuario);
                    $stmt_sec->execute();
                    break;
            }
            header("Location: ./secretario-usuario.php?msg=InsercionExitosa");
            exit;
        } else {
            echo "Error en la inserción: " . mysqli_error($conn);
        }
    }
}
mysqli_close($conn);

// ==================== FUNCIONES ====================

function validaciones($conn, $ci_usuario, $nombre_usuario, $apellido_usuario,
                     $gmail_usuario, $telefono_usuario, $contrasenia_usuario,
                     $cargo_usuario) {
    if(empty($ci_usuario) || empty($nombre_usuario)|| empty($apellido_usuario) || 
       empty($gmail_usuario) || empty($telefono_usuario) || empty($cargo_usuario) ||
       empty($contrasenia_usuario)) {
        header("Location: ./secretario-usuario.php?error=CamposVacios&abrirModal=true");
        exit;
    } else if(!preg_match("/^[0-9]{8}$/", $ci_usuario)) {
        header("Location: ./secretario-usuario.php?error=CiInvalida&abrirModal=true");
        exit;
    } else if (!preg_match("/^[0-9]{9}$/", $telefono_usuario)) {
        header("Location: ./secretario-usuario.php?error=TelefonoInvalido&abrirModal=true");
        exit;
    } else if (!preg_match("/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9]).{8,20}$/", $contrasenia_usuario)) {
        header("Location: ./secretario-usuario.php?error=ContraseniaInvalida&abrirModal=true");
        exit;
    }

    $duplicado = consultarBD($conn, $ci_usuario, $gmail_usuario, $telefono_usuario);
    if ($duplicado) {
        header("Location: ./secretario-usuario.php?error=Duplicado&campo={$duplicado}&abrirModal=true");
        exit;
    }

    return true;
}

function consultarBD($conn, $ci_usuario, $gmail_usuario, $telefono_usuario) {
    $query = "SELECT ci_usuario, gmail_usuario, telefono_usuario FROM usuario 
              WHERE ci_usuario = ? OR gmail_usuario = ? OR telefono_usuario = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "sss", $ci_usuario, $gmail_usuario, $telefono_usuario);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);

    if ($row) {
        if ($row['ci_usuario'] == $ci_usuario) return 'cedula';
        if ($row['gmail_usuario'] == $gmail_usuario) return 'email';
        if ($row['telefono_usuario'] == $telefono_usuario) return 'telefono';
    }
    return null;
}
?>