<?php
    session_start();
    include("cn_usuarios.php"); 
    require_once('lib/fpdf153/fpdf.php'); 
    //require_once('lib/merge_pdf/fpdi/fpdi.php'); 
    require_once('lib/FPDI-1.6.1/fpdi.php'); 
    
    $folio = $_GET['id'];
    $ca    = $_GET['ca'];
    $cb    = $_GET['cb'];
    $cc    = $_GET['cc'];
    $cd    = $_GET['cd'];
    $ce    = $_GET['ce'];
    $folio = preg_replace("/%u([0-9a-f]{3,4})/i","&#x\\1;",urldecode($folio)); 
    $folio = html_entity_decode($folio,null,'UTF-8');
    
    $sql    = "SELECT iConsecutivo, hContenidoDocumentoDigitalizado, sNombreArchivo, sTipoArchivo, iTamanioArchivo ".
              "FROM   cb_certificate_file WHERE  iConsecutivoCompania = '".$folio."'";
    $result = $conexion->query($sql);
    $rows   = $result->num_rows;    
    if ($rows > 0) {        
        while ($certificates = $result->fetch_assoc()) {
            $contenido = $certificates['hContenidoDocumentoDigitalizado'];
            $nombre    = $certificates['sNombreArchivo'];
        }
    }
            
    //proceso de obtener PDF    
    $nombre_pdf     = "documentos/$nombre";    
    $pdf_multiple[] = $nombre_pdf;
    $data           = $contenido; 
    $error          = false;
    $mensaje_error  = "";
    if(file_put_contents($nombre_pdf, $data) == FALSE) {
        $mensaje_error = "No se pudo generar correctamente el archivo PDF.";
        $error = true;                   
    } 
    $pdf = new FPDI();
    $pdf->AddPage();
    $pdf->setSourceFile($nombre_pdf);
    $tplIdx = $pdf->importPage(1);
    
    $pdf->useTemplate($tplIdx,null, null,211,305);
    //modificando el PDF
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
    //$pdf->Output();
    unlink($nombre_pdf);
?>

