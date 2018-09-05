<?php
  session_start();
  // Generic functions lib 
  include("functiones_genericas.php"); 
  $_POST["accion"] and  $_POST["accion"]!= "" ? call_user_func_array($_POST["accion"],array()) : ""; 
  define('USER',$_SESSION['usuario_actual']); // Constante UserId 
  
  function get_endorsements(){
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      $registros_por_pagina = $_POST["registros_por_pagina"];
      $pagina_actual = (isset($_POST["pagina_actual"]) && $_POST["pagina_actual"] != '' ? $_POST["pagina_actual"] : 1);
      $registros_por_pagina == "" ? $registros_por_pagina = 15 : false;
        
     //Filtros de informacion //
     $filtroQuery = " WHERE B.eStatus != 'E' AND iConsecutivoTipoEndoso = '1' ";
     $array_filtros = explode(",",$_POST["filtroInformacion"]);
     foreach($array_filtros as $key => $valor){
        if($array_filtros[$key] != ""){
            $campo_valor = explode("|",$array_filtros[$key]);
            if($campo_valor[0] == 'iConsecutivo'){ 
                $filtroQuery.= " AND  ".$campo_valor[0]."='".$campo_valor[1]."' ";
            }else{
                if($campo_valor[0] == 'eStatus'){
                     $filtroQuery .= " AND  ".$campo_valor[0]." = '".$campo_valor[1]."'";
                }else{
                     $filtroQuery.= " AND  ".$campo_valor[0]." LIKE '%".$campo_valor[1]."%'";
                }   
            } 
        }
     }
     // ordenamiento//
     $ordenQuery = " ORDER BY ".$_POST["ordenInformacion"]." ".$_POST["sortInformacion"];

    //contando registros // 
    $query_rows = "SELECT COUNT(B.iConsecutivo) AS total ".
                  "FROM cb_endoso_estatus A ".
                  "LEFT JOIN cb_endoso B ON A.iConsecutivoEndoso = B.iConsecutivo ".
                  "LEFT JOIN ct_tipo_endoso C ON B.iConsecutivoTipoEndoso = C.iConsecutivo  ".
                  "LEFT JOIN ct_companias D ON B.iConsecutivoCompania = D.iConsecutivo  ".
                  "LEFT JOIN ct_operadores E ON B.iConsecutivoOperador = E.iConsecutivo  ".
                  "LEFT JOIN ct_unidades F ON B.iConsecutivoUnidad = F.iConsecutivo ".
                  "LEFT JOIN ct_polizas P ON A.iConsecutivoPoliza = P.iConsecutivo ". 
                  "LEFT JOIN ct_brokers BR ON P.iConsecutivoBrokers = BR.iConsecutivo ".$filtroQuery;
    $Result = $conexion->query($query_rows);
    $items = $Result->fetch_assoc();
    $registros = $items["total"];
    if($registros == "0"){$pagina_actual = 0;}
    $paginas_total = ceil($registros / $registros_por_pagina);
    
    if($registros == "0"){
        $limite_superior = 0;
        $limite_inferior = 0;
        $htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";
    }else{
      $pagina_actual == "0" ? $pagina_actual = 1 : false;
      $limite_superior = $registros_por_pagina;
      $limite_inferior = ($pagina_actual*$registros_por_pagina)-$registros_por_pagina;
      $sql = "SELECT B.iConsecutivo, D.sNombreCompania,  DATE_FORMAT(B.dFechaAplicacion,  '%m/%d/%Y %H:%i') AS dFechaIngreso, C.sDescripcion, A.eStatus, eAccion, D.iOnRedList, sVIN, sNumeroPoliza, sName AS broker, A.iConsecutivoPoliza ".
             "FROM cb_endoso_estatus A ".
             "LEFT JOIN cb_endoso B ON A.iConsecutivoEndoso = B.iConsecutivo ".
             "LEFT JOIN ct_tipo_endoso C ON B.iConsecutivoTipoEndoso = C.iConsecutivo  ".
             "LEFT JOIN ct_companias D ON B.iConsecutivoCompania = D.iConsecutivo  ".
             "LEFT JOIN ct_unidades F ON B.iConsecutivoUnidad = F.iConsecutivo ".
             "LEFT JOIN ct_polizas P ON A.iConsecutivoPoliza = P.iConsecutivo ".
             "LEFT JOIN ct_brokers BR ON P.iConsecutivoBrokers = BR.iConsecutivo".$filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior;
      $result = $conexion->query($sql);
      $rows = $result->num_rows; 
         
        if ($rows > 0) {    
            while ($usuario = $result->fetch_assoc()) { 
               if($usuario["iConsecutivo"] != ""){
                     $btn_confirm = "";
                     $estado = "";
                     $class = "";
                     $descripcion = ""; 
                     switch($usuario["eStatus"]){
                         case 'S': 
                            $estado = 'NEW APPLICATION';
                            $class = "class = \"blue\"";
                            $btn_confirm = "<div class=\"btn_edit btn-icon edit btn-left\" title=\"View and Edit Endorsement Status\"><i class=\"fa fa-pencil-square-o\"></i> <span></span>".
                                           "</div><div class=\"btn_send_email btn-icon send-email btn-left\" title=\"Send e-mail to the brokers\"><i class=\"fa fa-envelope\"></i><span></span></div>"; 
                         break;
                         case 'A': 
                            $estado = 'APPROVED';
                            $class = "class = \"green\"";
                            $btn_confirm = "<div class=\"btn_edit btn-icon edit btn-left\" title=\"View and Edit Endorsement Status\"><i class=\"fa fa-pencil-square-o\"></i> <span></span>"; 
                         break;
                         case 'D': 
                            $estado = 'DENIED';
                            $class = "class = \"red\"";
                            $btn_confirm = "<div class=\"btn_edit btn-icon edit btn-left\" title=\"View and Edit Endorsement Status\"><i class=\"fa fa-pencil-square-o\"></i> <span></span>";
                                        
                         break;
                         case 'SB': 
                            $estado = 'SENT TO BROKERS';
                            $class = "class = \"yellow\"";
                            $btn_confirm = "<div class=\"btn_edit btn-icon edit btn-left\" title=\"View and Edit Endorsement Status\"><i class=\"fa fa-pencil-square-o\"></i> <span></span>"; 
                         break;
                         case 'P': 
                            $estado = 'IN PROCESS';
                            $class = "class = \"orange\"";
                            $btn_confirm = "<div class=\"btn_edit btn-icon edit btn-left\" title=\"View and Edit Endorsement Status\"><i class=\"fa fa-pencil-square-o\"></i> <span></span>".
                                           "</div><div class=\"btn_send_email btn-icon send-email btn-left\" title=\"Send e-mail to the brokers\"><i class=\"fa fa-envelope\"></i><span></span></div>"; 
                         break;
                     } 
                     $color_action = "";
                     $action = "";
                     switch($usuario["eAccion"]){
                         case 'A': 
                            $action = 'ADD';
                            $color_action = "color:#00970d"; 
                         break;
                         case 'D': 
                            $action = 'DELETE'; 
                            $color_action = "color:#ab0000"; 
                         break;
                     }
                     
                     
                      //Redlist:
                     $usuario['iOnRedList'] == '1' ? $redlist_icon = "<i class=\"fa fa-star\" style=\"color:#e8051b;margin-right:4px;\"></i>" : $redlist_icon = ""; 
                     $htmlTabla .= "<tr $class>
                                        <td>".$usuario['iConsecutivo']."</td>".
                                       "<td>".$redlist_icon.$usuario['sNombreCompania']."</td>".
                                       "<td>".strtoupper($usuario['sVIN'])."</td>". 
                                       "<td style=\"$color_action\">".$action."</td>".
                                       "<td class=\"text-center\">".$usuario['dFechaIngreso']."</td>". 
                                       "<td id=\"".$usuario['iConsecutivoPoliza']."\">".$usuario['sNumeroPoliza']."</td>".
                                       "<td>".$usuario['broker']."</td>".
                                       "<td class=\"text-center\">".$estado."</td>".                                                                                                                                                                                                                       
                                       "<td> $btn_confirm</td></tr>";
                 }else{                                                                                                                                                                                                        
                    
                     $htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>"   ;
                 }    
            }
            $conexion->rollback();
            $conexion->close();                                                                                                                                                                       
        } else { 
            
            $htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>"   ;    
            
        }
      }
      $response = array("total"=>"$paginas_total","pagina"=>"$pagina_actual","tabla"=>"$htmlTabla","mensaje"=>"$mensaje","error"=>"$error","tabla"=>"$htmlTabla");   
      echo json_encode($response); 
  }
  function get_endorsement(){
      #Err flags:
      $error = '0';
      $msj   = "";
      #Variables
      $fields   = "";
      $clave    = trim($_POST['clave']);
      $idPoliza = trim($_POST['idPoliza']);
      $domroot  = $_POST['domroot'];
      
      #Function Begin
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                 
      $sql    = "SELECT A.iConsecutivo, iConsecutivoCompania, iConsecutivoTipoEndoso, A.eStatus, iReeferYear,iTrailerExchange, iPDAmount, ". 
                "A.sComentarios, iPDApply, iConsecutivoUnidad, eAccion   ".  
                "FROM cb_endoso A ".
                "LEFT JOIN ct_tipo_endoso B ON A.iConsecutivoTipoEndoso = B.iConsecutivo ". 
                "WHERE A.iConsecutivo = '$clave'";
      $result = $conexion->query($sql);
      $items  = $result->num_rows; 
      if ($items > 0) {     
            
            $data = $result->fetch_assoc(); //<---Endorsement Data Array.
            #Cambiando texto de Action:
            if($data['eAccion'] == 'A'){$data['eAccion']= 'ADD'; }else if($data['eAccion'] == 'D'){$data['eAccion']  = 'DELETE';}
            
            $llaves  = array_keys($data);
            $datos   = $data; 
            foreach($datos as $i => $b){ 
                if($i != 'eStatus' && $i != 'sComentarios' && $i != 'iConsecutivoPoliza' && $i != 'iConsecutivoTipoEndoso'){
                  $fields .= "\$('#$domroot :input[id=".$i."]').val('".$datos[$i]."');";  
                }else if($i == 'sComentarios'){
                  $comentarios = utf8_decode($datos[$i]);  
                }    
            }
            
            //CONSULTAMOS LA TABLA DE POLIZAS LIGADA AL ENDOSO               
            $query  = "SELECT iConsecutivoPoliza, B.iTipoPoliza ".
                      "FROM cb_endoso_estatus AS A INNER JOIN ct_polizas AS B ON A.iConsecutivoPoliza = B.iConsecutivo AND B.iDeleted = '0' ".
                      "WHERE iConsecutivoEndoso = '$clave'"; 
            $result = $conexion->query($query);
            $rows   = $result->num_rows; 
            if($rows > 0){
                while($policies = $result->fetch_assoc()){
                   $policies_checkbox .= "\$('#$domroot :checkbox[value=".$policies['iConsecutivoPoliza']."]').prop('checked',true);";
                   //SI LA POLIZA ES DE PD
                   if($policies['iTipoPoliza'] == '1'){
                       $policies_checkbox.= "\$('#$domroot :input[name=iPDAmount]').removeProp('readonly');"; 
                   }            
                }
                       
            }  
                
            //SI LA UNIDAD EXISTE EN EL CATALOGO:
            if($data['iConsecutivoUnidad'] != '' && $data['iConsecutivoTipoEndoso']== '1'){
                $sql2 = "SELECT iConsecutivo AS iConsecutivoUnidad, iConsecutivoRadio, iYear, iModelo, sVIN AS sUnitTrailer, sModelo, sTipo ".    
                        "FROM ct_unidades A ".
                        "WHERE A.iConsecutivo = '".$data['iConsecutivoUnidad']."'";
                $result = $conexion->query($sql2);
                $items2 = $result->num_rows;
                if ($items2 > 0) {
                    
                     $data2    = $result->fetch_assoc();
                     $llaves2  = array_keys($data2);
                     $datos2   = $data2;
                     
                     foreach($datos2 as $i => $b){     
                        $fields .= "\$('#$domroot :input[id=".$i."]').val('".$datos2[$i]."');";
                     }
                }
            }
            $eStatus    = $data['eStatus'];  
      }
      $conexion->rollback();
      $conexion->close(); 
      $response = array(
                    "msj"=>"$msj",
                    "error"=>"$error",
                    "fields"=>"$fields",
                    "policies"=>"$policies_checkbox",
                    "sComentarios" => "$comentarios"
                  );   
      echo json_encode($response);  
      
  }
  function get_policies(){
      
      include("cn_usuarios.php");
      $company = trim($_POST['iConsecutivoCompania']);
      $conexion->autocommit(FALSE);
      $error          = '0';
      $pd_information = "false";
      
      $sql = "SELECT A.iConsecutivo, sNumeroPoliza, C.sName AS BrokerName, sDescripcion, D.iConsecutivo AS TipoPoliza ".
             "FROM ct_polizas A ".
             "LEFT JOIN ct_brokers C ON A.iConsecutivoBrokers = C.iConsecutivo ".
             "LEFT JOIN ct_tipo_poliza D ON A.iTipoPoliza = D.iConsecutivo ".
             "WHERE iConsecutivoCompania = '".$company."' ".
             "AND  A.iDeleted = '0' AND dFechaCaducidad >= CURDATE() AND (D.iConsecutivo = '1' OR D.iConsecutivo = '3' OR D.iConsecutivo = '5' OR D.iConsecutivo = '2') ".
             "ORDER BY sNumeroPoliza ASC";  
      $result = $conexion->query($sql);
      $rows = $result->num_rows;
      
      if($rows > 0) {   
            while ($items = $result->fetch_assoc()) { 
                $pdvalid = "";
                switch($items['TipoPoliza']){
                     case '1' : 
                        $pd_information = 'true'; 
                        $pdvalid = "onchange=\"if(\$(this).prop('checked')){\$('#frm_endorsement_information input[name=iPDAmount]').removeProp('readonly').removeClass('readonly');}".
                                   "else{\$('#frm_endorsement_information input[name=iPDAmount]').prop('readonly','readonly').addClass('readonly');}\"";
                     break;
                }
               
               $htmlTabla .= "<tr>".
                             "<td style=\"border: 1px solid #dedede;\">".
                             "<input name=\"chk_policies_endoso\" type=\"checkbox\" value=\"".$items['iConsecutivo']."\" $pdvalid/>".
                             "<label class=\"check-label\">".$items['sNumeroPoliza']."</label>".
                             "</td>".
                             "<td style=\"border: 1px solid #dedede;\">".$items['BrokerName']."</td>". 
                             "<td style=\"border: 1px solid #dedede;\">".$items['sDescripcion']."</td>".
                             "</tr>";
      
                    
            }                                                                                                                                                                       
        }else{$htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";}
        $conexion->rollback();
        $conexion->close();
        $response = array(
                "mensaje"=>"$mensaje",
                "error"=>"$error",
                "policies_information"=>"$htmlTabla",
                "pd_data"=>"$pd_information",
                );   
        echo json_encode($response);
  }
  #FUNCIONES UNITS:
  function get_units_autocomplete(){
      
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);
      $company = trim($_POST['iConsecutivoCompania']);
      $error   = "0";
      $mensaje = "";
      $sql     = "SELECT iConsecutivo, sVIN, sTipo, siConsecutivosPolizas, iYear, iConsecutivoRadio, iModelo  ". 
                 "FROM ct_unidades WHERE iConsecutivoCompania = '$company' ".
                 "ORDER BY iConsecutivo ASC";
      $result  = $conexion->query($sql);
      $rows    = $result->num_rows;  
      if($rows > 0){
             
        while ($items = $result->fetch_assoc()) {
           $cadena     = '"'.$items["sVIN"].' | '.$items["sTipo"].'|'.utf8_encode($response["iYear"]).'|'.$response["iConsecutivoRadio"].'|'.$response["iModelo"].'|'.$response["iConsecutivo"].'"';
           $respuesta == '' ? $respuesta .= $cadena : $respuesta .= ','.$cadena;    
        }                                                                                                                                                                        
      }else {$respuesta .="";}
      $conexion->rollback();
      $conexion->close();
       
      $respuesta = "[".$respuesta."]";
      echo $respuesta;
      
  }
  
  #FUNCIONES FILES:
  function get_files(){
        
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa  = true;
      $iConsecutivo         = trim($_POST['iConsecutivo']);
      $registros_por_pagina = $_POST["registros_por_pagina"];
      $pagina_actual        = (isset($_POST["pagina_actual"]) && $_POST["pagina_actual"] != '' ? $_POST["pagina_actual"] : 1);
      $registros_por_pagina == "" ? $registros_por_pagina = 15 : false;
      $query_union          = "";
      
      #CONSULTAR DATOS DEL ENDOSO                                                                                                               
      $sql    = "SELECT A.iConsecutivo, A.iConsecutivoCompania, A.iConsecutivoTipoEndoso, sDescripcion, iReeferYear,iTrailerExchange, iPDAmount, ". 
                "A.sComentarios, iPDApply, iConsecutivoUnidad,sVINUnidad, eAccion ".  
                "FROM cb_endoso A ".
                "LEFT JOIN ct_tipo_endoso B ON A.iConsecutivoTipoEndoso = B.iConsecutivo ".
                "WHERE A.iConsecutivo = '$iConsecutivo' ";
      $result = $conexion->query($sql);
      $rows   = $result->num_rows;
      
      if($rows > 0){ 
        
        $endoso = $result->fetch_assoc();
        //Filtros de informacion //
        $filtroQuery = " WHERE iConsecutivoEndoso = '".$iConsecutivo."' ";
        
        // ordenamiento//
        $ordenQuery = " ORDER BY ".$_POST["ordenInformacion"]." ".$_POST["sortInformacion"];
        
        #VERIFICAMOS, SI ES UN ADD, VERIFICAMOS TABLA DE ARCHIVOS X UNIDAD:
        if($endoso['eAccion'] == 'A' && $endoso['iConsecutivoUnidad'] != ""){
          $query_union = " UNION ".
                         "(SELECT iConsecutivo, sTipoArchivo, sNombreArchivo, iTamanioArchivo FROM cb_unidad_files WHERE iConsecutivoUnidad = '".$endoso['iConsecutivoUnidad']."' $ordenQuery)";    
        }

        //contando registros // 
        /*$query_rows = "(SELECT COUNT(iConsecutivo) AS total FROM cb_endoso_files ".$filtroQuery.")";
                      
        $Result    = $conexion->query($query_rows);
        $items     = $Result->fetch_assoc();
        $registros = $items["total"];
        if($registros == "0"){$pagina_actual = 0;}
        $paginas_total = ceil($registros / $registros_por_pagina);
        
        if($registros == "0"){
            $limite_superior = 0;
            $limite_inferior = 0;
            $htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";
        }else{*/
            
          $pagina_actual   == "0" ? $pagina_actual = 1 : false;
          $limite_superior = $registros_por_pagina;
          $limite_inferior = ($pagina_actual*$registros_por_pagina)-$registros_por_pagina;
          
          $sql    = "(SELECT iConsecutivo, sTipoArchivo,sNombreArchivo,iTamanioArchivo FROM cb_endoso_files ".$filtroQuery.$ordenQuery.")".$query_union;
          $result = $conexion->query($sql);
          $rows   = $result->num_rows; 
             
            if ($rows > 0) {    
                while ($items = $result->fetch_assoc()) { 
                   if($items["iConsecutivo"] != ""){

                         $htmlTabla .= "<tr>".
                                       "<td id=\"idFile_".$items['iConsecutivo']."\">".$items['sNombreArchivo']."</td>".
                                       "<td>".$items['sTipoArchivo']."</td>".
                                       "<td>".$items['iTamanioArchivo']."</td>". 
                                       "<td>".
                                            "<div class=\"btn-icon edit btn-left\" title=\"Open file in a new window\" onclick=\"window.open('open_pdf.php?idfile=".$items['iConsecutivo']."&type=endoso');\"><i class=\"fa fa-external-link\"></i><span></span></div>". 
                                            "<div class=\"btn_delete_file btn-icon trash btn-left\" title=\"Delete file\"><i class=\"fa fa-trash\"></i><span></span></div>".
                                       "</td></tr>";  
                                           
                     }else{                                                                                                                                                                                                        
                        
                         $htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>"   ;
                     }    
                }
                $conexion->rollback();
                $conexion->close();                                                                                                                                                                       
            }
            else{$htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";}
          //}
          
      }
      else{$htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">Error to query endorsement data.</td></tr>"; }
            
        
      $response = array("total"=>"$paginas_total","pagina"=>"$pagina_actual","tabla"=>"$htmlTabla","mensaje"=>"$mensaje","error"=>"$error","tabla"=>"$htmlTabla");   
      echo json_encode($response); 
  }
  
  #FUNCIONES BROKERS:
  function send_email_brokers(){
      include("cn_usuarios.php");
      $error = '0';
      //variables:
      $id= $_POST['clave'];
      $idPoliza= $_POST['idPoliza']; 
      $htmlEmail = "";
      $conexion->autocommit(FALSE);
      $transaccion_exitosa = true;
      #1- First Step: Consult the general information from the Endorsement with the id.                    
      $endorsement_query = "SELECT A.iConsecutivo, A.iConsecutivoCompania, sNombreCompania,iConsecutivoTipoEndoso,iConsecutivoUnidad, eAccion, sNumeroPoliza, T.sDescripcion AS Tipo, sEmail AS Broker, iPDAmount, iPDApply, P.iTipoPoliza
                            FROM cb_endoso A
                            LEFT JOIN ct_companias B ON A.iConsecutivoCompania = B.iConsecutivo
                            LEFT JOIN cb_endoso_estatus D ON A.iConseCutivo = D.iConsecutivoEndoso
                            LEFT JOIN ct_polizas P ON D.iConsecutivoPoliza = P.iConsecutivo
                            LEFT JOIN ct_tipo_poliza T ON P.iTipoPoliza = T.iConsecutivo
                            LEFT JOIN ct_brokers BR ON P.iConsecutivoBrokers = BR.iConsecutivo
                            WHERE A.iConsecutivo = '$id' AND D.iConsecutivoPoliza = '$idPoliza'";                      
      $result = $conexion->query($endorsement_query);
      $rows = $result->num_rows; 
      $rows > 0 ? $endorsement = $result->fetch_assoc() : $endorsement = "";
      if($endorsement['iConsecutivo'] != "" && $endorsement['iConsecutivoTipoEndoso'] == '1' && $endorsement['iConsecutivoUnidad'] != ''){  

          
             #Unit: Consult the unit information:
             $unit_query = "SELECT A.iConsecutivo, iConsecutivoCompania, iYear, iModelo, sVIN, sModelo, B.sAlias, B.sDescripcion AS Make, C.sDescripcion AS Radius, sTipo ".  
                           "FROM ct_unidades A ".
                           "LEFT JOIN ct_unidad_modelo B ON A.iModelo = B.iConsecutivo ".
                           "LEFT JOIN ct_unidad_radio C ON A.iConsecutivoRadio = C.iConsecutivo ".
                           "WHERE A.iConsecutivo = '".$endorsement['iConsecutivoUnidad']."'";
             $result_d = $conexion->query($unit_query);
             $rows_d = $result_d->num_rows; 
             $rows_d > 0 ? $unit = $result_d->fetch_assoc() : $unit = "";
             
             $subject_policy = $endorsement['sNumeroPoliza'].'-'.$endorsement['Tipo'];
                 
                      
             #MAKE
             if($unit['sAlias'] != ''){$make = $unit['sAlias'];}else if($unit['Make'] != ''){$make = $unit['Make']; }
                              
             #action to define the subject an body email: 
             if($endorsement["eAccion"] == 'A'){
                    $action = 'Please add to my policy the following '.strtolower($unit['sTipo']).'.';
                    $subject = "Endorsement application - please add the following ".strtolower($unit['sTipo'])." from policy number: ".$endorsement['sNombreCompania'].", ".$subject_policy;
                    
                    #PDAmount
                    $endorsement['iTipoPoliza'] == '1' && $endorsement["iPDApply"] == '1' && $endorsement["iPDAmount"] != '' ? $PDAmount = number_format($endorsement["iPDAmount"]) : $PDAmount = '';
                    
                    #RADIUS:
                    $radius = explode('(',$unit['Radius']);
                    
                    $bodyData = "<p style=\"color:#000;margin:5px auto; text-align:left;\">".$unit['iYear']."&nbsp;".$make."&nbsp;".$unit['sVIN']."&nbsp;".$radius[0]."&nbsp;11-22&nbsp;TONS&nbsp;".$PDAmount."</p><br><br>";
                  
             }else if($endorsement["eAccion"] == 'D'){
                   $action = 'Please delete of my policy the following '.strtolower($unit['sTipo']).'.';                                                                   
                   $subject = "Endorsement application - please delete the following ".strtolower($unit['sTipo'])." from policy number: ".$endorsement['sNombreCompania'].", ".$subject_policy;
                   $bodyData = "<p style=\"color:#000;margin:5px auto; text-align:left;\">".$unit['iYear']."&nbsp;".$make."&nbsp;".$unit['sVIN']."</p><br><br>";
             } 
                    #Building Email Body:                                   
                    require_once("./lib/phpmailer_master/class.phpmailer.php");
                    require_once("./lib/phpmailer_master/class.smtp.php");
                    
                    //header
                    $htmlEmail .= "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\"\"http://www.w3.org/TR/html4/strict.dtd\"><html>".
                                    "<head><meta content=\"text/html; charset=utf-8\" http-equiv=\"Content-Type\">".
                                    "<title>Endorsement from Solo-Trucking Insurance</title></head>"; 
                     
                     //Body - Armar Body dependiendo el tipo de poliza:
                    $htmlEmail .= "<body>".
                                  "<table style=\"font-size:12px;border:1px solid #6191df;border-radius:3px;padding:10px;width:95%; margin:5px auto;font-family: Arial, Helvetica, sans-serif;\">".
                                  "<tr><td>".
                                  "<h2 style=\"color:#313131;text-transform: uppercase; text-align:center;\">Endorsement application from Solo-Trucking Insurance</h2>".
                                  "</td></tr>".
                                  "<tr><td>".
                                  "<p style=\"color:#000;margin:5px auto; text-align:left;\">$action</p><br><br>".
                                  "</td></tr>".
                                  "<tr><td>".$bodyData."</td></tr>".
                                  "<tr>".
                                  "<tr><td><p style=\"color:#010101;margin:5px auto 10px; text-align:left;font-size:11px;\">Please reply this email to the account:<a href=\"mailto:customerservice@solo-trucking.com\"> customerservice@solo-trucking.com</a></p></td></tr>". 
                                  "<td><p style=\"color:#858585;margin:5px auto; text-align:left;font-size:10px;\">e-mail sent from Solo-trucking Insurance System.</p></td>".
                                  "</tr>".
                                  "</table>".
                                  "</body>";   
                        
                     //footer              
                     $htmlEmail .= "</html>"; 
                    
                     #TERMINA CUERPO DEL MENSAJE
                     $mail = new PHPMailer();   
                     $mail->IsSMTP(); // telling the class to use SMTP
                     $mail->Host       = "mail.solo-trucking.com"; // SMTP server
                     //$mail->SMTPDebug  = 2; // enables SMTP debug information (for testing) 1 = errors and messages 2 = messages only
                     $mail->SMTPAuth   = true;                  // enable SMTP authentication
                     $mail->SMTPSecure = "TLS";                 // sets the prefix to the servier
                     $mail->Host       = "smtp.gmail.com";      // sets GMAIL as the SMTP server
                     $mail->Port       = 587;                   // set the SMTP port for the GMAIL server
                     $mail->Username   = "systemsupport@solo-trucking.com";  // GMAIL username
                     $mail->Password   = "SL09100242"; 
                     $mail->SetFrom('systemsupport@solo-trucking.com', 'Solo-Trucking Insurance');
                     $mail->AddReplyTo('customerservice@solo-trucking.com','Customer service Solo-Trucking');
                     $mail->Subject    = $subject;
                     $mail->AltBody    = "To view the message, please use an HTML compatible email viewer!";  // optional, comment out and test
                     $mail->MsgHTML($htmlEmail);
                     $mail->IsHTML(true); 
                     
                     //Receptores:
                     $direcciones         = explode(",",trim($endorsement['Broker']));
                     $nombre_destinatario = trim($endorsement['NameBroker']);
                     foreach($direcciones as $direccion){
                        $mail->AddAddress(trim($direccion),$nombre_destinatario);
                     }
                     
                     //Revisar si se necesitan enviar archivos adjuntos:
                     include("./lib/fpdf153/fpdf.php"); // libreria fpdf 
                     
                     //Consult the driver files:
                     $driver_files_query = "SELECT iConsecutivo, sNombreArchivo, eArchivo, hContenidoDocumentoDigitalizado, sTipoArchivo, iTamanioArchivo ". 
                                           "FROM cb_unidad_files ". 
                                           "WHERE  iConsecutivoUnidad = '".$endorsement['iConsecutivoUnidad']."'";   
                     $result_files = $conexion->query($driver_files_query);
                     $rows_files = $result_files->num_rows; 
                     if($rows_files > 0){
                            while ($files = $result_files->fetch_assoc()){
                               #Here will constructed the temporary files: 
                               if($files['sNombreArchivo'] != ""){
                               
                                 if($endorsement["eAccion"] == 'A' && $files['eArchivo'] == 'TITLE'){ #<--- Si es un ADD verificar que el arhcivo que enviemos sea TITLE
                                    $file_tmp = fopen('tmp/'.$files["sNombreArchivo"],"w") or die("Error when creating the file. Please check."); 
                                    fwrite($file_tmp,$files["hContenidoDocumentoDigitalizado"]); 
                                    fclose($file_tmp);     
                                    $archivo = "tmp/".$files["sNombreArchivo"];  
                                    $mail->AddAttachment($archivo);
                                    $delete_files .= "unlink(\"tmp/\".".$files["sNombreArchivo"].");";  
                                 }else if($endorsement["eAccion"] == 'D' && ($files['eArchivo'] == 'DA' || $files['eArchivo'] == 'BS' || $files['eArchivo'] == 'NOR' || $files['eArchivo'] == 'PTL')){
                                    $file_tmp = fopen('tmp/'.$files["sNombreArchivo"],"w") or die("Error when creating the file. Please check."); 
                                    fwrite($file_tmp,$files["hContenidoDocumentoDigitalizado"]); 
                                    fclose($file_tmp);     
                                    $archivo = "tmp/".$files["sNombreArchivo"];  
                                    $mail->AddAttachment($archivo);
                                    $delete_files .= "unlink(\"tmp/\".".$files["sNombreArchivo"].");"; 
                                 }
                               }
                               
                            }
                     } 
          
                    $mail_error = false;
                    if(!$mail->Send()){$mail_error = true; $mail->ClearAddresses();}
                    if(!$mail_error){
                        //$mensaje = "Mail confirmation to the user $usuario was successfully sent.";  
                    }else{
                        $mensaje = "Error: The e-mail cannot be sent.";
                        $error = "1";            
                    }
                    $mail->ClearAttachments();
                    #deleting files attachment:
                    eval($delete_files);
                 
                 #VERIFICAR SI SE ENVIARON LOS CORREOS CORRECTAMENTE:
                 if($error == '0'){
                    #UPDATE ENDORSEMENT DETAILS:
                    $sql_endoso = "UPDATE cb_endoso_estatus SET eStatus = 'SB', dFechaActualizacion='".date("Y-m-d H:i:s")."', sIP='".$_SERVER['REMOTE_ADDR']."', sUsuarioActualizacion='".$_SESSION['usuario_actual']."' ".
                                  "WHERE iConsecutivoEndoso = '$id' AND iConsecutivoPoliza = '$idPoliza'"; 
                    $conexion->query($sql_endoso);
                    if($conexion->query($sql_endoso)){
                            $msj = "The Endorsement was sent successfully, please check your email (customerservice@solo-trucking.com) waiting for their response."; 
                    }else{
                          $transaccion_exitosa = false;
                            $msj = "The data of endorsement was not updated properly, please try again.";  
                    }  
  
                 }
     
            
      }else{
          $error = '1';
          $msj = "The endorsement data was not found, please try again.";
      }
      
      if($transaccion_exitosa){
            $conexion->commit();
            $conexion->close();
      }else{
            $conexion->rollback();
            $conexion->close();
            $error = "1";
      }
      $response = array("error"=>"$error","msj"=>"$msj");
      echo json_encode($response);
            
  } 

  
?>
