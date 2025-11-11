<?php
include('./../../../conexion.php');

session_start(); // Inicia o continúa la sesión
$id_usuario = $_SESSION['id_usuario'] ?? null;
if (!$id_usuario) {
  header('Location: ./../../../index.php');
  exit;
}
// Recuperamos el id_secretario de la sesión del usuario logueado.
// Si no está definido, lo dejamos como null para evitar errores.
$id_secretario = $_SESSION['id_secretario'] ?? null;

$con = conectar_bd();
header("Content-Type: application/json");

// Recibir datos POST
$accionHorario = $_POST['accionHorario'] ?? '';
$id_horario = $_POST['id_horario_clase'] ?? null;
$hora_inicio = $_POST['hora_inicio'] ?? '';
$hora_fin = $_POST['hora_fin'] ?? '';

try {
    if($accionHorario === 'insertar') {
        // Validar datos antes de insertar
        if( empty($hora_inicio) || empty($hora_fin) ) {
            throw new Exception("Faltan datos para insertar el horario");
        }
    
        // verificar si ya existe un horario con la misma hora de inicio y fin
        $check = $con->prepare("SELECT COUNT(*) FROM horario_clase WHERE hora_inicio = ? AND hora_fin = ?");
        $check->bind_param("ss", $hora_inicio, $hora_fin);
        $check->execute();
        $check->bind_result($count);
        $check->fetch();
        $check->close();

        if ($count > 0) {
            echo json_encode(["type"=>"error","message"=>"Ya existe un horario con la misma hora de inicio y fin"]);
            exit; // evita que se siga ejecutando el código
        }


        $stmt = $con->prepare("INSERT INTO horario_clase (hora_inicio, hora_fin, id_secretario) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $hora_inicio, $hora_fin, $id_secretario);
        if(!$stmt->execute()) throw new Exception($stmt->error);
        
        echo json_encode(["type"=>"success","message"=>"Horario agregado correctamente"]);


    } elseif($accionHorario === 'editar') {

        if(empty($id_horario)) throw new Exception("ID de horario no especificado");

        //validar duplicados al editar (excepto el mismo id: <>)
        $check = $con->prepare("SELECT COUNT(*) FROM horario_clase WHERE hora_inicio = ? AND hora_fin = ? AND id_horario_clase <> ?");
        $check->bind_param("ssi", $hora_inicio, $hora_fin, $id_horario);
        $check->execute();
        $check->bind_result($count);
        $check->fetch();
        $check->close();

        if ($count > 0) {
            echo json_encode(["type"=>"error","message"=>"Ya existe un horario con la misma hora de inicio y fin"]);
            exit; // evita que se siga ejecutando el código
        }

        $stmt = $con->prepare("UPDATE horario_clase SET hora_inicio=?, hora_fin=? WHERE id_horario_clase=?");
        $stmt->bind_param("ssi", $hora_inicio, $hora_fin, $id_horario);
        if(!$stmt->execute()) throw new Exception($stmt->error);

        echo json_encode(["type"=>"success","message"=>"Horario actualizado correctamente"]);

    } elseif($accionHorario === 'eliminar') {
        if(empty($id_horario)) throw new Exception("ID de horario no especificado");
        $stmt = $con->prepare("DELETE FROM horario_clase WHERE id_horario_clase=?");
        $stmt->bind_param("i", $id_horario);
        if(!$stmt->execute()) throw new Exception($stmt->error);

        echo json_encode(["type"=>"success","message"=>"Horario eliminado correctamente"]);

    } else {
        throw new Exception("Acción no reconocida");
    }
} catch(Exception $e){
    echo json_encode(["type"=>"error","message"=>"Error: ".$e->getMessage()]);
}
