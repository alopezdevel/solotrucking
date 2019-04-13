<?php
    session_start();
    include("cn_usuarios.php"); 
    include("functiones_genericas.php"); 
    require_once('lib/fpdf153/fpdf.php'); 
    require_once('lib/FPDI-1.6.1/fpdi.php'); 
    
    // PARAMETROS GET:
    isset($_GET['id']) ? $folio = urldecode($_GET['id']) : $folio = "";
    isset($_GET['ca']) ? $ca    = urldecode($_GET['ca']) : $ca    = "";
    isset($_GET['cb']) ? $cb    = urldecode($_GET['cb']) : $cb    = "";
    isset($_GET['cc']) ? $cc    = urldecode($_GET['cc']) : $cc    = "";
    isset($_GET['cd']) ? $cd    = urldecode($_GET['cd']) : $cd    = ""; 
    isset($_GET['ce']) ? $ce    = urldecode($_GET['ce']) : $ce    = ""; 
    isset($_GET['ds']) ? $ds    = urldecode($_GET['ds']) : $ds    = ""; 
    $error = false; 
   
    $folio = preg_replace("/%u([0-9a-f]{3,4})/i","&#x\\1;",urldecode($folio)); 
    $folio = html_entity_decode($folio,null,'UTF-8');
    
    if($folio == "" || $folio == null){$error = true; $mensaje = "Error al consultar los datos de la compañia, favor de intentarlo nuevamente.";}
    else{
        #CONSULTAR CONFIGURACION DEL CERTIFICADO Y DATOS:
        $sql    = "SELECT * FROM cb_certificate_file WHERE iConsecutivoCompania = '".$folio."'";
        $result = $conexion->query($sql);
        $rows   = $result->num_rows;    
        if($rows == 0){ $error = true; $mensaje = "Error al encontrar los datos del certificado, favor de intentarlo nuevamente.";}
        else{
           $certificado = $result->fetch_assoc(); 
           $origenCerti = $certificado['eOrigenCertificado'];
           
           #PLANTILLA
           if($origenCerti == "LAYOUT" && $ds == ""){
                $contenido   = $certificado['hContenidoDocumentoDigitalizado'];
                $nombre      = $certificado['sNombreArchivo'];  
                
                // Generar PDF en carpeta Documents    
                $nombre_pdf     = "documentos/$nombre";    
                $pdf_multiple[] = $nombre_pdf;
                $data           = $contenido; 
                if(file_put_contents($nombre_pdf, $data) == FALSE){ $error = true;$mensaje = "No se pudo generar correctamente el archivo PDF.";}   
    
           }
           #BASE DE DATOS
           else if($origenCerti == "DATABASE" || $ds == "PREVIEW"){
                // GET DATA EMPRESA:
                $query  = "SELECT * FROM ct_empresa";
                $result = $conexion->query($query); 
                $empresa= $result->fetch_assoc(); 
                
                // GET DATA polizas:
                $query  = "SELECT A.iConsecutivo, A.sNumeroPoliza,B.sAlias, B.sDescripcion,C.sName AS sAseguranza, C.sNAICNumber AS sNAIC, DATE_FORMAT(A.dFechaInicio, '%m/%d/%Y') AS dFechaInicio, DATE_FORMAT(A.dFechaCaducidad, '%m/%d/%Y') AS dFechaCaducidad, ".
                          "A.iPremiumAmount, A.iDeductible, A.iDeductibleAdditional, A.iPremiumAmountAdditional, A.iCGL_MedExp, A.iCGL_DamageRented, A.iCGL_ProductsComp, A.iCGL_EachOccurrence, A.iCGL_GeneralAggregate, A.iCGL_PersonalAdvInjury ".
                          "FROM ct_polizas AS A ".
                          "LEFT JOIN ct_tipo_poliza AS B ON A.iTipoPoliza = B.iConsecutivo ".
                          "LEFT JOIN ct_aseguranzas AS C ON A.iConsecutivoAseguranza = C.iConsecutivo ".
                          "WHERE A.iConsecutivoCompania = '$folio' AND  A.iDeleted = '0' AND dFechaCaducidad >= CURDATE()"; 
                $result = $conexion->query($query); 
                $rows   = $result->num_rows;
                if($rows == 0){$error = true; $mensaje = "Los datos de las polizas no han sido almacenados, favor de capturarlos e intentarlo nuevamente.";}  
                else{$polizas= mysql_fetch_all($result); }
                
                // GET DATA EMPRESA:
                $query  = "SELECT * FROM ct_companias WHERE iConsecutivo='$folio'";
                $result = $conexion->query($query); 
                $company= $result->fetch_assoc(); 
                
                //Cargar plantilla de certificado vacia:
                $nombre_pdf = "documentos/accord_form_certificate_template.pdf";    
           }
           
        }
    }
     
    if(!($error)){
        $pdf = new FPDI();
        $pdf->AddPage();
        $pdf->setSourceFile($nombre_pdf);
        $tplIdx = $pdf->importPage(1);
        $pdf->useTemplate($tplIdx,null, null,211,305);
               
        if($origenCerti == "DATABASE" || $ds == "PREVIEW"){
            
            $pdf->SetFillColor(255,255,255);
            $pdf->SetTextColor(0,0,0);
            $pdf->SetFont('Arial','b',10);
            
            //Datos Solo-Trucking:
            $EmpresaNombre = strtoupper($empresa['sNombreCompleto']);
            $EmpresaAddress= strtoupper($empresa['sCalle'])." SUITE ".strtoupper($empresa['sNumExterior']);
            $EmpresaEstado = strtoupper($empresa['sCiudad']).", ".strtoupper($empresa['sCveEntidad'])." ".strtoupper($empresa['sCodigoPostal']);
            $EmpresaTel1   = strtoupper($empresa['sTelefono1']);
            $EmpresaTel2   = strtoupper($empresa['sTelefono2']);
            $EmpresaEmail  = strtolower($empresa['sCorreoEmpresa']);
            
            //Datos del asegurado:
            $AseguradoNombre = strtoupper($company['sNombreCompania']);
            $AseguradoAddress= strtoupper($company['sDireccion']);
            $AseguradoEstado = strtoupper($company['sCiudad']).", ".strtoupper($company['sEstado'])." ".strtoupper($company['sCodigoPostal']);
            
            //Datos del Certificado:
            $sDescripcionOp  = strtoupper(utf8_decode($certificado['sDescripcionOperaciones'])); 
            
            #PDF HEADER IZQ
            $y = 52;
            $x = 7;
            $pdf->SetXY($x, $y);
            $pdf->Cell(90,3,$EmpresaNombre,0,0,'L',1);
            $y += 5;  
            $pdf->SetXY($x, $y);
            $pdf->Cell(90,3,$EmpresaAddress,0,0,'L',1);
            $y += 5; 
            $pdf->SetXY($x, $y);
            $pdf->Cell(90,3,$EmpresaEstado,0,0,'L',1); 
            
            $y += 12;
            $pdf->SetXY($x, $y);
            $pdf->Cell(90,3,$AseguradoNombre,0,0,'L',1); 
            $y += 5; 
            $pdf->SetXY($x, $y);
            $pdf->Cell(90,3,$AseguradoAddress,0,0,'L',1); 
            $y += 5; 
            $pdf->SetXY($x, $y);
            $pdf->Cell(90,3,$AseguradoEstado,0,0,'L',1); 
            
            #PDF HEADER DERECHA
            $pdf->SetFont('Arial','b',9);
            $y = 52;
            $x = 120;
            $pdf->SetXY($x, $y);
            $pdf->Cell(10,3,$EmpresaTel1,0,0,'L',1);
            $pdf->SetXY($x+57, $y);
            $pdf->Cell(10,3,$EmpresaTel2,0,0,'L',1);
            $pdf->SetXY($x, $y+4.5);
            $pdf->Cell(10,3,$EmpresaEmail,0,0,'L',1);
            
            #DESCRIPCION DE LAS OPERACIONES:
            $pdf->SetFont('Arial','b',10);
            $y = 222;
            $x = 10;
            $pdf->SetXY($x,$y);
            $pdf->MultiCell(190,3, $sDescripcionOp, 0,'C');
            
            #LLENADO DE POLIZAS:
            $count = count($polizas);
            $letter= array('A','B','C','D','E','F');
            $yExtra= 200; //Y para las ultimas polizas por agregar:
            for($z=0;$z<$count;$z++){
                //Revisar por tipo:
                $Palias  = $polizas[$z]['sAlias'];
                $Pnumero = strtoupper($polizas[$z]['sNumeroPoliza']);
                $PEffDate= $polizas[$z]['dFechaInicio'];
                $PExpDate= $polizas[$z]['dFechaCaducidad'];
                $letraIzq= $letter[$z];
                $Insuranc= iconv('UTF-8', 'windows-1252',$polizas[$z]['sAseguranza']); 
                $NAIC    = strtoupper($polizas[$z]['sNAIC']);
                $DescPoli= strtoupper($polizas[$z]['sDescripcion']);
                
                # COMMERCIAL GENERAL LIA..
                if($Palias == "CGL"){
                    
                    $eachoccurence = number_format($polizas[$z]['iCGL_EachOccurrence'],2,'.',',');
                    $damagedtorent = number_format($polizas[$z]['iCGL_DamageRented'],2,'.',',');
                    $medexp        = number_format($polizas[$z]['iCGL_MedExp'],2,'.',',');
                    $personaladvinj= number_format($polizas[$z]['iCGL_PersonalAdvInjury'],2,'.',',');
                    $generalaggrega= number_format($polizas[$z]['iCGL_GeneralAggregate'],2,'.',',');
                    $productsComp  = number_format($polizas[$z]['iCGL_ProductsComp'],2,'.',',');
                    
                    //Letra:
                    $x = 6.5;
                    $y = 120;
                    $pdf->SetFont('Arial','b',10);
                    $pdf->SetXY($x,$y);
                    $pdf->Cell(4,3,$letraIzq,0,0,'L',false);
                    
                    $x = 12.5;
                    $y = 116.5;
                    $pdf->SetFont('Arial','b',10);
                    $pdf->SetXY($x,$y);
                    $pdf->Cell(4,3,"X",0,0,'L',false);
                    $x += 62;
                    $y += 3;
                    $pdf->SetFont('Arial','b',8.2);
                    $pdf->SetXY($x,$y);
                    $pdf->Cell(30,3,$Pnumero,0,0,'L',false);
                    
                    //Dates
                    $x += 39.9;
                    $pdf->SetXY($x,$y);
                    $pdf->Cell(14,3,$PEffDate,0,0,'L',false);
                    $pdf->SetXY($x+16,$y);
                    $pdf->Cell(14,3,$PExpDate,0,0,'L',false);
                    
                    //Limits:
                    $x = 180;
                    $y = 116.5; $pdf->SetXY($x,$y); $pdf->Cell(4,3,$eachoccurence,0,0,'L',false); 
                    $y += 5;    $pdf->SetXY($x,$y); $pdf->Cell(4,3,$damagedtorent,0,0,'L',false);  
                    $y += 4.5;  $pdf->SetXY($x,$y); $pdf->Cell(4,3,$medexp,0,0,'L',false); 
                    $y += 4.5;  $pdf->SetXY($x,$y); $pdf->Cell(4,3,$personaladvinj,0,0,'L',false); 
                    $y += 5;    $pdf->SetXY($x,$y); $pdf->Cell(4,3,$generalaggrega,0,0,'L',false); 
                    $y += 4.5;  $pdf->SetXY($x,$y); $pdf->Cell(4,3,$productsComp,0,0,'L',false);  
                }
                # AUTO LIABILITY 
                else if($Palias == "AL"){
                    
                    $limit = number_format($polizas[$z]['iPremiumAmount'],2,'.',',');
                    
                    //Letra:
                    $x = 6.5;
                    $y = 153;
                    $pdf->SetFont('Arial','b',10);
                    $pdf->SetXY($x,$y);
                    $pdf->Cell(4,3,$letraIzq,0,0,'L',false);
                    
                    $x = 36.4;
                    $y = 158;
                    $pdf->SetFont('Arial','b',10);
                    $pdf->SetXY($x,$y);
                    $pdf->Cell(4,3,"X",0,0,'L',false);
                    $x += 38;
                    $y -= 5;
                    $pdf->SetFont('Arial','b',8.2);
                    $pdf->SetXY($x,$y);
                    $pdf->Cell(30,3,$Pnumero,0,0,'L',false); 
                    
                    //Dates
                    $x += 40;
                    $pdf->SetXY($x,$y);
                    $pdf->Cell(14,3,$PEffDate,0,0,'L',false);
                    $pdf->SetXY($x+16,$y);
                    $pdf->Cell(14,3,$PExpDate,0,0,'L',false);
                    
                    //Limits:
                    $x = 180;
                    $y -= 4; $pdf->SetXY($x,$y); $pdf->Cell(4,3,$limit,0,0,'L',false);   
                }
                # MOTOR TRUC CARGO:
                else if($Palias == "MTC" || $Palias == "PD" || $Palias == "MTCTI" || $Palias == "TI" || $Palias == "MTCRB"){
                   
                    //Letra:
                    $x       = 6.5;
                    $yExtra += 4.5;
                    $pdf->SetFont('Arial','b',10);
                    $pdf->SetXY($x,$yExtra);
                    $pdf->Cell(4,3,$letraIzq,0,0,'L',false); 
                    $pdf->SetXY($x+6,$yExtra);
                    
                    if($Palias != "MTCTI" && $Palias != "MTCRB"){$pdf->Cell(48,3,$DescPoli,0,0,'L',false);}
                    else{$pdf->MultiCell(48,3, $DescPoli, 0,'L',false);}
                 
                    
                    //Numero poliza
                    $pdf->SetFont('Arial','b',8.2);
                    $x = 74;
                    $pdf->SetXY($x,$yExtra);
                    $pdf->Cell(39,3,$Pnumero,0,0,'L',false); 
                    //Dates
                    $x += 40; $pdf->SetXY($x,$yExtra); $pdf->Cell(14,3,$PEffDate,0,0,'L',false);
                    $x += 16; $pdf->SetXY($x,$yExtra); $pdf->Cell(14,3,$PExpDate,0,0,'L',false); 
                    
                    //limits:
                    if($Palias == "MTC" || $Palias == "PD" || $Palias == "TI"){
                        
                        if($polizas[$z]['iPremiumAmount'] > 0) {$limit = "\$ ".number_format($polizas[$z]['iPremiumAmount'],2,'.',',');}else
                        if($polizas[$z]['iPremiumAmount'] == 0){$limit = "";}
                        else{$limit = strtoupper($polizas[$z]['iPremiumAmount']);}
            
                        $deduc = $polizas[$z]['iDeductible'] > 0 ? "DED \$".number_format($polizas[$z]['iDeductible'],2,'.',',') : "";
                        
                        $x += 17; $pdf->SetXY($x,$yExtra); $pdf->Cell(56,3,$limit." ".$deduc,0,0,'L',false);
                    }else{
                        
                       if($polizas[$z]['iPremiumAmount'] > 0) {$limit = "\$ ".number_format($polizas[$z]['iPremiumAmount'],2,'.',',');}else
                       if($polizas[$z]['iPremiumAmount'] == 0){$limit = "";}
                       else{$limit = strtoupper($polizas[$z]['iPremiumAmount']);}
                       
                       if($polizas[$z]['iPremiumAmountAdditional'] > 0) {$limit2 = "\$ ".number_format($polizas[$z]['iPremiumAmountAdditional'],2,'.',',');}else
                       if($polizas[$z]['iPremiumAmountAdditional'] == 0){$limit2 = "";}
                       else{$limit2 = strtoupper($polizas[$z]['iPremiumAmountAdditional']);}
                         
                       //$limit = $polizas[$z]['iPremiumAmount'] > 0 ? "\$ ".number_format($polizas[$z]['iPremiumAmount'],2,'.',',') : strtoupper($polizas[$z]['iPremiumAmount']);
                       $deduc = $polizas[$z]['iDeductible'] > 0 ? "DED \$".number_format($polizas[$z]['iDeductible'],2,'.',',') : "";
                       //$limit2= $polizas[$z]['iPremiumAmountAdditional'] > 0 ? "\$ ".number_format($polizas[$z]['iPremiumAmountAdditional'],2,'.',',') : strtoupper($polizas[$z]['iPremiumAmountAdditional']);
                       $deduc2= $polizas[$z]['iDeductibleAdditional'] > 0 ? "DED \$".number_format($polizas[$z]['iDeductibleAdditional'],2,'.',',') : "";
                       $x += 17; 
                       $pdf->SetXY($x,$yExtra); $pdf->Cell(56,3,$limit." ".$deduc,0,0,'L',false);
                       $pdf->SetXY($x,$yExtra+4); $pdf->Cell(56,3,$limit2." ".$deduc2,0,0,'L',false); 
                       
                       $yExtra += 3.5;
                    }
                    
                }   
                
                
                $pdf->SetFont('Arial','b',8.2);
                switch($letraIzq){
                    case 'A': $pdf->SetXY(120,66); $pdf->Cell(65,3,$Insuranc,0,0,'L',1); $pdf->SetXY(187,66); $pdf->Cell(15,3,$NAIC,0,0,'L',1); break;
                    case 'B': $pdf->SetXY(120,70.5); $pdf->Cell(65,3,$Insuranc,0,0,'L',1); $pdf->SetXY(187,70.5); $pdf->Cell(15,3,$NAIC,0,0,'L',1); break;
                    case 'C': $pdf->SetXY(120,75); $pdf->Cell(65,3,$Insuranc,0,0,'L',1); $pdf->SetXY(187,75); $pdf->Cell(15,3,$NAIC,0,0,'L',1); break;
                    case 'D': $pdf->SetXY(120,80); $pdf->Cell(65,3,$Insuranc,0,0,'L',1); $pdf->SetXY(187,80); $pdf->Cell(15,3,$NAIC,0,0,'L',1); break;
                    case 'E': $pdf->SetXY(120,84.5); $pdf->Cell(65,3,$Insuranc,0,0,'L',1); $pdf->SetXY(187,84.5); $pdf->Cell(15,3,$NAIC,0,0,'L',1); break;
                    case 'F': $pdf->SetXY(120,89); $pdf->Cell(65,3,$Insuranc,0,0,'L',1); $pdf->SetXY(187,89); $pdf->Cell(15,3,$NAIC,0,0,'L',1); break;
                }
            }
            
        }
        
        $pdf->SetFillColor(255,255,255);
        $pdf->SetFont('Arial','B', 15);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont('Arial','b',11);
        
        //fecha
        $time  = time();
        $fecha = date("m/d/Y", $time);
        $pdf->SetXY(175, 14);
        $pdf->Cell(29,4,$fecha,0,0,'C',1);
        //Holder
        $pdf->SetXY(10, 255);
        $pdf->Cell(90,21,'',0,0,'C',1);   
        $pdf->SetFont('Arial','B',9);
        
        //Ca
        $y_holder = 0;
        $y_holder = 258 + 4;
        $pdf->SetXY(12, $y_holder);
        $pdf->Cell(90,4,$ca,0,0,'L',1);   
        
        //Cb
        $y_holder = $y_holder + 4;
        $pdf->SetXY(12, $y_holder);
        $pdf->Cell(90,4,$cb,0,0,'L',1);
        
        //Cc
        $y_holder = $y_holder + 4;
        $pdf->SetXY(12, $y_holder);
        $pdf->Cell(90,4,$cd .' '. $cc.' '.$ce,0,0,'L',1); 
        
        $pdf->Output("Certificate-".$ca.".pdf","I");
        
        if($origenCerti == "LAYOUT" && $ds == ""){unlink($nombre_pdf);}
   }
   else{
        echo '<script language="javascript">alert(\''.$mensaje.'\')</script>';
        echo "<script language='javascript'>window.close();</script>"; 
   }    

?>

