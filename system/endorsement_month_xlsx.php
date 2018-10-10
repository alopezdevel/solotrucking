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
    include("functiones_genericas.php");  

    #PARAMETERS:
    isset($_GET['idReport']) ? $iConsecutivoReporte = urldecode($_GET['idReport']) : $iConsecutivoReporte = ""; 
  
    /*$vFecha1 = substr($fecha_inicio,6,4).'-'.substr($fecha_inicio,0,2).'-'.substr($fecha_inicio,3,2); 
    $vFecha2 = substr($fecha_fin,6,4).'-'.substr($fecha_fin,0,2).'-'.substr($fecha_fin,3,2); */
  
    #GENERATE THE FILTERS BY PARAMETER:
    $filtro_query = "WHERE A.iConsecutivo = '$iConsecutivoReporte' ";

    #CONSULTA:
    $query  = "SELECT A.iConsecutivo, A.iConsecutivoCompania, B.sNombreCompania, A.iConsecutivoBroker, C.sName AS sNombreBroker, A.dFechaInicio, A.dFechaFin, A.iRatePercent, A.iTipoReporte ".
              "FROM cb_endoso_mensual  AS A ".
              "LEFT JOIN ct_companias  AS B ON A.iConsecutivoCompania = B.iConsecutivo ".
              "LEFT JOIN ct_brokers    AS C ON A.iConsecutivoBroker = C.iConsecutivo ".
              "$filtro_query";
    $result = $conexion->query($query);
    $rows   = $result->num_rows; 
    
    if($rows > 0){
        
        #DATOS GENERALES DEL REPORTE
        $DatosReporte = $result->fetch_assoc();
        
        if($DatosReporte['iTipoReporte'] == "1"){
           $flt_join = "LEFT  JOIN ct_unidades                AS U ON C.iConsecutivoUnidad = U.iConsecutivo ";
           $flt_join.= "LEFT  JOIN ct_unidad_modelo           AS M ON U.iModelo            = M.iConsecutivo ";
           $flt_field= "C.sVINUnidad, C.iConsecutivoUnidad, U.sVIN, U.iYear, M.sAlias, M.sDescripcion AS sMake, U.sTipo, C.iPDAmount AS iPDAmount "; 
           
        }else if($DatosReporte['iTipoReporte'] == "2"){
           $flt_join  = "LEFT JOIN ct_operadores AS U ON C.iConsecutivoOperador = U.iConsecutivo "; 
           $flt_field = "C.sNombreOperador, C.iConsecutivoOperador, U.sNombre, DATE_FORMAT(U.dFechaNacimiento,'%m/%d/%Y') AS dFechaNacimiento, U.iExperienciaYear, U.iNumLicencia, DATE_FORMAT(U.dFechaExpiracionLicencia,'%m/%d/%Y') AS dFechaExpiracionLicencia "; 
        }
        
        $sql    = "SELECT A.iConsecutivo AS iConsecutivoEndosoMensual,C.iConsecutivo, A.iConsecutivoCompania, IF(C.eAccion = 'A','ADD','DELETE') AS eAccion, ".
                  "C.iConsecutivo AS iConsecutivoEndoso, E.sNumeroPoliza, F.sDescripcion,DATE_FORMAT(C.dFechaAplicacion,'%m/%d/%Y') AS dFechaAplicacion, DATE_FORMAT(E.dFechaInicio,'%m/%d/%Y') AS inceptionDate, DATE_FORMAT(E.dFechaCaducidad,'%m/%d/%Y') AS expirationDate,".
                  "$flt_field ".
                  "FROM cb_endoso_mensual AS A ".
                  "INNER JOIN cb_endoso_mensual_relacion AS B ON A.iConsecutivo       = B.iConsecutivoEndosoMensual ".
                  "INNER JOIN cb_endoso                  AS C ON B.iConsecutivoEndoso = C.iConsecutivo AND A.iTipoReporte = C.iConsecutivoTipoEndoso  ".
                  "INNER JOIN cb_endoso_estatus          AS D ON C.iConsecutivo       = D.iConsecutivoEndoso AND D.eStatus = 'S' ".
                  "INNER JOIN ct_polizas                 AS E ON D.iConsecutivoPoliza = E.iConsecutivo AND E.iDeleted = '0' AND A.iConsecutivoBroker = E.iConsecutivoBrokers  ".
                  "INNER JOIN ct_tipo_poliza             AS F ON E.iTipoPoliza        = F.iConsecutivo  ".$flt_join.
                  "WHERE A.iConsecutivo='$iConsecutivoReporte' ORDER BY A.dFechaAplicacion DESC"; 
        $r      = $conexion->query($sql);
        $rows   = $r->num_rows; 
        
        if($rows > 0){
            #DATOS DEL DETALLE DEL REPORTE
            $DatosDetalle = mysql_fetch_all($r);
            $NoPolizas    = array();
            $TotalValuePD = 0;
            
            #EXCEL BEGINS:
            $objPHPExcel = new PHPExcel();  // Create new PHPExcel object
            
            //Set document properties
            $objPHPExcel->getProperties()->setCreator("Solo-Trucking Insurance System")->setLastModifiedBy("Solo-Trucking Insurance System")->setTitle("Solo-Trucking Insurance On-line Reports")->setKeywords("office 2007 openxml php")->setCategory("result file"); 
            
            #ESTILOS 
            $EstiloYellow = new PHPExcel_Style();
            $EstiloYellow->applyFromArray(array(
                'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID,'color' => array('rgb' => 'FFFF00')),
                'borders' => array('bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN),'color' => array('rgb' => '000000')),
                'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER),
                'font' => array('bold' => true,'underline' => PHPExcel_Style_Font::UNDERLINE_DOUBLE)
            ));
            
            $EstiloBorderRight = new PHPExcel_Style();
            $EstiloBorderRight->applyFromArray(array('borders' => array('right' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM,'color' => array('rgb' => '000000'))),));
            
            $EstiloBorderTop = new PHPExcel_Style();
            $EstiloBorderTop->applyFromArray(array('borders' => array('top' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM,'color' => array('rgb' => '000000'))),));
            
            $EstiloBorderbottom = new PHPExcel_Style();
            $EstiloBorderbottom->applyFromArray(array('borders' => array('bottom' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM,'color' => array('rgb' => '000000'))),));
        
            $EstiloEncabezado = new PHPExcel_Style();
            $EstiloEncabezado->applyFromArray(array(
                'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER),
                'font' => array('bold' => true)
            ));
            
            $EstiloContenido = new PHPExcel_Style();
            $EstiloContenido->applyFromArray(array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN,'color' => array('argb' => 'FFababab'))),'alignment' => array('vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER),));
            
            $EstiloAlignR = new PHPExcel_Style();
            $EstiloAlignR->applyFromArray(array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT)));
            #------------------------------INCEPTION---------------------------#
            // Agregar un Sheet nuevo
            $objPHPExcel->createSheet(1);
            $objPHPExcel->setActiveSheetIndex(1);
            $objPHPExcel->getActiveSheet()->setTitle("Inception");
            //Encabezados:
            $row = 1;
            $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado, "A".$row.":L".$row);
            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(25);
            //Definir encabezados:
            if($DatosReporte['iTipoReporte'] == "1"){
                $objPHPExcel->getActiveSheet()
                ->setCellValue('A'.$row, 'Year')
                ->setCellValue('B'.$row, 'Make')
                ->setCellValue('C'.$row, 'Model')
                ->setCellValue('D'.$row, 'VIN')
                ->setCellValue('E'.$row, 'Value')
                ->setCellValue('F'.$row, 'Type')
                ->setCellValue('G'.$row, 'Leinholder Name')
                ->setCellValue('H'.$row, 'Leinholder Address')
                ->setCellValue('I'.$row, 'GVW')
                ->setCellValue('J'.$row, 'Additional Vehicle Detail')
                ->setCellValue('K'.$row, 'Garaging Location')
                ->setCellValue('L'.$row, 'Garaging State');
  
            }
            else if($DatosReporte['iTipoReporte'] == "2"){
                $objPHPExcel->getActiveSheet()
                ->setCellValue('A'.$row, 'Name')
                ->setCellValue('B'.$row, 'DOB')
                ->setCellValue('C'.$row, 'License Number')
                ->setCellValue('D'.$row, 'License Expiration Date')
                ->setCellValue('E'.$row, 'Experience Years');
            } 
            
            foreach($DatosDetalle as $i => $l){
                
                $row++;
                $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloContenido, "A".$row.":L".$row); 
                $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(20);
                if($DatosReporte['iTipoReporte'] == "1"){ 
                    
                    //Agregar formato numerico a columna de value:
                    $objPHPExcel->getActiveSheet()->getStyle('E'.$row)->getNumberFormat()->setFormatCode("_(\"$\"* #,##0.00_);_(\"$\"* \(#,##0.00\);_(\"$\"* \"-\"??_);_(@_)");
                    
                    //Reporte contenido:
                    $objPHPExcel->getActiveSheet()
                    ->setCellValue('A'.$row, $DatosDetalle[$i]['iYear'])
                    ->setCellValue('B'.$row, $DatosDetalle[$i]['sMake'])
                    ->setCellValue('C'.$row, '')
                    ->setCellValue('D'.$row, $DatosDetalle[$i]['sVIN'])
                    ->setCellValue('E'.$row, $DatosDetalle[$i]['iPDAmount'])
                    ->setCellValue('F'.$row, ucfirst(strtolower($DatosDetalle[$i]['sTipo'])));
                    
                    if(!(in_array($DatosDetalle[$i]['sNumeroPoliza']."|".$DatosDetalle[$i]['inceptionDate']."|".$DatosDetalle[$i]['expirationDate'],$NoPolizas))){
                       array_push($NoPolizas,$DatosDetalle[$i]['sNumeroPoliza']."|".$DatosDetalle[$i]['inceptionDate']."|".$DatosDetalle[$i]['expirationDate']); 
                    }
                    
                    $TotalValuePD += $DatosDetalle[$i]['iPDAmount'];
                    
                }else if($DatosReporte['iTipoReporte'] == "2"){
                    $objPHPExcel->getActiveSheet()
                    ->setCellValue('A'.$row, $DatosDetalle[$i]['sNombre'])
                    ->setCellValue('B'.$row, $DatosDetalle[$i]['dFechaNacimiento'])
                    ->setCellValue('C'.$row, $DatosDetalle[$i]['iNumLicencia'])
                    ->setCellValue('D'.$row, $DatosDetalle[$i]['dFechaExpiracionLicencia'])
                    ->setCellValue('E'.$row, $DatosDetalle[$i]['iExperienciaYear']);
                }   
                
            }
             
            //Ajustar la dimension de las columnas:
            foreach(range('A','G') as $columnID) {
                $objPHPExcel->getActiveSheet()->getColumnDimension($columnID)->setAutoSize(true);
            }
             
            
            #------------------------------SYNOPSIS---------------------------#
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->getActiveSheet()->setTitle("Synopsis");
            $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloBorderTop, "A1:C1");
            $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloBorderRight, "C1:C29"); 
            $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloBorderbottom, "A29:C29"); 
            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth('20');
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth('30');
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth('25');
            
            //Insured:
            $objPHPExcel->getActiveSheet()->setCellValue('A1', 'Insured'); 
            $objPHPExcel->getActiveSheet()->setCellValue('B1', $DatosReporte['sNombreCompania']);
            
            //Policy #
            $count = count($NoPolizas[0]);
            $poliza= "";
            $inDate= "";
            $exDate= "";
            for($x = 0; $x < $count; $x++){
                $valorp = explode("|",$NoPolizas[$x]);
                $poliza == "" ? $poliza = $valorp[0] : $poliza .= ",".$valorp[0];
                $inDate == "" ? $inDate = $valorp[1] : $inDate .= ",".$valorp[1];
                $exDate == "" ? $exDate = $valorp[2] : $exDate .= ",".$valorp[2];
            }
            
            $objPHPExcel->getActiveSheet()->setCellValue('A2', 'Policy #/UMR'); 
            $objPHPExcel->getActiveSheet()->setCellValue('B2', $poliza);
            
            //Inception Date:
            $objPHPExcel->getActiveSheet()->setCellValue('A3', 'Inception Date'); 
            $objPHPExcel->getActiveSheet()->setCellValue('B3', $inDate);
            
            //Expiration Date:
            $objPHPExcel->getActiveSheet()->setCellValue('A4', 'Inception Date'); 
            $objPHPExcel->getActiveSheet()->setCellValue('B4', $exDate);
            
            //Rate:
            $iRatePercent = number_format($DatosReporte['iRatePercent'],2,'.','')."%";
            $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignR, "B5");
            //$objPHPExcel->getActiveSheet()->getStyle('B5')->getNumberFormat()->setFormatCode('0.00%');
            $objPHPExcel->getActiveSheet()->getStyle('B5')->getNumberFormat()->applyFromArray(array('code'=>PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE));
            $objPHPExcel->getActiveSheet()->setCellValue('A5', 'Rate'); 
            $objPHPExcel->getActiveSheet()->setCellValue('B5', $iRatePercent);
            
            //Current Values:
            $TotalValuePD = number_format($TotalValuePD,2,'.','')."%";
            $objPHPExcel->getActiveSheet()->getStyle('B7')->getNumberFormat()->setFormatCode("_(\"$\"* #,##0.00_);_(\"$\"* \(#,##0.00\);_(\"$\"* \"-\"??_);_(@_)");
            $objPHPExcel->getActiveSheet()->setCellValue('A7', 'Current Total Values**'); 
            $objPHPExcel->getActiveSheet()->setCellValue('B7', $TotalValuePD);
            
            //Set number style to all Column C
            $objPHPExcel->getActiveSheet()->getStyle('C10')->getNumberFormat()->setFormatCode("_(\"$\"* #,##0.00_);_(\"$\"* \(#,##0.00\);_(\"$\"* \"-\"??_);_(@_)");
            $objPHPExcel->getActiveSheet()->getStyle('C11')->getNumberFormat()->setFormatCode("_(\"$\"* #,##0.00_);_(\"$\"* \(#,##0.00\);_(\"$\"* \"-\"??_);_(@_)");
            $objPHPExcel->getActiveSheet()->getStyle('C12')->getNumberFormat()->setFormatCode("_(\"$\"* #,##0.00_);_(\"$\"* \(#,##0.00\);_(\"$\"* \"-\"??_);_(@_)");
            $objPHPExcel->getActiveSheet()->getStyle('C13')->getNumberFormat()->setFormatCode("_(\"$\"* #,##0.00_);_(\"$\"* \(#,##0.00\);_(\"$\"* \"-\"??_);_(@_)");
            $objPHPExcel->getActiveSheet()->getStyle('C14')->getNumberFormat()->setFormatCode("_(\"$\"* #,##0.00_);_(\"$\"* \(#,##0.00\);_(\"$\"* \"-\"??_);_(@_)");
            $objPHPExcel->getActiveSheet()->getStyle('C15')->getNumberFormat()->setFormatCode("_(\"$\"* #,##0.00_);_(\"$\"* \(#,##0.00\);_(\"$\"* \"-\"??_);_(@_)");
            $objPHPExcel->getActiveSheet()->getStyle('C16')->getNumberFormat()->setFormatCode("_(\"$\"* #,##0.00_);_(\"$\"* \(#,##0.00\);_(\"$\"* \"-\"??_);_(@_)");
            $objPHPExcel->getActiveSheet()->getStyle('C17')->getNumberFormat()->setFormatCode("_(\"$\"* #,##0.00_);_(\"$\"* \(#,##0.00\);_(\"$\"* \"-\"??_);_(@_)");
            $objPHPExcel->getActiveSheet()->getStyle('C18')->getNumberFormat()->setFormatCode("_(\"$\"* #,##0.00_);_(\"$\"* \(#,##0.00\);_(\"$\"* \"-\"??_);_(@_)");
            $objPHPExcel->getActiveSheet()->getStyle('C19')->getNumberFormat()->setFormatCode("_(\"$\"* #,##0.00_);_(\"$\"* \(#,##0.00\);_(\"$\"* \"-\"??_);_(@_)");
            $objPHPExcel->getActiveSheet()->getStyle('C20')->getNumberFormat()->setFormatCode("_(\"$\"* #,##0.00_);_(\"$\"* \(#,##0.00\);_(\"$\"* \"-\"??_);_(@_)");
            $objPHPExcel->getActiveSheet()->getStyle('C21')->getNumberFormat()->setFormatCode("_(\"$\"* #,##0.00_);_(\"$\"* \(#,##0.00\);_(\"$\"* \"-\"??_);_(@_)");
            $objPHPExcel->getActiveSheet()->getStyle('C22')->getNumberFormat()->setFormatCode("_(\"$\"* #,##0.00_);_(\"$\"* \(#,##0.00\);_(\"$\"* \"-\"??_);_(@_)");
            
            $objPHPExcel->getActiveSheet()->setCellValue('A9', 'TAB'); 
            $objPHPExcel->getActiveSheet()->setCellValue('B9', 'Month');
            $objPHPExcel->getActiveSheet()->setCellValue('C9', 'Value Changes');
            $objPHPExcel->getActiveSheet()->setCellValue('A10', 'INCEPTION'); 
            $objPHPExcel->getActiveSheet()->setCellValue('B10', 'INCEPTION');
            $objPHPExcel->getActiveSheet()->setCellValue('C10', $TotalValuePD);
            
            $objPHPExcel->getActiveSheet()->setCellValue('A11', '1'); 
            $objPHPExcel->getActiveSheet()->setCellValue('A12', '2'); 
            $objPHPExcel->getActiveSheet()->setCellValue('A13', '3'); 
            $objPHPExcel->getActiveSheet()->setCellValue('A14', '4'); 
            $objPHPExcel->getActiveSheet()->setCellValue('A15', '5'); 
            $objPHPExcel->getActiveSheet()->setCellValue('A16', '6'); 
            $objPHPExcel->getActiveSheet()->setCellValue('A17', '7'); 
            $objPHPExcel->getActiveSheet()->setCellValue('A18', '8'); 
            $objPHPExcel->getActiveSheet()->setCellValue('A19', '9');
            $objPHPExcel->getActiveSheet()->setCellValue('A20', '10'); 
            $objPHPExcel->getActiveSheet()->setCellValue('A21', '11'); 
            $objPHPExcel->getActiveSheet()->setCellValue('A22', '12'); 
            
            //Calcular mes de fecha inicial:
            if($count = count($NoPolizas[0]) == 1){
                $fechaP = explode("/",$inDate);
                $fechaP = strtotime($fechaP[2]."-".$fechaP[0]."-".$fechaP[1]);
                $fechaP = getdate($fechaP);
                $mes    = $fechaP['mon'];
                $meses  = array(1=>"Juanary",2=>"February",3=>"March",4=>"April",5=>"May",6=>"June",7=>"July",8=>"August",9=>"September",10=>"October",11=>"November",12=>"December");
                $array  = array();
                
                $mes > 1 ? $mesRes = 12 - $mes : $mesRes = 0;
                for($m=$mes;$m < 12;$m++){array_push($array,$meses[$m]);}
                if($mesRes > 0){for($m=1;$m <= $mesRes;$m++){array_push($array,$meses[$m]);}} 
                
                $objPHPExcel->getActiveSheet()->setCellValue('B11', $array[0]);
                $objPHPExcel->getActiveSheet()->setCellValue('B12', $array[1]);
                $objPHPExcel->getActiveSheet()->setCellValue('B13', $array[2]);
                $objPHPExcel->getActiveSheet()->setCellValue('B14', $array[3]);
                $objPHPExcel->getActiveSheet()->setCellValue('B15', $array[4]);
                $objPHPExcel->getActiveSheet()->setCellValue('B16', $array[5]);
                $objPHPExcel->getActiveSheet()->setCellValue('B17', $array[6]);
                $objPHPExcel->getActiveSheet()->setCellValue('B18', $array[7]);
                $objPHPExcel->getActiveSheet()->setCellValue('B19', $array[8]);
                $objPHPExcel->getActiveSheet()->setCellValue('B20', $array[9]); 
                $objPHPExcel->getActiveSheet()->setCellValue('B21', $array[10]);
                $objPHPExcel->getActiveSheet()->setCellValue('B22', $array[11]); 
                $row = 24;
            }else{$row = 12;}
            
            //Linea division Amarilla:
            $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloYellow, "A24:C24");
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A24', 'NOTES AND WARRANTIES');
            $objPHPExcel->setActiveSheetIndex(0)->mergeCells("A24:C24");
            $objPHPExcel->getActiveSheet()->getRowDimension('24')->setRowHeight(15);
            
            
            #CREATE A FILE NAME:
            if($DatosReporte['iTipoReporte'] == "1"){$titleName = "Equipment List - ";}elseif($DatosReporte['iTipoReporte'] == "1"){$titleName = "Operator List - ";}
            
            $nombre_archivo = $titleName.'with Montly Reporting Tabs_'.$DatosReporte['sNombreCompania']."_".$DatosReporte['dFechaInicio']."_".$DatosReporte['dFechaFin'];
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
        exit;
        
        
        
        
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
        
    }else{
       echo '<script language="javascript">alert(\'There were no results in your query, please try again..\')</script>';
       echo "<script language='javascript'>window.close();</script>"; 
    }  
    
?>
