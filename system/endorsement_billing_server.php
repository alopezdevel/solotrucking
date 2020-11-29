<?php
  session_set_cookie_params(86400); ini_set('session.gc_maxlifetime', 86400);   
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
     $filtroQuery   = " WHERE (A.eStatus = 'A' OR A.eStatus = 'FINANCING' OR A.eStatus = 'UNBILLED' OR A.eStatus = 'BILLED' OR A.eStatus = 'PAID') ".
                      " AND D.iDeleted = '0' AND B.iConsecutivoTipoEndoso = '1' ".
                      " AND A.iAplicaFacturacion = '1'"; 
                      //AND C.dFechaCaducidad >= CURDATE() AND LEFT(B.dFechaAplicacion,10) > '2020-10-01'
                     
     $array_filtros = explode(",",$_POST["filtroInformacion"]);
     
     foreach($array_filtros as $key => $valor){
        if($array_filtros[$key] != ""){
            $campo_valor = explode("|",$array_filtros[$key]);
            
            if($campo_valor[0] == 'B.dFechaAplicacion'){ 
                $campo_valor[1] = date('Y-m-d',strtotime(trim($campo_valor[1])));
                $filtroQuery   .= " AND  ".$campo_valor[0]."='".$campo_valor[1]."' ";
            }
            else if($campo_valor[0] == 'eStatus'){
                     $filtroQuery .= " AND  ".$campo_valor[0]." = '".$campo_valor[1]."'";
            }
            else{
                 $filtroQuery.= " AND  ".$campo_valor[0]." LIKE '%".$campo_valor[1]."%'";
            }   
            
        }
     } 
     
     // ordenamiento//
     $ordenQuery = " ORDER BY ".$_POST["ordenInformacion"]." ".$_POST["sortInformacion"];

    //contando registros // 
    $query_rows = "SELECT COUNT(A.iConsecutivoEndoso) AS total ".
                  "FROM       cb_endoso_estatus AS A
                   INNER JOIN cb_endoso         AS B ON A.iConsecutivoEndoso   = B.iConsecutivo
                   LEFT  JOIN ct_polizas        AS C ON A.iConsecutivoPoliza   = C.iConsecutivo
                   INNER JOIN ct_companias      AS D ON B.iConsecutivoCompania = D.iConsecutivo
                   LEFT  JOIN ct_tipo_poliza    AS E ON C.iTipoPoliza          = E.iConsecutivo 
                   LEFT  JOIN cb_invoices       AS F ON A.iConsecutivoInvoice  = F.iConsecutivo 
                   LEFT  JOIN ct_brokers         AS G ON C.iConsecutivoBrokers = G.iConsecutivo ".$filtroQuery; 
    $Result     = $conexion->query($query_rows);
    $items      = $Result->fetch_assoc();
    $registros  = $items["total"];
    if($registros == "0"){$pagina_actual = 0;}
    $paginas_total = ceil($registros / $registros_por_pagina);
    
    if($registros == "0"){
        $limite_superior = 0;
        $limite_inferior = 0;
        $htmlTabla       ="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";
    }else{
      $pagina_actual == "0" ? $pagina_actual = 1 : false;
      $limite_superior = $registros_por_pagina;
      $limite_inferior = ($pagina_actual*$registros_por_pagina)-$registros_por_pagina;
      $sql   = "SELECT A.iConsecutivoEndoso,B.iConsecutivoCompania, D.sNombreCompania, A.iConsecutivoPoliza, C.sNumeroPoliza, E.sDescripcion, A.eStatus, A.sNumeroEndosoBroker, A.rImporteEndosoBroker, DATE_FORMAT(B.dFechaAplicacion,'%m/%d/%Y') AS dFechaAplicacion, ".
               "D.iOnRedList, E.sAlias,A.iConsecutivoInvoice, F.dTotal, F.sCveMoneda,B.eStatus AS estatusEndoso, F.eStatus AS InvoiceStatus, B.iEndosoMultiple, iConsecutivoFinanciera, sDiasFinanciamiento, dFinanciamientoMonto, iFinanciamiento,".
               "DATE_FORMAT(F.dFechaInvoice,'%m/%d/%Y') AS dFechaInvoice, F.dFechaInvoice AS FechaInvoiceFormat,F.sNombreArchivo AS sArchivoInvoice, A.iAplicaFinanciamiento, A.iConsecutivoFinanciamiento, G.sName AS sBrokerName ".
               "FROM       cb_endoso_estatus AS A
                INNER JOIN cb_endoso         AS B ON A.iConsecutivoEndoso   = B.iConsecutivo
                LEFT  JOIN ct_polizas        AS C ON A.iConsecutivoPoliza   = C.iConsecutivo
                INNER JOIN ct_companias      AS D ON B.iConsecutivoCompania = D.iConsecutivo
                LEFT  JOIN ct_tipo_poliza    AS E ON C.iTipoPoliza          = E.iConsecutivo 
                LEFT  JOIN cb_invoices       AS F ON A.iConsecutivoInvoice  = F.iConsecutivo 
                LEFT  JOIN ct_brokers         AS G ON C.iConsecutivoBrokers = G.iConsecutivo ".
                $filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior; 
      $result = $conexion->query($sql);
      $rows   = $result->num_rows; 
         
      if ($rows > 0) {    
            while ($items = $result->fetch_assoc()){ 
                
                     $btn_confirm  = "";
                     $titleEstatus = "";
                     $color_action = "";
                     $action       = "";
                     $detalle      = "";
                     $estado       = "";
                     $class        = "";
                     $FinancingData= "---";
                     $total        = 0;
                     $titletotal   = 0;
                     $limit_txt    = '';
                     $invoiceID    = '';
                     
                     if($items['iEndosoMultiple'] == "1"){
                         #CONSULTAR DETALLE DEL ENDOSO:
                         $query = "SELECT A.sVIN, (CASE 
                                    WHEN A.eAccion = 'ADDSWAP'    THEN 'ADD SWAP'
                                    WHEN A.eAccion = 'DELETESWAP' THEN 'DELETE SWAP'
                                    WHEN A.eAccion = 'CHANGEPD'   THEN 'CHANGE PD'
                                    ELSE A.eAccion
                                    END) AS eAccion FROM cb_endoso_unidad AS A WHERE A.iConsecutivoEndoso = '".$items['iConsecutivoEndoso']."' ORDER BY sVIN ASC";
                         $r     = $conexion->query($query);
                         
                         $description = "";   
                         $count       = 0;
                         $descTitle   = "";
                         
                         while($item = $r->fetch_assoc()){
                            
                            if($count == 0){
                                $firstA = $item['eAccion'];
                                $firstV = $item['sVIN'];
                                $description = "<tr style='background: none;'>".
                                                "<td style='border: 0;width:105px;padding: 0!important;min-height: auto!important;height:auto!important;'>".$item['eAccion']."</td>".
                                                "<td style='border: 0;padding: 0!important;min-height: auto!important;height:auto!important;'>".$item['sVIN']."</td>".
                                                "<td style='border: 0;width: 70px;padding: 0!important;min-height: auto!important;height:auto!important;'>".$items['dFechaAplicacion']."</td>".
                                                "</tr>";                    
                            }
                            else{
                                if($count == 1){
                                    $description = "<tr style='background: none;'>".
                                                    "<td style='border: 0;width:105px;padding: 0!important;min-height: auto!important;height:auto!important;'>".$firstA."</td>".
                                                    "<td style='border: 0;padding: 0!important;min-height: auto!important;height:auto!important;'>".$firstV."... </td>".
                                                    "<td style='border: 0;width: 70px;padding: 0!important;min-height: auto!important;height:auto!important;'>".$items['dFechaAplicacion']."</td>".
                                                    "</tr>"; 
                                }
                            } 
                            $descTitle == "" ? $descTitle .= $item['eAccion']." ".$item['sVIN'] : $descTitle.= "\n".$item['eAccion']." ".$item['sVIN'];
                            $count ++;
                         }
                         $detalle  = "<table title=\"$descTitle\" style=\"width:100%;padding:0!important;margin:0!important;border-collapse: collapse;\">";
                         $detalle .= $description."</table>";
                     } 
                     
                     
                     #FECHA LIMITE DE PAGO --- Calcular fecha de expiracion del pago del endoso: siempre son 10 dias:
                     $fechaLimite   = sumar_dias_fecha($items['dFechaAplicacion'],10);
                     $validExp      = verificar_vencimiento($fechaLimite); 
                     
                     if(!($validExp)){$class = "class = \"red\"";}
                     else{
                        $dias = calcula_dias_diff($fechaLimite,date('m/d/Y'));
                        if($dias <= 5){$class = "class = \"orange\"";}else{$class = "class = \"green\""; }
                     }
                     
                     $limit_txt  = '<br><span style="font-size: 9px;position: relative;top: -2px;display: inline-block;width: 100%;text-align: center;">Payday Limit: <b>'.$fechaLimite.'</b></span></span>';
                     
                     #APLICA FINANCIAMIENTO:
                     if($items['eStatus'] != 'A'){
                        $items['iAplicaFinanciamiento'] == '1' ? $FinancingData = "YES" : $FinancingData = "N/A"; 
                     }
                     
                     #OPCIONES DEPENTIENDO EL ESTATUS DEL ENDOSO:
                     if($items['eStatus'] == 'A'){
                        $estado      = '<span style="font-size: 11px;position: relative;top: 3px;font-weight: bold;width: 100%;display: inline-block;text-align: center;">---</span>'.$limit_txt;
                        $titleEstatus= "The financing has been sent."; 
                        $btn_confirm = '<div class="btn-icon btn-left btn-green btn_add" title="Add data"><i class="fa fa-plus"></i></div>';
                        $total       = '';
                        //$btn_confirm .= '<div class="btn_delete btn-icon trash btn-left" title="No Apply Billed"><i class="fa fa-times" aria-hidden="true"></i></div>';    
                     }else
                     // EN FINANCIAMIENTO
                     if($items['eStatus'] == 'FINANCING'){
                      
                        $estado      = '<span style="font-size: 11px;position: relative;top: 3px;font-weight: bold;width: 100%;display: inline-block;text-align: center;">'.$items['eStatus'].' SENT</span>'.$limit_txt;
                        $titleEstatus= "The financing has been sent."; 
                        $total       = '';
                        $btn_confirm = '<div class="btn-icon btn-left btn-green btn_add" title="Upload Financing"><i class="fa fa-upload"></i></div>';
                        
                        //$iEnviadoFuera == 1 ? $txtFechaApp = "<span style=\"font-size:9px;display:block;\">Mark As Sent</span>".$items['dFechaAplicacion'] : $txtFechaApp = $items['dFechaAplicacion'];
                    
                     }else
                     // YA SE SUBIO EL FINANCIAMIENTO PERO NO SE HA FACTURADO
                     if($items['eStatus'] == 'UNBILLED'){
                        $estado      = '<span style="font-size: 11px;position: relative;top: 3px;font-weight: bold;width: 100%;display: inline-block;text-align: center;">'.$items['eStatus'].'</span>'.$limit_txt;
                        $titleEstatus= "This endorsement is ready to bill, please create the invoice in QuickBooks and upload here."; 
                        $total       = '';
                        $btn_confirm = '<div class="btn-icon btn-left btn-green btn_add" title="Add data"><i class="fa fa-plus"></i></div>';   
                     }
                     // FACTURADO PERO AUN NO SE HA PAGADO
                     else if($items['eStatus'] == 'BILLED'){   
                        $estado      = '<span style="font-size: 11px;position: relative;top: 3px;font-weight: bold;width: 100%;display: inline-block;text-align: center;">'.$items['eStatus'].'</span>'.$limit_txt;
                        $titleEstatus= "This endorsement is ready bill but is not paid, please upload the payment when you received it."; 
                        $total       = "\$ ".number_format($items['dTotal'],2,'.',',')." ".$items['sCveMoneda'];
                        $invoiceID   = "invID_".$items['iConsecutivoInvoice'];
                        $btn_confirm = '<div class="btn-icon btn-left btn-green btn_add_pay" title="Add Payments"><i class="fa fa-plus"></i></div>';
                        
                        
                           
                     }
                     else{
                         
                     }
                     
                     #AGREGAR BOTONES PARA ARCHIVOS:
                     //Endoso
                     $sql = "SELECT iConsecutivo FROM cb_endoso_files WHERE iConsecutivoEndoso = '".$items['iConsecutivoEndoso']."' AND iConsecutivoPoliza ='".$items['iConsecutivoPoliza']."' LIMIT 1";
                     $res = $conexion->query($sql);
                    
                     if($res->num_rows > 0){
                       $idArchivo = $res->fetch_assoc();
                       $idArchivo = $idArchivo['iConsecutivo'];
                       $btn_confirm .= "<div class=\"btn-icon btn-left pdf\" title=\"Open endorsement file\" onclick=\"window.open('open_pdf.php?idfile=".$idArchivo."&type=endoso');\"><i class=\"fa fa-file-pdf-o\"></i><span></span></div>";
                     }
                     
                     //Factura:
                     if(($items['eStatus'] == 'BILLED' || $items['eStatus'] == 'PAID') && $items['iConsecutivoInvoice'] != ""){
                        if($items['sArchivoInvoice'] != ""){
                            $btn_confirm.= "<div class=\"btn-icon btn-left pdf\" title=\"Open invoice file\" onclick=\"window.open('open_pdf.php?idfile=".$items['iConsecutivoInvoice']."&type=invoice');\"><i class=\"fa fa-file-pdf-o\"></i><span></span></div>";
                        }
                        else{
                            $btn_confirm.= "<div class=\"btn_pdf btn-icon pdf btn-left\" title=\"Open Invoice PDF\"><i class=\"fa fa-file-pdf-o\"></i></div>";     
                        }    
                     }
                     
                     
                     // Revisar fecha limite de factuacion:
                     /*if($items['iConsecutivoInvoice'] == '' && $items['iConsecutivoFinanciamiento'] == ''){
                         $estado      = '<i class="fa fa-circle-o icon-estatus" style=\"margin-right: -5px;\"></i><span style="font-size: 10px;position: relative; top: 2px;">NO BILLED</span>';
                         $titleEstatus= "The invoice or financing has not been created.";
                         $btn_confirm = '<div class="btn-icon btn-left btn-green btn_add" title="Add data"><i class="fa fa-plus"></i></div>';
                         $invoiceID   = '';
                     }
                     else{
                         $invoiceID = "invID_".$items['iConsecutivoInvoice'];
                         
                         if($items['iFinanciamiento'] == "0"){
                            $FinancingData = 'N/A';  
                            //Validar fecha limite de pago para determinar el estatus:
                            $fechaLimite   = sumar_dias_fecha($items['dFechaAplicacion'],10);   
                         }
                         else{
                            $FinancingData = "<span style=\"font-weight: 700;\">".$items['sDiasFinanciamiento']." months</span>";
                            $FinancingData.= "<br><span style=\"font-size: 10px;opacity: 0.9;\">\$ ".$items['dFinanciamientoMonto']." ".$items['sCveMoneda']."</span>"; 
                        
                            // Calcular fecha de vencimiento:
                            $fecha_factura= $items['FechaInvoiceFormat'];
                            $today        = new DateTime($fecha_factura);            
                            $date         = $today->modify('+'.$items['sDiasFinanciamiento'].' months');
                            $fechaLimite  = $date->format('m/d/Y');
                         
                         }
                          
                         if($items['eStatus'] == 'EDITABLE'){
                            $estado      = '<i class="fa fa-circle-o icon-estatus" style="margin-right: -5px;"></i><span style="font-size: 10px;position: relative; top: 2px;">BILL NOT APPLIED</span>';
                            $titleEstatus= "The invoice has been created but not sent/applied, you can edit it.";
                            $btn_confirm = "<div class=\"btn_edit btn-icon edit btn-left\" title=\"View and Edit Endorsement Status\"><i class=\"fa fa-pencil-square-o\"></i></div>";
                         }
                         else if($items['eStatus'] == 'APPLIED' || $items['eStatus'] == 'SENT'){
                     
                            $validExp= verificar_vencimiento($fechaLimite);
                            if(!($validExp)){
                                $estado      = '<i class="fa fa-exclamation-circle icon-estatus" style="background-color: #ffe2e2;color: #de3636;border-color: #ffacac;position: relative;top: 3px;"></i><span style="font-size: 10px;position: relative; top: 2px;">'.$items['eStatus'].
                                               '<br><span style="font-size: 9px;position: relative;top: -5px;">Payday Limit: <b>'.$fechaLimite.'</b></span></span>';
                                $titleEstatus= "The invoice has not been paid and has exceeded the 10 day limit, please check it.";
                                $class       = "class = \"red\"";
                            }
                            else{
                                $dias = calcula_dias_diff($fechaLimite,date('m/d/Y'));
                                if($dias <= 5){
                                    $estado      = '<i class="fa fa-exclamation-triangle status-process icon-estatus" style="color: #ffba00;background-color: #ffecba;border-color: #f2d176;"></i><span style="font-size: 10px;">'.$items['eStatus'].
                                                   '<br><span style="font-size: 9px;position: relative;top: -5px;">Payday Limit: <b>'.$fechaLimite.'</b></span></span>';
                                    $titleEstatus= "The invoice has not been paid and it is close to the 10 day limit, please check it.";
                                    $class       = "class = \"orange\"";    
                                }
                                else{
                                    $estado      = '<i class="fa fa-check-circle status-success icon-estatus "></i><span style="font-size: 10px;">'.$items['eStatus'].
                                                   '<br><span style="font-size: 9px;position: relative;top: -5px;">Payday Limit: <b>'.$fechaLimite.'</b></span></span>';
                                    $titleEstatus= "The invoice has not been paid but is on time.";
                                    $class       = "class = \"green\""; 
                                }
                            }
                            
                            $btn_confirm = '<div class="btn-icon btn-left btn-green btn_add_pay" title="Add Payments"><i class="fa fa-plus"></i></div>';
                            if($items['sNombreArchivo'] != ""){
                                $btn_confirm.= "<div class=\"btn-icon btn-left pdf\" title=\"Open file\" onclick=\"window.open('open_pdf.php?idfile=".$items['iConsecutivo']."&type=invoice');\"><i class=\"fa fa-file-pdf-o\"></i><span></span></div>";
                            }
                            else{
                                $btn_confirm.= "<div class=\"btn_pdf btn-icon pdf btn-left\" title=\"Open Invoice PDF\"><i class=\"fa fa-file-pdf-o\"></i></div>";     
                            }
                            
                         }
                         
                     } */
                     
                     
                     //Redlist:
                     //$items['iOnRedList'] == '1' ? $redlist_icon = "<i class=\"fa fa-star\" style=\"color:#e8051b;margin-right:4px;\"></i>" : $redlist_icon = ""; 
                     
                     //Revisar si esta marcado como enviado y se envio fuera del sistema:
                     //$iEnviadoFuera == 1 ? $txtFechaApp = "<span style=\"font-size:9px;display:block;\">Mark As Sent</span>".$items['dFechaAplicacion'] : $txtFechaApp = $items['dFechaAplicacion'];
                 
                     /*$total      = "";
                     $titletotal = "";
                     if($items['iConsecutivoInvoice'] != ""){
                        $total     = "\$ ".number_format($items['dTotal'],2,'.',',')." ".$items['sCveMoneda'];
                        $titletotal= 'title="'.number_format($items['dTotal'],2,'.','').'"';    
                     } */
                     
                     if($items['sBrokerName'] == 'CRC INSURANCE SERVICES INC'){$sNombreBroker = 'CRC';}else{$sNombreBroker = $items['sBrokerName'];}
                     
                     //strlen($items['sBrokerName']) > 25 ? $sNombreBroker = substr($items['sBrokerName'],0,25)."..." : $sNombreBroker = $items['sBrokerName'];
                     
                     $htmlTabla .= "<tr $class>".
                                   "<td id=\"iCve_".$items['iConsecutivoCompania']."\" class=\"".$invoiceID."\">".$redlist_icon.$items['sNombreCompania']."</td>".
                                   "<td id=\"iPol_".$items['iConsecutivoPoliza']."\" title=\"".$items['sDescripcion']." | ".$items['sBrokerName']."\"><span style=\"display: block;float: left;width: 190px;\">".$items['sNumeroPoliza']."</span><span style=\"display: block;float: left;width: 80px;\">".$items['sAlias']."</span><span style=\"display: block;float: left;width: 53px;\">".$sNombreBroker."</span></td>". 
                                   "<td id=\"iEnd_".$items['iConsecutivoEndoso']."\" class=\"text-center\">".$items['sNumeroEndosoBroker']."</td>". 
                                   "<td>".$detalle."</td>".
                                   "<td class=\"text-center\">$FinancingData</td>".
                                   "<td id=\"iEstatus_".$items['eStatus']."\" title='$titleEstatus'>".$estado."</td>". 
                                   "<td class=\"text-right\" $titletotal>".$total."</td>". 
                                   "<td>".$btn_confirm."</td></tr>";
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
  
  function save_data(){
      
      $error   = '0'; 
      $valores = array();
      $campos  = array(); 
      $msj     = "";
      
      //Conexion:
      include("cn_usuarios.php"); 
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      $_POST["iConsecutivo"] == "" ? $edit_mode= 'false' : $edit_mode = 'true';
      $_POST['dFechaInvoice'] =  date('Y-m-d',strtotime(trim($_POST['dFechaInvoice'])));
  
      $valid_user = valid_user($_SESSION['usuario_actual']);

      if(!($valid_user)){
          $error = '1';
          $msj   = "This user does not have the privileges to modify or add data to the system.";
      }
     
      # Archivo
      if($error == '0' && isset($_FILES['file-0'])){
          
          $file        = fopen($_FILES['file-0']["tmp_name"], 'r'); 
          $fileContent = fread($file, filesize($_FILES['file-0']["tmp_name"]));
          $fileName    = $_FILES['file-0']['name'];
          $fileType    = $_FILES['file-0']['type']; 
          $fileTmpName = $_FILES['file-0']['tmp_name']; 
          $fileSize    = $_FILES['file-0']['size']; 
          $fileError   = $_FILES['file-0']['error'];
          $fileExten   = explode(".",$fileName);
          
          //Validando nombre del archivo sin puntos...
          if(count($fileExten) != 2){$error = '1';$msj = "Error: Please check that the name of the file should not contain points.";}
          else{
              //Extension Valida:
              $fileExten = strtolower($fileExten[1]);
              if($fileExten != "pdf" && $fileExten != "jpg" && $fileExten != "jpeg" && $fileExten != "png" && $fileExten != "doc" && $fileExten != "docx" && $fileExten != "xlsx" && $fileExten != "xls" && $fileExten != "zip" && $fileExten != "ppt" && $fileExten != "pptx"){
                  $error = '1'; $msj="Error: The file extension is not valid, please check it.";
              }
              else{
                  //Verificar Tamaño:
                  if($fileSize > 0  && $fileError == 0){
                      #CONVERT FILE VAR TO POST ARRAY:
                      $_POST['hContenidoDocumentoDigitalizado'] = $conexion->real_escape_string($fileContent); //Contenido del archivo 
                      $_POST['sTipoArchivo']    = $fileType;
                      $_POST['iTamanioArchivo'] = $fileSize;
                      $_POST['sNombreArchivo']  = $fileName;
                  }
                  else{$error = '1';$msj = "Error: The file you are trying to upload is empty or corrupt, please check it and try again.";}
              }
          }
          
      }
      
      if($error == '0'){
          //Validar que la referencia no este repetida:
          $query  = "SELECT COUNT(iConsecutivo) AS total FROM cb_invoices WHERE sNoReferencia ='".$_POST['sNoReferencia']."' AND bEliminado='0'";
          $result = $conexion->query($query);
          $valida = $result->fetch_assoc();
          
          if($valida['total'] != '0'){
              if($edit_mode != 'true'){
                  $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>
                          Error: The Reference that you trying to add already exists. Please verify the data.</p>';
                  $error = '1';
              }else{
                 foreach($_POST as $campo => $valor){
                    if($campo != "accion" && $campo != "edit_mode" && $campo != "iConsecutivo" && $campo != 'iConsecutivoEndoso' && $campo != "iConsecutivoPoliza"){ //Estos campos no se insertan a la tabla
                        array_push($valores,"$campo='".trim($valor)."'");
                    }
                 }   
              }
          }
          else if($edit_mode != 'true'){
             foreach($_POST as $campo => $valor){
               if($campo != "accion" && $campo != "edit_mode" && $campo != "iConsecutivo" && $campo != 'iConsecutivoEndoso' && $campo != "iConsecutivoPoliza"){ //Estos campos no se insertan a la tabla
                    array_push($campos ,$campo); 
                    array_push($valores, trim($valor));
               }
             }  
          }
      }
      
      if($error == '0'){
          
          //GET CLIENTE DATOS:
          $query  = "SELECT sNombreCompania AS sReceptorNombre, CONCAT(sDireccion,', ',sCiudad,' ',sEstado,' ',sCodigoPostal) AS sReceptorDireccion ".
                    "FROM ct_companias WHERE iConsecutivo = '".trim($_POST['iConsecutivoCompania'])."'";
          $result = $conexion->query($query);
          
          if($result->num_rows > 0){
             $items = $result->fetch_assoc(); 
             if($edit_mode == 'true'){
                array_push($valores,"sReceptorNombre='".trim($items['sReceptorNombre'])."'"); 
                array_push($valores,"sReceptorDireccion='".trim($items['sReceptorDireccion'])."'");
             }else{
                array_push($campos ,'sReceptorNombre');    array_push($valores, trim($items['sReceptorNombre'])); 
                array_push($campos ,'sReceptorDireccion'); array_push($valores, trim($items['sReceptorDireccion']));
             }
          }
          
          if($edit_mode == 'true'){
              
            array_push($valores ,"dFechaActualizacion='".date("Y-m-d H:i:s")."'");
            array_push($valores ,"sIP='".$_SERVER['REMOTE_ADDR']."'");
            array_push($valores ,"sUsuarioActualizacion='".$_SESSION['usuario_actual']."'");
            
            $sql = "UPDATE cb_invoices SET ".implode(",",$valores)." WHERE iConsecutivo = '".$_POST['iConsecutivo']."'";
            $conexion->query($sql);
            
            if($conexion->affected_rows < 0){$transaccion_exitosa = false;$msj = "Error update invoice data, please try again.";$error='1';}
            else{$idFactura = $_POST['iConsecutivo']; $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>The data has been updated successfully.</p>';}
            
          }
          else{
            array_push($campos ,"dFechaIngreso");
            array_push($valores ,date("Y-m-d H:i:s"));
            array_push($campos ,"sIP");
            array_push($valores ,$_SERVER['REMOTE_ADDR']);
            array_push($campos ,"sUsuarioIngreso");
            array_push($valores ,$_SESSION['usuario_actual']);
            
            $sql = "INSERT INTO cb_invoices (".implode(",",$campos).") VALUES ('".implode("','",$valores)."')"; 
            $conexion->query($sql);
            
            if($conexion->affected_rows < 1){$transaccion_exitosa = false;$msj = "Error saving invoice data, please try again.";$error = '1';}
            else{$idFactura = $conexion->insert_id; $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>The data has been saved successfully!</p>';}
            
            //ACTUALIZAR DATOS DEL ENDOSO:
            if($transaccion_exitosa && $error == '0'){
                $query = "UPDATE cb_endoso_estatus SET iConsecutivoInvoice='".$idFactura."',eStatus='BILLED' WHERE iConsecutivoEndoso='".$_POST['iConsecutivoEndoso']."' AND iConsecutivoPoliza='".$_POST['iConsecutivoPoliza']."'"; 
                $conexion->query($query);  
                
                if($conexion->affected_rows == 0){$error = '1'; $msj = "Error to update the endorsement data to link the invoice, please try again.";}
                else{
                    $query = "SELECT iConsecutivo AS iConsecutivoServicio, sClave, sDescripcion, sCveUnidadMedida, iPrecioUnitario, iPctImpuesto FROM ct_productos_servicios WHERE sDescripcion LIKE '%ENDORSEMENT%' LIMIT 1"; 
                    $res   = $conexion->query($query);
                    
                    if($res->num_rows > 0){
                        $service = $res->fetch_assoc();
                        
                        //Agregar registro automatico para ligar endoso desde la factura:
                        $campos_endoso = array();
                        $valores_endoso= array();
                        
                        foreach($service as $campo => $valor){
                            array_push($campos_endoso ,$campo); 
                            array_push($valores_endoso, trim($valor));
                        } 
                        
                        //Calcular precio extendido:
                        if($service['iPctImpuesto'] > 0){
                            $iImpuesto       = number_format(($servicio['iPrecioUnitario']*(($service['iPctImpuesto']/100))),2,'.',''); 
                            $precioExtendido = number_format(($servicio['iPrecioUnitario']+$iImpuesto),2,'.','');       
                        }
                        else{
                            $precioExtendido = 0;
                            $iImpuesto       = 0;
                        }
                        
                        //Campos adicionales:
                        array_push($campos_endoso ,"iCantidad");            array_push($valores_endoso ,'1');
                        array_push($campos_endoso ,"iConsecutivoInvoice");  array_push($valores_endoso ,"$idFactura");
                        array_push($campos_endoso ,"iEndorsementsApply");   array_push($valores_endoso ,'1');
                        array_push($campos_endoso ,"iMostrarEndorsements"); array_push($valores_endoso ,'1');
                        array_push($campos_endoso ,"iImpuesto");            array_push($valores_endoso ,$iImpuesto);
                        array_push($campos_endoso ,"iPrecioExtendido");     array_push($valores_endoso ,$precioExtendido);
                        array_push($campos_endoso ,"dFechaIngreso");        array_push($valores_endoso ,date("Y-m-d H:i:s"));
                        array_push($campos_endoso ,"sIP");                  array_push($valores_endoso ,$_SERVER['REMOTE_ADDR']);
                        array_push($campos_endoso ,"sUsuarioIngreso");      array_push($valores_endoso ,$_SESSION['usuario_actual']);
                        
                        $query = "INSERT INTO cb_invoice_detalle (".implode(",",$campos_endoso).") VALUES ('".implode("','",$valores_endoso)."')";
                        $conexion->query($query);  
                
                        if($conexion->affected_rows == 0){$error = '1'; $msj = "Error to add the endorsement summary data to link the invoice, please try again.";}
                        else{
                            $idDetalle = $conexion->insert_id;
                            $query = "INSERT INTO cb_invoice_detalle_endoso (iConsecutivoDetalle,iConsecutivoEndoso) VALUES ('".$idDetalle."','".$_POST['iConsecutivoEndoso']."')";
                            $conexion->query($query); 
                            if($conexion->affected_rows == 0){$error = '1'; $msj = "Error to update the endorsement summary data to link the invoice, please try again.";}   
                        }
                    }   
                } 
            }
            
          }
          
          
      }
      
      $transaccion_exitosa && $error == '0' ? $conexion->commit() : $conexion->rollback();
      $conexion->close();
      
      $response = array("error"=>"$error","msj"=>"$msj","idFactura"=>"$idFactura");
      echo json_encode($response);
  }
  
  // EXTRAS:
  function get_policy_data(){
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      $htmlTabla = "";
      $clave     = trim($_POST['iConsecutivoPoliza']);
      $clave2    = trim($_POST['iConsecutivoEndoso']);
      
      $clave != "" ? $flt_poliza = "AND iConsecutivoPoliza='$clave'" : $flt_poliza = "";
      
      // Get endorsement data by system id:
      $query  = "SELECT sNumeroEndosoBroker, sComentarios, rImporteEndosoBroker, iConsecutivoPoliza FROM cb_endoso_estatus WHERE iConsecutivoEndoso = '".$clave2."' $flt_poliza";
      $result = $conexion->query($query);
      $rows   = $result->num_rows; 
      if ($rows > 0) {
       
          while ($endosos = $result->fetch_assoc()){
               
              // Get policy data:
              $sql   = "SELECT A.iConsecutivo AS clave,B.sNombreCompania, sNumeroPoliza, sNombreCompania, C.sName AS sBroker, E.sName AS sInsurance , D.sDescripcion, iOnRedList, DATE_FORMAT(dFechaInicio,'%m/%d/%Y') AS dFechaInicio, DATE_FORMAT(dFechaCaducidad,'%m/%d/%Y') AS dFechaCaducidad, iConsecutivoArchivo,A.iConsecutivoCompania, iTipoPoliza, iConsecutivoArchivoPFA ".
                       "FROM      ct_polizas     AS A 
                        LEFT JOIN ct_companias   AS B ON A.iConsecutivoCompania   = B.iConsecutivo
                        LEFT JOIN ct_brokers     AS C ON A.iConsecutivoBrokers    = C.iConsecutivo
                        LEFT JOIN ct_tipo_poliza AS D ON A.iTipoPoliza            = D.iConsecutivo 
                        LEFT JOIN ct_aseguranzas AS E ON A.iConsecutivoAseguranza = E.iConsecutivo ".
                       "WHERE A.iConsecutivo='".$endosos['iConsecutivoPoliza']."'";
              $res   = $conexion->query($sql);
              $row   = $res->num_rows; 
              
              if ($row > 0) {
                  
                 $poliza = $res->fetch_assoc();
               
                 #CONSULTAR DETALLE DEL ENDOSO:
                 $query = "SELECT A.sVIN, (CASE 
                            WHEN A.eAccion = 'ADDSWAP'    THEN 'ADD SWAP'
                            WHEN A.eAccion = 'DELETESWAP' THEN 'DELETE SWAP'
                            WHEN A.eAccion = 'CHANGEPD'   THEN 'CHANGE PD'
                            ELSE A.eAccion
                            END) AS eAccion FROM cb_endoso_unidad AS A WHERE A.iConsecutivoEndoso = '".$clave2."' ORDER BY sVIN ASC";
                 $r     = $conexion->query($query);
                 
                 $description = "";   
                 $count       = 0;
                 $descTitle   = "";
                 
                 while($item = $r->fetch_assoc()){
                    
                    if($count == 0){
                        $firstA = $item['eAccion'];
                        $firstV = $item['sVIN'];
                        $description = "<tr style='background: none;'>".
                                        "<td style='border: 0;width:105px;padding: 0!important;min-height: auto!important;height:auto!important;'>".$item['eAccion']."</td>".
                                        "<td style='border: 0;padding: 0!important;min-height: auto!important;height:auto!important;'>".$item['sVIN']."</td>".
                                        "<td style='border: 0;width: 70px;padding: 0!important;min-height: auto!important;height:auto!important;'>".$endosos['dFechaAplicacion']."</td>".
                                        "</tr>";                    
                    }
                    else{
                        if($count == 1){
                            $description = "<tr style='background: none;'>".
                                            "<td style='border: 0;width:105px;padding: 0!important;min-height: auto!important;height:auto!important;'>".$firstA."</td>".
                                            "<td style='border: 0;padding: 0!important;min-height: auto!important;height:auto!important;'>".$firstV."... </td>".
                                            "<td style='border: 0;width: 70px;padding: 0!important;min-height: auto!important;height:auto!important;'>".$endosos['dFechaAplicacion']."</td>".
                                            "</tr>"; 
                        }
                    } 
                    $descTitle == "" ? $descTitle .= $item['eAccion']." ".$item['sVIN'] : $descTitle.= "\n".$item['eAccion']." ".$item['sVIN'];
                    $count ++;
                 }
                 $detalle  = "<table title=\"$descTitle\" style=\"width:100%;padding:0!important;margin:0!important;border-collapse: collapse;\">";
                 $detalle .= $description."</table>"; 
                  
                  
                $insurance = strtoupper(utf8_decode($poliza['sInsurance']));
                if(strlen($insurance) > 15){$insurance = substr($insurance,0,15)."... ";}
                
                $broker = strtoupper(utf8_decode($poliza['sBroker']));
                if(strlen($broker) > 15){$broker = substr($broker,0,15)."... ";}
                
                $query = "SELECT iConsecutivo FROM cb_endoso_files WHERE iConsecutivoEndoso = '$clave2' AND iConsecutivoPoliza ='".$endosos['iConsecutivoPoliza']."' LIMIT 1";
                $res2  = $conexion->query($query);
                
                if($res2->num_rows > 0){
                   $idArchivo = $res2->fetch_assoc();
                   $idArchivo = $idArchivo['iConsecutivo'];
                   $btn_pdf_endoso = "<td class=\"text-center\">".
                                     "<div class=\"btn-icon pdf btn-left active\" title=\"Open endorsement in a new window\" style=\"margin-left: 30%;\" onclick=\"window.open('open_pdf.php?idfile=".$idArchivo."&type=endoso');\"><i class=\"fa fa-file-pdf-o\"></i></div>".
                                     "</td>"; 
                }
                else{$btn_pdf_endoso = "<td class=\"text-center\" style=\"color:#d10e0e;\">NO UPLOADED</td>";}
                
                //$btn_pdf = '<a href="endorsement_files?idEndorsement='.$clave2.'&sNombreCompania='.$items['sNombreCompania'].'" target="_blank" class="btn-text-2 btn-right" title="Endorsement files"><i class="fa fa-external-link" aria-hidden="true" style="margin-right:0px!important;"></i></a>'
                $btn_pdf = "<div class=\"btn-icon edit btn-left\" title=\"Endorsement files\" onclick=\"window.open('endorsement_files?idEndorsement=".$clave2.'&sNombreCompania='.$poliza['sNombreCompania'].");\"><i class=\"fa fa-external-link\"></i></div>";
                
                $htmlTabla .= "<tr>".
                              "<td>".$poliza['sNumeroPoliza']."</td>".
                              "<td>".$poliza['sDescripcion']."</td>". 
                              "<td title=\"".strtoupper(utf8_decode($poliza['sBroker']))."\">".$broker."</td>".
                              "<td title=\"".strtoupper(utf8_decode($poliza['sInsurance']))."\">".$insurance."</td>". 
                              "<td>".$poliza['dFechaInicio']."</td>".
                              "<td>".$poliza['dFechaCaducidad']."</td>".  
                              "<td title=\"Customer service Comments: ".$endosos['sComentarios']."\">".$endosos['sNumeroEndosoBroker']."</td>".
                              "<td>".$detalle."</td>".
                              "<td class=\"text-right\"> \$".number_format($endosos['rImporteEndosoBroker'],2,'.',',')."</td>". 
                              $btn_pdf_endoso. 
                             // "<td>".$btn_pdf."</td>".                                                                                                                                                                                                                   
                              "</tr>";     
              }
                
          }
      
      }
      
      if($htmlTabla == ""){$htmlTabla ="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";} 
      $conexion->rollback();
      $conexion->close();  
      
      $response = array("html"=>"$htmlTabla");   
      echo json_encode($response);
  }
  function get_invoice_data(){
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      $clave = trim($_POST['iConsecutivoInvoice']);
      
      $sql   = "SELECT A.iConsecutivo,sNoReferencia, sNombreCompania, B.sEmailContacto, sNombreContacto, dTotal, iFinanciamiento, sDiasFinanciamiento, eStatus, iOnRedList, DATE_FORMAT(dFechaInvoice, '%m/%d/%Y') AS  dFechaInvoice, sCveMoneda, A.sNombreArchivo ".   
               "FROM      cb_invoices  AS A ".
               "LEFT JOIN ct_companias AS B ON A.iConsecutivoCompania = B.iConsecutivo ".
               "WHERE A.iConsecutivo='".$clave."'";
      $result= $conexion->query($sql);
      $rows  = $result->num_rows; 
      if ($rows > 0) {    
        while ($items = $result->fetch_assoc()){ 
            $FinancingData = "";
            if($items['iFinanciamiento'] == "0"){
                $FinancingData = 'N/A';  
                //Validar fecha limite de pago para determinar el estatus:
                $fechaLimite   = sumar_dias_fecha($items['dFechaAplicacion'],10);   
            }
            else{
                $FinancingData = "<span style=\"font-weight: 700;\">".$items['sDiasFinanciamiento']." months</span>";
                $FinancingData.= "<br><span style=\"font-size: 10px;opacity: 0.9;\">\$ ".$items['dFinanciamientoMonto']." ".$items['sCveMoneda']."</span>"; 
            }
            
            $htmlTabla = "<tr ".$redlist_class.">".
                         "<td id=\"inv_".$items['iConsecutivo']."\">".$items['sNoReferencia']."</td>".
                         "<td class=\"text-center\">".$FinancingData."</td>".
                         "<td class=\"text-center\">".$items['dFechaInvoice']."</td>".
                         "<td class=\"text-right\" style=\"padding-right: 5px;\">\$ ".number_format($items['dTotal'],2,'.',',')." ".$items['sCveMoneda']."</td>".  
                         "</tr>"; 
        }
      }
      else{$htmlTabla ="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";} 
      $conexion->rollback();
      $conexion->close();  
      
      $response = array("html"=>"$htmlTabla");   
      echo json_encode($response);
  }
  
  // FINANCIAMIENTO
  function get_financier_data(){
     include("cn_usuarios.php");
     $conexion->autocommit(FALSE);
     
     $clave  = trim($_POST['clave']);
     
     if($clave != ""){
        $sql   = "SELECT sNombreContacto,sEmail FROM ct_financieras WHERE iConsecutivo = '".$clave. "'";
        $result= $conexion->query($sql);
        $rows  = $result->num_rows;  
        if($rows > 0){
            while ($items = $result->fetch_assoc()) {
                
               $contacto = $items['sNombreContacto']; 
               $emails   = $items['sEmail'];
            }                                                                                                                                                                       
        }   
     }
      
     $conexion->rollback();
     $conexion->close();
     $htmlTabla = utf8_encode($htmlTabla);  
     $response = array("contacto"=>"$contacto","emails"=>"$emails");   
     echo json_encode($response);      
  }
  function financing_save(){
      
      $error   = '0'; 
      $valores = array();
      $campos  = array(); 
      $msj     = "";  
      //Conexion:
      include("cn_usuarios.php"); 
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      $edit_mode           = $_POST["edit_mode"];
      $idFinancing         = $_POST['iConsecutivoFinanciamiento'];
      
      #SI APLICA FINANCIAMIENTO...
      if($_POST['iAplicaFinanciamiento'] == '1'){
          $_POST['sComentarios'] != "" ? $sComentarios = trim(fix_string($_POST['sComentarios'])) : $sComentarios = "''";
      
          if($edit_mode == 'true'){
            foreach($_POST as $campo => $valor){
                if($campo != "accion" && $campo != "edit_mode" && $campo != "iConsecutivoEndoso" && $campo != 'iConsecutivoPoliza' && $campo != 'iConsecutivoFinanciamiento' && $campo != 'iConsecutivoCompania' && $campo != 'sComentariosFinanciamiento' && $campo != 'iAplicaFinanciamiento'){ //Estos campos no se insertan a la tabla
                    if($campo == "sNombreContactoFinanciera" && $valor != ""){$valor = (strtoupper($valor));}
                    if($campo == "sEmailFinanciera" && $valor != ""){$valor = strtolower($valor);}
                    if($campo == 'dFechaAplicacion'){$valor = date('Y-m-d',strtotime(trim($valor)));}
                    array_push($valores,"$campo=\"".($valor)."\"");
                }
            }  
            
            //Campos adicionales:
            array_push($valores ,"dFechaActualizacion='".date("Y-m-d H:i:s")."'");
            array_push($valores ,"sIPActualizacion='".$_SERVER['REMOTE_ADDR']."'");
            array_push($valores ,"sUsuarioActualizacion='".$_SESSION['usuario_actual']."'"); 
            $sql = "UPDATE cb_endoso_financiamiento SET ".implode(",",$valores)." WHERE iConsecutivoFinanciamiento =\"".$_POST['iConsecutivoFinanciamiento']."\"";
            $conexion->query($sql);
            
            if($conexion->affected_rows < 0){
                $transaccion_exitosa = false;
                $error               = '1';
                $msj                 = 'Error when update data, please try again later.';
            }else{$msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>Data have been updated successfully.</p>';}
               
          }
          else{
              foreach($_POST as $campo => $valor){
                  if($campo != "accion" && $campo != "edit_mode" && $campo != "iConsecutivoEndoso" && $campo != 'iConsecutivoPoliza' && $campo != 'iConsecutivoFinanciamiento' && $campo != 'iConsecutivoCompania' && $campo != 'sComentariosFinanciamiento' && $campo != 'iAplicaFinanciamiento'){ //Estos campos no se insertan a la tabla
                        if($campo == "sNombreContactoFinanciera" && $valor != ""){$valor = strtoupper($valor);}
                        if($campo == "sEmailFinanciera" && $valor != ""){$valor = strtolower($valor);}
                        if($campo == 'dFechaAplicacion'){$valor = date('Y-m-d',strtotime(trim($valor)));}
                        array_push($campos ,$campo); 
                        array_push($valores,trim($valor));
                  }
              }
      
              array_push($campos ,"sIPIngreso");      array_push($valores ,$_SERVER['REMOTE_ADDR']);
              array_push($campos ,"sUsuarioIngreso"); array_push($valores ,$_SESSION['usuario_actual']);
              
              $sql = "INSERT INTO cb_endoso_financiamiento (".implode(",",$campos).") VALUES (\"".implode("\",\"",$valores)."\")";
              $conexion->query($sql);
              
              if($conexion->affected_rows < 1){
                $transaccion_exitosa = false;
                $error               = '1';
                $msj                 = 'Error when save data, please try again later.';
              }
              else{
                  $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>Data have been added successfully.</p>';
                  $idFinancing = $conexion->insert_id;
              }
              
          }    
      }
      
      
      //Actualizar endoso para marcar financiamiento
      if($transaccion_exitosa && $error == '0' && $edit_mode == 'false'){
         
        $idFinancing != "" ? $idFinancingTxt = ", iConsecutivoFinanciamiento = '$idFinancing' " : "";
        $sql = "UPDATE cb_endoso_estatus SET iAplicaFinanciamiento='".trim($_POST['iAplicaFinanciamiento'])."', sComentariosFinanciamiento='".trim($_POST['sComentariosFinanciamiento'])."' ".$idFinancingTxt.
               "WHERE iConsecutivoEndoso ='".trim($_POST['iConsecutivoEndoso'])."' AND iConsecutivoPoliza='".trim($_POST['iConsecutivoPoliza'])."'";
        $conexion->query($sql); 
        if($conexion->affected_rows < 0){
            $transaccion_exitosa = false;
            $error               = '1';
            $msj                 = 'Error when update endorsement data, please try again later.';
        }      
      }

      $transaccion_exitosa && $error == '0' ? $conexion->commit() : $conexion->rollback(); 
      
      $response = array("error"=>"$error","msj"=>"$msj","iConsecutivoFinanciamiento"=>"$idFinancing");
      echo json_encode($response);    
  }
  function financing_mark_sent(){
      
      $clave = trim($_POST['iConsecutivoFinanciamiento']);
      $endid = trim($_POST['iConsecutivoEndoso']);
      $polid = trim($_POST['iConsecutivoPoliza']);
      $error = 0;
     
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);
      
      //Actualizar FINANCIAMIENTO:
      $query   = "UPDATE cb_endoso_financiamiento SET iEnviadoFuera='1' WHERE iConsecutivoFinanciamiento='$clave'"; 
      $success = $conexion->query($query); 
      
      if($conexion->affected_rows > 0){
          //Actualizar endoso estatus
          $query   = "UPDATE cb_endoso_estatus SET eStatus='FINANCING' WHERE iConsecutivoEndoso='$endid' AND iConsecutivoPoliza='$polid'"; 
          $success = $conexion->query($query); 
          
          if($conexion->affected_rows > 0){ 
              
              //Actualizar endoso general:
              $valid  = set_endoso_estatus($endid,'FINANCING',$conexion);
              $mensaje= 'The Financing has been mark has sent in solo-trucking, please update the data whe you have a answer from financier.';
              
          }else{$error = 1; $mensaje = "Error to update the policy/endorsement status data, please try again.";}  
      
      }else{$error = 1; $mensaje = "Error to update the financing data, please try again.";}  

      $error == 0 ? $conexion->commit() : $conexion->rollback();  
      $conexion->close();
      
      $response = array("msj"=>$mensaje,"error"=>"$error");   
      echo json_encode($response);      
  }
  function financing_get_data(){
      
      $endid   = trim($_POST['iConsecutivoEndoso']);
      $polid   = trim($_POST['iConsecutivoPoliza']); 
      $error   = '0';
      $fields  = "";
      $mensaje = "";
      $domroot = $_POST['domroot'];
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);
      
      $sql   = "SELECT iConsecutivoFinanciamiento FROM cb_endoso_estatus WHERE iConsecutivoEndoso = '".$endid."' AND iConsecutivoPoliza='".$polid."'";
      $result= $conexion->query($sql);
      $rows  = $result->num_rows; 
      if($rows > 0){
          $items = $result->fetch_assoc();
          
          $query = "SELECT iConsecutivoFinanciamiento, iConsecutivoFinanciera, DATE_FORMAT(dFechaAplicacion,'%m/%d/%Y') AS dFechaAplicacion, sComentarios, sNombreContactoFinanciera, sEmailFinanciera ".
                   "FROM cb_endoso_financiamiento WHERE iConsecutivoFinanciamiento='".$items['iConsecutivoFinanciamiento']."'";
          $result= $conexion->query($query);
          $rows  = $result->num_rows; 
          if($rows > 0){
            $data    = $result->fetch_assoc(); //<---Endorsement Data Array.
            $llaves  = array_keys($data);
            $datos   = $data; 
            
            foreach($datos as $i => $b){ 
                if($i != 'eStatus' && $i != 'sComentarios' && $i != 'iConsecutivoPoliza' && $i != 'iConsecutivoTipoEndoso'){
                  $fields .= "\$('#$domroot [name=".$i."]').val('".$datos[$i]."');";  
                }else if($i == 'sComentarios'){
                  $comentarios = fix_string($datos[$i]);  
                }    
            }    
          }         
      }  
      
      $conexion->rollback();
      $conexion->close();  
      
      $response = array("fields"=>"$fields","error"=>"$error","msj"=>"$mensaje");   
      echo json_encode($response); 
  }
  function financing_upload_pdf(){
        
        include("cn_usuarios.php");
        $conexion->autocommit(FALSE);
        $transaccion_exitosa = true;

        $error     = "0";
        $mensaje   = "";
        $clave     =  $_POST['iConsecutivoFinanciamiento'];
        $campos    = array();
        $valores   = array();
        $fileExist = false;
        $name_file = "";
        
        $_POST['iConsecutivoArchivo'] != '' ? $edit_mode = "true" : $edit_mode = "false";
        
        // Si el archivo existe, entonces:
        if(isset($_FILES['userfile']["tmp_name"])){
           
           $oFichero   = fopen($_FILES['userfile']["tmp_name"], 'r'); 
           $sContenido = fread($oFichero, filesize($_FILES['userfile']["tmp_name"]));  
           $sContenido =  $conexion->real_escape_string($sContenido);
           
           //Asignar al POST:
           $_POST['sNombreArchivo']                  = $_FILES['userfile']['name'];
           $_POST['iTamanioArchivo']                 = $_FILES['userfile']["size"];
           $_POST['sTipoArchivo']                    = $_FILES['userfile']["type"];
           $_POST['hContenidoDocumentoDigitalizado'] = $sContenido;
           
           $name_file   = $_POST['sNombreArchivo']; 
           $fileSize    = $_FILES['userfile']['size']; 
           $fileError   = $_FILES['userfile']['error'];
           $fileExten   = explode(".",$_POST['sNombreArchivo']); 
           $fileExist   = true;
                  
        }
        
        if($clave != '' && $fileError == '0'){
           
           // Validaciones generales para el archivo: 
           if(!($edit_mode) && !($fileExist)){
              $error   = "1";
              $mensaje = "Error to read the file data, please try again.";
           }
           else{
                $valid_user = valid_user($_SESSION['usuario_actual']);
                if(!($valid_user)){
                      $error  = '1';
                      $mensaje= "This user does not have the privileges to modify or add data to the system.";
                }
                else{
                  //Validando nombre del archivo sin puntos...
                  if(count($fileExten) != 2){$error="1";$mensaje = "Error: Please check that the name of the file should not contain points.";}
                  else{
                      //Extension Valida:
                      $fileExten = strtolower($fileExten[1]);
                      if($fileExten != "pdf" && $fileExten != "jpg" && $fileExten != "jpeg" && $fileExten != "png" && $fileExten != "doc" && $fileExten != "docx" && $fileExten != "xlsx" && $fileExten != "xls" && $fileExten != "mp3" && $fileExten != "mp4" && $fileExten != "key" && $fileExten != "cer" && $fileExten != "zip" && $fileExten != "ppt" && $fileExten != "pptx"){
                          $error = "1"; $mensaje="Error: The file extension is not valid, please check it.";
                      }    
                  }    
                } 
           } 
        }   
            
        if($error == '0'){
           // Get the endorsement system id
           $query  = "SELECT iConsecutivoEndoso, iConsecutivoPoliza FROM cb_endoso_estatus WHERE iConsecutivoFinanciamiento='".$clave."'";
           $result = $conexion->query($query); 
           $rows   = $result->num_rows; 
           $items  = $result->fetch_assoc();
           
           // GUARDAR ARCHIVO:
           #UPDATE
           if($edit_mode == "true"){
                foreach($_POST as $campo => $valor){
                    if($campo != "accion" && $campo != "iConsecutivoArchivo" && $campo != "iConsecutivoFinanciamiento"){ //Estos campos no se insertan a la tabla
                        array_push($valores,"$campo='".($valor)."'");    
                    }
                } 
                
                // agregar campos adicionales:
                array_push($valores,"dFechaActualizacion='".date("Y-m-d H:i:s")."'");
                array_push($valores,"sIP='".$_SERVER['REMOTE_ADDR']."'");
                array_push($valores,"sUsuarioActualizacion='".$_SESSION['usuario_actual']."'"); 
                array_push($valores,"eArchivo='FINANCING'");
                
                $sql     = "UPDATE cb_endoso_files SET ".implode(",",$valores)." WHERE iConsecutivo='".$items['iConsecutivoArchivo']."'";   
                $mensaje = "The data was updated successfully."; 
           }
           #INSERT
           else{
                foreach($_POST as $campo => $valor){
                    if($campo != "accion" && $campo != "iConsecutivoArchivo" && $campo != "iConsecutivoFinanciamiento"){ //Estos campos no se insertan a la tabla
                        array_push($campos , $campo);
                        array_push($valores, ($valor));      
                    }
                }
                // agregar campos adicionales:
                array_push($campos , "dFechaIngreso");       array_push($valores,date("Y-m-d H:i:s")); 
                array_push($campos , "sIP");                 array_push($valores,$_SERVER['REMOTE_ADDR']);
                array_push($campos , "dFechaActualizacion"); array_push($valores,date("Y-m-d H:i:s")); 
                array_push($campos , "sUsuarioIngreso");     array_push($valores,$_SESSION['usuario_actual']);
                array_push($campos , "eArchivo");            array_push($valores,'FINANCING');
                array_push($campos , "iConsecutivoEndoso");  array_push($valores,$items['iConsecutivoEndoso']);
                array_push($campos , "iConsecutivoPoliza");  array_push($valores,$items['iConsecutivoPoliza']);
                
                $sql     = "INSERT INTO cb_endoso_files (".implode(",",$campos).") VALUES ('".implode("','",$valores)."')"; 
                $mensaje = "The data was saved successfully."; 
           }
           
           $success = $conexion->query($sql);
           !($success) ? $transaccion_exitosa = false : $transaccion_exitosa = true;
        }
       
       
        if(!($transaccion_exitosa)){$error = '1'; $mensaje = "Error to save the data, please try again later.";}
       
        #ACTUALIZAR FINANCIAMIENTO Y ENDOSO:
        if($error == '0'){
           if($edit_mode == 'true'){$idArchivo = trim($_POST['iConsecutivoArchivo']);}else{$idArchivo = $conexion->insert_id;}
           
           $query  = "UPDATE cb_endoso_financiamiento SET iConsecutivoArchivo='".$idArchivo."' WHERE iConsecutivoFinanciamiento='".$_POST['iConsecutivoFinanciamiento']."'";    
           $conexion->query($query);
           if($conexion->affected_rows < 0){$error = '1';$mensaje="Error to update the financing data, please try again.";}
           else{
               
               $query  = "UPDATE cb_endoso_estatus SET eStatus='UNBILLED' WHERE iConsecutivoFinanciamiento='".$_POST['iConsecutivoFinanciamiento']."'";    
               $conexion->query($query);
               if($conexion->affected_rows < 0){$error = '1';$mensaje="Error to update the endorsement data, please try again.";}
               else{
                  $valid = set_endoso_estatus($items['iConsecutivoEndoso'],'UNBILLED',$conexion);    
               }
           }
       
        } 
        
        
        $error == '0' && $transaccion_exitosa ? $conexion->commit() : $conexion->rollback();      
        $conexion->close();   
        
        $response = array("mensaje"=>"$mensaje","error"=>"$error", "id_file"=>"$idArchivo", "name_file" => "$name_file"); 
        echo json_encode($response); 
  }
  
  
  function set_endoso_estatus($clave_endoso,$estatus,$conexion){
      
      $response = true;
      
      //Calcular total de estatus por endoso:
      $query = "SELECT COUNT(iConsecutivoEndoso) AS total FROM cb_endoso_estatus WHERE iConsecutivoEndoso = '$clave_endoso'";
      $res   = $conexion->query($query); 
      
      if($res->num_rows > 0 ){
         $totalEndosos = $res->fetch_assoc();
         
         //Calcular total de endosos con el estatus actualizado
         $query = "SELECT COUNT(iConsecutivoEndoso) AS total FROM cb_endoso_estatus ".
                  "WHERE iConsecutivoEndoso = '$clave_endoso' AND eStatus = '$estatus'"; 
         $res   = $conexion->query($query);
         if($res->num_rows > 0 ){
            $totalEndososActualizados = $res->fetch_assoc();
            
            if($totalEndosos['total'] == $totalEndososActualizados['total']){
                //Actualizar estatus general del endoso:
                $query = "UPDATE cb_endoso SET eStatus='$estatus' WHERE iConsecutivo='$clave_endoso'";
                $conexion->query($query);
                //if($conexion->affected_rows > 0){}
            }  
             
         }
      }
      
      return $response;
  }
  
  
  
  
  // FUNCIONES PARA MODULOS DE ENDOSO / BILLING
  function billing_send_endorsement(){
      
      $error = '0';
      $msj   = '';
      $clave = trim($_POST['iConsecutivoEndoso']);
      
      if($clave == ''){$error = '1'; $msj = "Error to get the endorsement data, please close this window and try again.";}
      else{
          //Conexion:
          include("cn_usuarios.php"); 
          $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
          
          //Update the endorsement row like "ready to bill" and update the control fields:
          $query = "UPDATE cb_endoso_estatus SET iAplicaFacturacion='1', dFechaActualizacion='".date("Y-m-d H:i:s")."', sIP='".$_SERVER['REMOTE_ADDR']."', sUsuarioActualizacion='".$_SESSION['usuario_actual']."' WHERE iConsecutivoEndoso='".$clave."'";
          $conexion->query($query);
          
          if($conexion->affected_rows < 0){$error = '1';$msj = 'We have a problem when update the endorsement like "ready to bill", please try again.';}
          else{
              
              $htmlTabla    = "<table style=\"font-size:12px;padding:0px;width:100%; margin:2px auto;font-family: Arial, Helvetica, sans-serif;\">";
              $sCompanyName = "";
              $files        = "";
              
              // Get endorsement data by system id:
              $query  = "SELECT sNumeroEndosoBroker, sComentarios, rImporteEndosoBroker, iConsecutivoPoliza FROM cb_endoso_estatus WHERE iConsecutivoEndoso = '".$clave."'";
              $result = $conexion->query($query);
              $rows   = $result->num_rows; 
              if ($rows > 0) {
               
                  while ($endosos = $result->fetch_assoc()){
                      
                      // Get policy data:
                      $sql   = "SELECT A.iConsecutivo AS clave,B.sNombreCompania, sNumeroPoliza, sNombreCompania, C.sName AS sBroker, E.sName AS sInsurance , D.sDescripcion, iOnRedList, DATE_FORMAT(dFechaInicio,'%m/%d/%Y') AS dFechaInicio, DATE_FORMAT(dFechaCaducidad,'%m/%d/%Y') AS dFechaCaducidad, iConsecutivoArchivo,A.iConsecutivoCompania, iTipoPoliza, iConsecutivoArchivoPFA ".
                               "FROM      ct_polizas     AS A 
                                LEFT JOIN ct_companias   AS B ON A.iConsecutivoCompania   = B.iConsecutivo
                                LEFT JOIN ct_brokers     AS C ON A.iConsecutivoBrokers    = C.iConsecutivo
                                LEFT JOIN ct_tipo_poliza AS D ON A.iTipoPoliza            = D.iConsecutivo 
                                LEFT JOIN ct_aseguranzas AS E ON A.iConsecutivoAseguranza = E.iConsecutivo ".
                               "WHERE A.iConsecutivo='".$endosos['iConsecutivoPoliza']."'";
                      $res   = $conexion->query($sql);
                      $row   = $res->num_rows; 
                      
                      if ($row > 0) {
                          
                         $poliza       = $res->fetch_assoc();
                         $sCompanyName = $poliza['sNombreCompania'];
                         
                         // GET ENDORSEMENT PDF FILE
                         $query  = "SELECT iConsecutivo, sNombreArchivo, eArchivo, hContenidoDocumentoDigitalizado, sTipoArchivo, iTamanioArchivo ".
                                    "FROM cb_endoso_files WHERE iConsecutivoEndoso = '".$clave."' AND iConsecutivoPoliza='".$endosos['iConsecutivoPoliza']."' AND eArchivo='ENDORSEMENT'"; 
                         $res    = $conexion->query($query) or die($conexion->error);
                         $row    = $res->num_rows;
                         
                         if($row > 0){  
                              while ($file = $res->fetch_assoc()){
                                #Here will constructed the temporary files: 
                                $archivo = array();
                                if($file['sNombreArchivo'] != ""){ 
                                     $archivo['id']     = $file['iConsecutivo'];
                                     $archivo['name']   = $file['sNombreArchivo'];
                                     $archivo['tipo']   = $file['eArchivo'];
                                     $archivo['content']= $file['hContenidoDocumentoDigitalizado'];
                                     $archivo['size']   = $file['iTamanioArchivo'];
                                     $archivo['type']   = $file['sTipoArchivo'];
                                     array_push($files,$archivo);
                                }
                              }
                         }
                       
                         #CONSULTAR DETALLE DEL ENDOSO:
                         $query = "SELECT A.sVIN, (CASE 
                                    WHEN A.eAccion = 'ADDSWAP'    THEN 'ADD SWAP'
                                    WHEN A.eAccion = 'DELETESWAP' THEN 'DELETE SWAP'
                                    WHEN A.eAccion = 'CHANGEPD'   THEN 'CHANGE PD'
                                    ELSE A.eAccion
                                    END) AS eAccion FROM cb_endoso_unidad AS A WHERE A.iConsecutivoEndoso = '".$clave2."' ORDER BY sVIN ASC";
                         $r     = $conexion->query($query);
                         
                         $description = "";   
                         $count       = 0;
                         $descTitle   = "";
                         
                         while($item = $r->fetch_assoc()){
                            
                            if($count == 0){
                                $firstA = $item['eAccion'];
                                $firstV = $item['sVIN'];
                                $description = "<tr style='background: none;'>".
                                                "<td style='border: 0;width:105px;padding: 0!important;min-height: auto!important;height:auto!important;'>".$item['eAccion']."</td>".
                                                "<td style='border: 0;padding: 0!important;min-height: auto!important;height:auto!important;'>".$item['sVIN']."</td>".
                                                "<td style='border: 0;width: 70px;padding: 0!important;min-height: auto!important;height:auto!important;'>".$endosos['dFechaAplicacion']."</td>".
                                                "</tr>";                    
                            }
                            else{
                                if($count == 1){
                                    $description = "<tr style='background: none;'>".
                                                    "<td style='border: 0;width:105px;padding: 0!important;min-height: auto!important;height:auto!important;'>".$firstA."</td>".
                                                    "<td style='border: 0;padding: 0!important;min-height: auto!important;height:auto!important;'>".$firstV."... </td>".
                                                    "<td style='border: 0;width: 70px;padding: 0!important;min-height: auto!important;height:auto!important;'>".$endosos['dFechaAplicacion']."</td>".
                                                    "</tr>"; 
                                }
                            } 
                            $descTitle == "" ? $descTitle .= $item['eAccion']." ".$item['sVIN'] : $descTitle.= "\n".$item['eAccion']." ".$item['sVIN'];
                            $count ++;
                         }
                         
                         $detalle  = "<table title=\"$descTitle\" style=\"width:100%;padding:0!important;margin:0!important;border-collapse: collapse;\">";
                         $detalle .= $description."</table>"; 
                          
                         $insurance = strtoupper(utf8_decode(utf8_encode($poliza['sInsurance'])));
                         $broker    = strtoupper(utf8_decode(utf8_encode($poliza['sBroker'])));
                         $endosos['rImporteEndosoBroker'] != "" ? $totalEndoso = "\$ ".number_format($endosos['rImporteEndosoBroker'],2,'.',',') : $totalEndoso = "";
                        
                         $htmlTabla .= "<tr>".
                                      "<td>".$poliza['sNumeroPoliza']."</td>".
                                      "<td>".$poliza['sDescripcion']."</td>". 
                                      "<td title=\"".strtoupper(utf8_decode($poliza['sBroker']))."\">".$broker."</td>".
                                      "<td title=\"".strtoupper(utf8_decode($poliza['sInsurance']))."\">".$insurance."</td>". 
                                      "<td>".$poliza['dFechaInicio']."</td>".
                                      "<td>".$poliza['dFechaCaducidad']."</td>".  
                                      "<td>".$endosos['sNumeroEndosoBroker']."</td>".
                                      "<td>".$detalle."</td>".
                                      "<td class=\"text-right\">".$totalEndoso."</td>". 
                                      "</tr>";     
                      }
                        
                  }
              
              }   
              $htmlTabla .= "</table>";
              
              #Building Email Body:                                   
              require_once("./lib/phpmailer_master/class.phpmailer.php");
              require_once("./lib/phpmailer_master/class.smtp.php");
              
              #BODY & SUBJECT
              $action   = trim($_POST['sEmailText']);                                                                   
              $subject  = "Endorsement ready to bill - please check it through our website: $sCompanyName";
             
              $htmlBody = "<table style=\"font-size:12px;border:1px solid #6191df;border-radius:3px;padding:10px;width:95%; margin:5px auto;font-family: Arial, Helvetica, sans-serif;\">".
                          "<tr><td><h2 style=\"color:#313131;text-transform: uppercase; text-align:center;\">Endorsement ready to bill from Solo-Trucking Insurance</h2></td></tr>".
                         "<tr><td><p style=\"color:#000;margin:5px auto; text-align:left;\">$action</p><br><br></td></tr>".
                         "<tr><td>$htmlTabla</td></tr>".
                         "<tr><td><p style=\"color:#010101;margin:5px auto 10px; text-align:left;font-size:11px;\">Please reply this email to the account:<a href=\"mailto:customerservice@solo-trucking.com\"> customerservice@solo-trucking.com</a></p></td></tr>". 
                         "<tr><td><p style=\"color:#858585;margin:5px auto; text-align:left;font-size:10px;\">e-mail sent from Solo-trucking Insurance System.</p></td></tr>".
                         "</table>";
              
              #HTML:
              $htmlEmail  = "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\"\"http://www.w3.org/TR/html4/strict.dtd\"><html>".
                            "<head><meta content=\"text/html; charset=utf-8\" http-equiv=\"Content-Type\">".
                            "<title>Endorsement from Solo-Trucking Insurance</title></head>"; 
              $htmlEmail .= "<body>".utf8_decode($htmlBody)."</body>";   
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
            
              #VERIFICAR SERVIDOR DONDE SE ENVIAN CORREOS:
              if($_SERVER["HTTP_HOST"]=="stdev.websolutionsac.com" || $_SERVER["HTTP_HOST"]=="www.stdev.websolutionsac.com"){
                $mail->Username   = "systemsupport@solo-trucking.com";  // GMAIL username
                $mail->Password   = "SL09100242";  
                $mail->SetFrom('systemsupport@solo-trucking.com', 'Customer Service Solo-Trucking Insurance');
              }
              else if($_SERVER["HTTP_HOST"] == "solotrucking.laredo2.net" || $_SERVER["HTTP_HOST"] == "st.websolutionsac.com" || $_SERVER["HTTP_HOST"] == "www.solo-trucking.com"){
                
                  $query = "SELECT sPasswordGmail FROM cu_control_acceso WHERE sCorreo = 'customerservice@solo-trucking.com' ";
                  $res   = $conexion->query($query);
                  $pass  = $res->fetch_assoc();
                  
                  
                  $mail->Username = "customerservice@solo-trucking.com";  // GMAIL username
                  $mail->Password = $pass['sPasswordGmail'];
                  $mail->SetFrom('customerservice@solo-trucking.com', 'Customer Service Solo-Trucking Insurance');   
              }
            
              $mail->AddReplyTo('customerservice@solo-trucking.com', 'Customer Service Solo-Trucking Insurance'); 
              $mail->AddAddress('systemsupport@solo-trucking.com','System Support Solo-Trucking Insurance');
            
              $mail->Subject    = $subject;
              $mail->AltBody    = "To view the message, please use an HTML compatible email viewer!";  // optional, comment out and test
              $mail->MsgHTML($htmlEmail);
              $mail->IsHTML(true); 
             
              //Receptores:
              $direcciones         = explode(",",trim(strtolower($_POST['sEmailToSend'])));
              $nombre_destinatario = trim('Invoices');
              foreach($direcciones as $direccion){
                $mail->AddAddress(trim($direccion),$nombre_destinatario);
              }
              
              //Atachments:
              $delete_files = "";
            
              if($files != ""){
               
               $countFiles = count($files);
               //SI SON MAS DE 10 ARCHIVOS GENERAR ZIP:
               if($countFiles > 10){
                   $listadoArchivos = array();
           
                   for($f=0; $f < $countFiles; $f++){
                   
                       $file_tmp = fopen('tmp/'.$files[$f]["name"],"w") or die("Error when creating the file. Please check."); 
                       fwrite($file_tmp,$files[$f]["content"]); 
                       fclose($file_tmp);  
                       $archivo       = "tmp/".$files[$f]["name"];  
                       $delete_files .= "unlink('".$archivo."');";
                       array_push($listadoArchivos,$files[$f]["name"]);   //Agregar archivo al array para ZIP: 
                   
                   }  
                   
                   //Generar archivo ZIP:
                   $zipName = 'Solo-Trucking_endorsement_files';
                   $zip     = new ZipArchive();
                   $fileZip = "tmp/$zipName.zip";
                   
                   if($zip->open($fileZip, ZIPARCHIVE::CREATE) != TRUE ){exit("No se puede crear el archivo <$zipName.zip>\n");}
                   
                   foreach($listadoArchivos as $archivos){
                     $nombre_ruta_archivo = "tmp/$archivos";
                     $zip->addFile($nombre_ruta_archivo,$archivos);
                   }
                    
                   $zip->close();
               
                   $mail->AddAttachment($fileZip);            
                   $delete_files .= "unlink('".$fileZip."');";      
               }
               else{
                  for($f=0; $f < $countFiles; $f++){
                   
                   //Revisamos si es PDF:
                   include("./lib/fpdf153/fpdf.php");//libreria fpdf
                   
                   $file_tmp = fopen('tmp/'.$files[$f]["name"],"w") or die("Error when creating the file. Please check."); 
                   fwrite($file_tmp,$files[$f]["content"]); 
                   fclose($file_tmp);     
                   $archivo = "tmp/".$files[$f]["name"];  
                   $mail->AddAttachment($archivo);
                   $delete_files .= "unlink('".$archivo."');";
                   
                  }  
               }
               
              } 
        
              $mail_error = false;
              if(!$mail->Send()){$mail_error = true; $mail->ClearAddresses();}
              if(!($mail_error)){$msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>The data has been updated and sent successfully.</p>';}
              else{$msj = "Error: The e-mail cannot be sent.";$error = "1";}
        
              $mail->ClearAttachments();
              eval($delete_files); 
              
              $error == '0' && !($mail_error) ? $conexion->commit() : $conexion->rollback();
          }
      }
      
      $response = array("error"=>"$error","msj"=>"$msj");
      echo json_encode($response);
      
  }
?>
