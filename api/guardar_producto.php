<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

require_once("/home/adminsys/conexion.php");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function responder($success, $message) {
  echo json_encode(["success" => $success, "message" => $message]);
  exit;
}

try {
  $conexion = obtenerConexion();
  if ($conexion->connect_error) {
    responder(false, "Error de conexión a la base de datos.");
  }

  // Recolección de campos
  $Id = intval($_POST["Id"] ?? 0);
  $IdUsuarioGs = intval($_POST["IdUsuarioGs"] ?? 0);
  $Codigo = trim($_POST["Codigo"] ?? "");
  $Descripcion = trim($_POST["Descripcion"] ?? "");
  $ClaveUnidad = trim($_POST["ClaveUnidad"] ?? "");
  $ClaveProdServ = trim($_POST["ClaveProdServ"] ?? "");
  $IdImpuestoTipo = intval($_POST["Impuesto"] ?? 0);
  $IdObjetoImpuesto = intval($_POST["ObjetoImpuesto"] ?? 0);
  $IdTipo = intval($_POST["Tipo"] ?? 0);
  $IdClasificacion = intval($_POST["Clasificacion"] ?? 0);
  $PrecioVentaBase = floatval($_POST["PrecioUnitario"] ?? 0);
  $IdUnidadBase = intval($_POST["Unidad"] ?? 0);
  $Caracteristicas = json_decode($_POST["Caracteristicas"] ?? "[]");

file_put_contents("php://stderr", json_encode($_POST, JSON_PRETTY_PRINT));
    
    if (
      $IdUsuarioGs <= 0 ||
      $Codigo === "" ||
      $Descripcion === "" ||
      $ClaveUnidad === "" ||
      $ClaveProdServ === "" ||
      $IdUnidadBase <= 0
    ) {
      responder(false, "Faltan datos obligatorios.");
    }

  // Validación de código único por usuario
  $sqlVal = "SELECT Id FROM Frm_Ct_Productos WHERE Codigo = ? AND IdUsuarioGs = ? AND Id != ?";
  $stmtVal = $conexion->prepare($sqlVal);
  $stmtVal->bind_param("sii", $Codigo, $IdUsuarioGs, $Id);
  $stmtVal->execute();
  $stmtVal->store_result();
  if ($stmtVal->num_rows > 0) {
    responder(false, "Ya existe un producto con ese código.");
  }

  // Manejo de imagen
  $FotoProducto = null;
  if (isset($_FILES["FotoProducto"]) && $_FILES["FotoProducto"]["error"] === UPLOAD_ERR_OK) {
    $nombreTmp = $_FILES["FotoProducto"]["tmp_name"];
    $ext = pathinfo($_FILES["FotoProducto"]["name"], PATHINFO_EXTENSION);
    $nombreFinal = uniqid("img_") . "." . $ext;
    $rutaDestino = __DIR__ . "/img_productos/" . $nombreFinal;

    if (!move_uploaded_file($nombreTmp, $rutaDestino)) {
      responder(false, "No se pudo guardar la imagen.");
    }

    $FotoProducto = $nombreFinal;
  }

  if ($Id === 0) {
    // INSERTAR
    $sql = "INSERT INTO Frm_Ct_Productos
      (IdUsuarioGs, Codigo, Descripcion, ClaveUnidad, ClaveProdServ, IdImpuestoTipo, IdObjetoImpuesto, IdTipo, IdClasificacion, PrecioVentaBase, IdUnidadBase, FotoProducto)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("issssiiiiids", $IdUsuarioGs, $Codigo, $Descripcion, $ClaveUnidad, $ClaveProdServ,
      $IdImpuestoTipo, $IdObjetoImpuesto, $IdTipo, $IdClasificacion, $PrecioVentaBase, $IdUnidadBase, $FotoProducto);
    $stmt->execute();
    $Id = $stmt->insert_id;
  } else {
    // ACTUALIZAR
    $sql = "UPDATE Frm_Ct_Productos SET
      Codigo = ?, Descripcion = ?, ClaveUnidad = ?, ClaveProdServ = ?,
      IdImpuestoTipo = ?, IdObjetoImpuesto = ?, IdTipo = ?, IdClasificacion = ?,
      PrecioVentaBase = ?, IdUnidadBase = ?";
    if ($FotoProducto !== null) {
      $sql .= ", FotoProducto = ?";
    }
    $sql .= " WHERE Id = ? AND IdUsuarioGs = ?";

    $stmt = $conexion->prepare($sql);
    if ($FotoProducto !== null) {
      $stmt->bind_param("ssssiiiidsisii", $Codigo, $Descripcion, $ClaveUnidad, $ClaveProdServ,
        $IdImpuestoTipo, $IdObjetoImpuesto, $IdTipo, $IdClasificacion,
        $PrecioVentaBase, $IdUnidadBase, $FotoProducto, $Id, $IdUsuarioGs);
    } else {
      $stmt->bind_param("ssssiiiidsii", $Codigo, $Descripcion, $ClaveUnidad, $ClaveProdServ,
        $IdImpuestoTipo, $IdObjetoImpuesto, $IdTipo, $IdClasificacion,
        $PrecioVentaBase, $IdUnidadBase, $Id, $IdUsuarioGs);
    }
    $stmt->execute();
  }

  // Características
  $conexion->query("DELETE FROM Frm_Ct_Productos_CaractItems WHERE IdProducto = $Id");
  if (is_array($Caracteristicas)) {
    $stmt = $conexion->prepare("INSERT INTO Frm_Ct_Productos_CaractItems (IdProducto, IdCaractItem) VALUES (?, ?)");
    foreach ($Caracteristicas as $caractId) {
      $caractId = intval($caractId);
      $stmt->bind_param("ii", $Id, $caractId);
      $stmt->execute();
    }
  }

  responder(true, "Producto guardado correctamente.");
} catch (Exception $e) {
  responder(false, "Error inesperado: " . $e->getMessage());
}
