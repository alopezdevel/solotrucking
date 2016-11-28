<?php
  require('/lib/fpdf153/fpdf.php');//Cargando  libreria
  $pdf->AddPage();
  $pdf->SetFont('Arial','',15);
  $pdf->Cell(40,20);
  $pdf->Write(5,'PDF prueba ');
  $pdf->Image('/files/PDF_Universal_Quick_Quote.pdf' , 80 ,22, 35 , 38,'PDF');

$pdf->Output();

?>
