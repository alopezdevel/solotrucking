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
    $query  = "SELECT sNombreCompania, sNumeroPoliza, C.sName AS sBrokerName,B.sDescripcion AS sTipoPoliza, INS.sName AS sInsuranceCo, DATE_FORMAT(P.dFechaCaducidad,'%m/%d/%Y') AS dFechaCaducidad ".
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
            'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID,'color' => array('argb' => 'FFCCFFCC')),
            'borders' => array('bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN),'right' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM))
        ));
        
        
        
        //Encabezado del reporte.
        $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado, "A1:F1");
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C1', 'SOLO-TRUCKING INSURANCE COMPANY');
        //$objPHPExcel->setActiveSheetIndex(0)->setCellValue('C1', 'SOLO-TRUCKING INSURANCE COMPANY');
        
        //Columnas:
        $row = 4;
        $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A'.$row, 'COMPANY NAME')
                ->setCellValue('B'.$row, 'POLICY NUMBER')
                ->setCellValue('C'.$row, 'POLICY TYPE')
                ->setCellValue('D'.$row, 'EXPIRATION DATE')
                ->setCellValue('E'.$row, 'BROKER')
                ->setCellValue('F'.$row, 'INSURANCE');   
              
        while ($items = $result->fetch_assoc()){ 
             
             $row++; 
             //Reporte contenido:
             $objPHPExcel->setActiveSheetIndex(0)
                         ->setCellValue('A'.$row, $items['sNombreCompania'])
                         ->setCellValue('B'.$row, $items['sNumeroPoliza'])
                         ->setCellValue('C'.$row, $items['sTipoPoliza'])
                         ->setCellValue('D'.$row, $items['dFechaCaducidad'])
                         ->setCellValue('E'.$row, $items['sBrokerName'])
                         ->setCellValue('F'.$row, $items['sInsuranceCo']); 
              
        }
                  
        // Rename worksheet
        $objPHPExcel->getActiveSheet()->setTitle('Policies');
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $objPHPExcel->setActiveSheetIndex(0);
        
        #CREATE A FILE NAME:
        /*$info_fecha = getdate();
        $nombre_archivo = 'Report_of_Policies'.$info_fecha['year']."_".$info_fecha['month']."_".$info_fecha['mday'].$info_fecha['hours'].$info_fecha['minutes'].$info_fecha['seconds'];
        $nombre_archivo = $nombre_archivo.".xlsx"; */
        
        // Redirect output to a client’s web browser (Excel2007)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="01simple.xlsx"');
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
