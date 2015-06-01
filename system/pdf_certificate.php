<?php
    require_once('./lib/fpdf153/fpdf.php'); 
    require_once('./lib/merge_pdf/fpdi/fpdi.php');
    include("cn_usuarios.php"); 
    $folio = $_GET['id'];
    $ca = $_GET['ca'];
    $cb = $_GET['cb'];
    $cc = $_GET['cc'];
    $cd = $_GET['cd'];
    $folio = preg_replace("/%u([0-9a-f]{3,4})/i","&#x\\1;",urldecode($folio)); 
    $folio = html_entity_decode($folio,null,'UTF-8');
    $sql = "SELECT iConsecutivoCompania,
            hContenidoDocumentoDigitalizado,
            sNombreArchivo,
            sTipoArchivo, 
            iTamanioArchivo
            FROM cb_certificate_file
            WHERE  iConsecutivo = '".$folio."'";
    $result = $conexion->query($sql);
    $NUM_ROWs_Certificates = $result->num_rows;    
    if ($NUM_ROWs_Certificates > 0) {        
        while ($certificates = $result->fetch_assoc()) {
            $contenido = $certificates['hContenidoDocumentoDigitalizado'];
            $nombre = $certificates['sNombreArchivo'];
        }
    }
            
    //proceso de obtener PDF    
    $nombre_pdf = "documentos/$nombre";    
    $pdf_multiple[] = $nombre_pdf;
    $data = $contenido; 
    $error = false;
    $mensaje_error = "";
    if(file_put_contents($nombre_pdf, $data) == FALSE) {
        $mensaje_error = "No se pudo generar correctamente el archivo PDF.";
        $error = true;                   
    } 
    $pdf = new FPDI();
    $pdf->AddPage();
    $pdf->setSourceFile($nombre_pdf);
    $tplIdx = $pdf->importPage(1);
    $pdf->useTemplate($tplIdx,null,null,null,null,true);
    //modificando el PDF
    $pdf->SetFillColor(255,255,255);
    $pdf->SetFont('Arial','B', 15);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial','b',11); 
    //fecha
    $time = time();
    $fecha = date("m/d/Y", $time);
    //$fecha =  '05/15/2017';
    $pdf->SetXY(180, 12);
    $pdf->Cell(29,4,$fecha,0,0,'C',1);
    //Holder
    $holder =  '05/15/2017';
    $pdf->SetXY(12, 235);
    $pdf->Cell(90,24,'',0,0,'C',1);   
    $pdf->SetFont('Arial','B',11);
    //Ca
    $y_holder = 0;
    $y_holder = 235 + 4;
    $pdf->SetXY(12, $y_holder);
    $pdf->Cell(90,4,$ca,0,0,'L',1);   
    
    //Cb
    $y_holder = $y_holder + 4;
    $pdf->SetXY(12, $y_holder);
    $pdf->Cell(90,4,$cb,0,0,'L',1);
    
    //Cc
    $y_holder = $y_holder + 4;
    $pdf->SetXY(12, $y_holder);
    $pdf->Cell(90,4,$cc.' '.$cd,0,0,'L',1);
    
    $pdf->Output("Certificate-".$ca.".pdf","D");
    //$pdf->Output();
?>

