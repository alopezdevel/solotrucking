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
    $conexion->autocommit(FALSE);  

    #PARAMETERS:
    isset($_GET['idReport']) ? $iConsecutivoReporte = urldecode($_GET['idReport']) : $iConsecutivoReporte = ""; 
    isset($_GET['mail'])     ? $iEmail              = urldecode($_GET['mail'])     : $iEmail = "0"; 
  
    /*$vFecha1 = substr($fecha_inicio,6,4).'-'.substr($fecha_inicio,0,2).'-'.substr($fecha_inicio,3,2); 
    $vFecha2 = substr($fecha_fin,6,4).'-'.substr($fecha_fin,0,2).'-'.substr($fecha_fin,3,2); */
  
    #GENERATE THE FILTERS BY PARAMETER:
    $filtro_query = "WHERE A.iConsecutivo = '$iConsecutivoReporte' ";

    #CONSULTA:
    $query  = "SELECT A.iConsecutivo, A.iConsecutivoCompania, B.sNombreCompania, A.iConsecutivoBroker, C.sName AS sNombreBroker, A.dFechaInicio, A.dFechaFin, A.iRatePercent, A.iTipoReporte, A.sMensajeEmail, A.sEmail ".
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
           
        }
        else if($DatosReporte['iTipoReporte'] == "2"){
           $flt_join  = "LEFT JOIN ct_operadores AS U ON C.iConsecutivoOperador = U.iConsecutivo "; 
           $flt_field = "C.sNombreOperador, C.iConsecutivoOperador, U.sNombre, DATE_FORMAT(U.dFechaNacimiento,'%m/%d/%Y') AS dFechaNacimiento, U.iExperienciaYear, U.iNumLicencia, DATE_FORMAT(U.dFechaExpiracionLicencia,'%m/%d/%Y') AS dFechaExpiracionLicencia "; 
        }
        
        $sql    = "SELECT A.iConsecutivo AS iConsecutivoEndosoMensual,C.iConsecutivo, A.iConsecutivoCompania, IF(C.eAccion = 'A','ADD','DELETE') AS eAccion, ".
                  "C.iConsecutivo AS iConsecutivoEndoso, D.iConsecutivoPoliza, E.sNumeroPoliza, F.sDescripcion,F.sAlias,DATE_FORMAT(C.dFechaAplicacion,'%m/%d/%Y') AS dFechaAplicacion, ".
                  "DATE_FORMAT(E.dFechaInicio,'%m/%d/%Y') AS inceptionDate, DATE_FORMAT(E.dFechaCaducidad,'%m/%d/%Y') AS expirationDate, C.iEndosoMultiple, ".
                  "$flt_field ".
                  "FROM cb_endoso_mensual AS A ".
                  "INNER JOIN cb_endoso_mensual_relacion AS B ON A.iConsecutivo       = B.iConsecutivoEndosoMensual ".
                  "INNER JOIN cb_endoso                  AS C ON B.iConsecutivoEndoso = C.iConsecutivo AND A.iTipoReporte = C.iConsecutivoTipoEndoso  ".
                  "INNER JOIN cb_endoso_estatus          AS D ON C.iConsecutivo       = D.iConsecutivoEndoso ".//" AND D.eStatus = 'S' ".
                  "AND D.iConsecutivoPoliza = A.iConsecutivoPoliza ".
                  "INNER JOIN ct_polizas                 AS E ON D.iConsecutivoPoliza = E.iConsecutivo AND E.iDeleted = '0' AND A.iConsecutivoBroker = E.iConsecutivoBrokers  ".
                  "INNER JOIN ct_tipo_poliza             AS F ON E.iTipoPoliza        = F.iConsecutivo  ".$flt_join.
                  "WHERE A.iConsecutivo='$iConsecutivoReporte' ORDER BY A.dFechaAplicacion DESC"; 
        $r      = $conexion->query($sql);
        $rows   = $r->num_rows; 
        
        if($rows > 0){
            #DATOS DEL DETALLE DEL REPORTE
            $DatosDetalle = mysql_fetch_all($r);
            $NoPolizas    = array();
                    
            #EXCEL BEGINS:
            $objPHPExcel = new PHPExcel();  // Create new PHPExcel object
            
            //Set document properties
            $objPHPExcel->getProperties()->setCreator("Solo-Trucking Insurance System")->setLastModifiedBy("Solo-Trucking Insurance System")->setTitle("Solo-Trucking Insurance On-line Reports")->setKeywords("office 2007 openxml php")->setCategory("result file"); 
            
            #ESTILOS 
            $EstiloYellow = new PHPExcel_Style();
            $EstiloYellow->applyFromArray(array(
                'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID,'color' => array('rgb' => 'FFFF00')),
                'borders' => array('bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN),'color' => array('rgb' => '000000'),'right' => array('style' => PHPExcel_Style_Border::BORDER_THIN),'color' => array('rgb' => '000000')),
                'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER),
                'font' => array('bold' => true,'underline' => PHPExcel_Style_Font::UNDERLINE_DOUBLE)
            ));
            
            $EstiloBorderRight = new PHPExcel_Style();
            $EstiloBorderRight->applyFromArray(array('borders' => array('right' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM,'color' => array('rgb' => '000000'))),));
            
            $EstiloBorderTop = new PHPExcel_Style();
            $EstiloBorderTop->applyFromArray(array('borders' => array('top' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM,'color' => array('rgb' => '000000'))),));
            
            $EstiloBorderbottom = new PHPExcel_Style();
            $EstiloBorderbottom->applyFromArray(array('borders' => array('bottom' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM,'color' => array('rgb' => '000000'))),));
        
            $EstiloBorderTopRightB = new PHPExcel_Style();
            $EstiloBorderTopRightB->applyFromArray(array(
                'borders' => array('top' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM,'color' => array('rgb' => '000000')),'right' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM,'color' => array('rgb' => '000000')),'bottom' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM,'color' => array('rgb' => '000000'))),
                'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,'vertical' => PHPExcel_Style_Alignment::VERTICAL_TOP, 'wrap' => true),
            ));
            
            $EstiloBorderRightB = new PHPExcel_Style();
            $EstiloBorderRightB->applyFromArray(array(
                'borders' => array('right' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM,'color' => array('rgb' => '000000')),'bottom' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM,'color' => array('rgb' => '000000'))),
                'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,'vertical' => PHPExcel_Style_Alignment::VERTICAL_TOP, 'wrap' => true),
            ));
            
            
            $EstiloEncabezado = new PHPExcel_Style();
            $EstiloEncabezado->applyFromArray(array(
                'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER),
                'font' => array('bold' => true)
            ));
            
            $EstiloContenido = new PHPExcel_Style();
            $EstiloContenido->applyFromArray(array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN,'color' => array('argb' => 'FFababab'))),'alignment' => array('vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER),));
            
            $EstiloAlignR = new PHPExcel_Style();
            $EstiloAlignR->applyFromArray(array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT)));
            
            $EstiloLink = new PHPExcel_Style();
            $EstiloLink->applyFromArray(array(
                'font' => array('color' => array('rgb' => '3b63c1'),'underline' => 'single'),
                'borders' => array('right' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM,'color' => array('rgb' => '000000'))),
            ));
            #------------------------------INCEPTION---------------------------#
            // Agregar un Sheet nuevo
            $objPHPExcel->createSheet(1);
            $objPHPExcel->setActiveSheetIndex(1);
            $objPHPExcel->getActiveSheet()->setTitle("Inception");
           
            $row = 1;
            //Definir encabezados:
            #VEHICULOS
            if($DatosReporte['iTipoReporte'] == "1"){
                $objPHPExcel->getActiveSheet()
                ->setCellValue('A'.$row, 'Year')
                ->setCellValue('B'.$row, 'Make')
                ->setCellValue('C'.$row, 'Model')
                ->setCellValue('D'.$row, 'VIN')
                ->setCellValue('E'.$row, 'Value')
                ->setCellValue('F'.$row, 'Type')
                ->setCellValue('G'.$row, 'Action');
                /*->setCellValue('G'.$row, 'Leinholder Name')
                ->setCellValue('H'.$row, 'Leinholder Address')
                ->setCellValue('I'.$row, 'GVW')
                ->setCellValue('J'.$row, 'Additional Vehicle Detail')
                ->setCellValue('K'.$row, 'Garaging Location')
                ->setCellValue('L'.$row, 'Garaging State');*/
                $limitCol = "G";
  
            }
            #DRIVERS
            else if($DatosReporte['iTipoReporte'] == "2"){
                $objPHPExcel->getActiveSheet()
                ->setCellValue('A'.$row, 'Name')
                ->setCellValue('B'.$row, 'DOB')
                ->setCellValue('C'.$row, 'License Number')
                ->setCellValue('D'.$row, 'License Expiration Date')
                ->setCellValue('E'.$row, 'Experience Years')
                ->setCellValue('F'.$row, 'Action');
                $limitCol = "F";
            } 
            
            $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado, "A".$row.":".$limitCol.$row);
            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(25);
            
            foreach($DatosDetalle as $i => $l){
                
                $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloContenido, "A".$row.":".$limitCol.$row); 
                $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(20); 
                
                #VEHICULOS
                if($DatosReporte['iTipoReporte'] == "1"){ 
                    
                    // ENDOSO NO MULTIPLE
                    if($DatosDetalle[$i]['iEndosoMultiple'] == 0){
                        
                        $row++;
                        
                        //Agregar formato numerico a columna de value:
                        $objPHPExcel->getActiveSheet()->getStyle('E'.$row)->getNumberFormat()->setFormatCode("_(\"$\"* #,##0.00_);_(\"$\"* \(#,##0.00\);_(\"$\"* \"-\"??_);_(@_)");
                        
                        //Reporte contenido:
                        $objPHPExcel->getActiveSheet()
                        ->setCellValue('A'.$row, $DatosDetalle[$i]['iYear'])
                        ->setCellValue('B'.$row, $DatosDetalle[$i]['sMake'])
                        ->setCellValue('C'.$row, '')
                        ->setCellValue('D'.$row, $DatosDetalle[$i]['sVIN'])
                        ->setCellValue('E'.$row, $DatosDetalle[$i]['iPDAmount'])
                        ->setCellValue('F'.$row, ucfirst(strtolower($DatosDetalle[$i]['sTipo'])))
                        ->setCellValue('G'.$row, $DatosDetalle[$i]['eAccion']);
                    }
                    // ENDOSO MULTIPLE
                    else if ($DatosDetalle[$i]['iEndosoMultiple'] == 1){
                         #CONSULTAR DETALLE DEL ENDOSO:
                         $query = "SELECT A.sVIN, iConsecutivoUnidad, B.iYear, A.iTotalPremiumPD, C.sDescripcion AS sRadio, D.sAlias AS sModelo, B.sTipo, B.sPeso, ".
                                  "(CASE  WHEN A.eAccion = 'ADDSWAP'    THEN 'ADD SWAP' WHEN A.eAccion = 'DELETESWAP' THEN 'DELETE SWAP' ELSE A.eAccion END) AS eAccion ".
                                  "FROM cb_endoso_unidad      AS A ".
                                  "LEFT JOIN ct_unidades      AS B ON A.iConsecutivoUnidad = B.iConsecutivo ".
                                  "LEFT JOIN ct_unidad_radio  AS C ON A.iConsecutivoRadio  = C.iConsecutivo ".
                                  "LEFT JOIN ct_unidad_modelo AS D ON B.iModelo = D.iConsecutivo ".
                                  "WHERE A.iConsecutivoEndoso = '".$DatosDetalle[$i]['iConsecutivo']."' ORDER BY sVIN ASC";
                         $r     = $conexion->query($query);
                         
                         while($item = $r->fetch_assoc()){
                             
                             $row++;
                             
                             //Agregar formato numerico a columna de value:
                             $objPHPExcel->getActiveSheet()->getStyle('E'.$row)->getNumberFormat()->setFormatCode("_(\"$\"* #,##0.00_);_(\"$\"* \(#,##0.00\);_(\"$\"* \"-\"??_);_(@_)");
                        
                             //Reporte contenido:
                             $objPHPExcel->getActiveSheet()
                             ->setCellValue('A'.$row, $item['iYear'])
                             ->setCellValue('B'.$row, $item['sModelo'])
                             ->setCellValue('C'.$row, '')
                             ->setCellValue('D'.$row, strtoupper($item['sVIN']))
                             ->setCellValue('E'.$row, $item['iTotalPremiumPD'])
                             ->setCellValue('F'.$row, ucfirst(strtolower($item['sTipo'])))
                             ->setCellValue('G'.$row, $item['eAccion']);
                         }
                    }
                    
                    
                    //Revisar si la poliza ya existe en o no para agregarla al XLS
                    if(!(in_array($DatosDetalle[$i]['sNumeroPoliza']."|".$DatosDetalle[$i]['inceptionDate']."|".$DatosDetalle[$i]['expirationDate'],$NoPolizas))){
                       array_push($NoPolizas,$DatosDetalle[$i]['sNumeroPoliza']."|".$DatosDetalle[$i]['inceptionDate']."|".$DatosDetalle[$i]['expirationDate']); 
                    }
                    
                }
                #DRIVERS
                else if($DatosReporte['iTipoReporte'] == "2"){
                    
                    // ENDOSO NO MULTIPLE
                    if($DatosDetalle[$i]['iEndosoMultiple'] == 0){
                        
                        $row++;
                        
                        $objPHPExcel->getActiveSheet()
                        ->setCellValue('A'.$row, $DatosDetalle[$i]['sNombre'])
                        ->setCellValue('B'.$row, $DatosDetalle[$i]['dFechaNacimiento'])
                        ->setCellValue('C'.$row, $DatosDetalle[$i]['iNumLicencia'])
                        ->setCellValue('D'.$row, $DatosDetalle[$i]['dFechaExpiracionLicencia'])
                        ->setCellValue('E'.$row, $DatosDetalle[$i]['iExperienciaYear'])
                        ->setCellValue('F'.$row, $DatosDetalle[$i]['eAccion']);
                    }
                    // ENDOSO MULTIPLE
                    else if ($DatosDetalle[$i]['iEndosoMultiple'] == 1){
                         #CONSULTAR DETALLE DEL ENDOSO:
                         $query = "SELECT A.sNombre, iConsecutivoOperador, B.iNumLicencia, DATE_FORMAT(B.dFechaNacimiento,'%m/%d/%Y') AS dFechaNacimiento, DATE_FORMAT(B.dFechaExpiracionLicencia,'%m/%d/%Y') AS dFechaExpiracionLicencia, B.iExperienciaYear, B.eTipoLicencia, ".
                                  "(CASE WHEN A.eAccion = 'ADDSWAP' THEN 'ADD SWAP' WHEN A.eAccion = 'DELETESWAP' THEN 'DELETE SWAP' ELSE A.eAccion END) AS eAccion ".
                                  "FROM cb_endoso_operador      AS A ".
                                  "LEFT JOIN ct_operadores      AS B ON A.iConsecutivoOperador = B.iConsecutivo ".
                                  "WHERE A.iConsecutivoEndoso = '".$DatosDetalle[$i]['iConsecutivo']."' ORDER BY sNombre ASC";
                         $r     = $conexion->query($query);
                         
                         while($item = $r->fetch_assoc()){ 
                             
                             $row++;
                             $objPHPExcel->getActiveSheet()
                                ->setCellValue('A'.$row, strtoupper($item['sNombre']))
                                ->setCellValue('B'.$row, $item['dFechaNacimiento'])
                                ->setCellValue('C'.$row, $item['iNumLicencia'])
                                ->setCellValue('D'.$row, $item['dFechaExpiracionLicencia'])
                                ->setCellValue('E'.$row, $item['iExperienciaYear'])
                                ->setCellValue('F'.$row, $item['eAccion']);
                         }
                           
                    }
                    
                    //polizas:
                    if(!(in_array($DatosDetalle[$i]['sNumeroPoliza']."|".$DatosDetalle[$i]['inceptionDate']."|".$DatosDetalle[$i]['expirationDate'],$NoPolizas))){
                       array_push($NoPolizas,$DatosDetalle[$i]['sNumeroPoliza']."|".$DatosDetalle[$i]['inceptionDate']."|".$DatosDetalle[$i]['expirationDate']); 
                    }
                } 
                
            }
             
            //Ajustar la dimension de las columnas:
            foreach(range('A',$limitCol) as $columnID) {
                $objPHPExcel->getActiveSheet()->getColumnDimension($columnID)->setAutoSize(true);
            }
             
            
            #------------------------------SYNOPSIS---------------------------#
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->getActiveSheet()->setTitle("Synopsis");
            $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloBorderTop, "A1:C1");
            $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloBorderRight, "C1:C12"); 
            //$objPHPExcel->getActiveSheet()->setSharedStyle($EstiloBorderbottom, "A29:C12");
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
            $objPHPExcel->getActiveSheet()->getStyle('B5')->getNumberFormat()->applyFromArray(array('code'=>PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE));
            $objPHPExcel->getActiveSheet()->setCellValue('A5', 'Rate'); 
            $objPHPExcel->getActiveSheet()->setCellValue('B5', $iRatePercent);
            
            if($DatosReporte['iTipoReporte'] == "1"){
                
                $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloBorderRight, "C13:C25"); 
                //Current Values:
                $objPHPExcel->getActiveSheet()->getStyle('B7')->getNumberFormat()->setFormatCode("_(\"$\"* #,##0.00_);_(\"$\"* \(#,##0.00\);_(\"$\"* \"-\"??_);_(@_)");
                $objPHPExcel->getActiveSheet()->setCellValue('A7', 'Current Total Values**'); 
                $objPHPExcel->getActiveSheet()->setCellValue('C7', '=SUM(C10:C22)');
                
                //Set number style to all Column C
                $objPHPExcel->getActiveSheet()->getStyle('C7')->getNumberFormat()->setFormatCode("_(\"$\"* #,##0.00_);_(\"$\"* \(#,##0.00\);_(\"$\"* \"-\"??_);_(@_)");
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
                $objPHPExcel->getActiveSheet()->setCellValue('C10', '=SUM(Inception!E:E)');
                
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
                }
                else{$row = 12;} 
                
                //Linea division Amarilla:
                $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloYellow, "A".$row.":C".$row);
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A'.$row, 'NOTES AND WARRANTIES');
                $objPHPExcel->setActiveSheetIndex(0)->mergeCells("A".$row.":C".$row);
                $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(15);
                
                $row+=1;
                $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(76);
                $objPHPExcel->getActiveSheet()->getStyle($row)->getAlignment()->setWrapText(true); 
                $content = "- All values should be stated amount or ACV.
    - Values cannot depreciate during the policy term. If a unit is totalled, it must remain on the list of equipment.
    - Vehicle license for over the road use must have a full and verified 17-character VIN.  Helpful Websites to verify:";
                $objPHPExcel->getActiveSheet()->setCellValue('A'.$row,$content);
                $objPHPExcel->getActiveSheet()->mergeCells("A".$row.":C".$row);
                
                $row+=1;
                $objPHPExcel->getActiveSheet()->mergeCells("A".$row.":C".$row);
                $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloLink, "A".$row.":C".$row);
                $objPHPExcel->getActiveSheet()->setCellValue('A'.$row,'http://www.nisrinc.com/apps/cmvid/');
                $objPHPExcel->getActiveSheet()->getCell('A'.$row)->getHyperlink()->setUrl("http://www.nisrinc.com/apps/cmvid/");
                $row+=1;
                $objPHPExcel->getActiveSheet()->mergeCells("A".$row.":C".$row);
                $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloLink, "A".$row.":C".$row);
                $objPHPExcel->getActiveSheet()->setCellValue('A'.$row,'http://www.autocheck.com/vehiclehistory/autocheck/en/');
                $objPHPExcel->getActiveSheet()->getCell('A'.$row)->getHyperlink()->setUrl("http://www.autocheck.com/vehiclehistory/autocheck/en/");   
            }
            else{$row = 12;}
            
            $row+=1;
            $objPHPExcel->getActiveSheet()->mergeCells("A".$row.":C".$row);
            $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloBorderTopRightB, "A".$row.":C".$row);
            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(160); 
            $content = "Any person who knowingly and with intent to defraud any insurance company files an application for insurance or statement of claim containing any materially false or misleading information, or conceals for the purpose of misleading, information concerning any fact material thereto, commits a fraudulent insurance act, which is a crime and subjects the person to criminal and (NY; substantial) civil penalties.  In some states insurance benfits may also be denied.  By submission of this application the insured and the agent represent that all information is true and correct to the best of their knowledge and that they have not deleted or altered the questions herein. ";
            $objPHPExcel->getActiveSheet()->setCellValue('A'.$row,$content);
            
            $row+=1;
            $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloBorderRightB, "A".$row.":C".$row);
            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(43);
            $content = "If there are any mortgagees, lien holders, loss payees, additional named insureds or other additional interests, attach a list to this CRC Application/Schedule of Values.";
            $objPHPExcel->getActiveSheet()->setCellValue('A'.$row,$content);
            $objPHPExcel->getActiveSheet()->mergeCells("A".$row.":C".$row);
            
            #CREATE A FILE NAME:
            if($DatosReporte['iTipoReporte'] == "1"){$titleName = "Equipment List - ";}elseif($DatosReporte['iTipoReporte'] == "2"){$titleName = "Operator List - ";}
            
            $nombre_archivo = $titleName.'with Montly Reporting Tabs_'.$DatosReporte['sNombreCompania']."_".$DatosReporte['dFechaInicio']."_".$DatosReporte['dFechaFin'];
            $nombre_archivo = $nombre_archivo.".xlsx";
            
            if($iEmail == "1"){
                
                $nombre_archivo = "/tmp/$nombre_archivo";
                $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007'); 
                $objWriter->save("$nombre_archivo"); //Crea el archivo temporal en la ruta especifica.
                                                                                 
                $subject  = "Endorsement application - policy number: ".$DatosReporte['sNombreCompania'].", ".$DatosDetalle[0]['sNumeroPoliza']." - ".$DatosDetalle[0]['sAlias'];
                $bodyData = "<p style=\"color:#000;margin:5px auto; text-align:left;\">".utf8_decode($DatosReporte['sMensajeEmail'])."</p><br><br>"; 
                $htmlEmail= "<table style=\"font-size:12px;border:1px solid #6191df;border-radius:3px;padding:10px;width:95%; margin:5px auto;font-family: Arial, Helvetica, sans-serif;\">".
                            "<tr><td><h2 style=\"color:#313131;text-transform: uppercase; text-align:center;\">Endorsement application from Solo-Trucking Insurance</h2></td></tr>".
                            "<tr><td>$bodyData</td></tr>".
                            "<tr><td><p style=\"color:#010101;margin:5px auto 10px; text-align:left;font-size:11px;\">Please reply this email to the account:<a href=\"mailto:customerservice@solo-trucking.com\"> customerservice@solo-trucking.com</a></p></td></tr>". 
                            "<tr><td><p style=\"color:#858585;margin:5px auto; text-align:left;font-size:10px;\">e-mail sent from Solo-trucking Insurance System.</p></td></tr>".
                            "</table>";
                
                #HTML:
                $htmlEmail  = "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\"\"http://www.w3.org/TR/html4/strict.dtd\"><html>".
                                "<head><meta content=\"text/html; charset=utf-8\" http-equiv=\"Content-Type\">".
                                "<title>Endorsement from Solo-Trucking Insurance</title></head>"; 
                $htmlEmail .= "<body>$htmlEmail</body>";   
                $htmlEmail .= "</html>";
                
                #EMAILS TO SEND (VALIDATE)
                $emailRegex   = "/^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/"; 
                $emailstosend = explode(",",$DatosReporte['sEmail']);
                $countemails  = count($emailstosend);
                $emailerror   = "";
                  
                for($z = 0; $z < $countemails; $z++){ 
                    if($emailstosend[$z] != ""){
                          $validaemail = preg_match($emailRegex,trim($emailstosend[$z]));
                          if(!($validaemail)){$emailerror .= $emailstosend[$z]."<br>";}
                    }
                }
                if($emailerror != ""){
                    echo '<script language="javascript">alert(\'Please check the e-mail(s) address of the brokers and try again.\')</script>';
                    echo "<script language='javascript'>window.close();</script>"; 
                }
                else{
                    #Building Email Body:                                   
                    require_once("./lib/phpmailer_master/class.phpmailer.php");
                    require_once("./lib/phpmailer_master/class.smtp.php");
                    
                    #TERMINA CUERPO DEL MENSAJE
                    $mail = new PHPMailer();   
                    $mail->IsSMTP(); // telling the class to use SMTP
                    $mail->Host       = "mail.solo-trucking.com"; // SMTP server
                    //$mail->SMTPDebug  = 2; // enables SMTP debug information (for testing) 1 = errors and messages 2 = messages only
                    $mail->SMTPAuth   = true;                  // enable SMTP authentication
                    $mail->SMTPSecure = "TLS";                 // sets the prefix to the servier
                    $mail->Host       = "smtp.gmail.com";      // sets GMAIL as the SMTP server
                    $mail->Port       = 587;                   // set the SMTP port for the GMAIL server
                    
                    #VERIFICAR SERVIDOR DONDE SE ENVIAN CORREOS:
                    if($_SERVER["HTTP_HOST"]=="stdev.websolutionsac.com" || $_SERVER["HTTP_HOST"]=="www.stdev.websolutionsac.com"){
                      $mail->Username   = "systemsupport@solo-trucking.com";  // GMAIL username
                      $mail->Password   = "SL09100242";  
                      $mail->SetFrom('systemsupport@solo-trucking.com', 'Customer Service Solo-Trucking Insurance');
                    }else if($_SERVER["HTTP_HOST"] == "solotrucking.laredo2.net" || $_SERVER["HTTP_HOST"] == "st.websolutionsac.com" || $_SERVER["HTTP_HOST"] == "www.solo-trucking.com"){
                      $mail->Username   = "customerservice@solo-trucking.com";  // GMAIL username
                      $mail->Password   = "SL641404tK";
                      $mail->SetFrom('customerservice@solo-trucking.com', 'Customer Service Solo-Trucking Insurance');   
                    }
                    
                    $mail->AddReplyTo('customerservice@solo-trucking.com','Customer service Solo-Trucking');
                    $mail->AddCC('systemsupport@solo-trucking.com','System Support Solo-Trucking Insurance');
                    
                    $mail->Subject    = $subject;
                    $mail->AltBody    = "To view the message, please use an HTML compatible email viewer!";  // optional, comment out and test
                    $mail->MsgHTML($htmlEmail);
                    $mail->IsHTML(true); 
                    
                    //Receptores:
                    $nombre_destinatario = trim($DatosReporte['sNombreBroker']);
                    foreach($emailstosend as $direccion){
                        $mail->AddAddress(trim($direccion),$nombre_destinatario);
                    }
                    $mail->AddAttachment($nombre_archivo);
                    $mail_error = false;
                    if(!$mail->Send()){
                        echo '<script language="javascript">alert(\'Error with sending the e-mail, please try again.\')</script>';
                        echo "<script language='javascript'>window.close();</script>";  
                        $mail->ClearAddresses();
                    }else{
                        
                        $dFechaActual = date("Y-m-d H:i:s");
                        $IP           = $_SERVER['REMOTE_ADDR'];
                        $sUsuario     = $_SESSION['usuario_actual'];
                        
                        #ACTUALIZAR REPORTE COMO ENVIADO A LOS BROKERS:
                        $query = "UPDATE cb_endoso_mensual SET eStatus='SB',dFechaAplicacion='$dFechaActual',dFechaActualizacion='$dFechaActual',sUsuarioActualizacion='$sUsuario',sIP='$IP' ".
                                 "WHERE iConsecutivo = '".$DatosReporte['iConsecutivo']."'";
                        $success = $conexion->query($query);
                        if($success){
                            
                            #ACTUALIZAR ENDOSOS ASOCIADOS:
                            $transaccion_exitosa = true;
                            foreach($DatosDetalle as $i => $l){
                               $query   = "UPDATE cb_endoso_estatus SET eStatus='SB',dFechaAplicacion='$dFechaActual',dFechaActualizacion='$dFechaActual',sUsuarioActualizacion='$sUsuario',sIP='$IP' ".
                                          "WHERE iConsecutivoEndoso='".$DatosDetalle[$i]['iConsecutivo']."' AND iConsecutivoPoliza='".$DatosDetalle[$i]['iConsecutivoPoliza']."'"; 
                               $success = $conexion->query($query);
                               if(!($success)){$transaccion_exitosa=false;}
                            }
                            
                            if($transaccion_exitosa){
                                $conexion->commit(); 
                                echo "<script language='javascript'>window.close();</script>";
                            }else{
                                echo '<script language="javascript">alert(\'Error with sending the e-mail, please try again.\')</script>';
                                $conexion->rollback();
                            }
                            
                        }
                        else{
                           $conexion->rollback();
                           echo '<script language="javascript">alert(\'Error with sending the e-mail, please try again.\')</script>';
                           echo "<script language='javascript'>window.close();</script>"; 
                        }
                    }
                    $conexion->close();  
                    $mail->ClearAttachments();
                    unlink($nombre_archivo);  
                }
            }
            else{
                $conexion->rollback();
                $conexion->close();  
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
        }
        else{
            echo '<script language="javascript">alert(\'There were no results in your report, please try again.\')</script>';
            echo "<script language='javascript'>window.close();</script>"; 
        }
       
    }
    else{
       echo '<script language="javascript">alert(\'There were no results in your query, please try again..\')</script>';
       echo "<script language='javascript'>window.close();</script>"; 
    }  
    
?>
