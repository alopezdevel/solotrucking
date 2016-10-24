<?php
  session_start();
  // Generic functions lib 
  include("functiones_genericas.php"); 
  $_POST["accion"] and  $_POST["accion"]!= "" ? call_user_func_array($_POST["accion"],array()) : ""; 
  define('USER',$_SESSION['usuario_actual']); // Constante UserId 
  
  /*----------------------------------------------------------------------   CLAIMS   ------------------------------------*/
  function get_data_grid(){
        include("cn_usuarios.php");
        $company = $_SESSION['company'];
        $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
        $transaccion_exitosa = true;
        $registros_por_pagina = $_POST["registros_por_pagina"];
        $pagina_actual = (isset($_POST["pagina_actual"]) && $_POST["pagina_actual"] != '' ? $_POST["pagina_actual"] : 1);
        $registros_por_pagina == "" ? $registros_por_pagina = 15 : false;
            
        //Filtros de informacion //
        $filtroQuery = " WHERE B.iConsecutivoCompania = '".$company."' AND iDeleted = '0'";
        $array_filtros = explode(",",$_POST["filtroInformacion"]);
        foreach($array_filtros as $key => $valor){
            if($array_filtros[$key] != ""){
                $campo_valor = explode("|",$array_filtros[$key]);
                $campo_valor[0] == 'iConsecutivo' ? $filtroQuery.= " AND  ".$campo_valor[0]."='".$campo_valor[1]."' " : $filtroQuery == "" ? $filtroQuery.= " AND  ".$campo_valor[0]." LIKE '%".$campo_valor[1]."%'": $filtroQuery.= " AND ".$campo_valor[0]." LIKE '%".$campo_valor[1]."%'";
            }
        }
        // ordenamiento//
        $ordenQuery = " ORDER BY ".$_POST["ordenInformacion"]." ".$_POST["sortInformacion"];

        //contando registros // 
        $query_rows = "SELECT COUNT(A.iConsecutivo) AS total ".
                      "FROM cb_claims A ".
                      "LEFT JOIN ct_polizas B ON A.iConsecutivoPoliza = B.iConsecutivo ".
                      "LEFT JOIN ct_tipo_claim C ON A.iConsecutivoTipoClaim = C.iConsecutivo ".
                      "LEFT JOIN ct_tipo_incidente_claim D ON A.iConsecutivoIncidente = D.iConsecutivo ".$filtroQuery;
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
          $sql = "SELECT A.iConsecutivo, sNumeroPoliza, C.sNombre AS sTipoClaim , D.sNombre AS sTipoIncidente, DATE_FORMAT(dFechaHoraIncidente,'%m/%d/%Y %H:%i') AS dFechaHoraIncidente, DATE_FORMAT(dFechaAplicacion,'%m/%d/%Y %H:%i') AS dFechaAplicacion ".
                 "FROM cb_claims A ".
                 "LEFT JOIN ct_polizas B ON A.iConsecutivoPoliza = B.iConsecutivo ".
                 "LEFT JOIN ct_tipo_claim C ON A.iConsecutivoTipoClaim = C.iConsecutivo ".
                 "LEFT JOIN ct_tipo_incidente_claim D ON A.iConsecutivoIncidente = D.iConsecutivo ".$filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior;
          $result = $conexion->query($sql);
          $rows = $result->num_rows; 
             
            if ($rows > 0) {    
                while ($items = $result->fetch_assoc()) { 
                   if($items["sNumeroPoliza"] != ""){

                         $htmlTabla .= "<tr $EstatusPoliza >
                                            <td>".$items['iConsecutivo']."</td>".
                                           "<td>".$items['sNumeroPoliza']."</td>".
                                           "<td>".$items['sTipoClaim']."</td>". 
                                           "<td>".$items['sTipoIncidente']."</td>".  
                                           "<td>".$items['dFechaHoraIncidente']."</td>".
                                           "<td>".$items['dFechaAplicacion']."</td>".                                                                                                                                                                                                                     
                                           "<td></td></tr>";  
                                           
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
  
  
  
  function get_company_policies(){
      include("cn_usuarios.php");   
      $company = $_SESSION['company']; 
      $error = "0";
      $mensaje = "";
      $check_items = "";
      
      $query = "SELECT A.iConsecutivo, sNumeroPoliza, sName, sDescripcion ".
               "FROM ct_polizas A ".
               "LEFT JOIN ct_brokers C ON A.iConsecutivoBrokers = C.iConsecutivo ".
               "LEFT JOIN ct_tipo_poliza D ON A.iTipoPoliza = D.iConsecutivo ".
               "WHERE A.iConsecutivoCompania = '$company' AND A.iDeleted = '0'";
      $result = $conexion->query($query);
      $rows = $result->num_rows;   
      
      if($rows > 0){    
        while ($items = $result->fetch_assoc()){
           $check_items .= "<input class=\"num_policies\" type=\"checkbox\" value=\"".$items['iConsecutivo']."\" ><label class=\"check-label\"> ".$items['sNumeroPoliza']." / ".$items['sDescripcion']."/".$items['sName']."</label><br>"; 
        }
      }

      $response = array("checkboxes"=>"$check_items","mensaje"=>"$mensaje","error"=>"$error");   
      echo json_encode($response);
      
  }
  
  
  
?>
