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
        $filtroQuery = " WHERE iConsecutivoCompania = '".$company."' ";
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
          $sql = "SELECT A.iConsecutivo, sVIN, B.sDescripcion AS sModelo, C.sDescripcion AS sRadio, iYear, sPeso, sTipo,inPoliza, siConsecutivosPolizas ".
                 "FROM ct_unidades A ".
                 "LEFT JOIN ct_unidad_modelo B ON A.iModelo = B.iConsecutivo ".
                 "LEFT JOIN ct_unidad_radio  C ON A.iConsecutivoRadio = C.iConsecutivo ".$filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior;
          $result = $conexion->query($sql);
          $rows = $result->num_rows; 
             
            if ($rows > 0) {    
                while($items = $result->fetch_assoc()) { 
                    
                    //$items['iConsecutivoArchivo'] == '' ? $btn_pdf = "" : $btn_pdf = "<div class=\"btn_view_pdf btn-icon pdf btn-left\" title=\"view Policy File\" onclick=\"window.open('open_pdf.php?idfile=".$items['iConsecutivoArchivo']."&type=company');\"><i class=\"fa fa-file-pdf-o\"></i> <span></span></div>" ;  
                    //$items['inPoliza'] == '1' ? $estatus = "ACTIVE" : $estatus = "NON-ACTIVE";
                    if($items['siConsecutivosPolizas'] != ""){
                        $polizas = explode(",",$items['siConsecutivosPolizas']);
                        $count   = count($polizas);
                        for($x=0;$x<$count;$x++){
                           $filtro_polizas == "" ? $filtro_polizas .= "iConsecutivo ='".$polizas[$x]."' " : $filtro_polizas .= "OR iConsecutivo ='".$polizas[$x]."'"; 
                        }
                        if($filtro_polizas != ""){
                            $query   = "SELECT sNumeroPoliza, iTipoPoliza FROM ct_polizas ".
                                       "WHERE iConsecutivoCompania = '".$company."' AND iDeleted = '0' AND dFechaCaducidad >= CURDATE()".
                                       "AND ($filtro_polizas)";
                            $result2 = $conexion->query($query); 
                            $estatus = "";
                            while($items2 = $result2->fetch_assoc()) { 
                                 $estatus == "" ? $estatus .= $items2['sNumeroPoliza'] : $estatus .= " / ".$items2['sNumeroPoliza'];
                            }
                        }
                        

                    }
                    $htmlTabla .= "<tr><td id=\"".$items['iConsecutivo']."\">".strtoupper($items['sVIN'])."</td>".
                                   "<td>".$items['iYear']."</td>". 
                                   "<td>".$items['sTipo']."</td>".  
                                   "<td>".$items['sRadio']."</td>".
                                   "<td>".$items['sModelo']."</td>".   
                                   "<td>".$items['sPeso']."</td>".
                                   "<td class=\"txt-center\">".$estatus."</td>".                                                                                                                                                                                                                     
                                   "<td></td></tr>";  
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
