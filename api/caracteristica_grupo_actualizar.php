<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once("/home/adminsys/conexion.php");

try {
  $conexion = obtenerConexion();
  if (!$conexion) throw new Exception("Sin conexión");

  $input = json_decode(file_get_contents("php://input"), true);
  $idGrupo = intval($input["Id"] ?? 0);
  $descripcion = trim($input["Descripcion"] ?? '');
  $items = $input["Items"] ?? [];

  if ($idGrupo <= 0 || $descripcion === '' || !is_array($items) || count($items) === 0) {
    throw new Exception("Datos incompletos");
  }

  // Actualizar nombre del grupo
  $stmt = $conexion->prepare("UPDATE Frm_Ct_Caract SET Descripcion = ? WHERE Id = ?");
  $stmt->bind_param("si", $descripcion, $idGrupo);
  $stmt->execute();
  $stmt->close();

  // Obtener ítems actuales del grupo sin usar get_result
  $actuales = [];
  $stmt = $conexion->prepare("SELECT Id, Nombre FROM Frm_Ct_Caract_Items WHERE IdCaract = ?");
  $stmt->bind_param("i", $idGrupo);
  $stmt->execute();
  $stmt->bind_result($idItem, $nombreItem);
  while ($stmt->fetch()) {
    $actuales[intval($idItem)] = $nombreItem;
  }
  $stmt->close();

  // Marcar los IDs recibidos
  $idsRecibidos = [];

  // Procesar cada ítem (renombrar o insertar)
  foreach ($items as $itm) {
    $idItem = intval($itm["Id"]);
    $nombreNuevo = trim($itm["Nombre"]);

    if ($idItem > 0 && isset($actuales[$idItem])) {
      // Renombrar si el nombre cambió
      if ($nombreNuevo !== $actuales[$idItem]) {
        // Validar si está vinculado a productos
        $stmtCheck = $conexion->prepare("SELECT COUNT(*) FROM Frm_Ct_Productos_CaractItems WHERE IdCaractItem = ?");
        $stmtCheck->bind_param("i", $idItem);
        $stmtCheck->execute();
        $stmtCheck->bind_result($enUso);
        $stmtCheck->fetch();
        $stmtCheck->close();

        if ($enUso == 0) {
          // Se puede renombrar
          $stmtUpdate = $conexion->prepare("UPDATE Frm_Ct_Caract_Items SET Nombre = ? WHERE Id = ?");
          $stmtUpdate->bind_param("si", $nombreNuevo, $idItem);
          $stmtUpdate->execute();
          $stmtUpdate->close();
        }
        // Si está en uso, se deja el nombre actual
      }
      $idsRecibidos[] = $idItem;
    } elseif ($idItem === 0 && $nombreNuevo !== "") {
      // Insertar nuevo ítem
      $stmtIns = $conexion->prepare("INSERT INTO Frm_Ct_Caract_Items (IdCaract, Nombre) VALUES (?, ?)");
      $stmtIns->bind_param("is", $idGrupo, $nombreNuevo);
      $stmtIns->execute();
      $stmtIns->close();
    }
  }

  // Eliminar ítems omitidos, sólo si no están en uso
  $todosActuales = array_keys($actuales);
  $aEliminar = array_diff($todosActuales, $idsRecibidos);
  foreach ($aEliminar as $idEliminar) {
    $stmtCheck = $conexion->prepare("SELECT COUNT(*) FROM Frm_Ct_Productos_CaractItems WHERE IdCaractItem = ?");
    $stmtCheck->bind_param("i", $idEliminar);
    $stmtCheck->execute();
    $stmtCheck->bind_result($enUso);
    $stmtCheck->fetch();
    $stmtCheck->close();

    if ($enUso == 0) {
      $stmtDel = $conexion->prepare("DELETE FROM Frm_Ct_Caract_Items WHERE Id = ?");
      $stmtDel->bind_param("i", $idEliminar);
      $stmtDel->execute();
      $stmtDel->close();
    }
    // Si está en uso, no se elimina
  }

  echo json_encode(["success" => true]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
