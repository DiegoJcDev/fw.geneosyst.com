<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

require_once("/home/adminsys/conexion.php");
$conexion = obtenerConexion();

$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input['IdUsuarioGs']) || !isset($input['NombreComercial'])) {
    echo json_encode(["success" => false, "message" => "Faltan datos obligatorios"]);
    exit;
}

$Id = isset($input['Id']) ? intval($input['Id']) : 0;
$IdUsuarioGs = intval($input['IdUsuarioGs']);
$IdTipoEmpresa = 4; // Clientes

// Datos generales
$NombreComercial = trim($input['NombreComercial']);
$NombreOficial = trim($input['NombreOficial'] ?? '');
$RfcNoIdFiscal = trim($input['RfcNoIdFiscal'] ?? '');
$IdPersonaJuridicaSAT = intval($input['IdPersonaJuridicaSAT'] ?? 0);
$IdRegimenSAT = intval($input['IdRegimenSAT'] ?? 0);

// Contacto
$Telefono1 = trim($input['Telefono1'] ?? '');
$Telefono2 = trim($input['Telefono2'] ?? '');
$Movil = trim($input['Movil'] ?? '');
$Email = trim($input['Email'] ?? '');
$EmailCc = trim($input['EmailCc'] ?? '');

// Dirección
$NombreVialidad = trim($input['NombreVialidad'] ?? '');
$NumExt = trim($input['NumExt'] ?? '');
$NumInt = trim($input['NumInt'] ?? '');
$ColoniaLocalidad = trim($input['ColoniaLocalidad'] ?? '');
$CodigoPostal = trim($input['CodigoPostal'] ?? '');
$CiudadEntidad = trim($input['CiudadEntidad'] ?? '');
$EstadoProvincia = trim($input['EstadoProvincia'] ?? '');
$Pais = trim($input['Pais'] ?? '');

try {
    if ($Id > 0) {
        // UPDATE
        $stmt = $conexion->prepare("
            UPDATE Frm_Ct_Empresas
            SET NombreComercial = ?, NombreOficial = ?, RfcNoIdFiscal = ?, IdPersonaJuridicaSAT = ?, IdRegimenSAT = ?,
                Telefono1 = ?, Telefono2 = ?, Movil = ?, Email = ?, EmailCc = ?,
                NombreVialidad = ?, NumExt = ?, NumInt = ?, ColoniaLocalidad = ?, CodigoPostal = ?, 
                CiudadEntidad = ?, EstadoProvincia = ?, Pais = ?
            WHERE Id = ? AND IdUsuarioGs = ? AND IdTipoEmpresa = ?
        ");

        if (!$stmt) {
            echo json_encode(["success" => false, "message" => "Error en prepare (UPDATE)", "error" => $conexion->error]);
            exit;
        }

        $stmt->bind_param("sssiisssssssssssssiii", 
            $NombreComercial, $NombreOficial, $RfcNoIdFiscal, $IdPersonaJuridicaSAT, $IdRegimenSAT,
            $Telefono1, $Telefono2, $Movil, $Email, $EmailCc,
            $NombreVialidad, $NumExt, $NumInt, $ColoniaLocalidad, $CodigoPostal,
            $CiudadEntidad, $EstadoProvincia, $Pais,
            $Id, $IdUsuarioGs, $IdTipoEmpresa
        );

        if (!$stmt->execute()) {
            echo json_encode(["success" => false, "message" => "Error al ejecutar (UPDATE)", "error" => $stmt->error]);
            exit;
        }

        echo json_encode(["success" => true, "message" => "Cliente actualizado correctamente"]);
    } else {
        // INSERT
        $stmt = $conexion->prepare("
            INSERT INTO Frm_Ct_Empresas (
                IdUsuarioGs, IdTipoEmpresa,
                NombreComercial, NombreOficial, RfcNoIdFiscal, IdPersonaJuridicaSAT, IdRegimenSAT,
                Telefono1, Telefono2, Movil, Email, EmailCc,
                NombreVialidad, NumExt, NumInt, ColoniaLocalidad, CodigoPostal,
                CiudadEntidad, EstadoProvincia, Pais
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            echo json_encode(["success" => false, "message" => "Error en prepare (INSERT)", "error" => $conexion->error]);
            exit;
        }

        $stmt->bind_param("iisssiisssssssssssss",
            $IdUsuarioGs, $IdTipoEmpresa,
            $NombreComercial, $NombreOficial, $RfcNoIdFiscal, $IdPersonaJuridicaSAT, $IdRegimenSAT,
            $Telefono1, $Telefono2, $Movil, $Email, $EmailCc,
            $NombreVialidad, $NumExt, $NumInt, $ColoniaLocalidad, $CodigoPostal,
            $CiudadEntidad, $EstadoProvincia, $Pais
        );
        
        if (!$stmt->execute()) {
            echo json_encode([
                "success" => false,
                "message" => "Error al ejecutar (INSERT)",
                "error" => $stmt->error,
                "sqlstate" => $stmt->sqlstate,
                "parametros" => [
                    "IdUsuarioGs" => $IdUsuarioGs,
                    "IdTipoEmpresa" => $IdTipoEmpresa,
                    "NombreComercial" => $NombreComercial,
                    "NombreOficial" => $NombreOficial,
                    "RfcNoIdFiscal" => $RfcNoIdFiscal,
                    "IdPersonaJuridicaSAT" => $IdPersonaJuridicaSAT,
                    "IdRegimenSAT" => $IdRegimenSAT,
                    "Telefono1" => $Telefono1,
                    "Telefono2" => $Telefono2,
                    "Movil" => $Movil,
                    "Email" => $Email,
                    "EmailCc" => $EmailCc,
                    "NombreVialidad" => $NombreVialidad,
                    "NumExt" => $NumExt,
                    "NumInt" => $NumInt,
                    "ColoniaLocalidad" => $ColoniaLocalidad,
                    "CodigoPostal" => $CodigoPostal,
                    "CiudadEntidad" => $CiudadEntidad,
                    "EstadoProvincia" => $EstadoProvincia,
                    "Pais" => $Pais
                ]
            ]);
            exit;
        }

        echo json_encode(["success" => true, "message" => "Cliente registrado correctamente", "IdNuevo" => $stmt->insert_id]);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Excepción al guardar cliente", "error" => $e->getMessage()]);
}
?>
