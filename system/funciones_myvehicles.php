<?php
  
  session_start();
  $_POST["accion"] and  $_POST["accion"]!= "" ? call_user_func_array($_POST["accion"],array()) : ""; 
  define('USER',$_SESSION['usuario_actual']); // Constante UserId 
  
  function get_data(){
        include("cn_usuarios.php");
        $company = $_SESSION['company'];
        $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
        $transaccion_exitosa = true;
        $registros_por_pagina = $_POST["registros_por_pagina"];
        $pagina_actual = (isset($_POST["pagina_actual"]) && $_POST["pagina_actual"] != '' ? $_POST["pagina_actual"] : 1);
        $registros_por_pagina == "" ? $registros_por_pagina = 15 : false;
            
        //Filtros de informacion //
        $filtroQuery = " WHERE iConsecutivoCompania = '".$company."' AND iDeleted='0'";
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
        $query_rows = "SELECT COUNT(A.iConsecutivo) AS total FROM ct_unidades A ".
                      "LEFT JOIN ct_unidad_modelo B ON A.iModelo = B.iConsecutivo ".
                      "LEFT JOIN ct_unidad_radio  C ON A.iConsecutivoRadio = C.iConsecutivo $filtroQuery";
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
          $sql    = "SELECT A.iConsecutivo, C.sDescripcion AS Make, C.sDescripcion AS sMakeDescription, B.sDescripcion AS Radio, iYear, sVIN, sPeso, sTipo, sModelo, eModoIngreso, iTotalPremiumPD ".
                    "FROM ct_unidades A ".
                    "LEFT JOIN ct_unidad_radio B ON A.iConsecutivoRadio = B.iConsecutivo ".
                    "LEFT JOIN ct_unidad_modelo C ON A.iModelo = C.iConsecutivo ".$filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior;
          $result = $conexion->query($sql);
          $rows   = $result->num_rows; 
             
            if ($rows > 0) {    
                while($items = $result->fetch_assoc()) { 
                    
                    //Revisar polizas:
                    $query  = "SELECT iConsecutivoPoliza, B.sNumeroPoliza, C.sDescripcion AS sTipoPoliza, C.sAlias, DATE_FORMAT(A.dFechaIngreso,'%m/%d/%Y') AS dFechaIngreso,eModoIngreso ".
                               "FROM cb_poliza_unidad AS A ".
                               "INNER JOIN ct_polizas   AS B ON A.iConsecutivoPoliza = B.iConsecutivo AND B.iDeleted = '0' AND B.dFechaCaducidad >= CURDATE() ".
                               "LEFT JOIN  ct_tipo_poliza AS C ON B.iTipoPoliza = C.iConsecutivo ".
                               "WHERE A.iConsecutivoUnidad = '".$items['iConsecutivo']."' AND A.iDeleted = '0' ";
                    $r      = $conexion->query($query);
                    $total  = $r->num_rows;
                    $polizas= "";
                    $PDApply= false;
                      
                    if($total > 0){
                        $polizas  = '<table style="width: 100%;text-transform: uppercase;border-collapse: collapse;">';
                        //$classpan = "style=\"display:block;width:100%;padding:1px;\""; 
                        while ($poli = $r->fetch_assoc()){
                           
                            if($poli['eModoIngreso'] == 'AMIC'){$poli['eModoIngreso'] = 'BIND';}
                            $polizas .= "<tr>";
                            $polizas .= "<td style=\"width:40%;\">".$poli['sNumeroPoliza']."</td>";
                            $polizas .= "<td style=\"width:10;\">".$poli['sAlias']."</td>";
                            $polizas .= "<td style=\"width:50%;\">".$poli['eModoIngreso']." - ".$poli['dFechaIngreso']."</td>";
                            $polizas .= "</tr>";
                            
                            //Revisar si aplica PD:
                            $pos = strpos($poli['sAlias'],'PD');
                            if(!($pos === false)){$PDApply = true;}
                        }
                        $polizas .= "</table>"; 
                    }
                    
                    if($polizas != ""){
                        $PDApply && $items['iTotalPremiumPD'] != 0 ? $value = "\$ ".number_format($items['iTotalPremiumPD'],2,'.',',') : $value = "N/A"; 
                    
                        $htmlTabla .= "<tr><td id=\"".$items['iConsecutivo']."\">".strtoupper($items['sVIN'])."</td>".
                                       "<td>".$items['iYear']."</td>". 
                                       "<td>".$items['sTipo']."</td>".  
                                       "<td>".$items['sRadio']."</td>".
                                       "<td class=\"txt-uppercase\">".$items['Make']."</td>".   
                                       "<td>".$items['sPeso']."</td>".
                                       "<td>".$value."</td>".
                                       "<td style=\"padding: 0px!important;\">".$polizas."</td>". 
                                       "<td></td></tr>";    
                    }
                 
                }
                $conexion->rollback();
                $conexion->close();                                                                                                                                                                       
            } else { 
                
                $htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>"   ;    
                
            }
          }
          $htmlTabla = utf8_decode($htmlTabla);
          $response = array("total"=>"$paginas_total","pagina"=>"$pagina_actual","tabla"=>"$htmlTabla","mensaje"=>"$mensaje","error"=>"$error","tabla"=>"$htmlTabla");   
          echo json_encode($response); 
  }
  
  
?>
