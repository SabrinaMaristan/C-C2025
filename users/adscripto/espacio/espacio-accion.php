<?php
include('./../../../conexion.php');
$con = conectar_bd();
session_start();
header("Content-Type: application/json; charset=UTF-8");

$accion = strtolower(trim($_POST['accion'] ?? ''));
if ($accion === 'insertar') $accion = 'crear';
if ($accion === 'borrar') $accion = 'eliminar';

// ================================
// Helpers
// ================================
function tiposValidos(mysqli $con): array {
  $res = $con->query("SHOW COLUMNS FROM espacio LIKE 'tipo_espacio'");
  if (!$res) return [];
  preg_match_all("/'([^']+)'/", $res->fetch_assoc()['Type'], $out);
  return $out[1] ?? [];
}

function existeNombre(mysqli $con, string $nombre, ?int $excluirId = null): bool {
  if ($excluirId) {
    $q = $con->prepare("SELECT COUNT(*) FROM espacio WHERE nombre_espacio = ? AND id_espacio <> ?");
    $q->bind_param("si", $nombre, $excluirId);
  } else {
    $q = $con->prepare("SELECT COUNT(*) FROM espacio WHERE nombre_espacio = ?");
    $q->bind_param("s", $nombre);
  }
  $q->execute();
  $q->bind_result($c);
  $q->fetch();
  $q->close();
  return $c > 0;
}

function validarNombre(string $nombre): bool {
  return (bool)preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9 \-_]+$/u', $nombre);
}

function json_ok($msg, $extra = []) {
  echo json_encode(array_merge(["type" => "success", "message" => $msg], $extra));
  exit;
}

function json_err($msg) {
  echo json_encode(["type" => "error", "message" => $msg]);
  exit;
}

// ================================
// Configuración de rutas (IMÁGENES)
// ================================
$rutaUploads = $_SERVER['DOCUMENT_ROOT'] . '/CoffeeAndCode/C-C2025/uploads/';
$urlBaseUploads = 'https://' . $_SERVER['HTTP_HOST'] . '/CoffeeAndCode/C-C2025/uploads/';

if (!file_exists($rutaUploads)) mkdir($rutaUploads, 0777, true);

// ================================
// Acciones principales
// ================================
try {
  $tipos = tiposValidos($con);

  // CREAR
  if ($accion === 'crear') {
    $nombre = trim($_POST['nombre_espacio'] ?? '');
    $cap = (int)($_POST['capacidad_espacio'] ?? 0);
    $hist = $_POST['historial_espacio'] ?? '';
    $tipo = $_POST['tipo_espacio'] ?? '';

    if ($nombre === '' || !validarNombre($nombre)) json_err("Nombre inválido.");
    if (existeNombre($con, $nombre)) json_err("El nombre '$nombre' ya existe.");
    if ($cap < 1 || $cap > 100) json_err("Capacidad inválida (1-100).");
    if (!in_array($tipo, $tipos)) json_err("Tipo de espacio inválido.");

    // --- Imagen (opcional) ---
    $id_imagen = null;
    if (isset($_FILES['imagen_espacio']) && $_FILES['imagen_espacio']['error'] === UPLOAD_ERR_OK) {
      $nombreOriginal = $_FILES['imagen_espacio']['name'];
      $tmp = $_FILES['imagen_espacio']['tmp_name'];
      $ext = pathinfo($nombreOriginal, PATHINFO_EXTENSION);
      $nombreUnico = uniqid() . '.' . $ext;
      $destino = $rutaUploads . $nombreUnico;

      if (move_uploaded_file($tmp, $destino)) {
        $stmt = $con->prepare("INSERT INTO imagenes (nombre) VALUES (?)");
        $stmt->bind_param("s", $nombreUnico);
        $stmt->execute();
        $id_imagen = $stmt->insert_id;
      }
    }

    // Crear el espacio
    $q = $con->prepare("INSERT INTO espacio (nombre_espacio, capacidad_espacio, historial_espacio, tipo_espacio, id_imagen)
                        VALUES (?,?,?,?,?)");
    $q->bind_param("sissi", $nombre, $cap, $hist, $tipo, $id_imagen);
    if (!$q->execute()) json_err("Error al crear espacio: " . $con->error);
    json_ok("Espacio creado.", ["id_espacio" => $q->insert_id]);
  }

  // EDITAR
  if ($accion === 'editar') {
    $id = (int)($_POST['id_espacio'] ?? 0);
    $nombre = trim($_POST['nombre_espacio'] ?? '');
    $cap = (int)($_POST['capacidad_espacio'] ?? 0);
    $hist = $_POST['historial_espacio'] ?? '';
    $tipo = $_POST['tipo_espacio'] ?? '';

    if ($id <= 0) json_err("Falta el ID del espacio.");
    if ($nombre === '' || !validarNombre($nombre)) json_err("Nombre duplicado o inválido.");
    if (existeNombre($con, $nombre, $id)) json_err("Ya existe otro espacio con ese nombre.");
    if ($cap < 1 || $cap > 100) json_err("Capacidad inválida (1-100).");
    if (!in_array($tipo, $tipos)) json_err("Tipo de espacio inválido.");

    $id_imagen = null;
    if (isset($_FILES['imagen_espacio']) && $_FILES['imagen_espacio']['error'] === UPLOAD_ERR_OK) {
      $nombreOriginal = $_FILES['imagen_espacio']['name'];
      $tmp = $_FILES['imagen_espacio']['tmp_name'];
      $ext = pathinfo($nombreOriginal, PATHINFO_EXTENSION);
      $nombreUnico = uniqid() . '.' . $ext;
      $destino = $rutaUploads . $nombreUnico;

      if (move_uploaded_file($tmp, $destino)) {
        $stmt = $con->prepare("INSERT INTO imagenes (nombre) VALUES (?)");
        $stmt->bind_param("s", $nombreUnico);
        $stmt->execute();
        $id_imagen = $stmt->insert_id;
      }
    }

    if ($id_imagen) {
      $sql = "UPDATE espacio SET nombre_espacio=?, capacidad_espacio=?, historial_espacio=?, tipo_espacio=?, id_imagen=? WHERE id_espacio=?";
      $q = $con->prepare($sql);
      $q->bind_param("sissii", $nombre, $cap, $hist, $tipo, $id_imagen, $id);
    } else {
      $sql = "UPDATE espacio SET nombre_espacio=?, capacidad_espacio=?, historial_espacio=?, tipo_espacio=? WHERE id_espacio=?";
      $q = $con->prepare($sql);
      $q->bind_param("sissi", $nombre, $cap, $hist, $tipo, $id);
    }

    if (!$q->execute()) json_err("Error al actualizar: ".$q->error);
    json_ok("Espacio actualizado correctamente.");
  }

  // ELIMINAR
  if ($accion === 'eliminar') {
    $id = (int)($_POST['id_espacio'] ?? 0);
    if ($id <= 0) json_err("Falta el ID del espacio.");

    $q = $con->prepare("DELETE FROM espacio WHERE id_espacio=?");
    $q->bind_param("i", $id);
    if (!$q->execute()) json_err("No se pudo eliminar: ".$con->error);

    json_ok("Espacio eliminado.");
  }

  json_err("Acción desconocida.");
} catch(Throwable $e) {
  json_err("Error interno: ".$e->getMessage());
}
?>