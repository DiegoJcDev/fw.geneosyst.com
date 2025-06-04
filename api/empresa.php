<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

require_once("/home/adminsys/conexion.php");
$mysqli = obtenerConexion();
if($mysqli->connect_error){
  http_response_code(500);
  echo json_encode(["success"=>false,"message"=>"Error de conexión"]);
  exit;
}

if($_SERVER['REQUEST_METHOD'] === 'GET'){
  $email = $_GET['email'] ?? '';
  $stmt  = $mysqli->prepare("
    SELECT RFC, RazonSocial, RegimenFiscal,
           Calle, NumExt, Colonia, CP, Municipio, Estado,
           LogoURL, Impresora
      FROM Frm_Ct_Empresas
     WHERE Email = ?
  ");
  $stmt->bind_param("s",$email);
  $stmt->execute();
  $stmt->bind_result($rfc,$rs,$reg,$calle,$num,$col,$cp,$mun,$est,$logo,$imp);
  if($stmt->fetch()){
    echo json_encode([
      "success"=>true,
      "rfc"=>$rfc,
      "razon_social"=>$rs,
      "regimen"=>$reg,
      "calle"=>$calle,
      "num_ext"=>$num,
      "colonia"=>$col,
      "cp"=>$cp,
      "municipio"=>$mun,
      "estado"=>$est,
      "logo_url"=>$logo,
      "impresora"=>$imp
    ]);
  } else {
    echo json_encode(["success"=>false,"message"=>"No hay datos para este usuario"]);
  }
  $stmt->close();
  exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
  $input = json_decode(file_get_contents("php://input"), true);
  $email       = $input['email'] ?? '';
  $rfc         = $input['rfc'] ?? '';
  $rs          = $input['razon_social'] ?? '';
  $reg         = $input['regimen'] ?? '';
  $calle       = $input['calle'] ?? '';
  $num         = $input['num_ext'] ?? '';
  $col         = $input['colonia'] ?? '';
  $cp          = $input['cp'] ?? '';
  $mun         = $input['municipio'] ?? '';
  $est         = $input['estado'] ?? '';
  $logo        = $input['logo_url'] ?? '';
  $imp         = $input['impresora'] ?? '';

  // ¿Existe?
  $chk = $mysqli->prepare("SELECT COUNT(*) FROM Frm_Ct_Empresas WHERE Email=?");
  $chk->bind_param("s",$email);
  $chk->execute();
  $chk->bind_result($count);
  $chk->fetch();
  $chk->close();

  if($count>0){
    // actualiza
    $sql = "
      UPDATE Frm_Ct_Empresas
         SET RFC=?, RazonSocial=?, RegimenFiscal=?,
             Calle=?, NumExt=?, Colonia=?, CP=?, Municipio=?, Estado=?,
             LogoURL=?, Impresora=?
       WHERE Email=?
    ";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param(
      "ssssssssssss",
      $rfc,$rs,$reg,
      $calle,$num,$col,$cp,$mun,$est,
      $logo,$imp,
      $email
    );
  } else {
    // inserta
    $sql = "
      INSERT INTO Frm_Ct_Empresas
        (Email,RFC,RazonSocial,RegimenFiscal,
         Calle,NumExt,Colonia,CP,Municipio,Estado,
         LogoURL,Impresora)
      VALUES(?,?,?,?,?,?,?,?,?,?,?,?)
    ";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param(
      "ssssssssssss",
      $email,$rfc,$rs,$reg,
      $calle,$num,$col,$cp,$mun,$est,
      $logo,$imp
    );
  }

  if($stmt->execute()){
    echo json_encode(["success"=>true]);
  } else {
    http_response_code(500);
    echo json_encode(["success"=>false,"message"=>"Error al guardar"]);
  }
  $stmt->close();
  exit;
}

// otros métodos no permitidos
http_response_code(405);
echo json_encode(["success"=>false,"message"=>"Método no permitido"]);
