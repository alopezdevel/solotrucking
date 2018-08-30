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
    date_default_timezone_set('Europe/London');
    
    if (PHP_SAPI == 'cli')
    die('This example should only be run from a Web Browser');

    /** Include PHPExcel */
    require_once dirname(__FILE__) . '/lib/PHPExcel-1.8/Classes/PHPExcel.php';
    
    session_start();   
    include("cn_usuarios.php");  

    #PARAMETERS:
    isset($_GET['company'])   ? $iConsecutivoCompania   = urldecode($_GET['company'])   : $iConsecutivoCompania = ""; 
    isset($_GET['broker'])    ? $iConsecutivoBrokers    = urldecode($_GET['broker'])    : $iConsecutivoBrokers = "";
    isset($_GET['insurance']) ? $iConsecutivoAseguranza = urldecode($_GET['insurance']) : $iConsecutivoAseguranza = "";
    isset($_GET['politype'])  ? $iTipoPoliza            = urldecode($_GET['politype'])  : $iTipoPoliza = "";
    isset($_GET['dExp_init']) ? $fecha_inicio           = urldecode($_GET['dExp_init']) : $fecha_inicio = "";
    isset($_GET['dExp_endi']) ? $fecha_fin              = urldecode($_GET['dExp_endi']) : $fecha_fin = "";
  
    $vFecha1 = substr($fecha_inicio,6,4).'-'.substr($fecha_inicio,0,2).'-'.substr($fecha_inicio,3,2); 
    $vFecha2 = substr($fecha_fin,6,4).'-'.substr($fecha_fin,0,2).'-'.substr($fecha_fin,3,2);
  
    #GENERATE THE FILTERS BY PARAMETER:
    $filtro_query = "WHERE P.iDeleted = '0' AND A.iDeleted = '0' ";
    if($iConsecutivoCompania != "")  {$filtro_query .= "AND iConsecutivoCompania   = '$iConsecutivoCompania' ";}
    if($iConsecutivoBrokers  != "")  {$filtro_query .= "AND iConsecutivoBrokers    = '$iConsecutivoBrokers' ";} 
    if($iConsecutivoAseguranza != ""){$filtro_query .= "AND iConsecutivoAseguranza = '$iConsecutivoAseguranza' ";} 
    if($iTipoPoliza != ""){$filtro_query .= "AND iTipoPoliza = '$iTipoPoliza' ";} 
    $filtro_query .= "AND P.dFechaCaducidad BETWEEN '$vFecha1' AND '$vFecha2' "; 
    
    #CONSULTA:
    $query  = "SELECT sNombreCompania, sNumeroPoliza, C.sName AS sBrokerName,B.sDescripcion AS sTipoPoliza, INS.sName AS sInsuranceCo, ".
              "DATE_FORMAT(P.dFechaCaducidad,'%m/%d/%Y') AS dFechaCaducidad, DATE_FORMAT(P.dFechaInicio,'%m/%d/%Y') AS dFechaEfectiva  ".
              "FROM ct_polizas AS P ".
              "LEFT JOIN ct_companias   AS A   ON P.iConsecutivoCompania = A.iConsecutivo ".
              "LEFT JOIN ct_tipo_poliza AS B   ON P.iTipoPoliza = B.iConsecutivo ".
              "LEFT JOIN ct_brokers     AS C   ON P.iConsecutivoBrokers = C.iConsecutivo ".
              "LEFT JOIN ct_aseguranzas AS INS ON P.iConsecutivoAseguranza = INS.iConsecutivo ".
              "$filtro_query ORDER BY sNombreCompania ASC";
    $result = $conexion->query($query);
    $rows   = $result->num_rows; 
    
    if($rows > 0){
        
        #EXCEL BEGINS:
        $objPHPExcel = new PHPExcel();  // Create new PHPExcel object
        
        // Set document properties
        $objPHPExcel->getProperties()
        ->setCreator("Solo-Trucking Insurance System")
        ->setLastModifiedBy("Solo-Trucking Insurance System")
        ->setTitle("Solo-Trucking Insurance On-line Reports")
        ->setSubject("Policies")
        ->setDescription("Report of policies registered in the system that are about to expire.")
        ->setKeywords("office 2007 openxml php")
        ->setCategory("result file"); 
        
        #ESTILOS 
        $EstiloEncabezado = new PHPExcel_Style();
        $EstiloEncabezado->applyFromArray
        (array(
            'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID,'color' => array('argb' => 'FF538DD5')),
            'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN),'color' => array('argb' => 'FF215698')),
            'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER),
            'font' => array('bold' => true)
        ));
        $EstiloEncabezado2 = new PHPExcel_Style();
        $EstiloEncabezado2->applyFromArray
        (array(
            'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID,'color' => array('argb' => 'FFF2F2F2')),
            'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN,'color' => array('argb' => 'FFB8B8B8'))),
            'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER),
            'font' => array('color' => array('argb' => 'FF515151'),'size'=> 10,)
        ));
        
        $EstiloEncabezado3 = new PHPExcel_Style();
        $EstiloEncabezado3->applyFromArray
        (array(
            'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID,'color' => array('argb' => 'FF8FB1DB')),
            'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN,'color' => array('argb' => 'FF6489b5'))),
            'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER),
            'font' => array('bold' => true)
        ));
        
        $EstiloContenido = new PHPExcel_Style();
        $EstiloContenido->applyFromArray
        (array(
            'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN,'color' => array('argb' => 'FFababab'))),
            'alignment' => array('vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER),
        ));
        
        
        //Encabezado del reporte.
        $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado, "A1:G1");
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', 'SOLO-TRUCKING INSURANCE COMPANY');
        $objPHPExcel->setActiveSheetIndex(0)->mergeCells("A1:G1");
        $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(40);
        
        //Subtitulo del Reporte:
        $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado2, "A2:G2");
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A2', 'Results of the On-line Report: '.date('m/d/Y',strtotime($vFecha1))." - ".date('m/d/Y',strtotime($vFecha2))." ");
        $objPHPExcel->setActiveSheetIndex(0)->mergeCells("A2:G2");
        $objPHPExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(25);
        
        //Columnas:
        $row = 3;
        $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado3, "A".$row.":G".$row);
        $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(35);
        $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A'.$row, 'COMPANY NAME')
                ->setCellValue('B'.$row, 'POLICY NUMBER')
                ->setCellValue('C'.$row, 'POLICY TYPE')
                ->setCellValue('D'.$row, 'EFFECTIVE DATE')
                ->setCellValue('E'.$row, 'EXPIRATION DATE')
                ->setCellValue('F'.$row, 'BROKER')
                ->setCellValue('G'.$row, 'INSURANCE');   
              
        while ($items = $result->fetch_assoc()){ 
             
             $row++;
             $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloContenido, "A".$row.":G".$row); 
             $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(20);
             //Reporte contenido:
             $objPHPExcel->setActiveSheetIndex(0)
                         ->setCellValue('A'.$row, $items['sNombreCompania'])
                         ->setCellValue('B'.$row, $items['sNumeroPoliza'])
                         ->setCellValue('C'.$row, $items['sTipoPoliza'])
                         ->setCellValue('D'.$row, $items['dFechaEfectiva'])
                         ->setCellValue('E'.$row, $items['dFechaCaducidad'])
                         ->setCellValue('F'.$row, $items['sBrokerName'])
                         ->setCellValue('G'.$row, $items['sInsuranceCo']); 
              
        }
        
        //Ajustar la dimension de las columnas:
        foreach(range('A','G') as $columnID) {
            $objPHPExcel->getActiveSheet()->getColumnDimension($columnID)->setAutoSize(true);
        }
                  
        // Rename worksheet
        $objPHPExcel->getActiveSheet()->setTitle('Policies');
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $objPHPExcel->setActiveSheetIndex(0);
        
        #CREATE A FILE NAME:
        $info_fecha = getdate();
        $nombre_archivo = 'Report_of_Policies'.$info_fecha['year']."_".$info_fecha['month']."_".$info_fecha['mday'];
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
        //exit; 
        
    }else{
       echo '<script language="javascript">alert(\'There were no results in your query, please try again..\')</script>';
       echo "<script language='javascript'>window.close();</script>"; 
    }  
    
?>
