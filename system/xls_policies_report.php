<?php
  require('lib/excel/class.writeexcel_workbook.inc.php');
  require('lib/excel/class.writeexcel_worksheet.inc.php');
  session_start();   
  include("cn_usuarios.php"); 
  include("functiones_genericas.php"); 
  
  #PARAMETERS:
  $iConsecutivoCompania   = trim($_GET['company']); 
  $iConsecutivoBrokers    = trim($_GET['broker']);
  $iConsecutivoAseguranza = trim($_GET['insurance']);
  $iTipoPoliza            = trim($_GET['politype']);
  $fecha_inicio           = urldecode($_GET['dExp_init']);
  $fecha_fin              = urldecode($_GET['dExp_endi']);
  
  $vFecha1 = substr($fecha_inicio,6,4).'-'.substr($fecha_inicio,3,2).'-'.substr($fecha_inicio,0,2); 
  $vFecha2 = substr($fecha_fin,6,4).'-'.substr($fecha_fin,3,2).'-'.substr($fecha_fin,0,2);
  
  #GENERATE THE FILTERS BY PARAMETER:
  $filtro_query = "WHERE iDeleted = '0' ";
  if($iConsecutivoCompania != "")  {$filtro_query .= "AND iConsecutivoCompania   = '$iConsecutivoCompania' ";}
  if($iConsecutivoBrokers  != "")  {$filtro_query .= "AND iConsecutivoBrokers    = '$iConsecutivoBrokers' ";} 
  if($iConsecutivoAseguranza != ""){$filtro_query .= "AND iConsecutivoAseguranza = '$iConsecutivoAseguranza' ";} 
  if($iTipoPoliza != ""){$filtro_query .= "AND iTipoPoliza = '$iTipoPoliza' ";} 
  $filtro_query .= "AND P.dFechaCaducidad BETWEEN '".format_date($fecha_inicio)."' AND '".format_date($fecha_fin)."' ";
  
  #CREATE A FILE NAME:
  $info_fecha = getdate();
  $nombre_archivo = 'Report_of_Policies'.$info_fecha['year']."_".$info_fecha['month']."_".$info_fecha['mday'].$info_fecha['hours'].$info_fecha['minutes'].$info_fecha['seconds'];
  $fname = tempnam('/tmp/', $nombre_archivo . '.xls'); 
  
  #EXCEL BEGINS:
  $workbook = new writeexcel_workbook($fname);
  $worksheet = $workbook->addworksheet();
  
  //Formatos letras:
  $formato1 =& $workbook->addformat();
  $formato1->set_bold();
  $formato1->set_color('gray');
  $formato1->set_size(9); 
  
  $formato2 =& $workbook->addformat();
  $formato2->set_bold();
  $formato2->set_color('gray');
  $formato2->set_size(9);    
  $formato2->set_align('right');
  
  $formato_titulo =& $workbook->addformat();
  $formato_titulo->set_bold();
  $formato_titulo->set_size(12);
  $formato_titulo->set_align('center');
  $formato_titulo->set_align('vcenter');
  $formato_titulo->set_merge();
  
  $formato_subtitulo =& $workbook->addformat();
  $formato_subtitulo->set_bold();
  $formato_subtitulo->set_color('gray');
  $formato_subtitulo->set_size(10);
  $formato_subtitulo->set_align('center');
  $formato_subtitulo->set_align('vcenter');
  $formato_subtitulo->set_merge();
  
  //Formatos Columnas:
  $formato_tcolumna =& $workbook->addformat();
  $formato_tcolumna->set_bold();
  $formato_tcolumna->set_color('gray');
  $formato_tcolumna->set_size(10);
  $formato_tcolumna->set_align('center');
  $formato_tcolumna->set_border_color('black');
  $formato_tcolumna->set_bottom(2);
  
  // Tamano columnas
  $worksheet->set_column(0, 20, 30);
  
  #Encabezado:
  $worksheet->write(0, 0, "SOLO-TRUCKING INSURANCE COMPANY", $formato1);
  $worksheet->write(0, 6, 'Results of the On-line Report', $formato2);
  
  $worksheet->write(2, 0,'INSURANCES POLICIES REPORT', $formato_titulo);
  $worksheet->write_blank(2, 1, $formato_titulo);
  $worksheet->write_blank(2, 2, $formato_titulo);
  $worksheet->write_blank(2, 3, $formato_titulo);
  $worksheet->write_blank(2, 4, $formato_titulo);
  
  $str_condiciones = "From " . $fecha_inicio . " to " . $fecha_fin;
  $worksheet->write(3, 0, $str_condiciones, $formato_subtitulo);
  $worksheet->write_blank(3, 1, $formato_subtitulo);
  $worksheet->write_blank(3, 2, $formato_subtitulo);
  $worksheet->write_blank(3, 3, $formato_subtitulo);
  $worksheet->write_blank(3, 4, $formato_subtitulo);
  
  #Columnas
  $worksheet->write(5, 0, 'COMPANY NAME', $formato_tcolumna);
  $worksheet->write(5, 1, 'POLICY NUMBER', $formato_tcolumna);  
  $worksheet->write(5, 2, 'BROKER', $formato_tcolumna);
  $worksheet->write(5, 3, 'POLICY TYPE', $formato_tcolumna);
  $worksheet->write(5, 4, 'EXPIRATION DATE', $formato_tcolumna);
  
  $query = "SELECT sNombreCompania, sNumeroPoliza, C.sName,B.sDescripcion, DATE_FORMAT(P.dFechaCaducidad,'%m/%d/%Y') AS dFechaCaducidad ".
           "FROM ct_polizas P ".
           "LEFT JOIN ct_companias A ON P.iConsecutivoCompania = A.iConsecutivo ".
           "LEFT JOIN ct_tipo_poliza B ON P.iTipoPoliza = B.iConsecutivo ".
           "LEFT JOIN ct_brokers C ON P.iConsecutivoBrokers = C.iConsecutivo ".
           "$filtro_query ORDER BY sNombreCompania ASC";
  $result = $conexion->query($query);
  $rows = $result->num_rows; 
  if($rows > 0){
      $row = 6;
      while ($items = $result->fetch_assoc()){ 
         $worksheet->write($row, 0, $items['sNombreCompania']);
         $worksheet->write($row, 1, $items['sNumeroPoliza']);
         $worksheet->write($row, 2, $items['sName']);
         $worksheet->write($row, 3, $items['sDescripcion']);
         $worksheet->write($row, 4, $items['dFechaCaducidad']);
         $row++;  
      }
  }
  
  
  #EXCEL ENDS
  $workbook->close();
  header("Content-Type: application/x-msexcel; name=\"$nombre_archivo.xls\"");
  header("Content-Disposition: inline; filename=\"$nombre_archivo.xls\"");
  $fh = fopen($fname, "rb");
  fpassthru($fh);
  unlink($fname);
  
?>
