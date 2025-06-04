<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once("/home/adminsys/conexion.php");

try {
  $conexion = obtenerConexion();

  $sql = "SELECT Id, TipoDocumento, Tipo FROM Sys_Ct_TipoDocumento WHERE Form IN('DocVentas','CuentasCobrar') AND IsCfdi = 1 ORDER BY TipoDocumento";
  $result = $conexion->query($sql);

  $tipos = [];
  while ($row = $result->fetch_assoc()) {
    $tipos[] = [
      "Id" => $row["Id"],
      "TipoDocumento" => $row["TipoDocumento"],
      "Tipo" => $row["Tipo"]
    ];
  }

  echo json_encode(["success" => true, "data" => $tipos]);
} catch (Exception $e) {
  echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
