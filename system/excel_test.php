<?php
  //composer require phpoffice/phpspreadsheet
  //require_once ($libpath . 'lib/phpoffice_phpspreadsheet_1.4/vendor/autoload.php');
  
  /*use PhpOffice\PhpSpreadsheet\Spreadsheet;
  use PhpOffice\PhpSpreadsheet\Writer\Xls;   */
  
  //$spreadsheet = new Spreadsheet();  /*----Spreadsheet object-----*/
  //$Excel_writer = new Xls($spreadsheet);  /*----- Excel (Xls) Object*/
  
  /*$spreadsheet->setActiveSheetIndex(0);
  $activeSheet = $spreadsheet->getActiveSheet();
  $activeSheet->setCellValue('A1' , 'New file content')->getStyle('A1')->getFont()->setBold(true);
  
  header('Content-Type: application/vnd.ms-excel');  */
  //header('Content-Disposition: attachment;filename="'. $filename .'.xls"'); /*-- $filename is  xsl filename ---*/
  //header('Cache-Control: max-age=0');
     
  //$Excel_writer->save('php://output');
  echo 'Current PHP version: ' . phpversion();
?>
