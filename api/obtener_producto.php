<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

require_once("/home/adminsys/conexion.php");

function responder($success, $data = []) {
  echo json_encode(array_merge(["success" => $success], $data));
  exit;
}

try {
  $conexion = obtenerConexion();
  if ($conexion->connect_error) responder(false, ["message" => "Error de conexión"]);

  $input = json_decode(file_get_contents("php://input"), true);
  $Id = intval($input["Id"] ?? 0);
  $IdUsuarioGs = intval($input["IdUsuarioGs"] ?? 0);

  if (!$Id || !$IdUsuarioGs) responder(false, ["message" => "Parámetros incompletos"]);

  // Consulta de producto con bind_result
  $sql = "SELECT Id, IdUsuarioGs, Codigo, Descripcion, ClaveUnidad, ClaveProdServ,
                 IdImpuestoTipo, IdObjetoImpuesto, IdTipo, IdClasificacion,
                 PrecioVentaBase, IdUnidadBase, FotoProducto
          FROM Frm_Ct_Productos
          WHERE Id = ? AND IdUsuarioGs = ?";
  $stmt = $conexion->prepare($sql);
  $stmt->bind_param("ii", $Id, $IdUsuarioGs);
  $stmt->execute();
  $stmt->store_result();

  if ($stmt->num_rows === 0) responder(false, ["message" => "Producto no encontrado"]);

  $stmt->bind_result(
    $pid, $pusuario, $pcodigo, $pdesc, $punidad, $pprodserv,
    $pidimp, $pidobj, $ptipo, $pclasif,
    $pprecio, $pidunidadbase, $pfoto
  );
  $stmt->fetch();

  $producto = [
    "Id" => $pid,
    "IdUsuarioGs" => $pusuario,
    "Codigo" => $pcodigo,
    "Descripcion" => $pdesc,
    "ClaveUnidad" => $punidad,
    "ClaveProdServ" => $pprodserv,
    "IdImpuestoTipo" => $pidimp,
    "IdObjetoImpuesto" => $pidobj,
    "IdTipo" => $ptipo,
    "IdClasificacion" => $pclasif,
    "PrecioVentaBase" => $pprecio,
    "IdUnidadBase" => $pidunidadbase,
    "FotoProducto" => $pfoto
  ];

  // Características
  $caracteristicas = [];
  $res2 = $conexion->query("SELECT IdCaractItem FROM Frm_Ct_Productos_CaractItems WHERE IdProducto = $Id");
  while ($row = $res2->fetch_assoc()) {
    $caracteristicas[] = intval($row["IdCaractItem"]);
  }

  responder(true, [
    "producto" => $producto,
    "caracteristicas" => $caracteristicas
  ]);
} catch (Exception $e) {
  responder(false, ["message" => "Error: " . $e->getMessage()]);
}
