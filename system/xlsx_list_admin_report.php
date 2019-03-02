<?php
    /**
     * PHPExcel
     *
     * Copyright (c) 2006 - 2015 PHPExcel
     *
     * This library is free software; you can redistribute it and/or
     * modify it under the terms of the GNU Lesser General Public
     * License as published by the Free Software Foundation; either
     * version 2.1 of the License, or (at your option) any later version.
     *
     * This library is distributed in the hope that it will be useful,
     * but WITHOUT ANY WARRANTY; without even the implied warranty of
     * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
     * Lesser General Public License for more details.
     *
     * You should have received a copy of the GNU Lesser General Public
     * License along with this library; if not, write to the Free Software
     * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
     *
     * @category   PHPExcel
     * @package    PHPExcel
     * @copyright  Copyright (c) 2006 - 2015 PHPExcel (http://www.codeplex.com/PHPExcel)
     * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt    LGPL
     * @version    ##VERSION##, ##DATE##
    */
    
    /** Error reporting */
    error_reporting(E_ALL);
    ini_set('display_errors', TRUE);
    ini_set('display_startup_errors', TRUE);
    //date_default_timezone_set('Europe/London');
    
    if (PHP_SAPI == 'cli')
    die('This example should only be run from a Web Browser');
    
    session_start();   
    include("cn_usuarios.php");  
    include("functiones_genericas.php"); 

    /** Include PHPExcel */
    require_once dirname(__FILE__) . '/lib/PHPExcel-1.8/Classes/PHPExcel.php';

    $error = 0;
    #PARAMETERS:
    isset($_GET['company'])    ? $iConsecutivoCompania   = urldecode($_GET['company'])   : $iConsecutivoCompania = ""; 
    isset($_GET['reportType']) ? $reporttype             = urldecode($_GET['reportType']): $reporttype = "";
    
    #OBTENER DATOS DE LA COMPANIA:
    $query  = "SELECT * FROM ct_companias WHERE iConsecutivo='$iConsecutivoCompania'";
    $result = $conexion->query($query);
    $DatosCo= $result->fetch_assoc();
    
    $objPHPExcel = new PHPExcel();  // Create new PHPExcel object
    
    switch($reporttype){
        case 'D' : $title = "Drivers"; break;
        case 'U' : $title = "Vehicles"; break;
        case 'B' : $title = "Drivers/Vehicles"; break;
    }
            
    // Set document properties
    $objPHPExcel->getProperties()->setCreator("Solo-Trucking Insurance System")->setLastModifiedBy("Solo-Trucking Insurance System")
    ->setTitle("Solo-Trucking Insurance On-line Reports")->setSubject("Endorsement list for ".$title)
    ->setDescription("Report of endorsement list of ".$title." of a company.")
    ->setKeywords("office 2007 openxml php")
    ->setCategory("result file"); 
    
    #ESTILOS 
    $EstiloEncabezado  = new PHPExcel_Style();
    $EstiloEncabezado2 = new PHPExcel_Style();
    $EstiloEncabezado3 = new PHPExcel_Style();
    $EstiloEncabezado22= new PHPExcel_Style(); 
    $EstiloContenido   = new PHPExcel_Style();
    $EstiloEncabezado->applyFromArray(array(
        'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID,'color' => array('argb' => 'FFF2F2F2')),
        //'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN),'color' => array('argb' => 'FFB8B8B8')),
        'borders'   => array('right' => array('style' => PHPExcel_Style_Border::BORDER_THIN,'color' => array('argb' => 'FFB8B8B8'))),
        'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER),
        'font'      => array('bold' => true,'size'=> 14,)
    ));
    $EstiloEncabezado2->applyFromArray(array(
        'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID,'color' => array('argb' => 'FFF2F2F2')),
        'borders' => array(
            'left' => array('style' => PHPExcel_Style_Border::BORDER_THIN,'color' => array('argb' => 'FFB8B8B8')),
            //'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN,'color' => array('argb' => 'FFB8B8B8')),
        ),
        'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER),
        'font' => array('color' => array('argb' => 'FF515151'),'size'=> 11,)
    ));
    $EstiloEncabezado22->applyFromArray(array(
        'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID,'color' => array('argb' => 'FFF2F2F2')),
        'borders' => array(
            'right' => array('style' => PHPExcel_Style_Border::BORDER_THIN,'color' => array('argb' => 'FFB8B8B8')),
            //'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN,'color' => array('argb' => 'FFB8B8B8')),
        ),
        'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER),
        'font' => array('color' => array('argb' => 'FF515151'),'size'=> 11,)
    ));
    $EstiloEncabezado3->applyFromArray(array(
        'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID,'color' => array('argb' => 'FF8FB1DB')),
        'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN,'color' => array('argb' => 'FF6489b5'))),
        'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER),
        'font' => array('bold' => true)
    ));
    $EstiloContenido->applyFromArray(array(
        'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN,'color' => array('argb' => 'FFababab'))),
        'alignment' => array('vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER),
    ));
    
    $EstiloAlignR = new PHPExcel_Style();
    $EstiloAlignR->applyFromArray(array(
        'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT),
        'borders'   => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN,'color' => array('argb' => 'FFababab'))),
    ));
    $EstiloAlignC = new PHPExcel_Style();
    $EstiloAlignC->applyFromArray(array(
        'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER),
        'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN,'color' => array('argb' => 'FFababab'))),
    ));
    $EstiloAlignL = new PHPExcel_Style();
    $EstiloAlignL->applyFromArray(array(
        'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT),
        'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN,'color' => array('argb' => 'FFababab'))),
    ));
            
    
    #GET DRIVERS SHEET:
    if($reporttype == 'B' || $reporttype == 'D'){
        $query  = "SELECT iConsecutivo, sNombre, DATE_FORMAT(dFechaNacimiento,'%m/%d/%Y') AS dFechaNacimiento, DATE_FORMAT(dFechaExpiracionLicencia,'%m/%d/%Y') AS dFechaExpiracionLicencia, iExperienciaYear, iNumLicencia, (CASE eTipoLicencia WHEN  'Federal/B1' THEN 'Federal / B1' WHEN  'Commercial/CDL-A' THEN 'Commercial / CDL - A' END) AS TipoLicencia,eModoIngreso ".
                  "FROM ct_operadores ".
                  "WHERE iConsecutivoCompania = '$iConsecutivoCompania' AND iDeleted='0' ORDER BY sNombre ASC";
        $result = $conexion->query($query);
        $rows   = $result->num_rows;
        if($rows <= 0){ $error = 1; $mensaje = "The data of drivers was not found, please try again later.";}
        else{
            // Rename worksheet
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->getActiveSheet()->setTitle('Drivers Report');
            
            $items = mysql_fetch_all($result);
            
            //Encabezado del reporte.
            $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado, "A1:L1");
            $objPHPExcel->getActiveSheet()->setCellValue('A1', strtoupper($DatosCo['sNombreCompania']).' - DRIVERS REPORT');
            $objPHPExcel->getActiveSheet()->mergeCells("A1:L1");
            $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(40);
            
            //Subtitulo del Reporte:
            $descripcionReporte = "";   
            $descripcionReporte2= "On-line Report from: ".date("m/d/Y g:i a"); 
            $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado2, "A2:F2");
            $objPHPExcel->getActiveSheet()->setCellValue('A2', $descripcionReporte);
            $objPHPExcel->getActiveSheet()->mergeCells("A2:F2");
            $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado22, "G2:L2"); 
            $objPHPExcel->getActiveSheet()->setCellValue('G2', $descripcionReporte2);
            $objPHPExcel->getActiveSheet()->mergeCells("G2:L2");
            $objPHPExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(25); 
            
            //Columnas:
            $row = 3;
            $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado3, "A".$row.":L".$row);
            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(35);
            $objPHPExcel->getActiveSheet()
                    ->setCellValue('A'.$row, 'NAME')
                    ->setCellValue('B'.$row, 'DOB')
                    ->setCellValue('C'.$row, 'LICENSE #')
                    ->setCellValue('D'.$row, 'EXPIRE DATE')
                    ->setCellValue('E'.$row, 'ACTION')
                    ->setCellValue('F'.$row, 'DATE')
                    ->setCellValue('G'.$row, 'END AL#')
                    ->setCellValue('H'.$row, 'AL')
                    ->setCellValue('I'.$row, 'END MTC#')
                    ->setCellValue('J'.$row, 'CARGO')
                    ->setCellValue('K'.$row, 'END PD#')
                    ->setCellValue('L'.$row, 'PD');
                    
            $countD = count($items);
            $No     = 0;
            
            for($d=0;$d<$countD;$d++){
                         
                 $row++;
                 $No++;
                 $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloContenido, "A".$row.":L".$row); 
                 $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(20);
                 
                 $action  = "";
                 $dateApp = ""; 
                 $endALNo = "";
                 $endAL   = "";
                 $endMTCNo= "";
                 $endMTC  = "";
                 $endPDNo = "";
                 $endPD   = "";
                 
                 //Revisar polizas:
                 $query  = "SELECT iConsecutivoPoliza, B.sNumeroPoliza, C.sDescripcion AS sTipoPoliza, C.sAlias, DATE_FORMAT(A.dFechaIngreso,'%m/%d/%Y') AS dFechaIngreso,eModoIngreso ".
                           "FROM cb_poliza_operador   AS A ".
                           "INNER JOIN ct_polizas     AS B ON A.iConsecutivoPoliza = B.iConsecutivo AND B.iDeleted = '0' AND B.dFechaCaducidad >= CURDATE() ".
                           "LEFT JOIN  ct_tipo_poliza AS C ON B.iTipoPoliza = C.iConsecutivo ".
                           "WHERE A.iConsecutivoOperador = '".$items[$d]['iConsecutivo']."' AND A.iDeleted = '0' ORDER BY dFechaIngreso DESC";
                 $r      = $conexion->query($query) or die($conexion->error);
                 $total  = $r->num_rows;
                 $PDApply= false; 
                 
                 if($total > 0){
                    while ($rowD = $r->fetch_assoc()){
                        //REVISAMO MODO DE INGRESO:
                        if($rowD['eModoIngreso'] != 'ENDORSEMENT'){
                            
                            $dateApp = $rowD['eModoIngreso']; //DATE OF APPLICATION
                            
                            switch($rowD['sAlias']){
                                case 'AL'  : $endAL  = "BIND"; break;
                                case 'PD'  : $endPD  = "BIND"; break;
                                case 'MTC' : $endMTC = "BIND"; break;
                            }
                        }
                        else{
                            
                           $dateApp= $rowD['dFechaIngreso']; 
                           $query  = "SELECT C.iConsecutivoEndoso, C.sNumeroEndosoBroker, C.rImporteEndosoBroker, IF (A.iEndosoMultiple = '1', B.eAccion, IF(A.eAccion = 'A', 'ADD','DELETE') ) AS eAccion ".
                                     "FROM cb_endoso AS A ".
                                     "INNER JOIN cb_endoso_estatus  AS C ON A.iConsecutivo = C.iConsecutivoEndoso ".
                                     "LEFT  JOIN cb_endoso_operador AS B ON A.iConsecutivo = B.iConsecutivoEndoso ".
                                     "WHERE A.iDeleted = '0' AND C.iConsecutivoPoliza = '".$rowD['iConsecutivoPoliza']."' ".
                                     "AND IF(A.iEndosoMultiple = '0', A.iConsecutivoOperador = '".$items[$d]['iConsecutivo']."', B.iConsecutivoOperador = '".$items[$d]['iConsecutivo']."') ".
                                     "ORDER BY C.iConsecutivoEndoso DESC LIMIT 1";
                           $r2     = $conexion->query($query) or die($conexion->error);
                           $endo   = $r2->fetch_assoc();
                           switch($rowD['sAlias']){
                                case 'AL'  : 
                                    $endALNo = $endo['sNumeroEndosoBroker']; 
                                    $endAL   = $endo['rImporteEndosoBroker'] > 0 ? "\$ ".number_format($endo['rImporteEndosoBroker'],2,'.', ',') : ""; 
                                    $action  = $endo['eAccion']; 
                                break;
                                case 'PD'  : 
                                    $endPDNo = $endo['sNumeroEndosoBroker']; 
                                    $endPD   = $endo['rImporteEndosoBroker'] > 0 ? "\$ ".number_format($endo['rImporteEndosoBroker'],2,'.', ',') : "";
                                    $action  = $endo['eAccion']; 
                                break;
                                case 'MTC' : 
                                    $endMTCNo= $endo['sNumeroEndosoBroker']; 
                                    $endMTC  = $endo['rImporteEndosoBroker'] > 0 ? "\$ ".number_format($endo['rImporteEndosoBroker'],2,'.', ',') : ""; 
                                    $action  = $endo['eAccion']; 
                                break;
                           }
                        } 
                        
                        if($rowD['sAlias'] == "PD"){$PDApply = true;}
                    }
                 }
                 
                 

                 //Reporte contenido:
                 $objPHPExcel->getActiveSheet()
                             ->setCellValue('A'.$row, utf8_decode($items[$d]['sNombre']))    
                             ->setCellValue('B'.$row, $items[$d]['dFechaNacimiento']) 
                             ->setCellValue('C'.$row, $items[$d]['iNumLicencia'])
                             ->setCellValue('D'.$row, $items[$d]['dFechaExpiracionLicencia'])
                             ->setCellValue('E'.$row, $action)
                             ->setCellValue('F'.$row, $dateApp)
                             ->setCellValue('G'.$row, $endALNo)
                             ->setCellValue('H'.$row, $endAL)
                             ->setCellValue('I'.$row, $endMTCNo)
                             ->setCellValue('J'.$row, $endMTC)
                             ->setCellValue('K'.$row, $endPDNo)
                             ->setCellValue('L'.$row, $endPD);
                             
                 // Aplicar formatos/estilos:
                 $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignL,'A'.$row);
                 $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'B'.$row);
                 $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignL,'C'.$row);
                 $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'D'.$row);
                 $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'F'.$row);
                 $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'G'.$row);
                 $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'H'.$row);
                 $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'I'.$row);
                 $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'J'.$row);
                 $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'K'.$row);
                 $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'L'.$row);
                 
                 
                 $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
                 $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth('17');
                 $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
                 $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth('17');
                 $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth('17');
                 $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth('17');
                 $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth('17');
                 $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth('17');
                 $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth('17');
                 $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth('17');
                 $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth('17');
                 $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth('17');
                 //$objPHPExcel->getActiveSheet()->getStyle('B'.$row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_YYYYMMDDSLASH);
                     
            }
        }      
    }
    
    #GET VEHICLES SHEET:
    if($reporttype == 'B' || $reporttype == 'U'){
        $query  = "SELECT A.iConsecutivo, C.sAlias AS Make, C.sDescripcion AS sMakeDescription, B.sDescripcion AS Radio, iYear, sVIN, sPeso, sTipo, sModelo, eModoIngreso, iTotalPremiumPD ".
                  "FROM ct_unidades A ".
                  "LEFT JOIN ct_unidad_radio B ON A.iConsecutivoRadio = B.iConsecutivo ".
                  "LEFT JOIN ct_unidad_modelo C ON A.iModelo = C.iConsecutivo ".
                  "WHERE iConsecutivoCompania = '$iConsecutivoCompania' AND iDeleted='0' ORDER BY sVIN ASC";
        $result = $conexion->query($query);
        $rows   = $result->num_rows;
        if($rows <= 0){ $error = 1; $mensaje = "The data of vehicles was not found, please try again later.";}  
        else{
            
            // Rename worksheet
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->getActiveSheet()->setTitle('Vehicles Report');
            
            $items = mysql_fetch_all($result);
            
            //Encabezado del reporte.
            $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado, "A1:L1");
            $objPHPExcel->getActiveSheet()->setCellValue('A1', strtoupper($DatosCo['sNombreCompania']).' - VEHICLES REPORT');
            $objPHPExcel->getActiveSheet()->mergeCells("A1:L1");
            $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(40);
            
            //Subtitulo del Reporte:
            $descripcionReporte = "";   
            $descripcionReporte2= "On-line Report from: ".date("m/d/Y g:i a"); 
            $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado2, "A2:F2");
            $objPHPExcel->getActiveSheet()->setCellValue('A2', $descripcionReporte);
            $objPHPExcel->getActiveSheet()->mergeCells("A2:F2");
            $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado22, "G2:L2"); 
            $objPHPExcel->getActiveSheet()->setCellValue('G2', $descripcionReporte2);
            $objPHPExcel->getActiveSheet()->mergeCells("G2:L2");
            $objPHPExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(25); 
            
            //Columnas:
            $row = 3;
            $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado3, "A".$row.":L".$row);
            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(35);
            $objPHPExcel->getActiveSheet()
                    ->setCellValue('A'.$row, 'YEAR')
                    ->setCellValue('B'.$row, 'MAKE')
                    ->setCellValue('C'.$row, 'VIN')
                    ->setCellValue('D'.$row, 'VALUE')
                    ->setCellValue('E'.$row, 'ACTION')
                    ->setCellValue('F'.$row, 'DATE')
                    ->setCellValue('G'.$row, 'END AL#')
                    ->setCellValue('H'.$row, 'AL')
                    ->setCellValue('I'.$row, 'END MTC#')
                    ->setCellValue('J'.$row, 'CARGO')
                    ->setCellValue('K'.$row, 'END PD#')
                    ->setCellValue('L'.$row, 'PD');
                    
            $countD = count($items);
            $No     = 0;   
            for($d=0;$d<$countD;$d++){
                         
                 $row++;
                 $No++;
                 $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloContenido, "A".$row.":L".$row); 
                 $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(20);
                 
                 $action  = "";
                 $dateApp = ""; 
                 $endALNo = "";
                 $endAL   = "";
                 $endMTCNo= "";
                 $endMTC  = "";
                 $endPDNo = "";
                 $endPD   = "";
                 
                 //Revisar polizas:
                 $query  = "SELECT iConsecutivoPoliza, B.sNumeroPoliza, C.sDescripcion AS sTipoPoliza, C.sAlias, DATE_FORMAT(A.dFechaIngreso,'%m/%d/%Y') AS dFechaIngreso,eModoIngreso ".
                          "FROM cb_poliza_unidad     AS A ".
                           "INNER JOIN ct_polizas     AS B ON A.iConsecutivoPoliza = B.iConsecutivo AND B.iDeleted = '0' AND B.dFechaCaducidad >= CURDATE() ".
                           "LEFT JOIN  ct_tipo_poliza AS C ON B.iTipoPoliza = C.iConsecutivo ".
                           "WHERE A.iConsecutivoUnidad = '".$items[$d]['iConsecutivo']."' AND A.iDeleted = '0' ORDER BY dFechaIngreso DESC";
                 $r      = $conexion->query($query);
                 $total  = $r->num_rows;
                 $PDApply= false;   
                 
                 if($total > 0){
                    while ($rowD = $r->fetch_assoc()){
                        //REVISAMO MODO DE INGRESO:
                        if($rowD['eModoIngreso'] != 'ENDORSEMENT'){
                            
                            $dateApp = $rowD['eModoIngreso']; //DATE OF APPLICATION
                            
                            switch($rowD['sAlias']){
                                case 'AL'  : $endAL  = "BIND"; break;
                                case 'PD'  : $endPD  = "BIND"; break;
                                case 'MTC' : $endMTC = "BIND"; break;
                            }
                        }
                        else{
                            
                           $dateApp = $rowD['dFechaIngreso']; 
                           $query = "SELECT C.iConsecutivoEndoso, C.sNumeroEndosoBroker, C.rImporteEndosoBroker, IF (A.iEndosoMultiple = '1', B.eAccion, IF(A.eAccion = 'A', 'ADD','DELETE') ) AS eAccion  ".
                                    "FROM       cb_endoso         AS A ".
                                    "INNER JOIN cb_endoso_estatus AS C ON A.iConsecutivo = C.iConsecutivoEndoso ".
                                    "LEFT  JOIN cb_endoso_unidad  AS B ON A.iConsecutivo = B.iConsecutivoEndoso ".
                                    "WHERE A.iDeleted = '0' AND C.iConsecutivoPoliza = '".$rowD['iConsecutivoPoliza']."' ".
                                    "AND IF(A.iEndosoMultiple = '0', A.iConsecutivoUnidad = '".$items[$d]['iConsecutivo']."', B.iConsecutivoUnidad = '".$items[$d]['iConsecutivo']."') ".
                                    "ORDER BY C.iConsecutivoEndoso DESC LIMIT 1";
                           $r2    = $conexion->query($query);
                           $endo  = $r2->fetch_assoc();
                           switch($rowD['sAlias']){
                                case 'AL'  : 
                                    $endALNo = $endo['sNumeroEndosoBroker']; 
                                    $endAL   = $endo['rImporteEndosoBroker'] > 0 ? "\$ ".number_format($endo['rImporteEndosoBroker'],2,'.', ',') : ""; 
                                    $action  = $endo['eAccion']; 
                                break;
                                case 'PD'  : 
                                    $endPDNo = $endo['sNumeroEndosoBroker']; 
                                    $endPD   = $endo['rImporteEndosoBroker'] > 0 ? "\$ ".number_format($endo['rImporteEndosoBroker'],2,'.', ',') : "";
                                    $action  = $endo['eAccion']; 
                                break;
                                case 'MTC' : 
                                    $endMTCNo= $endo['sNumeroEndosoBroker']; 
                                    $endMTC  = $endo['rImporteEndosoBroker'] > 0 ? "\$ ".number_format($endo['rImporteEndosoBroker'],2,'.', ',') : ""; 
                                    $action  = $endo['eAccion']; 
                                break;
                           }
                        } 
                        
                        if($rowD['sAlias'] == "PD"){$PDApply = true;}
                    }
                 }
                 
                 
                 $PDApply && $items[$d]['iTotalPremiumPD'] > 0 ? $value = "\$ ".number_format($items[$d]['iTotalPremiumPD'],2,'.',',') : $value = ""; 

                 //Reporte contenido:
                 $objPHPExcel->getActiveSheet()
                             ->setCellValue('A'.$row, utf8_decode($items[$d]['iYear']))    
                             ->setCellValue('B'.$row, $items[$d]['Make']) 
                             ->setCellValue('C'.$row, $items[$d]['sVIN'])
                             ->setCellValue('D'.$row, $value)
                             ->setCellValue('E'.$row, $action)
                             ->setCellValue('F'.$row, $dateApp)
                             ->setCellValue('G'.$row, $endALNo)
                             ->setCellValue('H'.$row, $endAL)
                             ->setCellValue('I'.$row, $endMTCNo)
                             ->setCellValue('J'.$row, $endMTC)
                             ->setCellValue('K'.$row, $endPDNo)
                             ->setCellValue('L'.$row, $endPD);
                             
                 // Aplicar formatos/estilos:
                 $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'A'.$row);
                 $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignL,'B'.$row);
                 $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignL,'C'.$row);
                 $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignR,'D'.$row);
                 $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'F'.$row);
                 $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'G'.$row);
                 $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'H'.$row);
                 $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'I'.$row);
                 $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'J'.$row);
                 $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'K'.$row);
                 $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'L'.$row);
                 
                 
                 $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
                 $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth('17');
                 $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
                 $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth('17');
                 $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth('17');
                 $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth('17');
                 $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth('17');
                 $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth('17');
                 $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth('17');
                 $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth('17');
                 $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth('17');
                 $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth('17');
                 //$objPHPExcel->getActiveSheet()->getStyle('B'.$row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_YYYYMMDDSLASH);
                     
            } 
        }  
    }
    
    #GENERAR ARCHIVO:
    if($error == 0){
        
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $objPHPExcel->setActiveSheetIndex(0);
             
        #CREATE A FILE NAME:
        $info_fecha     = getdate();
        $nombre_archivo = strtoupper($DatosCo['sNombreCompania'])."_".$info_fecha['year']."_".$info_fecha['month']."_".$info_fecha['mday'];
        $nombre_archivo = $nombre_archivo.".xlsx";
        
        // Redirect output to a client’s web browser (Excel2007)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$nombre_archivo.'"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

        // If you're serving to IE over SSL, then the following may be needed
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');    
    }
    
    if($error != 0){
        echo '<script language="javascript">alert(\''.$mensaje.'\')</script>';
        echo "<script language='javascript'>window.close();</script>"; 
    }
?>
