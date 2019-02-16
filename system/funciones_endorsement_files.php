<?php
  session_start();
  // Generic functions lib 
  include("functiones_genericas.php"); 
  $_POST["accion"] and  $_POST["accion"]!= "" ? call_user_func_array($_POST["accion"],array()) : ""; 
  define('USER',$_SESSION['usuario_actual']); // Constante UserId 
  
  function get_endorsements(){
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa   = true;
      $registros_por_pagina  = $_POST["registros_por_pagina"];
      $pagina_actual         = (isset($_POST["pagina_actual"]) && $_POST["pagina_actual"] != '' ? $_POST["pagina_actual"] : 1);
      $registros_por_pagina == "" ? $registros_por_pagina = 15 : false;
        
     //Filtros de informacion //
     $filtroQuery   = " WHERE A.eStatus != 'E' ";
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
    $query_rows= "SELECT COUNT(A.iConsecutivo) AS total ".
                 "FROM      cb_endoso      AS A ".
                 "LEFT JOIN ct_tipo_endoso AS B ON A.iConsecutivoTipoEndoso = B.iConsecutivo ".
                 "LEFT JOIN ct_operadores  AS C ON A.iConsecutivoOperador = C.iConsecutivo ".
                 "LEFT JOIN ct_unidades    AS D ON A.iConsecutivoUnidad = D.iConsecutivo ".
                 "LEFT JOIN ct_companias   AS E ON A.iConsecutivoCompania = E.iConsecutivo ".$filtroQuery;
    $Result    = $conexion->query($query_rows);
    $items     = $Result->fetch_assoc();
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
      $sql    = "SELECT A.iConsecutivo, B.sDescripcion AS categoria, A.eStatus, eAccion, sNombre, sVIN, sNombreCompania,iOnRedList, iConsecutivoTipoEndoso,iEndosoMultiple ".
                "FROM      cb_endoso      AS A ".
                "LEFT JOIN ct_tipo_endoso AS B ON A.iConsecutivoTipoEndoso = B.iConsecutivo ".
                "LEFT JOIN ct_operadores  AS C ON A.iConsecutivoOperador   = C.iConsecutivo ".
                "LEFT JOIN ct_unidades    AS D ON A.iConsecutivoUnidad     = D.iConsecutivo ".
                "LEFT JOIN ct_companias   AS E ON A.iConsecutivoCompania   = E.iConsecutivo ".$filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior;
      $result = $conexion->query($sql);
      $rows   = $result->num_rows; 
         
        if ($rows > 0) {    
            while ($items = $result->fetch_assoc()) { 
               
                     $btn_confirm = "";
                     $estado      = "";
                     $class       = "";
                     $descripcion = "";
                     $categoria   = strtoupper($items['categoria']); 
                     /*switch($items["eStatus"]){
                         case 'S': 
                            $estado = 'SENT';
                            $class = "class = \"blue\"";   
                         break;
                         case 'SB': 
                            $estado = 'SENT TO BROKERS';
                            $class = "class = \"yellow\""; 
                         break;
                         case 'A': 
                            $estado = 'APPROVED'; 
                            $class = "class = \"green\"";
                            break;
                         case 'D': 
                            $estado = 'DENIED'; 
                            $class = "class = \"red\"";  
                         break;
                         case 'P': 
                            $estado = 'IN PROCESS'; 
                            $class = "class = \"orange\"";     
                         break;
                     }*/ 
                     
                     if($items['iEndosoMultiple'] == 0){
                        $items['eAccion'] == 'A' ? $eAccion = "ADD" : "DELETE"; 
                        if($categoria == 'DRIVER') {$detalle = strtoupper($items['sNombre']);}else 
                        if($categoria == 'VEHICLE'){$detalle = strtoupper($items['sVIN']);} 
                        
                        $descripcion = "<table style=\"border-collapse: collapse;width: 100%;\"><tr>".
                                       "<td style=\"border: 0px;width:105px;\">".$eAccion."</td>".
                                       "<td style=\"border: 0px;\">".$detalle."</td>".
                                       "</tr></table>";     
                     }
                     else if($items['iEndosoMultiple'] == "1"){
                         #CONSULTAR DETALLE DEL ENDOSO:
                         if($categoria == 'DRIVER'){
                            $query   = "SELECT A.sNombre AS sDetalle, (CASE WHEN A.eAccion = 'ADDSWAP' THEN 'ADD SWAP' WHEN A.eAccion = 'DELETESWAP' THEN 'DELETE SWAP' ELSE A.eAccion END) AS eAccion ".
                                       "FROM cb_endoso_operador AS A WHERE A.iConsecutivoEndoso = '".$items['iConsecutivo']."' ORDER BY A.sNombre ASC";    
                         }
                         else if($categoria == 'VEHICLE'){
                             $query = "SELECT A.sVIN AS sDetalle, (CASE WHEN A.eAccion = 'ADDSWAP'    THEN 'ADD SWAP' WHEN A.eAccion = 'DELETESWAP' THEN 'DELETE SWAP' ELSE A.eAccion END) AS eAccion ".
                                      "FROM cb_endoso_unidad AS A WHERE A.iConsecutivoEndoso = '".$items['iConsecutivo']."' ORDER BY A.sVIN ASC";
                         }
                         $r       = $conexion->query($query);
                         $detalle = "";
                         while($item = $r->fetch_assoc()){
                            $detalle .= "<tr>".
                                        "<td style=\"border: 0px;width:105px;\">".$item['eAccion']."</td>".
                                        "<td style=\"border: 0px;\">".$item['sDetalle']."</td>".
                                        "</tr>"; 
                         } 
                         $descripcion.= "<table style=\"border-collapse: collapse;width: 100%;\">".$detalle."</table>";
                     }
   
                     //Redlist:
                     $items['iOnRedList'] == '1' ? $redlist_icon = "<i class=\"fa fa-star\" style=\"color:#e8051b;margin-right:4px;\"></i>" : $redlist_icon = "";
                     
                     $htmlTabla .= "<tr>
                                        <td class=\"$categoria\">".$items['iConsecutivo']."</td>".
                                       "<td>".$redlist_icon.$items['sNombreCompania']."</td>".
                                       "<td>".$descripcion."</td>". 
                                       "<td>".$categoria."</td>".                                                                                                                                                                                                                       
                                       "<td>".
                                            "<div class=\"btn_open_files btn-icon edit btn-left\" title=\"View Files of Endorsement - ".$items['iConsecutivo']."\"><i class=\"fa fa-file-text\"></i> <span></span></div> ".
                                       "</td></tr>";
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
  function get_endorsement_files(){
      
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa   = true;
      $registros_por_pagina  = $_POST["registros_por_pagina"];
      $pagina_actual         = (isset($_POST["pagina_actual"]) && $_POST["pagina_actual"] != '' ? $_POST["pagina_actual"] : 1);
      $registros_por_pagina == "" ? $registros_por_pagina = 15 : false;
      $iConsecutivoEndoso    = trim($_POST['iConsecutivo']);
      $error                 = '0';
      $mensaje               = '';
      
      /*#TRAER CONSECUTIVO DE LA UNIDAD O DEL DRIVER:
      $query = "SELECT iConsecutivoUnidad, iConsecutivoOperador FROM  cb_endoso WHERE iConsecutivo = '$iConsecutivoEndoso'";
      $result_query = $conexion->query($query);
      $items_desc = $result_query->fetch_assoc(); 
      
      
      if($_POST['tipo'] == '1' && $items_desc['iConsecutivoUnidad'] != ''){ 
          //UNITS
          $tabla_consulta   = 'cb_unidad_files';
          $iConsecutivoDesc = " iConsecutivoUnidad = '".trim($items_desc['iConsecutivoUnidad'])."'";
          $type             = "unit";
          
      }else if($_POST['tipo'] == '2' && $items_desc['iConsecutivoOperador'] != ''){
          //DRIVER
          $tabla_consulta   = 'cb_operador_files';
          $iConsecutivoDesc = " iConsecutivoOperador = '".trim($items_desc['iConsecutivoOperador'])."'";
          $type             = "driver";
      }else{
          $error   = '1';
          $mensaje = "Error: data files is not found";
      }  */
      
      //Filtros de informacion //
      $filtroQuery   = " WHERE iConsecutivoEndoso='$iConsecutivoEndoso' ";
      $array_filtros = explode(",",$_POST["filtroInformacion"]);
      foreach($array_filtros as $key => $valor){
        if($array_filtros[$key] != ""){
            $campo_valor = explode("|",$array_filtros[$key]); 
            $filtroQuery.= " AND  ".$campo_valor[0]." LIKE '%".$campo_valor[1]."%' ";
            
        }
      }
      // ordenamiento//
      $ordenQuery = " ORDER BY ".$_POST["ordenInformacion"]." ".$_POST["sortInformacion"];

      //contando registros // 
      $query_rows = "SELECT COUNT(iConsecutivo) AS total FROM cb_endoso_files ".$filtroQuery;
      $Result     = $conexion->query($query_rows);
      $items      = $Result->fetch_assoc();
      $registros  = $items["total"];
      if($registros == "0"){$pagina_actual = 0;}
      $paginas_total = ceil($registros / $registros_por_pagina);
    
      if($registros == "0"){
        $limite_superior = 0;
        $limite_inferior = 0;
        $htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";
      }
      else{
          $pagina_actual == "0" ? $pagina_actual = 1 : false;
          $limite_superior = $registros_por_pagina;
          $limite_inferior = ($pagina_actual*$registros_por_pagina)-$registros_por_pagina;
          $sql    = "SELECT iConsecutivo, sNombreArchivo, sTipoArchivo, iTamanioArchivo, eArchivo, dFechaIngreso, dFechaActualizacion ".
                    "FROM cb_endoso_files ".$filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior;
          $result = $conexion->query($sql);
          $rows   = $result->num_rows; 
             
          if ($rows > 0) {    
                while ($items = $result->fetch_assoc()) { 
                     if($items['sTipoArchivo'] == 'application/pdf'){$btnIcon = "fa-file-pdf-o";}else if($items['sTipoArchivo'] == 'image/jpeg'){$btnIcon = "fa-file-image-o";} 
                     $htmlTabla .= "<tr>".
                                   "<td id=\"".$items['iConsecutivo']."\">".$items['sNombreArchivo']."</td>".
                                   "<td>".$items['eArchivo']."</td>".                                                                                                                                                                                                                        
                                   "<td>".
                                        "<div onclick=\"window.open('open_pdf.php?idfile=".$items['iConsecutivo']."&type=$type');\" class=\"btn-icon pdf btn-left\" title=\"View PDF File\"><i class=\"fa $btnIcon\"></i><span></span></div>".
                                        "<div class=\"btn_delete_file btn-icon trash btn-left\" title=\"Delete file of Endorsement - ".$items['iConsecutivo']."\"><i class=\"fa fa-trash\"></i> <span></span></div>".
                                   "</td>". 
                                   "</tr>";
                }
                $conexion->rollback();
                $conexion->close();                                                                                                                                                                       
          }
          else{$htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";}
      }      
          
      $response = array("total"=>"$paginas_total","pagina"=>"$pagina_actual","tabla"=>"$htmlTabla","mensaje"=>"$mensaje","error"=>"$error","tabla"=>"$htmlTabla");   
      echo json_encode($response);   
      
  } 
  
?>
