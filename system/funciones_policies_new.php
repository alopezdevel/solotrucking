<?php
  session_start();
  // Generic functions lib 
  include("functiones_genericas.php"); 
  $_POST["accion"] and  $_POST["accion"]!= "" ? call_user_func_array($_POST["accion"],array()) : ""; 
  define('USER',$_SESSION['usuario_actual']); // Constante UserId 
 
  function upload_list_file(){
      
        
      error_reporting(E_ALL);
      ini_set('display_errors', TRUE);
      ini_set('display_startup_errors', TRUE); 
      
      /** Include PHPExcel */
      require_once dirname(__FILE__) . '/lib/PHPExcel-1.8/Classes/PHPExcel.php';
      
      #variables:
      $iConsecutivoCompania = trim($_POST['iConsecutivoCompania']);
      $iConsecutivoPolizas  = trim($_POST['iConsecutivoPolizas']);
      $iConsecutivoPolizas  = explode(',',trim($iConsecutivoPolizas));
      $count                = count($iConsecutivoPolizas);
      $error                = 0;
      $mensaje              = "";
      $htmlTabla            = ""; 
      $success_unit         = 0;
      $success_driv         = 0;
      //Archivo Data:
      $fileName      = $_FILES['userfile']['name'];
      $tmpName       = $_FILES['userfile']['tmp_name'];
      $inputFileName = $tmpName.'.xls';
      
      //Crear archivo fisico en el Temporal:
      $fp      = fopen($tmpName, 'r'); 
      $content = fread($fp, filesize($tmpName));  
      $fp      = fopen($inputFileName,"w") or die("Error al momento de crear el archivo. Favor de verificarlo.");
      fwrite($fp,$content); 
      fclose($fp);
      
      try {
            $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
            $objReader     = PHPExcel_IOFactory::createReader($inputFileType);
            $objPHPExcel   = $objReader->load($inputFileName);
      }catch(Exception $e) {
            $error   = 1;
            $mensaje = 'Error loading file "'.pathinfo($inputFileName,PATHINFO_BASENAME).'": '.$e->getMessage();
      }
      
      if($error == 0 && $iConsecutivoCompania != '' && $iConsecutivoPolizas != ''){
          
            //Open Connection.
            include("cn_usuarios.php");  
          
            #CONTAMOS LAS SHEET:
            $sheetCount = $objPHPExcel->getSheetCount();
            if($sheetCount <= 0){$error = 1; $mensaje = "The Excel file is empty, please check it.";}
            else{
                for($x = 0;$x<$sheetCount;$x++){
                    
                    // Obtener nombre del sheet:
                    $objWorksheet  = $objPHPExcel->setActiveSheetIndex($x);
                    $title         = $objWorksheet->getTitle();
                    
                    // Calcular no. de renglones y columnas:
                    $rows  = $objWorksheet->getHighestRow();
                    $colsL = $objWorksheet->getHighestColumn(); //Max columna en LETRA
                    $cols  = PHPExcel_Cell::columnIndexFromString($colsL); // Max columna INDEX
                    
                    #UNIDADES/VEHICLES
                    if($title == "UNITS"){
                        
                        // Transaccion BEGIN:
                        $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
                        $success = true;
                        
                        #ELIMINAR UNIDADES DE LAS POLIZAS:
                        for($p=0;$p<$count;$p++){
                            $query  = "DELETE FROM cb_poliza_unidad WHERE iConsecutivoPoliza='".$iConsecutivoPolizas[$p]."'";
                            $success= $conexion->query($query);
                            if(!($success)){$error = 1; $mensaje = "Failed to restart the policies data of units, please try again.";}
                        }
            
                        // Recorrer sheet por RENGLONES:
                        for($row = 2; $row <= $rows; $row++){
                            
                            // Variables:
                            $existe       = ""; 
                            $campos       = array(); //<--- insert
                            $valores      = array(); //<--- insert
                            $update       = array(); //<--- update
                            
                            //Recorrer Sheet por COLUMNAS:   
                            for($col = 0; $col < $cols; $col++){
                                
                                $header = $objWorksheet->getCellByColumnAndRow($col,1)->getValue();
                                $value  = $objWorksheet->getCellByColumnAndRow($col,$row)->getValue();
                                
                                if($header == "MAKE"){
                                    $iMake = strtoupper(trim($value)); 
                                    //Revisamos si existe el modelo en el catalogo:
                                    $query  = "SELECT iConsecutivo AS Make FROM ct_unidad_modelo WHERE sDescripcion = '$iMake' OR sAlias = '$iMake'";
                                    $result = $conexion->query($query);
                                    $items  = $result->fetch_assoc();
                                    $iMake  = $items["Make"];
                                    if($iMake != ""){
                                         //UPDATE 
                                         array_push($update,"iModelo='$iMake'");
                                         //INSERT 
                                         array_push($campos ,"iModelo");
                                         array_push($valores,$iMake); 
                                    }
                                }else
                                if($header == "VIN"){
                                    $sVIN = trim($value); 
                                    $sVIN = str_replace(' ','',nl2br($sVIN));
                                    strpos($sVIN,'<br/>')? $sVIN =  str_replace('<br/>','',nl2br($sVIN)) : "";
                                    
                                    if($sVIN != ""){
                                      
                                          $sVIN = strtoupper(trim($sVIN));
                                          $sVIN = preg_replace("[^A-Za-z0-9]","",$sVIN);
                                          
                                          if(strlen($sVIN) > 18){$error = 1;$mensaje = "Error: Please verify the VIN on the column $col row $row from Excel file.";}
                                          else{  
                                              //Revisamos si ya existe la unidad para esta compañia:
                                              $query  = "SELECT COUNT(iConsecutivo) AS total FROM ct_unidades WHERE sVIN='$sVIN' AND iConsecutivoCompania = '$iConsecutivoCompania'";
                                              $result = $conexion->query($query);
                                              $items  = $result->fetch_assoc();
                                              $existe = $items["total"];
                                              
                                              //INSERT:
                                              if($existe == "0"){array_push($campos,"sVIN");array_push($valores,$sVIN);} 
                                          } 
                                
                                    }
                                    else{$error = 1; $mensaje = "Error: Please verify the VIN \"$sVIN\" on the row $row from Excel file."; $success = false;}
                                }else
                                if($header == "RADIUS"){
                                    $iRadius = trim($value);
                                    $iRadius = str_replace(' ','',$iRadius);
                                  
                                    //Revisamos si existe el Radius en el catalogo: 
                                    $query  = "SELECT iConsecutivo AS Radius FROM ct_unidad_radio WHERE sDescripcion = '$iRadius'";
                                    $result = $conexion->query($query);
                                    $items  = $result->fetch_assoc();
                                    $iRadius= $items["Radius"];
                                    
                                    if($iRadius != ""){
                                      //UPDATE 
                                      array_push($update,"iConsecutivoRadio='$iRadius'");
                                      //INSERT
                                      array_push($campos,"iConsecutivoRadio");
                                      array_push($valores,$iRadius); 
                                    }
                                }else
                                if($header == "TYPE"){
                                    $sType = strtoupper(trim($value)); 
                                    if($sType == 'UNIT' || $sType == 'TRAILER' || $sType == "TRACTOR"){
                                      //UPDATE 
                                      array_push($update,"sTipo='$sType'");
                                      //INSERT 
                                      array_push($campos,"sTipo");
                                      array_push($valores,$sType);
                                    }
                                }else
                                if($header == "TOTALPREMIUM" || $header == "PD" || $header == "TOTALPREMIUMPD"){
                                    $totalP = trim($value);
                                    $totalP = str_replace(" ","",$totalP);
                                    $totalP = str_replace(",","",$totalP);
                                    $totalP = str_replace("\$","",$totalP);
                                    $totalP = preg_replace('/[^0-9]+/', '', $totalP);
                                    $totalP = intval(trim($totalP)); 
                                      if($totalP != ''){
                                          //UPDATE 
                                          array_push($update,"iTotalPremiumPD='$totalP'");
                                          //INSERT 
                                          array_push($campos,"iTotalPremiumPD");
                                          array_push($valores,$totalP); 
                                      }
                                }
                                
                            }  
                            
                            #INSERT / UPDATE EFFECT:
                            if($error != 0){$success = false;}
                            else{
                               // update:
                               if($existe != "0"){
                                    #UPDATE INFORMATION: Agregando campos adicionales:
                                    array_push($update,"dFechaActualizacion='".date("Y-m-d H:i:s")."'");
                                    array_push($update,"sIP='".$_SERVER['REMOTE_ADDR']."'");
                                    array_push($update,"sUsuarioActualizacion='".$_SESSION['usuario_actual']."'");
                                    array_push($update,"iDeleted='0'"); 
                                    array_push($update,"eModoIngreso='EXCEL'"); 
                                    
                                    $query   = "UPDATE ct_unidades SET ".implode(",",$update)." WHERE sVIN='$sVIN' AND iConsecutivoCompania='$iConsecutivoCompania'"; 
                                    $success = $conexion->query($query);
                                    if(!($success)){$error = 1; $mensaje = "The data of unit was not updated properly, please try again.";}
                                    else{
                                        #ACTUALIZAR TABLA DE POLIZAS/UNIDADES:
                                        $query = "SELECT iConsecutivo FROM ct_unidades WHERE sVIN = '$sVIN' AND iConsecutivoCompania ='$iConsecutivoCompania'";
                                        $result= $conexion->query($query);
                                        $item  = $result->fetch_assoc();
                                        
                                        $iConsecutivoUnidad = $item['iConsecutivo']; 
                                    }  
                               } 
                               else{
                                    #INSERT INFORMATION: Agregando campos adicionales:
                                    array_push($campos ,"iConsecutivoCompania"); array_push($valores,trim($iConsecutivoCompania)); //<-- Compania
                                    array_push($campos ,"dFechaIngreso");        array_push($valores,date("Y-m-d H:i:s"));
                                    array_push($campos ,"sIP");                  array_push($valores,$_SERVER['REMOTE_ADDR']);
                                    array_push($campos ,"sUsuarioIngreso");      array_push($valores,$_SESSION['usuario_actual']);
                                    array_push($campos ,"eModoIngreso");         array_push($valores,'EXCEL');
                                    
                                    $query = "INSERT INTO ct_unidades (".implode(",",$campos).") VALUES ('".implode("','",$valores)."')";
                                    $conexion->query($query);
                                    
                                    if($conexion->affected_rows < 1){$error = 1; $success = false; $mensaje = "The data of unit was not saved properly, please try again.";}
                                    else{
                                        #ACTUALIZAR TABLA DE POLIZAS/UNIDADES:
                                        $iConsecutivoUnidad = $conexion->insert_id;
                                    }
                               }
                               #ACTUALIZAR TABLA DE POLIZAS/UNIDADES:
                               if($error == 0){
                                    for($p=0;$p<$count;$p++){
                                        
                                        $query  = "INSERT INTO cb_poliza_unidad (iConsecutivoPoliza,iConsecutivoUnidad,eModoIngreso,dFechaIngreso,sIPIngreso,sUsuarioIngreso) ".
                                                  "VALUES('".$iConsecutivoPolizas[$p]."','$iConsecutivoUnidad','AMIC','".date("Y-m-d H:i:s")."','".$_SERVER['REMOTE_ADDR']."','".$_SESSION['usuario_actual']."')";
                                        $conexion->query($query);
                                                
                                        if($conexion->affected_rows < 1){$error = 1; $success = false; $mensaje = "The data of unit in the policy was not saved properly, please try again.";}
                                        else{$success_unit ++;}
                                   }    
                               }
                            }           
                        } 
                        
                        if($success && $error == 0){$conexion->commit(); $mensaje .= "The data of $success_unit units has been uploaded successfully, please verify the data in the company policies.<br><br>";}
                        else{$conexion->rollback();}   
                    }
                    #DRIVERS/OPERADORES
                    if($title == "DRIVERS"){
                        // Transaccion BEGIN:
                        $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
                        $success = true;
                        
                        #ELIMINAR OPERADORES DE LAS POLIZAS:
                        for($p=0;$p<$count;$p++){
                            $query  = "DELETE FROM cb_poliza_operador WHERE iConsecutivoPoliza='".$iConsecutivoPolizas[$p]."'";
                            $success= $conexion->query($query);
                            if(!($success)){$error = 1; $mensaje = "Failed to restart the policies data of drivers, please try again.";}
                        }
                        
                        // Recorrer sheet por RENGLONES:
                        for($row = 2; $row <= $rows; $row++){
                            
                            // Variables:
                            $existe       = ""; 
                            $campos       = array(); //<--- insert
                            $valores      = array(); //<--- insert
                            $update       = array(); //<--- update
                            
                            //Recorrer Sheet por COLUMNAS:   
                            for($col = 0; $col < $cols; $col++){
                                
                                $header = trim($objWorksheet->getCellByColumnAndRow($col,1)->getValue());
                                $value  = trim($objWorksheet->getCellByColumnAndRow($col,$row)->getValue());
                                
                                if($header == "NAME"){
                                    $sName = strtoupper($value);
                                    if($sName != ""){
                                        $sName = str_replace(",","",$sName);
                                        //UPDATE 
                                        array_push($update,"sNombre='$sName'"); 
                                        //INSERT 
                                        array_push($campos ,"sNombre");
                                        array_push($valores,$sName); 
                                    } 
                                }else
                                if($header == "DOB"){
                                    $DOB = $value; 
                                    if($DOB != ""){
                                        
                                       if(strpos($DOB,'-') || strpos($DOB,'/')){
                                           
                                       }else{
                                           $unixDate  = ($DOB - 25569) * 86400;
                                           $ExcelDate = 25569 + ($unixDate / 86400);
                                           $unixDate  = ($ExcelDate - 25569) * 86400;
                                           $DOB       = gmdate("Y-m-d", $unixDate);
                                       } 
                                       
                                       $DOB       = date("Y-m-d", strtotime($DOB));
                                       
                                       //UPDATE 
                                       array_push($update,"dFechaNacimiento='$DOB'"); 
                                       //INSERT 
                                       array_push($campos,"dFechaNacimiento");
                                       array_push($valores,$DOB);
                                    } 
                                }else
                                if($header == "LICENSE"){
                                    $iLicense = strtoupper($value);
                                    $iLicense = str_replace(' ','',nl2br($iLicense));
                                    $iLicense = trim($iLicense);
                                    
                                    if($iLicense != ""){
                                        if(strpos($iLicense,'<br/>')){$error = 1;$mensaje = "Error: Please verify the LICENSE $iLicense on the column $col row $row from Excel file.";}
                                        else{ 
                                          $pos = strpos($iLicense,'-');
                                          if(!($pos === false)){$iLicense = substr($iLicense,0,$pos);}  
                                          //Revisamos si ya existe el driver para esta compañia:
                                          $query  = "SELECT COUNT(iConsecutivo) AS total FROM ct_operadores WHERE iNumLicencia = '$iLicense' AND iConsecutivoCompania = '$iConsecutivoCompania'";
                                          $result = $conexion->query($query);
                                          $items  = $result->fetch_assoc();
                                          $existe = $items["total"];
                                          if($existe == "0"){array_push($campos,"iNumLicencia");array_push($valores,trim($iLicense));}
                                        }       
                                    }else{$error = 1; $mensaje = "Error: Please verify the LICENSE $iLicense on the row $row from Excel file."; $success = false;} 
                                }else
                                if($header == "YOE"){
                                    $YOE = $value; 
                                    if($YOE != ""){
                                       $YOE = str_replace("+",'',$YOE);
                                       //UPDATE 
                                       array_push($update,"iExperienciaYear='$YOE'"); 
                                       //INSERT 
                                       array_push($campos ,"iExperienciaYear");
                                       array_push($valores,$YOE);
                                    } 
                                }else
                                if($header == "EXPIREDATE"){
                                    $ExpireDate = $value; 
                                    if($ExpireDate != ""){
                                        
                                       $UNIX_DATE  = ($ExpireDate - 25569) * 86400;
                                       $EXCEL_DATE = 25569 + ($UNIX_DATE / 86400);
                                       $UNIX_DATE  = ($EXCEL_DATE - 25569) * 86400;
                                       $ExpireDate = gmdate("Y-m-d", $UNIX_DATE);
                                       
                                       //UPDATE 
                                       array_push($update,"dFechaExpiracionLicencia='$ExpireDate'"); 
                                       //INSERT 
                                       array_push($campos ,"dFechaExpiracionLicencia");
                                       array_push($valores,$ExpireDate); 
                                    }
                                }else
                                if($header == "TYPE" || $header == "LICENSETYPE"){
                                   $Type = strtoupper($value); 
                                   if($Type != ""){
                                       //UPDATE 
                                       array_push($update,"eTipoLicencia='$Type'"); 
                                       //INSERT 
                                       array_push($campos ,"eTipoLicencia");
                                       array_push($valores,$Type); 
                                   } 
                                }
                                
                                
                            }  
                            #INSERT / UPDATE EFFECT:
                            if($error != 0){$success = false;}
                            else{
                               // update:
                               if($existe != "0"){
                                    #UPDATE INFORMATION: Agregando campos adicionales:
                                    array_push($update,"dFechaActualizacion='".date("Y-m-d H:i:s")."'");
                                    array_push($update,"sIP='".$_SERVER['REMOTE_ADDR']."'");
                                    array_push($update,"sUsuarioActualizacion='".$_SESSION['usuario_actual']."'");
                                    array_push($update,"iDeleted='0'"); 
                                    array_push($update,"eModoIngreso='EXCEL'"); 
                                    
                                    $query   = "UPDATE ct_operadores SET ".implode(",",$update)." WHERE iNumLicencia='$iLicense' AND iConsecutivoCompania='$iConsecutivoCompania'"; 
                                    $success = $conexion->query($query);
                                    if(!($success)){$error = 1; $mensaje = "The data of driver was not updated properly, please try again.";}
                                    else{
                                        #ACTUALIZAR TABLA DE POLIZAS/OPERADORES:
                                        $query = "SELECT iConsecutivo FROM ct_operadores WHERE iNumLicencia='$iLicense' AND iConsecutivoCompania='$iConsecutivoCompania'";
                                        $result= $conexion->query($query);
                                        $item  = $result->fetch_assoc();
                                        
                                        $iConsecutivoOperador = $item['iConsecutivo'];
                        
                                    }  
                               } 
                               else{
                                    #INSERT INFORMATION: Agregando campos adicionales:
                                    array_push($campos ,"iConsecutivoCompania"); array_push($valores,trim($iConsecutivoCompania)); //<-- Compania
                                    array_push($campos ,"dFechaIngreso");        array_push($valores,date("Y-m-d H:i:s"));
                                    array_push($campos ,"sIP");                  array_push($valores,$_SERVER['REMOTE_ADDR']);
                                    array_push($campos ,"sUsuarioIngreso");      array_push($valores,$_SESSION['usuario_actual']);
                                    array_push($campos ,"eModoIngreso");         array_push($valores,'EXCEL');
                                    
                                    $query = "INSERT INTO ct_operadores (".implode(",",$campos).") VALUES ('".implode("','",$valores)."')";
                                    $conexion->query($query);
                                    
                                    if($conexion->affected_rows < 1){$error = 1; $success = false; $mensaje = "The data of driver was not saved properly, please try again.";}
                                    else{
                                        #ACTUALIZAR TABLA DE POLIZAS/UNIDADES:
                                        $iConsecutivoOperador = $conexion->insert_id;
                                    }
                               }
                               
                               #ACTUALIZAR TABLA DE POLIZAS/UNIDADES:
                               if($error == 0){
                                   for($p=0;$p<$count;$p++){
                                        $query = "INSERT INTO cb_poliza_operador (iConsecutivoPoliza,iConsecutivoOperador,eModoIngreso,dFechaIngreso,sIPIngreso,sUsuarioIngreso) ".
                                                 "VALUES('".$iConsecutivoPolizas[$p]."','$iConsecutivoOperador','AMIC','".date("Y-m-d H:i:s")."','".$_SERVER['REMOTE_ADDR']."','".$_SESSION['usuario_actual']."')";
                                        $conexion->query($query);
                                        
                                        if($conexion->affected_rows < 1){$error = 1; $success = false; $mensaje = "The data of driver in the policy was not saved properly, please try again.";}
                                        else{$success_driv ++;}
                                        
                                   }
                               }
                            }           
                        } 
                        
                        if($success && $error == 0){$conexion->commit(); $mensaje .= "The data of $success_driv drivers has been uploaded successfully, please verify the data in the company policies.<br><br>";}
                        else{$conexion->rollback();}  
                            
                    }
                    // Verificar en caso que suban un sheet con un nombre invalido:
                    if($title != 'UNITS' && $title != 'DRIVERS'){
                        $error = 1; $mensaje = "Error: The title of sheet '$title' is not valid, please upload the file with the layout format and try again.";
                    }
                     
                }
            }
      }  
      $conexion->close();
      $response = array("mensaje"=>"$mensaje","error"=>"$error", "name_file" => "$fileName"); 
      echo json_encode($response); 
      
  }
  
  
  
?>
