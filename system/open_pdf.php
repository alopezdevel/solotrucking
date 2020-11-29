<?php
  
    session_start();
    include("cn_usuarios.php");
    $conexion->autocommit(FALSE);
    $transaccion_exitosa = true;
    $folio = $_GET['idfile'];
    $tipoF = $_GET['type'];
    
    $iConsecutivo = 'iConsecutivo';
    
    switch($tipoF){
       case 'driver'     : $ct = 'cb_operador_files'; break; 
       case 'unit'       : $ct = 'cb_unidad_files';   break;
       case 'company'    : $ct = 'cb_company_files';  break;
       case 'claims'     : $ct = 'cb_claims_files';   break;
       case 'endoso'     : $ct = 'cb_endoso_files';   break;
       case 'endoso_add' : $ct = 'cb_endoso_adicional_files'; break;
       case 'poliza'     : $ct = 'cb_poliza_files';   break;
       case 'pago'       : $ct = 'cb_pago'; $iConsecutivo = 'iConsecutivoPago';  break;
       case 'invoice'    : $ct = 'cb_invoices';   break;
    }  
    $sql   = "SELECT ".$iConsecutivo.", hContenidoDocumentoDigitalizado, sNombreArchivo, sTipoArchivo, iTamanioArchivo ".
             "FROM ".$ct." WHERE ".$iConsecutivo." = '".$folio."'";  
    $result = $conexion->query($sql);
    $rows   = $result->num_rows;
    
    if ($rows > 0) {                                
        while ($archivo = $result->fetch_assoc()) {
              $data  = $archivo ["hContenidoDocumentoDigitalizado"]; 
              $type  = $archivo ["sTipoArchivo"];
              $size  = $archivo ["iTamanioArchivo"];
              $name  = $archivo ["sNombreArchivo"];
        } 
        
        
        $conexion->rollback();
        $conexion->close();  
        
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: no-cache, post-check=0, pre-check=0");
        header("Cache-Control: public"); 
        
        /*$name = strtolower($name);  
        $name = str_replace("#","NO",$name);
        $name = str_replace("_","-",$name);
        $name = trim($name);*/
        
        //strlen($name) > 15 ? $name = $tipoF."-".$folio.".pdf" : "";
        
        if($type == "application/pdf"){
            
            $name = $tipoF."-".$folio.".pdf";
            
            header("Content-type: $type");
            header("Content-length: $size");
            header("Content-Disposition: inline; filename=$name");
            header("Content-Transfer-Encoding: binary");
            header("Content-Description: PHP Generated Data");
            
            readfile("$name");
            echo $data;
                
        }else{
        
            header("Content-Description: File Transfer"); 
            header("Content-Type: application/force-download");
            header("Content-Disposition: inline; filename=$name");
            echo $data; 
        }

    } else {
        header("Location: inicio.php");
        exit;
    }    

?>

