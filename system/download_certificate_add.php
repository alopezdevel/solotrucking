<?php                  
            
            include("cn_usuarios.php");
            $conexion->autocommit(FALSE);
            $transaccion_exitosa = true;
            $sql = "SELECT hContenidoDocumentoDigitalizadoAdd, sNombreArchivoAdd, sTipoArchivoAdd, iTamanioArchivoAdd FROM cb_certificate_file WHERE iConsecutivo = '".$_GET['cve']."'";
      
            $result = $conexion->query($sql);
                $NUM_ROWs_Usuario = $result->num_rows;
            if ($NUM_ROWs_Usuario > 0) {                                
                while ($archivo = $result->fetch_assoc()) {
                      $data .= $archivo ["hContenidoDocumentoDigitalizadoAdd"]; 
                      $type = $archivo ["sTipoArchivoAdd"];
                      $size = $archivo ["iTamanioArchivoAdd"];
                      $name = $archivo ["sNombreArchivoAdd"];
                }
                $conexion->close();    

                header("Pragma: public");
                header("Expires: 0");
                header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                header("Cache-Control: public");
                header("Content-type: $type");
                header("Content-length: $size");
                header("Content-Disposition: attachment; filename=$name");
                header("Content-Transfer-Encoding: binary");
                header("Content-Description: PHP Generated Data");
                echo $data;
            } else {
                header("Location: login.php");
                exit;
            }    
?>
