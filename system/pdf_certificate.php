<?php
    require_once('./lib/fpdf153/fpdf.php'); 
    require_once('./lib/merge_pdf/fpdi/fpdi.php');
    include("cn_usuarios.php"); 
    $folio = $_GET['id'];
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
    $fecha =  '05/15/2017';
    $pdf->SetXY(180, 12);
    $pdf->Cell(29,4,$fecha,0,0,'C',1);
    //Holder
    $holder =  '05/15/2017';
    $pdf->SetXY(17, 238);
    $pdf->Cell(60,12,'',0,0,'C',1);   
    $pdf->Output();
?>

