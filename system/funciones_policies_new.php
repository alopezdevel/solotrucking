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
      $iConsecutivoCompania = $_POST['iConsecutivoCompania'];
      $iConsecutivoPolizas  = $_POST['iConsecutivoPolizas'];
      $iConsecutivoPolizas  = explode(',',$iConsecutivoPolizas);
      $error = 0;
      $mensaje = "";
      $htmlTabla = ""; 
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
            $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
            $transaccion_exitosa = true;
          
            #CONTAMOS LAS SHEET:
            $sheetCount = $objPHPExcel->getSheetCount();
            if($sheetCount <= 0){$error = 1; $mensaje = "The Excel file is empty, please check it.";}
            else{
                for($x = 0;$x<$sheetCount;$x++){
                    
                    // Obtener nombre del sheet:
                    $objWorksheet  = $objPHPExcel->setActiveSheetIndex($x);
                    $title         = $objWorksheet->getTitle();
                    
                    // Calcular no. de renglones y columnas:
                    $rows = $objWorksheet->getHighestRow();
                    $cols = $objWorksheet->getHighestColumn();
                    
                    #UNIDADES/VEHICLES
                    if($title == "UNITS" && $error == 0){
                        // Recorrer sheet por RENGLONES:
                        for($i = 2; $i <= $rows; $i++){
                            
                            // Variables:
                            $campos_unit       = array(); //<--- insert
                            $valores_unit      = array(); //<--- insert
                            $update_unit_array = array(); //<--- update
                            $success_unit      = 0;
                            $existe            = 0; 
                            
                            //Recorrer Sheet por COLUMNAS:   
                            for($z = 1; $z <= $col; $z++){
                                
                            }             
                        }    
                    }
                    
                    #DRIVERS/OPERADORES
                    if($title == "DRIVERS" && $error == 0){
                        
                    }
                    // Verificar en caso que suban un sheet con un nombre invalido:
                    if($title != 'UNITS' && $title != 'DRIVERS'){
                        $error = 1; $mensaje = "Error: The title of sheet '$title' is not valid, please upload the file with the layout format and try again.";
                    }
                     
                }
            }
            
            /*$sheetNames    = $objPHPExcel->getSheetNames();
            $sheet         = $objPHPExcel->getSheet(0); 
            $highestRow    = $sheet->getHighestRow(); 
            $highestColumn = $sheet->getHighestColumn(); */
      }  
      
      !($transaccion_exitosa) || $error == 1 ? $conexion->rollback() : $conexion->commit();
      
      $response = array("mensaje"=>"$mensaje","error"=>"$error", "name_file" => "$fileName"); 
      echo json_encode($response); 
      
  }
  
  
  
?>
