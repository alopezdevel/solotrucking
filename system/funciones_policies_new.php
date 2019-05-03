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
      $eTipoLista           = trim($_POST['eTipoLista']);
      $iConsecutivoPolizas  = explode(',',trim($iConsecutivoPolizas));
      $count                = count($iConsecutivoPolizas);
      $error                = 0;
      $mensaje              = "";
      $htmlTabla            = ""; 
      $success_unit         = 0;
      $success_driv         = 0;
      $fileName             = $_FILES['userfile']['name'];
      $tmpName              = $_FILES['userfile']['tmp_name'];
      $inputFileName        = $tmpName.'.xls';
      $sIP                  = $_SERVER['REMOTE_ADDR'];
      $sUsuario             = $_SESSION['usuario_actual'];
      $dFecha               = date("Y-m-d H:i:s");
      
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
            
            //CONSULTAR DATOS DE LAS POLIZAS:
            $polizas = array();
            for($p=0;$p<$count;$p++){
                
                $q = "SELECT iConsecutivo, sNumeroPoliza, iTipoPoliza FROM ct_polizas WHERE iConsecutivo = '".$iConsecutivoPolizas[$p]."'";
                $r = $conexion->query($q);
                $r = $r->fetch_assoc();  
                
                $polizas[$r['iConsecutivo']]['sNumeroPoliza'] = $r['sNumeroPoliza']; 
                $polizas[$r['iConsecutivo']]['iTipoPoliza']   = $r['iTipoPoliza'];
            
            }
          
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
                    
                    
                    /* --------------------------- BIND LISTS ------------------------------------------*/
                    #UNIDADES/VEHICLES 
                    if($title == "UNITS" && $eTipoLista == 'BIND'){
                        
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
                                    /*$sVIN = trim($value); 
                                    $sVIN = str_replace(' ','',nl2br($sVIN));
                                    strpos($sVIN,'<br/>')? $sVIN =  str_replace('<br/>','',nl2br($sVIN)) : ""; */
                                    $sVIN = utf8_decode(preg_replace("[A-Za-z0-9]","",utf8_decode($value)));
                                    $sVIN = str_replace(' ','',nl2br($sVIN)); 
                                    $sVIN = str_replace('?','',nl2br($sVIN));
                                    
                                    if($sVIN != ""){
                                      
                                         /* $sVIN = strtoupper(trim($sVIN));
                                          $sVIN = preg_replace("[^A-Za-z0-9]","",$sVIN);
                                          $sVIN = trim($sVIN); */
                                          
                                          if(strlen($sVIN) > 18){$error = 1;$mensaje = "Error: Please verify the VIN \"$sVIN\" on the column ".($col+1)." and row $row from Excel file.";}
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
                                    else{$error = 1; $mensaje = "Error: Please verify the VIN \"$sVIN\" on the row $col from Excel file."; $success = false;}
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
                                if($header == "YEAR"){
                                    $iYear = intval(trim($value)); 
                                    if($iYear > 0){
                                      //UPDATE 
                                      array_push($update,"iYear='$iYear'");
                                      //INSERT 
                                      array_push($campos,"iYear");
                                      array_push($valores,$iYear);
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
                                        
                                        //CONSULTAR FECHA PARA BIND:
                                        $q = "SELECT dFechaInicio FROM ct_polizas WHERE iConsecutivo = '".$iConsecutivoPolizas[$p]."'";
                                        $r = $conexion->query($q);
                                        $r = $r->fetch_assoc();
                                        
                                        $query  = "INSERT INTO cb_poliza_unidad (iConsecutivoPoliza,iConsecutivoUnidad,eModoIngreso,dFechaIngreso,sIPIngreso,sUsuarioIngreso) ".
                                                  "VALUES('".$iConsecutivoPolizas[$p]."','$iConsecutivoUnidad','AMIC','".$r['dFechaInicio']."','".$_SERVER['REMOTE_ADDR']."','".$_SESSION['usuario_actual']."')";
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
                    if($title == "DRIVERS" && $eTipoLista == "BIND"){
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
                                        if(strpos($iLicense,'<br/>') === false){ 
                                          $pos = strpos($iLicense,'-');
                                          if(!($pos === false)){$iLicense = substr($iLicense,0,$pos);}  
                                          //Revisamos si ya existe el driver para esta compañia:
                                          $query  = "SELECT COUNT(iConsecutivo) AS total FROM ct_operadores WHERE iNumLicencia = '$iLicense' AND iConsecutivoCompania = '$iConsecutivoCompania'";
                                          $result = $conexion->query($query);
                                          $items  = $result->fetch_assoc();
                                          $existe = $items["total"];
                                          if($existe == "0"){array_push($campos,"iNumLicencia");array_push($valores,trim($iLicense));}
                                        
                                        }else{$error = 1;$mensaje = "Error: Please verify the LICENSE $iLicense on the column ".($col+1)." and row $row from Excel file.";}      
                                    }else{$error = 1; $mensaje = "Error: Please verify the LICENSE $iLicense on the row $col from Excel file."; $success = false;} 
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
                                       
                                        //CONSULTAR FECHA PARA BIND:
                                        $q = "SELECT dFechaInicio FROM ct_polizas WHERE iConsecutivo = '".$iConsecutivoPolizas[$p]."'";
                                        $r = $conexion->query($q);
                                        $r = $r->fetch_assoc();
                                        
                                        $query = "INSERT INTO cb_poliza_operador (iConsecutivoPoliza,iConsecutivoOperador,eModoIngreso,dFechaIngreso,sIPIngreso,sUsuarioIngreso) ".
                                                 "VALUES('".$iConsecutivoPolizas[$p]."','$iConsecutivoOperador','AMIC','".$r['dFechaInicio']."','".$_SERVER['REMOTE_ADDR']."','".$_SESSION['usuario_actual']."')";
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
                    
                    /* --------------------------- ENDOSOS ------------------------------------------*/
                    #DRIVERS/ENDOSOS OPERADORES
                    if($title == "DRIVERS" && $eTipoLista == "ENDOSOS"){
                        
                        // Transaccion BEGIN:
                        $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
                        $success = true; 
                        
                        // Recorrer sheet por RENGLONES:
                        for($row = 2; $row <= $rows; $row++){
                            
                            // Variables:
                            $existe    = false; 
                            $campos    = array(); //<--- insert
                            $valores   = array(); //<--- insert
                            $endoso    = array(); //<--- insert endoso
                            $dataArray = array();
                            
                            //Recorrer Sheet por COLUMNAS:   
                            for($col = 0; $col < $cols; $col++){
                                
                                $header = strtoupper(trim($objWorksheet->getCellByColumnAndRow($col,1)->getValue()));
                                $value  = trim($objWorksheet->getCellByColumnAndRow($col,$row)->getValue());
                                
                                if($header == "ACTION"){
                                    $value = strtoupper(str_replace(" ","",$value)); 
                                    if($value == "" || $value == NULL){$error = 1; $mensaje = "Error: Please verify the ACTION: $value on the column ".($col+1)." and row $row from Excel file in the DRIVERS sheet.";}
                                    else{
                                        //Dar formato en caso de solo contener INICIALES:
                                        if($value == "A"){$value = 'ADD';}else if($value == "D"){$value = "DELETE";}else if($value == "AS"){$value = "ADDSWAP";}else if($value == "DS"){$value = "DELETESWAP";}
                                        $dataArray['eAccion'] = $value;
                                    }   
                                }else
                                //NUMEROS  DE ENDOSO:
                                if($header == "ENDAL#" || $header == "ENDAL" || $header == "AL#"){
                                    $value = strtoupper(str_replace(" ","",$value));
                                    /*if($value == "" || $value == NULL){$error = 1; $mensaje = "Error: Please verify the END AL#: $value on the column ".($col+1)." and row $row from Excel file.";}
                                    else{$dataArray['ENDAL'] = $value;}*/
                                    if($value != ""){$dataArray['ENDAL'] = $value;}     
                                }else
                                if($header == "ENDMTC#" || $header == "ENDMTC" || $header == "MTC#"){
                                    $value = strtoupper(str_replace(" ","",$value));
                                    if($value != ""){$dataArray['ENDMTC'] = $value;}    
                                }else
                                if($header == "ENDPD#" || $header == "ENDPD" || $header == "PD#"){
                                    $value = strtoupper(str_replace(" ","",$value));
                                    if($value != ""){$dataArray['ENDPD'] = $value;}   
                                }else
                                // VALUES $$ DEL ENDOSO
                                if($header == "AL" || $header == "ALVALUE"){
                                    
                                    $value = str_replace(" ","",$value);
                                    $value = str_replace(",","",$value);
                                    $value = str_replace("\$","",$value);
                                    $value = preg_replace('/[^0-9]+/', '', $value);
                                    $value = floatval(trim($value)); 
                                    if($value != ''){$dataArray['AL'] = $value;}
                                }else
                                if($header == "MTC" || $header == "MTCVALUE"){
                                    
                                    $value = str_replace(" ","",$value);
                                    $value = str_replace(",","",$value);
                                    $value = str_replace("\$","",$value);
                                    $value = preg_replace('/[^0-9]+/', '', $value);
                                    $value = floatval(trim($value)); 
                                    if($value != ''){$dataArray['MTC'] = $value;}
                                }else
                                if($header == "PD" || $header == "PDVALUE"){
                                    
                                    $value = str_replace(" ","",$value);
                                    $value = str_replace(",","",$value);
                                    $value = str_replace("\$","",$value);
                                    $value = preg_replace('/[^0-9]+/', '', $value);
                                    $value = floatval(trim($value)); 
                                    if($value != ''){$dataArray['PD'] = $value;}
                                }else
                                if($header == "APPLICATIONDATE" || $header == "APPLICATION" || $header == "APPDATE"){
                                     
                                    if($value == "" || $value == NULL){$error = 1; $mensaje = "Error: Please verify the APP DATE: $value on the column ".($col+1)." and row $row from Excel file in the DRIVERS sheet.";}
                                    else{
                                        
                                       $UNIX_DATE  = ($value - 25569) * 86400;
                                       $EXCEL_DATE = 25569 + ($UNIX_DATE / 86400);
                                       $UNIX_DATE  = ($EXCEL_DATE - 25569) * 86400;
                                       $value      = gmdate("Y-m-d", $UNIX_DATE);
                                       
                                       $dataArray['dFechaAplicacion'] = date("Y-m-d", strtotime($value));
                                       
                                    }
                                }else
                                if($header == "NAME"){
                                    
                                    $value = strtoupper($value);
                                    $value = str_replace(",","",$value);
                                    
                                    if($value == "" || $value == NULL){$error = 1; $mensaje = "Error: Please verify the NAME: $value on the column ".($col+1)." and row $row from Excel file.";}
                                    else{$dataArray['sNombre'] = $value;}
                                     
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
          
                                       $dataArray['dFechaNacimiento'] = date("Y-m-d", strtotime($DOB));
            
                                    } 
                                }else
                                if($header == "LICENSE"){
                                    
                                    $iLicense = strtoupper($value);
                                    $iLicense = str_replace(' ','',nl2br($iLicense));
                                    $iLicense = trim($iLicense);
                                    
                                    if($iLicense != ""){
                                        if(strpos($iLicense,'<br/>') === false){ 
                                          $pos = strpos($iLicense,'-');
                                          if(!($pos === false)){$iLicense = substr($iLicense,0,$pos);}  
                                          //Revisamos si ya existe el driver para esta compañia:
                                          $query  = "SELECT COUNT(iConsecutivo) AS total FROM ct_operadores WHERE iNumLicencia = '$iLicense' AND iConsecutivoCompania = '$iConsecutivoCompania'";
                                          $result = $conexion->query($query);
                                          $items  = $result->fetch_assoc();
                                          
                                          if($items["total"] != "0"){$existe = true;}
                                          
                                          $dataArray['iNumLicencia'] = $iLicense;
                                        
                                        }else{$error = 1;$mensaje = "Error: Please verify the LICENSE $iLicense on the column ".($col+1)." and row $row from Excel file.";}      
                                    }else{$error = 1; $mensaje = "Error: Please verify the LICENSE $iLicense on the row $col from Excel file."; $success = false;} 
                                }else
                                if($header == "YOE"){
                                    $YOE = $value; 
                                    if($YOE != ""){
                                       $dataArray['iExperienciaYear'] = str_replace("+",'',$YOE);
                                    } 
                                }else
                                if($header == "EXPIREDATE"){
                                    $ExpireDate = $value; 
                                    if($ExpireDate != ""){
                                        
                                       $UNIX_DATE  = ($ExpireDate - 25569) * 86400;
                                       $EXCEL_DATE = 25569 + ($UNIX_DATE / 86400);
                                       $UNIX_DATE  = ($EXCEL_DATE - 25569) * 86400;
                                       $ExpireDate = gmdate("Y-m-d", $UNIX_DATE);
                                       
                                       $dataArray['dFechaExpiracionLicencia'] = date("Y-m-d", strtotime($ExpireDate));
                                       
                                    }
                                }else
                                if($header == "TYPE" || $header == "LICENSETYPE"){
                                   $Type = strtoupper($value); 
                                   if($Type != ""){
                                       $dataArray['eTipoLicencia'] = $Type;
                                   } 
                                }
                                
                               
                            }  
                            #INSERT / UPDATE EFFECT:
                            if($error != 0){$success = false;}
                            else{
                               // update:
                               if($existe){
                                   
                                    #----------------------------- ACTUALIZAR OPERADOR -------------------------#
                                    //Campos para actualizar solo registro del operador:
                                    foreach($dataArray as $campo => $valor){
                                        if($campo == "sNombre" || $campo == "dFechaNacimiento" || $campo == "dFechaExpiracionLicencia" || $campo == "eTipoLicencia"){
                                            array_push($valores,"$campo='".date_to_server($valor)."'");
                                        }    
                                    }
                                    
                                    // Agregando campos adicionales:
                                    array_push($valores,"dFechaActualizacion='".date("Y-m-d H:i:s")."'");
                                    array_push($valores,"sIP='".$_SERVER['REMOTE_ADDR']."'");
                                    array_push($valores,"sUsuarioActualizacion='".$_SESSION['usuario_actual']."'");
                                    array_push($valores,"iDeleted='0'"); 
                                    
                                    // Si es un ADD se cambia tambien el metodo de ingreso en la tabla de operdores.
                                    if($dataArray['eAccion'] == "ADD" || $dataArray['eAccion'] == "ADDSWAP"){array_push($valores,"eModoIngreso='ENDORSEMENT'");}
                                   
                                    //Actualizamos el registro del operador:
                                    $query = "UPDATE ct_operadores SET  ".implode(",",$valores)." WHERE iNumLicencia='".$dataArray['iNumLicencia']."' AND iConsecutivoCompania='$iConsecutivoCompania'";
                                    $success = $conexion->query($query);
                                    
                                    if(!($success)){$error = 1; $mensaje = "The data of driver ".$dataArray['iNumLicencia']." was not updated properly, please try again.";}
                                    else{
                                        #ACTUALIZAR TABLA DE POLIZAS/OPERADORES:
                                        $query = "SELECT iConsecutivo FROM ct_operadores WHERE iNumLicencia='$iLicense' AND iConsecutivoCompania='$iConsecutivoCompania'";
                                        $result= $conexion->query($query);
                                        $item  = $result->fetch_assoc();
                                        
                                        $iConsecutivoOperador = $item['iConsecutivo'];
                        
                                    }  
                               } 
                               else{
                                   
                                   
                                   //Campos para actualizar solo registro del operador:
                                   foreach($dataArray as $campo => $valor){
                                        if($campo == "sNombre" || $campo == "dFechaNacimiento" || $campo == "dFechaExpiracionLicencia" || $campo == "eTipoLicencia" || $campo == 'iNumLicencia'){
                                            array_push($campos, $campo);
                                            array_push($valores,date_to_server($valor));
                                        }    
                                   }
                                   
                                   /////
                                    #INSERT INFORMATION: Agregando campos adicionales:
                                    array_push($campos ,"iConsecutivoCompania"); array_push($valores,trim($iConsecutivoCompania)); //<-- Compania
                                    array_push($campos ,"dFechaIngreso");        array_push($valores,date("Y-m-d H:i:s"));
                                    array_push($campos ,"sIP");                  array_push($valores,$_SERVER['REMOTE_ADDR']);
                                    array_push($campos ,"sUsuarioIngreso");      array_push($valores,$_SESSION['usuario_actual']);
                                    array_push($campos ,"eModoIngreso");         array_push($valores,'ENDORSEMENT');
                                    
                                    $query = "INSERT INTO ct_operadores (".implode(",",$campos).") VALUES ('".implode("','",$valores)."')";
                                    $conexion->query($query);
                                    
                                    if($conexion->affected_rows < 1){$error = 1; $success = false; $mensaje = "The data of driver ".$dataArray['iNumLicencia']." was not saved properly, please try again.";}
                                    else{
                                        #ACTUALIZAR TABLA DE POLIZAS/UNIDADES:
                                        $iConsecutivoOperador = $conexion->insert_id;
                                    }
                               }
                               
                               
                               #----------------------------- AGREGAR ENDOSO -------------------------#
                               if($error == 0){ 
                               
                                   $filtro_search      = "";
                                   $iConsecutivoEndoso = "";
                                   if(isset($dataArray['ENDAL'])) {$filtro_search .= "A.sNumeroEndosoBroker = '".$dataArray['ENDAL']."'";} 
                                   if(isset($dataArray['ENDMTC'])){$filtro_search != "" ? $filtro_search .= " OR A.sNumeroEndosoBroker = '".$dataArray['ENDMTC']."'" : $filtro_search = "A.sNumeroEndosoBroker = '".$dataArray['ENDMTC']."'";} 
                                   if(isset($dataArray['ENDPD'])) {$filtro_search != "" ? $filtro_search .= " OR A.sNumeroEndosoBroker = '".$dataArray['ENDPD']."'"  : $filtro_search = "A.sNumeroEndosoBroker = '".$dataArray['ENDPD']."'";}
                                     
                                   if($filtro_search != ""){
                                        //VERIFICAR SI YA EXITE ALGUN ENDOSO CON LOS DATOS DEL DRIVER:
                                        $query  = "SELECT iConsecutivoEndoso ".
                                                  "FROM cb_endoso_estatus AS A ".
                                                  "INNER JOIN cb_endoso   AS B ON A.iConsecutivoEndoso = B.iConsecutivo ".
                                                  "WHERE B.dFechaAplicacion='".$dataArray['dFechaAplicacion']."' AND B.iConsecutivoCompania = '$iConsecutivoCompania' AND B.iConsecutivoTipoEndoso = '2' ".
                                                  "AND ($filtro_search)";  
                                        $result = $conexion->query($query);  
                                        $result = $result->fetch_assoc(); 
                                        
                                        $iConsecutivoEndoso = $result['iConsecutivoEndoso'];  
                                   }
                                            
                                   if($iConsecutivoEndoso == ""){
                                        // ACREGAR ENDOSO:
                                        $query = "INSERT INTO cb_endoso (iConsecutivoCompania, iConsecutivoTipoEndoso, eStatus, sComentarios, dFechaAplicacion, sIP,sUsuarioIngreso,dFechaIngreso) ".
                                                 "VALUES('$iConsecutivoCompania','2','A','".utf8_encode('ENDORSEMENT UPLOADED FROM EXCEL FILE')."', '".$dataArray['dFechaAplicacion']."','$sUsuario','$sIP','$dFecha')";
                                        $conexion->query($query); 
                                        if($conexion->affected_rows < 1){$error = 1; $mensaje = "The data of endorsement was not saved properly, please try again. <br> Error DB: ".$conexion->error;}
                                        else{$iConsecutivoEndoso =  $conexion->insert_id;}    
                                   }
                                   
                                   if($error == 0){
                                       #----------------------------- AGREGAR DETALLE DEL ENDOSO -------------------------#
                                       //verificar si ya existe para el endoso:
                                       $query  = "SELECT COUNT(iConsecutivoOperador) AS total FROM cb_endoso_operador ".
                                                 "WHERE iConsecutivoEndoso='$iConsecutivoEndoso' AND iConsecutivoOperador='$iConsecutivoOperador'";
                                       $result = $conexion->query($query); 
                                       $result = $result->fetch_assoc(); 
                                       
                                       // si no existe, lo agregamos:
                                       if($result['total'] == 0){
                                           $query = "INSERT INTO cb_endoso_operador (iConsecutivoEndoso, iConsecutivoOperador, sNombre, iNumLicencia, eAccion) ".
                                                    "VALUES('$iConsecutivoEndoso','$iConsecutivoOperador', '".$dataArray['sNombre']."', '".$dataArray['iNumLicencia']."', '".$dataArray['eAccion']."')";
                                           $conexion->query($query);
                                           if($conexion->affected_rows < 1){$error = 1; $mensaje = "The data of driver with license #: ".$dataArray['iNumLicencia']." was not saved properly to the endorsement, please try again. <br> Error DB: ".$conexion->error;}
                                       }
                                   }
                                   
                                   // Recorrer polizas:
                                   for($p=0;$p<$count;$p++){
                                       
                                        $iConsecutivoPoliza = $iConsecutivoPolizas[$p]; 
                                        
                                        // AL
                                        if($polizas[$iConsecutivoPoliza]['iTipoPoliza'] == 3){
                                            isset($dataArray['ENDAL']) ? $sNumeroEndosoBroker  =  $dataArray['ENDAL'] : $sNumeroEndosoBroker  = "";  
                                            isset($dataArray['AL'])    ? $rImporteEndosoBroker =  $dataArray['AL']    : $rImporteEndosoBroker = "";
                                        }
                                        // MTC 
                                        else if($polizas[$iConsecutivoPoliza]['iTipoPoliza'] == 2 || $polizas[$iConsecutivoPoliza]['iTipoPoliza'] == 5 || $polizas[$iConsecutivoPoliza]['iTipoPoliza'] == 10){
                                            isset($dataArray['ENDMTC']) ? $sNumeroEndosoBroker  =  $dataArray['ENDMTC'] : $sNumeroEndosoBroker  = "";  
                                            isset($dataArray['MTC'])    ? $rImporteEndosoBroker =  $dataArray['MTC']    : $rImporteEndosoBroker = "";
                                        }
                                        // PD 
                                        else if($polizas[$iConsecutivoPoliza]['iTipoPoliza'] == 1){
                                            isset($dataArray['ENDPD']) ? $sNumeroEndosoBroker  =  $dataArray['ENDPD'] : $sNumeroEndosoBroker  = "";  
                                            isset($dataArray['PD'])    ? $rImporteEndosoBroker =  $dataArray['PD']    : $rImporteEndosoBroker = "";
                                        }
                                        else{$error = 1;$mensaje = "The Endorsements can only be applied to policies of type: AL, MTC OR PD, Please check it.";}
                                        
                                        if($error == 0){
                                        
                                            #-------------------- ACREGAR ENDOSO ESTATUS ---------------------#
                                            //Verificamos si el registro para el estatus de la poliza existe:
                                            $query  = "SELECT COUNT(iConsecutivoEndoso) AS total ".
                                                      "FROM cb_endoso_estatus WHERE iConsecutivoPoliza='$iConsecutivoPoliza' AND iConsecutivoEndoso='$iConsecutivoEndoso'";
                                            $result = $conexion->query($query);
                                            $result = $result->fetch_assoc(); 
                                            
                                            if($result['total'] == 0){
                                                $query   = "INSERT INTO cb_endoso_estatus (iConsecutivoEndoso, iConsecutivoPoliza, eStatus, sNumeroEndosoBroker, rImporteEndosoBroker, sEmail, sIP,sUsuarioIngreso,dFechaIngreso) ".
                                                           "VALUES('$iConsecutivoEndoso','$iConsecutivoPoliza', 'A','$sNumeroEndosoBroker','$rImporteEndosoBroker', 'N/A', '$sIP', '$sUsuario', '$dFecha')";
                                                $conexion->query($query);  
                                                if($conexion->affected_rows < 1){$error = 1; $mensaje = "The data of endorsement policies was not saved properly, please try again.<br> Error DB: ".$conexion->error;}
                                            }
                                            
                                            #----------------- AGREGAR / ACTUALIZAR POLIZA/OPERADOR RELACION ------------------ #
                                            // Verificar si ya existe el registro:
                                            $query  = "SELECT COUNT(iConsecutivoPoliza) AS total FROM cb_poliza_operador ".
                                                      "WHERE iConsecutivoPoliza='$iConsecutivoPoliza' AND iConsecutivoOperador='$iConsecutivoOperador'";
                                            $result = $conexion->query($query);
                                            $result = $result->fetch_assoc();
                                            $eAccion= $dataArray['eAccion'];
                                            
                                            #UPDATE
                                            if($result['total'] != 0){
                                                if($eAccion == "ADD" || $eAccion == "ADDSWAP"){
                                                    //Agregar registro a la tabla de relacion: 
                                                    $query   = "UPDATE cb_poliza_operador SET iDeleted='0',dFechaActualizacion='".$dataArray['dFechaAplicacion']."',sIPActualizacion='$sIP',sUsuarioActualizacion='$sUsuario' ".
                                                               "WHERE iConsecutivoPoliza='$iConsecutivoPoliza' AND iConsecutivoOperador='$iConsecutivoOperador'";
                                                    $success = $conexion->query($query);     
                                                }
                                                else if($eAccion == "DELETE" || $eAccion == "DELETESWAP"){
                                                    //Agregar registro a la tabla de relacion: 
                                                    $query   = "UPDATE cb_poliza_operador SET iDeleted='1',dFechaActualizacion='".$dataArray['dFechaAplicacion']."',sIPActualizacion='$sIP',sUsuarioActualizacion='$sUsuario' ".
                                                               "WHERE iConsecutivoPoliza='$iConsecutivoPoliza' AND iConsecutivoOperador='$iConsecutivoOperador'";
                                                    $success = $conexion->query($query); 
                                                }      
                                            }
                                            #INSERT
                                            else{
                                                if($eAccion == "ADD" || $eAccion == "ADDSWAP"){
                                                    //Agregar registro a la tabla de relacion: 
                                                    $query   = "INSERT INTO cb_poliza_operador (iConsecutivoPoliza,iConsecutivoOperador,eModoIngreso,dFechaIngreso,sIPIngreso,sUsuarioIngreso) ".
                                                               "VALUES('$iConsecutivoPoliza','$iConsecutivoOperador','ENDORSEMENT','".$dataArray['dFechaAplicacion']."','$sIP','$sUsuario')";
                                                    $success = $conexion->query($query);         
                                                }
                                                else if($eAccion == "DELETE" || $eAccion == "DELETESWAP"){
                                                    //Agregar registro a la tabla de relacion: 
                                                    $query   = "INSERT INTO cb_poliza_operador (iConsecutivoPoliza,iConsecutivoOperador,eModoIngreso,dFechaIngreso,sIPIngreso,sUsuarioIngreso,iDeleted) ".
                                                               "VALUES('$iConsecutivoPoliza','$iConsecutivoOperador','ENDORSEMENT','".$dataArray['dFechaAplicacion']."','$sIP','$sUsuario','1')";
                                                    $success = $conexion->query($query);     
                                                }  
                                                
                                            } 
                                           
                                            if(!($success)){$error = 1; $success = false; $mensaje = "The data of driver with license #: ".$dataArray['iNumLicencia']." in the policy was not saved properly, please try again.";}
                                        }
                                   }
                               }
                            }
                            
                            //print_r($dataArray); 
                            if($error == 0){$success_driv ++;}           
                        } 
                        
                        if($success && $error == 0){$conexion->commit(); $mensaje .= "The data of $success_driv drivers endorsements has been updated/added successfully, please verify the data in the company policies.<br><br>";}
                        else{$conexion->rollback();}         
                    }
                    #ENDORSEMENTS/ENDOSOS UNIDADES
                    if($title == "UNITS" && $eTipoLista == "ENDOSOS"){
                        // Transaccion BEGIN:
                        $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
                        $success = true;  
                        
                        // Recorrer sheet por RENGLONES:
                        for($row = 2; $row <= $rows; $row++){
                            
                            // Variables:
                            $existe    = false; 
                            $campos    = array(); //<--- insert
                            $valores   = array(); //<--- insert
                            $endoso    = array(); //<--- insert endoso
                            $dataArray = array();
                            
                            //Recorrer Sheet por COLUMNAS:   
                            for($col = 0; $col < $cols; $col++){
                                
                                $header = strtoupper(trim($objWorksheet->getCellByColumnAndRow($col,1)->getValue()));
                                $value  = trim($objWorksheet->getCellByColumnAndRow($col,$row)->getValue());
                                
                                #CAMPOS PARA ENDOSO:
                                if($header == "ACTION"){
                                    $value = strtoupper(str_replace(" ","",$value)); 
                                    if($value == "" || $value == NULL){$error = 1; $mensaje = "Error: Please verify the ACTION: $value the column ".($col+1)." and row $row from Excel file in the UNITS sheet.";}
                                    else{
                                        //Dar formato en caso de solo contener INICIALES:
                                        if($value == "A"){$value = 'ADD';}else if($value == "D"){$value = "DELETE";}else if($value == "AS"){$value = "ADDSWAP";}else if($value == "DS"){$value = "DELETESWAP";}
                                        $dataArray['eAccion'] = $value;
                                    }   
                                }else
                                //NUMEROS  DE ENDOSO:
                                if($header == "ENDAL#" || $header == "ENDAL" || $header == "AL#"){
                                    $value = strtoupper(str_replace(" ","",$value));
                                    if($value != ""){$dataArray['ENDAL'] = $value;}     
                                }else
                                if($header == "ENDMTC#" || $header == "ENDMTC" || $header == "MTC#"){
                                    $value = strtoupper(str_replace(" ","",$value));
                                    if($value != ""){$dataArray['ENDMTC'] = $value;}    
                                }else
                                if($header == "ENDPD#" || $header == "ENDPD" || $header == "PD#"){
                                    $value = strtoupper(str_replace(" ","",$value));
                                    if($value != ""){$dataArray['ENDPD'] = $value;}   
                                }else
                                // VALUES $$ DEL ENDOSO
                                if($header == "AL" || $header == "ALVALUE"){
                                    $value = str_replace(" ","",$value);
                                    $value = str_replace(",","",$value);
                                    $value = str_replace("\$","",$value);
                                    $value = preg_replace('/[^0-9]+/', '', $value);
                                    $value = floatval(trim($value)); 
                                    if($value != ''){$dataArray['AL'] = $value;}
                                }else
                                if($header == "MTC" || $header == "MTCVALUE"){
                                    $value = str_replace(" ","",$value);
                                    $value = str_replace(",","",$value);
                                    $value = str_replace("\$","",$value);
                                    $value = preg_replace('/[^0-9]+/', '', $value);
                                    $value = floatval(trim($value)); 
                                    if($value != ''){$dataArray['MTC'] = $value;}
                                }else
                                if($header == "PD" || $header == "PDVALUE"){
                                    $value = str_replace(" ","",$value);
                                    $value = str_replace(",","",$value);
                                    $value = str_replace("\$","",$value);
                                    $value = preg_replace('/[^0-9]+/', '', $value);
                                    $value = floatval(trim($value)); 
                                    if($value != ''){$dataArray['PD'] = $value;}
                                }else
                                if($header == "APPLICATIONDATE" || $header == "APPLICATION" || $header == "APPDATE"){
                                    if($value == "" || $value == NULL){$error = 1; $mensaje = "Error: Please verify the APP DATE: $value in row $row from Excel file in the UNITS sheet.";}
                                    else{
                                        
                                       $UNIX_DATE  = ($value - 25569) * 86400;
                                       $EXCEL_DATE = 25569 + ($UNIX_DATE / 86400);
                                       $UNIX_DATE  = ($EXCEL_DATE - 25569) * 86400;
                                       $value      = gmdate("Y-m-d", $UNIX_DATE);
                                       
                                       $dataArray['dFechaAplicacion'] = date("Y-m-d", strtotime($value));
                                       
                                    }
                                }else
                                #CAMPOS PARA VEHICULO
                                if($header == "MAKE"){
                                    $value = strtoupper($value); 
                                    //Revisamos si existe el modelo en el catalogo:
                                    $query  = "SELECT iConsecutivo AS Make FROM ct_unidad_modelo WHERE sDescripcion = '$value' OR sAlias = '$value'";
                                    $result = $conexion->query($query);
                                    $items  = $result->fetch_assoc();
                                    $iMake  = $items["Make"];
                                    if($iMake != ""){ $dataArray['iModelo'] = $iMake;}
                                }else
                                if($header == "VIN"){  
                                    $sVIN = utf8_decode(preg_replace("[A-Za-z0-9]","",utf8_decode($value)));
                                    $sVIN = str_replace(' ','',nl2br($sVIN)); 
                                    $sVIN = str_replace('?','',nl2br($sVIN));
                                    
                                    if($sVIN != ""){
                                        if(strlen($sVIN) > 18){$error = 1;$mensaje = "Error: Please verify the VIN \"$sVIN\" on the column ".($col+1)." and row $row from Excel file.";}
                                        else{  
                                              //Revisamos si ya existe la unidad para esta compañia:
                                              $query  = "SELECT COUNT(iConsecutivo) AS total FROM ct_unidades WHERE sVIN='$sVIN' AND iConsecutivoCompania = '$iConsecutivoCompania'";
                                              $result = $conexion->query($query);
                                              $items  = $result->fetch_assoc();
                                              
                                              if($items["total"] != "0"){$existe = true;}
                                              
                                              $dataArray['sVIN'] = $sVIN;
                                              
                                        } 
                                
                                    }
                                    else{$error = 1; $mensaje = "Error: Please verify the VIN \"$sVIN\" on the column ".($col+1)." and row $row from Excel file."; $success = false;}
                                }else
                                if($header == "RADIUS"){
                                    $iRadius = trim($value);
                                    $iRadius = str_replace(' ','',$iRadius);
                                  
                                    //Revisamos si existe el Radius en el catalogo: 
                                    $query  = "SELECT iConsecutivo AS Radius FROM ct_unidad_radio WHERE sDescripcion = '$iRadius'";
                                    $result = $conexion->query($query);
                                    $items  = $result->fetch_assoc();
                                    $iRadius= $items["Radius"];
                                    
                                    if($iRadius != ""){$dataArray['iConsecutivoRadio'] = $iRadius;}
                                }else
                                if($header == "TYPE"){
                                    $value = strtoupper(trim($value)); 
                                    if($value == 'UNIT' || $value == 'TRAILER' || $value == "TRACTOR"){ $dataArray['sTipo'] = $value;}
                                }else
                                if($header == "YEAR"){
                                    $value = intval(trim($value)); 
                                    if($value > 0){ $dataArray['iYear'] = $value;}
                                }else
                                if($header == "TOTALPREMIUM" || $header == "PD" || $header == "TOTALPREMIUMPD"){
                                    $value = trim($value);
                                    $value = str_replace(" ","",$value);
                                    $value = str_replace(",","",$value);
                                    $value = str_replace("\$","",$value);
                                    $value = preg_replace('/[^0-9]+/', '', $value);
                                    $value = intval(trim($value)); 
                                    if($value != ''){ $dataArray['iTotalPremiumPD'] = $value;}
                                }
                                
                            }  
                            
                            
                        
                            #INSERT / UPDATE EFFECT:
                            if($error != 0){$success = false;}
                            else{
                               
                               // update:
                               if($existe){
                                   
                                    #----------------------------- ACTUALIZAR UNIDAD -------------------------#
                                    //Campos para actualizar solo registro del vehiculo:
                                    foreach($dataArray as $campo => $valor){
                                        if($campo == "iYear" || $campo == "iModelo" || $campo == "iConsecutivoRadio" || $campo == "sTipo"){
                                            array_push($valores,"$campo='".date_to_server($valor)."'");
                                        }    
                                    }
                                    
                                    // Agregando campos adicionales:
                                    array_push($valores,"dFechaActualizacion='".date("Y-m-d H:i:s")."'");
                                    array_push($valores,"sIP='".$_SERVER['REMOTE_ADDR']."'");
                                    array_push($valores,"sUsuarioActualizacion='".$_SESSION['usuario_actual']."'");
                                    array_push($valores,"iDeleted='0'"); 
                                    
                                    // Si es un ADD se cambia tambien el metodo de ingreso en la tabla de operdores.
                                    if($dataArray['eAccion'] == "ADD" || $dataArray['eAccion'] == "ADDSWAP"){array_push($valores,"eModoIngreso='ENDORSEMENT'");}
                                   
                                    //Actualizamos el registro del operador:
                                    $query = "UPDATE ct_unidades SET  ".implode(",",$valores)." WHERE sVIN='".$dataArray['sVIN']."' AND iConsecutivoCompania='$iConsecutivoCompania'";
                                    $success = $conexion->query($query);
                                    
                                    if(!($success)){$error = 1; $mensaje = "The data of vehicle: ".$dataArray['sVIN']." was not updated properly, please try again.";}
                                    else{
                                        #ACTUALIZAR TABLA DE POLIZAS/VEHICULOS:
                                        $query = "SELECT iConsecutivo FROM ct_unidades WHERE sVIN='".$dataArray['sVIN']."' AND iConsecutivoCompania='$iConsecutivoCompania'";
                                        $result= $conexion->query($query);
                                        $item  = $result->fetch_assoc();
                                        
                                        $iConsecutivoUnidad = $item['iConsecutivo'];
                        
                                    }  
                               } 
                               else{
                                   
                                   
                                   //Campos para actualizar solo registro del operador:
                                   foreach($dataArray as $campo => $valor){
                                        if($campo == "iYear" || $campo == "iModelo" || $campo == "iConsecutivoRadio" || $campo == "sTipo" || $campo == "sVIN"){
                                            array_push($campos, $campo);
                                            array_push($valores,date_to_server($valor));
                                        }    
                                   }
                                   
                                   /////
                                    #INSERT INFORMATION: Agregando campos adicionales:
                                    array_push($campos ,"iConsecutivoCompania"); array_push($valores,trim($iConsecutivoCompania)); //<-- Compania
                                    array_push($campos ,"dFechaIngreso");        array_push($valores,date("Y-m-d H:i:s"));
                                    array_push($campos ,"sIP");                  array_push($valores,$_SERVER['REMOTE_ADDR']);
                                    array_push($campos ,"sUsuarioIngreso");      array_push($valores,$_SESSION['usuario_actual']);
                                    array_push($campos ,"eModoIngreso");         array_push($valores,'ENDORSEMENT');
                                    
                                    $query = "INSERT INTO ct_unidades (".implode(",",$campos).") VALUES ('".implode("','",$valores)."')";
                                    $conexion->query($query);
                                    
                                    if($conexion->affected_rows < 1){$error = 1; $success = false; $mensaje = "The data of vehicle ".$dataArray['sVIN']." was not saved properly, please try again.";}
                                    else{
                                        #ACTUALIZAR TABLA DE POLIZAS/UNIDADES:
                                        $iConsecutivoUnidad = $conexion->insert_id;
                                    }
                               } 
                               
                               #----------------------------- AGREGAR ENDOSO -------------------------#
                               if($error == 0){ 
                               
                                   $filtro_search      = "";
                                   $iConsecutivoEndoso = "";
                                   if(isset($dataArray['ENDAL'])) {$filtro_search .= "A.sNumeroEndosoBroker = '".$dataArray['ENDAL']."'";} 
                                   if(isset($dataArray['ENDMTC'])){$filtro_search != "" ? $filtro_search .= " OR A.sNumeroEndosoBroker = '".$dataArray['ENDMTC']."'" : $filtro_search = "A.sNumeroEndosoBroker = '".$dataArray['ENDMTC']."'";} 
                                   if(isset($dataArray['ENDPD'])) {$filtro_search != "" ? $filtro_search .= " OR A.sNumeroEndosoBroker = '".$dataArray['ENDPD']."'"  : $filtro_search = "A.sNumeroEndosoBroker = '".$dataArray['ENDPD']."'";}
                                     
                                   if($filtro_search != ""){
                                        //VERIFICAR SI YA EXITE ALGUN ENDOSO CON LOS DATOS DEL VEHICULO:
                                        $query  = "SELECT iConsecutivoEndoso ".
                                                  "FROM cb_endoso_estatus AS A ".
                                                  "INNER JOIN cb_endoso   AS B ON A.iConsecutivoEndoso = B.iConsecutivo ".
                                                  "WHERE B.dFechaAplicacion='".$dataArray['dFechaAplicacion']."' AND B.iConsecutivoCompania = '$iConsecutivoCompania' AND B.iConsecutivoTipoEndoso = '1' ".
                                                  "AND ($filtro_search)";  
                                        $result = $conexion->query($query);  
                                        $result = $result->fetch_assoc(); 
                                        
                                        $iConsecutivoEndoso = $result['iConsecutivoEndoso'];  
                                   }
                                            
                                   if($iConsecutivoEndoso == ""){
                                        // ACREGAR ENDOSO:
                                        $query = "INSERT INTO cb_endoso (iConsecutivoCompania, iConsecutivoTipoEndoso, eStatus, sComentarios, dFechaAplicacion, sIP,sUsuarioIngreso,dFechaIngreso) ".
                                                 "VALUES('$iConsecutivoCompania','1','A','".utf8_encode('ENDORSEMENT UPLOADED FROM EXCEL FILE')."', '".$dataArray['dFechaAplicacion']."','$sUsuario','$sIP','$dFecha')";
                                        $conexion->query($query); 
                                        if($conexion->affected_rows < 1){$error = 1; $mensaje = "The data of endorsement was not saved properly, please try again. <br> Error DB: ".$conexion->error;}
                                        else{$iConsecutivoEndoso =  $conexion->insert_id;}    
                                   }
                                   
                                   #----------------------------- AGREGAR DETALLE DEL ENDOSO -------------------------#
                                   if($error == 0){
                                       
                                       //verificar si ya existe para el endoso:
                                       $query  = "SELECT COUNT(iConsecutivoUnidad) AS total FROM cb_endoso_unidad ".
                                                 "WHERE iConsecutivoEndoso='$iConsecutivoEndoso' AND iConsecutivoUnidad='$iConsecutivoUnidad'";
                                       $result = $conexion->query($query); 
                                       $result = $result->fetch_assoc(); 
                                       
                                       // si no existe, lo agregamos:
                                       if($result['total'] == 0){
                                           $cadena1 = ""; $cadena2 = "";
                                           if(isset($dataArray['iConsecutivoRadio'])){$cadena1 = ",iConsecutivoRadio";$cadena2 = ", '".$dataArray['iConsecutivoRadio']."'";}
                                           if(isset($dataArray['iTotalPremiumPD']))  {$cadena1.= ",iTotalPremiumPD";  $cadena2.= ", '".$dataArray['iTotalPremiumPD']."'";}
                                           
                                           $query = "INSERT INTO cb_endoso_unidad (iConsecutivoEndoso, iConsecutivoUnidad, sVIN, eAccion $cadena1) ".
                                                    "VALUES('$iConsecutivoEndoso','$iConsecutivoUnidad', '".$dataArray['sVIN']."', '".$dataArray['eAccion']."' $cadena2)";
                                           $conexion->query($query);
                                           if($conexion->affected_rows < 1){$error = 1; $mensaje = "The data of vehicle with VIN #: ".$dataArray['sVIN']." was not saved properly to the endorsement, please try again. <br> Error DB: ".$conexion->error;}
                                       }
                                   }
                                   
                                   // Recorrer polizas:
                                   for($p=0;$p<$count;$p++){
                                       
                                        $iConsecutivoPoliza = $iConsecutivoPolizas[$p]; 
                                        
                                        // AL
                                        if($polizas[$iConsecutivoPoliza]['iTipoPoliza'] == 3){
                                            isset($dataArray['ENDAL']) ? $sNumeroEndosoBroker  =  $dataArray['ENDAL'] : $sNumeroEndosoBroker  = "";  
                                            isset($dataArray['AL'])    ? $rImporteEndosoBroker =  $dataArray['AL']    : $rImporteEndosoBroker = "";
                                        }
                                        // MTC 
                                        else if($polizas[$iConsecutivoPoliza]['iTipoPoliza'] == 2 || $polizas[$iConsecutivoPoliza]['iTipoPoliza'] == 5 || $polizas[$iConsecutivoPoliza]['iTipoPoliza'] == 10){
                                            isset($dataArray['ENDMTC']) ? $sNumeroEndosoBroker  =  $dataArray['ENDMTC'] : $sNumeroEndosoBroker  = "";  
                                            isset($dataArray['MTC'])    ? $rImporteEndosoBroker =  $dataArray['MTC']    : $rImporteEndosoBroker = "";
                                        }
                                        // PD 
                                        else if($polizas[$iConsecutivoPoliza]['iTipoPoliza'] == 1){
                                            isset($dataArray['ENDPD']) ? $sNumeroEndosoBroker  =  $dataArray['ENDPD'] : $sNumeroEndosoBroker  = "";  
                                            isset($dataArray['PD'])    ? $rImporteEndosoBroker =  $dataArray['PD']    : $rImporteEndosoBroker = "";
                                        }
                                        else{$error = 1;$mensaje = "The Endorsements can only be applied to policies of type: AL, MTC OR PD, Please check it.";}
                                        
                                        if($error == 0){
                                        
                                            #-------------------- ACREGAR ENDOSO ESTATUS ---------------------#
                                            //Verificamos si el registro para el estatus de la poliza existe:
                                            $query  = "SELECT COUNT(iConsecutivoEndoso) AS total ".
                                                      "FROM cb_endoso_estatus WHERE iConsecutivoPoliza='$iConsecutivoPoliza' AND iConsecutivoEndoso='$iConsecutivoEndoso'";
                                            $result = $conexion->query($query);
                                            $result = $result->fetch_assoc(); 
                                            
                                            if($result['total'] == 0){
                                                $query   = "INSERT INTO cb_endoso_estatus (iConsecutivoEndoso, iConsecutivoPoliza, eStatus, sNumeroEndosoBroker, rImporteEndosoBroker, sEmail, sIP,sUsuarioIngreso,dFechaIngreso) ".
                                                           "VALUES('$iConsecutivoEndoso','$iConsecutivoPoliza', 'A','$sNumeroEndosoBroker','$rImporteEndosoBroker', 'N/A', '$sIP', '$sUsuario', '$dFecha')";
                                                $conexion->query($query);  
                                                if($conexion->affected_rows < 1){$error = 1; $mensaje = "The data of endorsement policies was not saved properly, please try again.<br> Error DB: ".$conexion->error;}
                                            }
                                            
                                            #----------------- AGREGAR / ACTUALIZAR POLIZA/VEHICULO RELACION ------------------ #
                                            // Verificar si ya existe el registro:
                                            $query  = "SELECT COUNT(iConsecutivoPoliza) AS total FROM cb_poliza_unidad ".
                                                      "WHERE iConsecutivoPoliza='$iConsecutivoPoliza' AND iConsecutivoUnidad='$iConsecutivoUnidad'";
                                            $result = $conexion->query($query);
                                            $result = $result->fetch_assoc();
                                            $eAccion= $dataArray['eAccion'];
                                            
                                            #UPDATE
                                            if($result['total'] != 0){
                                                if($eAccion == "ADD" || $eAccion == "ADDSWAP"){
                                                    //Agregar registro a la tabla de relacion: 
                                                    $query   = "UPDATE cb_poliza_unidad SET iDeleted='0',dFechaActualizacion='".$dataArray['dFechaAplicacion']."',sIPActualizacion='$sIP',sUsuarioActualizacion='$sUsuario' ".
                                                               "WHERE iConsecutivoPoliza='$iConsecutivoPoliza' AND iConsecutivoUnidad='$iConsecutivoUnidad'";
                                                    $success = $conexion->query($query);     
                                                }
                                                else if($eAccion == "DELETE" || $eAccion == "DELETESWAP"){
                                                    //Agregar registro a la tabla de relacion: 
                                                    $query   = "UPDATE cb_poliza_unidad SET iDeleted='1',dFechaActualizacion='".$dataArray['dFechaAplicacion']."',sIPActualizacion='$sIP',sUsuarioActualizacion='$sUsuario' ".
                                                               "WHERE iConsecutivoPoliza='$iConsecutivoPoliza' AND iConsecutivoUnidad='$iConsecutivoUnidad'";
                                                    $success = $conexion->query($query); 
                                                }      
                                            }
                                            #INSERT
                                            else{
                                                if($eAccion == "ADD" || $eAccion == "ADDSWAP"){
                                                    //Agregar registro a la tabla de relacion: 
                                                    $query   = "INSERT INTO cb_poliza_unidad (iConsecutivoPoliza,iConsecutivoUnidad,eModoIngreso,dFechaIngreso,sIPIngreso,sUsuarioIngreso) ".
                                                               "VALUES('$iConsecutivoPoliza','$iConsecutivoUnidad','ENDORSEMENT','".$dataArray['dFechaAplicacion']."','$sIP','$sUsuario')";
                                                    $success = $conexion->query($query);         
                                                }
                                                else if($eAccion == "DELETE" || $eAccion == "DELETESWAP"){
                                                    //Agregar registro a la tabla de relacion: 
                                                    $query   = "INSERT INTO cb_poliza_unidad (iConsecutivoPoliza,iConsecutivoUnidad,eModoIngreso,dFechaIngreso,sIPIngreso,sUsuarioIngreso,iDeleted) ".
                                                               "VALUES('$iConsecutivoPoliza','$iConsecutivoUnidad','ENDORSEMENT','".$dataArray['dFechaAplicacion']."','$sIP','$sUsuario','1')";
                                                    $success = $conexion->query($query);     
                                                }  
                                                
                                            } 
                                           
                                            if(!($success)){$error = 1; $success = false; $mensaje = "The data of vehicle with VIN #: ".$dataArray['sVIN']." in the policy was not saved properly, please try again.";}
                                        }
                                   }
                               }
                               // termina agregar endoso unidad
                            } 
                            
                            if($error == 0){$success_unit ++;}          
                        } 
                        
                        if($success && $error == 0){$conexion->commit(); $mensaje .= "The data of $success_unit vehicle endorsements has been updated/added successfully, please verify the data in the company policies.<br><br>";}
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
