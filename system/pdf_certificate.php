<?php
    session_start();  
    require_once('./system/lib/fpdf153/fpdf.php'); 
    require_once('./system/lib/merge_pdf/fpdi/fpdi.php');
    $pdf = new FPDI();
    $pdf->AddPage();
    $pdf->setSourceFile('certificado.pdf');
    $tplIdx = $pdf->importPage(1);
    $pdf->useTemplate($tplIdx,null,null,null,null,true);
    //modificando el PDF
    $nombre = "prueba";
    $pdf->SetFont('Arial','B', 15);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetXY($x, 98);
    $pdf->Write(0, $nombre);
     $pdf->Output('diploma2008.pdf', 'D');
    
  
?>
