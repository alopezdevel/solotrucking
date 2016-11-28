<?php
  
    include("cn_usuarios.php");
    $conexion->autocommit(FALSE);
    $transaccion_exitosa = true;
    $folio = $_GET['idfile'];
    $type = $_GET['type'];
    
    switch($type){
       case 'driver': $ct = 'cb_operador_files'; break; 
       case 'unit': $ct = 'cb_unidad_files'; break;
       case 'company': $ct = 'cb_company_files'; break;
       case 'claims' : $ct = 'cb_claims_files'; break;
    } 
    $sql = "SELECT iConsecutivo, hContenidoDocumentoDigitalizado, sNombreArchivo, sTipoArchivo, iTamanioArchivo 
            FROM ".$ct." WHERE iConsecutivo = '".$folio."'";
    $result = $conexion->query($sql);
    $rows = $result->num_rows;
    
    if ($rows > 0) {                                
        while ($archivo = $result->fetch_assoc()) {
              $data .= $archivo ["hContenidoDocumentoDigitalizado"]; 
              $type = $archivo ["sTipoArchivo"];
              $size = $archivo ["iTamanioArchivo"];
              $name = $archivo ["sNombreArchivo"];
        }
        $conexion->close();    

        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: public");
        header("Content-type: $type");
        header("Content-length: $size");
        header("Content-Disposition: inline; filename=$name");
        header("Content-Transfer-Encoding: binary");
        header("Content-Description: PHP Generated Data");
        echo $data;
    } else {
        header("Location: inicio.php");
        exit;
    }    

?>

