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
    isset($_GET['reporttype']) ? $reporttype             = urldecode($_GET['reporttype']): $reporttype = "";
    isset($_GET['policy'])     ? $policy                 = urldecode($_GET['policy'])    : $policy = "";
    isset($_GET['filtro'])     ? $filtro                 = urldecode($_GET['filtro'])    : $filtro = "";
    
    #OBTENER DATOS DE LA COMPANIA:
    $query  = "SELECT * FROM ct_companias WHERE iConsecutivo='$iConsecutivoCompania'";
    $result = $conexion->query($query);
    $DatosCo= $result->fetch_assoc();
    
    #REVISAR POLIZAS SELECCIONADAS:
    $policy == "" || $policy == "ALL"? $flt_policy = "A.iConsecutivoCompania='$iConsecutivoCompania'" : $flt_policy = "A.iConsecutivo='$policy'";
    //Consultar polizas del cliente:
    $query  = "SELECT A.iConsecutivo, A.sNumeroPoliza, B.sName AS sBroker, C.sDescripcion AS sTipoPoliza, C.sAlias ".
              "FROM      ct_polizas     AS A ".
              "LEFT JOIN ct_brokers     AS B ON A.iConsecutivoBrokers = B.iConsecutivo ".
              "LEFT JOIN ct_tipo_poliza AS C ON A.iTipoPoliza         = C.iConsecutivo ".  
              "WHERE $flt_policy AND A.iDeleted = '0' AND A.dFechaCaducidad >= CURDATE() ORDER BY A.iConsecutivo ASC";
    $result = $conexion->query($query);
    $rows   = $result->num_rows;
    if($rows > 0){
        
        $polizas = mysql_fetch_all($result);
        $count   = count($polizas);

        //ARMAR ARCHIVO POR POLIZA:
        for($x=0;$x<$count;$x++){
            // Variable:
            $polizaNo = $polizas[$x]['sNumeroPoliza'];
            $polizaId = $polizas[$x]['iConsecutivo'];
            $polizaTy = $polizas[$x]['sTipoPoliza'];
            $polizaAl = $polizas[$x]['sAlias'];
            $polizaBr = $polizas[$x]['sBroker'];
            $PDApply  = true;
            
            #-------------------- EXCEL BEGINS ---------------------------------#
            $objPHPExcel = new PHPExcel();  // Create new PHPExcel object
            
            // Set document properties
            $objPHPExcel->getProperties()->setCreator("Solo-Trucking Insurance System")->setLastModifiedBy("Solo-Trucking Insurance System")
            ->setTitle("Solo-Trucking Insurance On-line Reports")->setSubject("List Drivers/Vehicles")
            ->setDescription("Report of history list of drivers or vehicles of a company.")
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
            
            /*$filtro_query  = " WHERE iConsecutivoCompania = '$iConsecutivoCompania' AND iDeleted='0' ";
            $orden_query   = " ORDER BY iConsecutivo DESC"; 
            if($filtro != ""){
                $array_filtros = explode(",",$filtro);
                foreach($array_filtros as $key => $valor){
                    if($array_filtros[$key] != ""){
                        $campo_valor = explode("|",$array_filtros[$key]);
                        $campo_valor[0] == 'iConsecutivo' ? $filtro_query.= " AND  ".$campo_valor[0]."='".$campo_valor[1]."' " : $filtro_query == "" ? $filtro_query.= " AND  ".$campo_valor[0]." LIKE '%".$campo_valor[1]."%'": $filtro_query.= " AND ".$campo_valor[0]." LIKE '%".$campo_valor[1]."%'";
                    }
                }
            }*/
            
            #REPORTE DRIVERS
            if($reporttype != "" && $reporttype == '2'){
            
                #GET BIND SHEET:
                $query  = "SELECT B.iConsecutivo, B.sNombre, DATE_FORMAT(B.dFechaNacimiento, '%m/%d/%Y') AS dFechaNacimiento, DATE_FORMAT(B.dFechaExpiracionLicencia, '%m/%d/%Y') AS dFechaExpiracionLicencia,iExperienciaYear,iNumLicencia,(CASE eTipoLicencia WHEN 1 THEN 'Federal / B1' WHEN 2 THEN 'Commercial / CDL - A' END) AS TipoLicencia,
                          DATE_FORMAT(A.dFechaIngreso, '%m/%d/%Y') AS dFechaIngreso, A.iDeleted
                          FROM cb_poliza_operador AS A 
                          LEFT JOIN ct_operadores AS B ON A.iConsecutivoOperador = B.iConsecutivo
                          WHERE A.iConsecutivoPoliza = '$polizaId' AND B.iConsecutivoCompania='$iConsecutivoCompania'  AND A.eModoIngreso='AMIC' AND A.iDeleted='0'
                          ORDER BY B.sNombre ASC"; 
                $result = $conexion->query($query);
                $rows   = $result->num_rows;  
                
                if($rows > 0){
                    
                    // Rename worksheet
                    $objPHPExcel->setActiveSheetIndex(0);
                    $objPHPExcel->getActiveSheet()->setTitle('Bind List');
                     
                    $items = mysql_fetch_all($result);
                    
                    //Encabezado del reporte.
                    $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado, "A1:H1");
                    $objPHPExcel->getActiveSheet()->setCellValue('A1', strtoupper($DatosCo['sNombreCompania']).' - BIND LIST');
                    $objPHPExcel->getActiveSheet()->mergeCells("A1:H1");
                    $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(40);
                    
                    //Subtitulo del Reporte:
                    $descripcionReporte = "$polizaNo - $polizaTy - $polizaBr";   
                    $descripcionReporte2= "On-line Report from: ".date("m/d/Y g:i a"); 
                    $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado2, "A2:D2");
                    $objPHPExcel->getActiveSheet()->setCellValue('A2', $descripcionReporte);
                    $objPHPExcel->getActiveSheet()->mergeCells("A2:D2");
                    $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado22, "E2:H2"); 
                    $objPHPExcel->getActiveSheet()->setCellValue('E2', $descripcionReporte2);
                    $objPHPExcel->getActiveSheet()->mergeCells("E2:H2");
                    $objPHPExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(25); 
                     
                    //Columnas:
                    $row = 3;
                    $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado3, "A".$row.":H".$row);
                    $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(35);
                    $objPHPExcel->getActiveSheet()
                            ->setCellValue('A'.$row, 'NO.')
                            ->setCellValue('B'.$row, 'NAME')
                            ->setCellValue('C'.$row, 'DOB')
                            ->setCellValue('D'.$row, 'LICENSE #')
                            ->setCellValue('E'.$row, 'LICENSE TYPE')
                            ->setCellValue('F'.$row, 'EXPIRE DATE')
                            ->setCellValue('G'.$row, 'YOE')
                            ->setCellValue('H'.$row, 'APPLICATION DATE');
                            
                    $countD = count($items);
                    $No = 0;
                    for($d=0;$d<$countD;$d++){
                         
                         $row++;
                         $No++;
                         $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloContenido, "A".$row.":G".$row); 
                         $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(20);
        
                         //Reporte contenido:
                         $objPHPExcel->getActiveSheet()
                                     ->setCellValue('A'.$row, $No)    
                                     ->setCellValue('B'.$row, utf8_decode($items[$d]['sNombre'])) 
                                     ->setCellValue('C'.$row, $items[$d]['dFechaNacimiento'])
                                     ->setCellValue('D'.$row, $items[$d]['iNumLicencia'])
                                     ->setCellValue('E'.$row, $items[$d]['TipoLicencia'])
                                     ->setCellValue('F'.$row, $items[$d]['dFechaExpiracionLicencia'])
                                     ->setCellValue('G'.$row, $items[$d]['iExperienciaYear'])
                                     ->setCellValue('H'.$row, $items[$d]['dFechaIngreso']);
                                     
                         // Aplicar formatos/estilos:
                         $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'A'.$row);
                         $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignL,'B'.$row);
                         $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'C'.$row);
                         $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignL,'D'.$row);
                         $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'F'.$row);
                         $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'G'.$row);
                         $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'H'.$row);
                         
                         $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
                         $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true); 
                         $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth('17');
                         $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth('19');
                         $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth('17');
                         $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth('17');
                         $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth('9');
                         $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth('17');
                         //$objPHPExcel->getActiveSheet()->getStyle('B'.$row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_YYYYMMDDSLASH);
                             
                    }
                     
                    #GET ENDORSEMENT SHEET:
                    $query  = "SELECT E.iConsecutivo AS iConsecutivoEndoso, S.eStatus, S.sNumeroEndosoBroker, DATE_FORMAT(E.dFechaAplicacion,'%m/%d/%Y') AS dFechaAplicacion, ".
                              "iEndosoMultiple, E.iConsecutivoOperador, (CASE WHEN E.eAccion='A' THEN 'ADD' WHEN E.eAccion='D' THEN 'DELETE' END) AS eAccion, D.sNombre AS sNombreOperador, D.iNumLicencia
                               FROM cb_endoso_estatus AS S
                               LEFT JOIN cb_endoso    AS E ON S.iConsecutivoEndoso  = E.iConsecutivo
                               LEFT JOIN ct_polizas   AS P ON S.iConsecutivoPoliza  = P.iConsecutivo
                               LEFT JOIN ct_operadores AS D ON E.iConsecutivoOperador = D.iConsecutivo
                              WHERE iConsecutivoPoliza='$polizaId' AND E.iConsecutivoTipoEndoso='2' AND E.iDeleted='0' AND E.iConsecutivoCompania='$iConsecutivoCompania' 
                              ORDER BY E.dFechaAplicacion ASC"; 
                    $result = $conexion->query($query);
                    $rows   = $result->num_rows; 
                    if($rows > 0){
                        // Rename worksheet
                        $objPHPExcel->createSheet(1);
                        $objPHPExcel->setActiveSheetIndex(1);
                        $objPHPExcel->getActiveSheet()->setTitle('Endorsements');
                         
                        $items = mysql_fetch_all($result);
                        
                        //Encabezado del reporte.
                        $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado, "A1:F1");
                        $objPHPExcel->getActiveSheet()->setCellValue('A1', strtoupper($DatosCo['sNombreCompania']).' - ENDORSEMENTS');
                        $objPHPExcel->getActiveSheet()->mergeCells("A1:F1");
                        $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(40);
                        
                        //Subtitulo del Reporte:
                        $descripcionReporte = "$polizaNo - $polizaTy - $polizaBr";   
                        $descripcionReporte2= "On-line Report from: ".date("m/d/Y g:i a"); 
                        $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado2, "A2:D2");
                        $objPHPExcel->getActiveSheet()->setCellValue('A2', $descripcionReporte);
                        $objPHPExcel->getActiveSheet()->mergeCells("A2:D2");
                        $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado22, "E2:F2");
                        $objPHPExcel->getActiveSheet()->setCellValue('E2', $descripcionReporte2);
                        $objPHPExcel->getActiveSheet()->mergeCells("E2:F2");
                        $objPHPExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(25); 

                        //Columnas:
                        $row = 3;
                        $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado3, "A".$row.":F".$row);
                        $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(35);
                        $objPHPExcel->getActiveSheet()
                        ->setCellValue('A'.$row, 'END #')
                        ->setCellValue('B'.$row, 'ACTION')
                        ->setCellValue('C'.$row, 'NAME')
                        ->setCellValue('D'.$row, 'LICENSE #')
                        ->setCellValue('E'.$row, 'APPLICATION DATE')
                        ->setCellValue('F'.$row, 'STATUS');
                                
                        $countD = count($items); 
                        for($d=0;$d<$countD;$d++){
                            
                             // Definir estatus descripcion:
                             switch($items[$d]["eStatus"]){
                                 case 'S' : $estado = 'SENT TO SOLO-TRUCKING'; break;
                                 case 'A' : $estado = 'APPLIED TO THE POLICY'; break;
                                 case 'D' : $estado = 'CANCELED'; break;
                                 case 'SB': $estado = 'SENT TO BROKERS'; break;
                                 case 'P' : $estado = 'IN PROCESS'; break;
                             }
                             
                             // Revisamos si es endoso multiple:
                             if($items[$d]['iEndosoMultiple'] == '1'){
                                 $query  = "SELECT iConsecutivoOperador, sNombre, iNumLicencia,eAccion FROM cb_endoso_operador ".
                                          "WHERE iConsecutivoEndoso='".$items[$d]['iConsecutivoEndoso']."'"; 
                                 $result = $conexion->query($query);
                                 
                                 while($data   = $result->fetch_assoc()){
                                    $row++;    
                                    $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloContenido, "A".$row.":G".$row); 
                                    $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(20);
                                    
                                    $nombre   = utf8_decode($data['sNombre']); 
                                    $licencia = $data['iNumLicencia'];
                                    $accion   = $data['eAccion'];
                                    
                                    //Reporte contenido:
                                    $objPHPExcel->getActiveSheet()
                                                 ->setCellValue('A'.$row, $items[$d]['sNumeroEndosoBroker'])   
                                                 ->setCellValue('B'.$row, $accion) 
                                                 ->setCellValue('C'.$row, $nombre)
                                                 ->setCellValue('D'.$row, $licencia)
                                                 ->setCellValue('E'.$row, $items[$d]['dFechaAplicacion'])
                                                 ->setCellValue('F'.$row, $estado);
                                    // Aplicar formatos/estilos:
                                    $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'A'.$row);
                                    $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'B'.$row);
                                    $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignL,'C'.$row);
                                    $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignL,'D'.$row);
                                    $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'E'.$row);
                                    $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'F'.$row);
                                    
                                    $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
                                    $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth('10');
                                    $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
                                    $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth('19');
                                    $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth('17');
                                    $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth('25');
                                            
                                 }    
                             }
                             else{
                                $row++;    
                                $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloContenido, "A".$row.":G".$row); 
                                $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(20); 
                                 
                                $nombre   = utf8_decode($items[$d]['sNombreOperador']); 
                                $licencia = $items[$d]['iNumLicencia']; 
                                
                                //Reporte contenido:
                                $objPHPExcel->getActiveSheet()
                                             ->setCellValue('A'.$row, $items[$d]['sNumeroEndosoBroker'])    
                                             ->setCellValue('B'.$row, $items[$d]['eAccion']) 
                                             ->setCellValue('C'.$row, $nombre)
                                             ->setCellValue('D'.$row, $licencia)
                                             ->setCellValue('E'.$row, $items[$d]['dFechaAplicacion'])
                                             ->setCellValue('F'.$row, $estado);
                                             
                                // Aplicar formatos/estilos:
                                $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'A'.$row);
                                $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'B'.$row);
                                $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignL,'C'.$row);
                                $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignL,'D'.$row);
                                $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'E'.$row);
                                $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'F'.$row);
                                
                                $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
                                $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth('10');
                                $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
                                $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth('19');
                                $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth('17');
                                $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth('25');
                                
                             }
                        }  
                    }
                    
                    #GET ACTUAL LIST IN THE POLICY: 
                    $query  = "SELECT B.iConsecutivo, B.sNombre, DATE_FORMAT(B.dFechaNacimiento, '%m/%d/%Y') AS dFechaNacimiento, DATE_FORMAT(B.dFechaExpiracionLicencia, '%m/%d/%Y') AS dFechaExpiracionLicencia,iExperienciaYear,iNumLicencia,(CASE eTipoLicencia WHEN 1 THEN 'Federal / B1' WHEN 2 THEN 'Commercial / CDL - A' END) AS TipoLicencia,
                               DATE_FORMAT(A.dFechaIngreso, '%m/%d/%Y') AS dFechaIngreso, A.eModoIngreso
                               FROM cb_poliza_operador AS A 
                               LEFT JOIN ct_operadores AS B ON A.iConsecutivoOperador = B.iConsecutivo
                               WHERE A.iConsecutivoPoliza = '$polizaId' AND B.iConsecutivoCompania='$iConsecutivoCompania' AND A.iDeleted='0' ORDER BY B.sNombre ASC";
                    $result = $conexion->query($query);
                    $rows   = $result->num_rows;  
                
                    if($rows > 0){
                        
                        $index = $objPHPExcel->getSheetCount();
                        $objPHPExcel->createSheet($index);
                        $objPHPExcel->setActiveSheetIndex($index);
                        $objPHPExcel->getActiveSheet()->setTitle('Actual List'); 
                         
                        $items = mysql_fetch_all($result);
                        //Encabezado del reporte.
                        $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado, "A1:H1");
                        $objPHPExcel->getActiveSheet()->setCellValue('A1', strtoupper($DatosCo['sNombreCompania']).' - ACTUAL LIST');
                        $objPHPExcel->getActiveSheet()->mergeCells("A1:H1");
                        $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(40);
                        //Subtitulo del Reporte:
                        $descripcionReporte = "$polizaNo - $polizaTy - $polizaBr";   
                        $descripcionReporte2= "On-line Report from: ".date("m/d/Y g:i a"); 
                        $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado2, "A2:D2");
                        $objPHPExcel->getActiveSheet()->setCellValue('A2', $descripcionReporte);
                        $objPHPExcel->getActiveSheet()->mergeCells("A2:D2");
                        $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado22, "E2:H2"); 
                        $objPHPExcel->getActiveSheet()->setCellValue('E2', $descripcionReporte2);
                        $objPHPExcel->getActiveSheet()->mergeCells("E2:H2");
                        $objPHPExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(25);
                         
                        //Columnas:
                        $row = 3;
                        $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado3, "A".$row.":H".$row);
                        $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(35);
                        $objPHPExcel->getActiveSheet()
                                ->setCellValue('A'.$row, 'NO.')
                                ->setCellValue('B'.$row, 'NAME')
                                ->setCellValue('C'.$row, 'DOB')
                                ->setCellValue('D'.$row, 'LICENSE #')
                                ->setCellValue('E'.$row, 'LICENSE TYPE')
                                ->setCellValue('F'.$row, 'EXPIRE DATE')
                                ->setCellValue('G'.$row, 'YOE')
                                ->setCellValue('H'.$row, 'APPLICATION DATE');
                                
                        $countD = count($items);
                        $No = 0;
                        for($d=0;$d<$countD;$d++){
                             
                             $row++;
                             $No++;
                             $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloContenido, "A".$row.":G".$row); 
                             $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(20);
            
                             //Reporte contenido:
                             $objPHPExcel->getActiveSheet()
                                         ->setCellValue('A'.$row, $No)    
                                         ->setCellValue('B'.$row, utf8_decode($items[$d]['sNombre'])) 
                                         ->setCellValue('C'.$row, $items[$d]['dFechaNacimiento'])
                                         ->setCellValue('D'.$row, $items[$d]['iNumLicencia'])
                                         ->setCellValue('E'.$row, $items[$d]['TipoLicencia'])
                                         ->setCellValue('F'.$row, $items[$d]['dFechaExpiracionLicencia'])
                                         ->setCellValue('G'.$row, $items[$d]['iExperienciaYear'])
                                         ->setCellValue('H'.$row, $items[$d]['eModoIngreso']." - ".$items[$d]['dFechaIngreso']);
                                         
                             // Aplicar formatos/estilos:
                             $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'A'.$row);
                             $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignL,'B'.$row);
                             $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'C'.$row);
                             $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignL,'D'.$row);
                             $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'F'.$row);
                             $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'G'.$row);
                             $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'H'.$row);
                             
                             $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
                             $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true); 
                             $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth('17');
                             $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth('19');
                             $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth('17');
                             $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth('17');
                             $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth('9');
                             $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth('30');
                        }   
                        
                    }
                }
                else{$error = 1; $mensaje = "The data of BIND has not found, please verify if you are uploaded the first list.";}              
            }
            else if($reporttype != "" && $reporttype == '1'){
                #GET BIND SHEET:
                $query  = "SELECT B.iConsecutivo, B.sVIN, B.iYear, sPeso,sTipo,C.sAlias AS Make,D.sDescripcion AS sRadius,B.iTotalPremiumPD, DATE_FORMAT(A.dFechaIngreso, '%m/%d/%Y') AS dFechaIngreso, A.iDeleted
                            FROM cb_poliza_unidad      AS A 
                            LEFT JOIN ct_unidades      AS B ON A.iConsecutivoUnidad = B.iConsecutivo
                            LEFT JOIN ct_unidad_modelo AS C ON B.iModelo = C.iConsecutivo
                            LEFT JOIN ct_unidad_radio  AS D ON B.iConsecutivoRadio = D.iConsecutivo
                          WHERE A.iConsecutivoPoliza = '$polizaId' AND B.iConsecutivoCompania='$iConsecutivoCompania'  AND A.eModoIngreso='AMIC' AND A.iDeleted='0'
                          ORDER BY B.sVIN ASC"; 
                $result = $conexion->query($query);
                $rows   = $result->num_rows;  
                
                if($rows > 0){
                    
                    // Rename worksheet
                    $objPHPExcel->setActiveSheetIndex(0);
                    $objPHPExcel->getActiveSheet()->setTitle('Bind List');
                     
                    $items = mysql_fetch_all($result);
                    
                    //Encabezado del reporte.
                    $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado, "A1:I1");
                    $objPHPExcel->getActiveSheet()->setCellValue('A1', strtoupper($DatosCo['sNombreCompania']).' - BIND LIST');
                    $objPHPExcel->getActiveSheet()->mergeCells("A1:I1");
                    $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(40);
                    
                    //Subtitulo del Reporte:
                    $descripcionReporte = "$polizaNo - $polizaTy - $polizaBr";   
                    $descripcionReporte2= "On-line Report from: ".date("m/d/Y g:i a"); 
                    $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado2, "A2:E2");
                    $objPHPExcel->getActiveSheet()->setCellValue('A2', $descripcionReporte);
                    $objPHPExcel->getActiveSheet()->mergeCells("A2:E2");
                    $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado22, "F2:I2"); 
                    $objPHPExcel->getActiveSheet()->setCellValue('F2', $descripcionReporte2);
                    $objPHPExcel->getActiveSheet()->mergeCells("F2:I2");
                    $objPHPExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(25); 
                     
                    //Columnas:
                    $row = 3;
                    $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado3, "A".$row.":I".$row);
                    $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(35);
                    $objPHPExcel->getActiveSheet()
                            ->setCellValue('A'.$row, 'NO.')
                            ->setCellValue('B'.$row, 'VIN')
                            ->setCellValue('C'.$row, 'YEAR')
                            ->setCellValue('D'.$row, 'MAKE')
                            ->setCellValue('E'.$row, 'RADIUS')
                            ->setCellValue('F'.$row, 'TYPE')
                            ->setCellValue('G'.$row, 'CAPACITY')
                            ->setCellValue('H'.$row, 'TOTAL PREMIUM') 
                            ->setCellValue('I'.$row, 'APPLICATION DATE');
                            
                    $countD = count($items);
                    $No = 0;
                    for($d=0;$d<$countD;$d++){
                         
                         $row++;
                         $No++;
                         $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloContenido, "A".$row.":I".$row); 
                         $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(20);
                         
                         $PDApply && $items[$d]['iTotalPremiumPD'] > 0 ? $value = "\$ ".number_format($items[$d]['iTotalPremiumPD'],2,'.',',') : $value = "";  
        
                         //Reporte contenido:
                         $objPHPExcel->getActiveSheet()
                                     ->setCellValue('A'.$row, $No)    
                                     ->setCellValue('B'.$row, strtoupper($items[$d]['sVIN'])) 
                                     ->setCellValue('C'.$row, $items[$d]['iYear'])
                                     ->setCellValue('D'.$row, $items[$d]['Make'])
                                     ->setCellValue('E'.$row, $items[$d]['sRadius'])
                                     ->setCellValue('F'.$row, $items[$d]['sTipo'])
                                     ->setCellValue('G'.$row, $items[$d]['sPeso'])
                                     ->setCellValue('H'.$row, $value) 
                                     ->setCellValue('I'.$row, $items[$d]['dFechaIngreso']);
                                     
                         // Aplicar formatos/estilos:
                         $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'A'.$row);
                         $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignL,'B'.$row);
                         $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'C'.$row);
                         $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'D'.$row);
                         $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'E'.$row); 
                         $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'F'.$row);
                         $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'G'.$row);
                         $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignR,'H'.$row);
                         $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'I'.$row); 
                         
                         $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
                         $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true); 
                         $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth('9');
                         $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth('13');
                         $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth('9');
                         $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth('12');
                         $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth('15');
                         $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth('20');
                         $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth('17');
                             
                    }
                     
                    #GET ENDORSEMENT SHEET:
                    $query  = "SELECT E.iConsecutivo AS iConsecutivoEndoso, S.eStatus, S.sNumeroEndosoBroker, DATE_FORMAT(E.dFechaAplicacion,'%m/%d/%Y') AS dFechaAplicacion, ".
                              "iEndosoMultiple, E.iConsecutivoUnidad, (CASE WHEN E.eAccion='A' THEN 'ADD' WHEN E.eAccion='D' THEN 'DELETE' END) AS eAccion,
                               D.sVIN, D.iYear, M.sAlias, R.sDescripcion AS sRadius, D.iTotalPremiumPD
                               FROM cb_endoso_estatus AS S
                               LEFT JOIN cb_endoso AS E ON S.iConsecutivoEndoso = E.iConsecutivo
                               LEFT JOIN ct_polizas  AS P ON S.iConsecutivoPoliza = P.iConsecutivo
                               LEFT JOIN ct_unidades AS D ON E.iConsecutivoUnidad = D.iConsecutivo
                               LEFT JOIN ct_unidad_modelo AS M ON D.iModelo = M.iConsecutivo
                               LEFT JOIN ct_unidad_radio  AS R ON D.iConsecutivoRadio = R.iConsecutivo 
                              WHERE iConsecutivoPoliza='$polizaId' AND E.iConsecutivoTipoEndoso='1' AND E.iDeleted='0' AND E.iConsecutivoCompania='$iConsecutivoCompania' 
                              ORDER BY E.dFechaAplicacion ASC"; 
                    $result = $conexion->query($query);
                    $rows   = $result->num_rows; 
                    if($rows > 0){
                        // Rename worksheet
                        $objPHPExcel->createSheet(1);
                        $objPHPExcel->setActiveSheetIndex(1);
                        $objPHPExcel->getActiveSheet()->setTitle('Endorsements');
                         
                        $items = mysql_fetch_all($result);

                        //Encabezado del reporte.
                        $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado, "A1:I1");
                        $objPHPExcel->getActiveSheet()->setCellValue('A1', strtoupper($DatosCo['sNombreCompania']).' - ENDORSEMENTS');
                        $objPHPExcel->getActiveSheet()->mergeCells("A1:I1");
                        $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(40);
                        
                        //Subtitulo del Reporte:
                        $descripcionReporte = "$polizaNo - $polizaTy - $polizaBr";   
                        $descripcionReporte2= "On-line Report from: ".date("m/d/Y g:i a"); 
                        $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado2, "A2:E2");
                        $objPHPExcel->getActiveSheet()->setCellValue('A2', $descripcionReporte);
                        $objPHPExcel->getActiveSheet()->mergeCells("A2:E2");
                        $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado22, "F2:I2");
                        $objPHPExcel->getActiveSheet()->setCellValue('F2', $descripcionReporte2);
                        $objPHPExcel->getActiveSheet()->mergeCells("F2:I2");
                        $objPHPExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(25); 

                        //Columnas:
                        $row = 3;
                        $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado3, "A".$row.":I".$row);
                        $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(35);
                        $objPHPExcel->getActiveSheet()
                        ->setCellValue('A'.$row, 'END #')
                        ->setCellValue('B'.$row, 'ACTION')
                        ->setCellValue('C'.$row, 'VIN')
                        ->setCellValue('D'.$row, 'YEAR')
                        ->setCellValue('E'.$row, 'MAKE') 
                        ->setCellValue('F'.$row, 'RADIUS') 
                        ->setCellValue('G'.$row, 'TOTAL PREMIUM') 
                        ->setCellValue('H'.$row, 'APPLICATION DATE')
                        ->setCellValue('I'.$row, 'STATUS');
                                
                        $countD = count($items); 
                        for($d=0;$d<$countD;$d++){
                         
                             $row++;
                             $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloContenido, "A".$row.":J".$row); 
                             $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(20);
                             // Obtener nombre/licencia
                             if($items[$d]['iEndosoMultiple'] == '1'){
                                $query  = "SELECT A.iConsecutivoUnidad, A.sVIN, (CASE WHEN A.eAccion = 'ADDSWAP' THEN 'ADD SWAP' WHEN A.eAccion = 'DELETESWAP' THEN 'DELETE SWAP' ELSE A.eAccion END) AS eAccion,
                                            A.iTotalPremiumPD, C.sAlias, D.sDescripcion AS sRadius, B.iYear
                                            FROM cb_endoso_unidad AS A
                                            LEFT JOIN ct_unidades AS B ON A.iConsecutivoUnidad = B.iConsecutivo 
                                            LEFT JOIN ct_unidad_modelo AS C ON B.iModelo = C.iConsecutivo
                                            LEFT JOIN ct_unidad_radio  AS D ON A.iConsecutivoRadio = D.iConsecutivo ".
                                          "WHERE iConsecutivoEndoso='".$items[$d]['iConsecutivoEndoso']."'"; 
                                $result = $conexion->query($query);
                                $data   = mysql_fetch_all($result); 
                                $datac  = count($data);
                                for($z=0;$z<$datac;$z++){
                                     // Definir estatus descripcion:
                                     $estado = get_estatus($items[$d]["eStatus"]);
                                     $PDApply && $data[$z]['iTotalPremiumPD'] > 0 ? $value = "\$ ".number_format($data[$z]['iTotalPremiumPD'],2,'.',',') : $value = "";
                                     
                                     //Reporte contenido:
                                     $objPHPExcel->getActiveSheet()
                                     ->setCellValue('A'.$row, $items[$d]['sNumeroEndosoBroker'])    
                                     ->setCellValue('B'.$row, $data[$z]['eAccion']) 
                                     ->setCellValue('C'.$row, $data[$z]['sVIN']) 
                                     ->setCellValue('D'.$row, $data[$z]['iYear'])
                                     ->setCellValue('E'.$row, $data[$z]['sAlias'])
                                     ->setCellValue('F'.$row, $data[$z]['sRadius'])
                                     ->setCellValue('G'.$row, $value)
                                     ->setCellValue('H'.$row, $items[$d]['dFechaAplicacion'])
                                     ->setCellValue('I'.$row, $estado);
                                                 
                                     // Aplicar formatos/estilos:
                                     $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'A'.$row);
                                     $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'B'.$row);
                                     $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignL,'C'.$row);
                                     $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'D'.$row);
                                     $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'E'.$row);
                                     $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'F'.$row);
                                     $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignR,'G'.$row);
                                     $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'H'.$row);
                                     $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'I'.$row);
                                     
                                     $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
                                     $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true); 
                                     $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
                                     $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth('10');
                                     $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth('13');
                                     $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth('12');
                                     $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth('15');
                                     $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth('17');
                                     $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth('25');
                                     
                                     ($z+1) < $datac ? $row++ : "";
                                }
                                
                             }
                             else{
                                 // Definir estatus descripcion:
                                 $estado = get_estatus($items[$d]["eStatus"]);
                                 $PDApply && $items[$d]['iTotalPremiumPD'] > 0 ? $value = "\$ ".number_format($items[$d]['iTotalPremiumPD'],2,'.',',') : $value = "";
                                 
                                 //Reporte contenido:
                                 $objPHPExcel->getActiveSheet()
                                 ->setCellValue('A'.$row, $items[$d]['sNumeroEndosoBroker'])    
                                 ->setCellValue('B'.$row, $items[$d]['eAccion']) 
                                 ->setCellValue('C'.$row, $items[$d]['sVIN']) 
                                 ->setCellValue('D'.$row, $items[$d]['iYear'])
                                 ->setCellValue('E'.$row, $items[$d]['sAlias'])
                                 ->setCellValue('F'.$row, $items[$d]['sRadius'])
                                 ->setCellValue('G'.$row, $value)
                                 ->setCellValue('H'.$row, $items[$d]['dFechaAplicacion'])
                                 ->setCellValue('I'.$row, $estado);
                                             
                                 // Aplicar formatos/estilos:
                                 $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'A'.$row);
                                 $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'B'.$row);
                                 $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignL,'C'.$row);
                                 $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'D'.$row);
                                 $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'E'.$row);
                                 $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'F'.$row);
                                 $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignR,'G'.$row);
                                 $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'H'.$row);
                                 $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'I'.$row);
                                 
                                 $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
                                 $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true); 
                                 $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
                                 $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth('10');
                                 $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth('13');
                                 $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth('12');
                                 $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth('15');
                                 $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth('17');
                                 $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth('25');
                             }
                        }  
                    }  
                    
                    #GET ACTUAL LIST IN THE POLICY: 
                    $query  = "SELECT B.iConsecutivo, B.sVIN, B.iYear, sPeso, sTipo, C.sAlias AS Make, D.sDescripcion AS sRadius, B.iTotalPremiumPD, DATE_FORMAT(A.dFechaIngreso, '%m/%d/%Y') AS dFechaIngreso, A.eModoIngreso
                                FROM      cb_poliza_unidad AS A
                                LEFT JOIN ct_unidades      AS B ON A.iConsecutivoUnidad = B.iConsecutivo
                                LEFT JOIN ct_unidad_modelo AS C ON B.iModelo = C.iConsecutivo
                                LEFT JOIN ct_unidad_radio  AS D ON B.iConsecutivoRadio = D.iConsecutivo
                                WHERE
                                    A.iConsecutivoPoliza = '$polizaId'
                                AND B.iConsecutivoCompania = '$iConsecutivoCompania'
                                AND A.iDeleted = '0'
                                ORDER BY B.sVIN ASC";
                    $result = $conexion->query($query);
                    $rows   = $result->num_rows;  
                
                    if($rows > 0){
                        
                        $index = $objPHPExcel->getSheetCount();
                        $objPHPExcel->createSheet($index);
                        $objPHPExcel->setActiveSheetIndex($index);
                        $objPHPExcel->getActiveSheet()->setTitle('Actual List'); 
                         
                        $items = mysql_fetch_all($result);
                        //Encabezado del reporte.
                        $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado, "A1:I1");
                        $objPHPExcel->getActiveSheet()->setCellValue('A1', strtoupper($DatosCo['sNombreCompania']).' - ACTUAL LIST');
                        $objPHPExcel->getActiveSheet()->mergeCells("A1:I1");
                        $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(40);
                        
                        //Subtitulo del Reporte:
                        $descripcionReporte = "$polizaNo - $polizaTy - $polizaBr";   
                        $descripcionReporte2= "On-line Report from: ".date("m/d/Y g:i a"); 
                        $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado2, "A2:E2");
                        $objPHPExcel->getActiveSheet()->setCellValue('A2', $descripcionReporte);
                        $objPHPExcel->getActiveSheet()->mergeCells("A2:E2");
                        $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado22, "F2:I2"); 
                        $objPHPExcel->getActiveSheet()->setCellValue('F2', $descripcionReporte2);
                        $objPHPExcel->getActiveSheet()->mergeCells("F2:I2");
                        $objPHPExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(25); 
                         
                        //Columnas:
                        $row = 3;
                        $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado3, "A".$row.":I".$row);
                        $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(35);
                        $objPHPExcel->getActiveSheet()
                                ->setCellValue('A'.$row, 'NO.')
                                ->setCellValue('B'.$row, 'VIN')
                                ->setCellValue('C'.$row, 'YEAR')
                                ->setCellValue('D'.$row, 'MAKE')
                                ->setCellValue('E'.$row, 'RADIUS')
                                ->setCellValue('F'.$row, 'TYPE')
                                ->setCellValue('G'.$row, 'CAPACITY')
                                ->setCellValue('H'.$row, 'TOTAL PREMIUM') 
                                ->setCellValue('I'.$row, 'APPLICATION DATE');
                                
                        $countD = count($items);
                        $No = 0;
                        for($d=0;$d<$countD;$d++){
                             
                             $row++;
                             $No++;
                             $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloContenido, "A".$row.":I".$row); 
                             $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(20);
                             
                             $PDApply && $items[$d]['iTotalPremiumPD'] > 0 ? $value = "\$ ".number_format($items[$d]['iTotalPremiumPD'],2,'.',',') : $value = "";  
            
                             //Reporte contenido:
                             $objPHPExcel->getActiveSheet()
                                         ->setCellValue('A'.$row, $No)    
                                         ->setCellValue('B'.$row, strtoupper($items[$d]['sVIN'])) 
                                         ->setCellValue('C'.$row, $items[$d]['iYear'])
                                         ->setCellValue('D'.$row, $items[$d]['Make'])
                                         ->setCellValue('E'.$row, $items[$d]['sRadius'])
                                         ->setCellValue('F'.$row, $items[$d]['sTipo'])
                                         ->setCellValue('G'.$row, $items[$d]['sPeso'])
                                         ->setCellValue('H'.$row, $value) 
                                         ->setCellValue('I'.$row, $items[$d]['dFechaIngreso']);
                                         
                             // Aplicar formatos/estilos:
                             $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'A'.$row);
                             $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignL,'B'.$row);
                             $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'C'.$row);
                             $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'D'.$row);
                             $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'E'.$row); 
                             $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'F'.$row);
                             $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'G'.$row);
                             $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignR,'H'.$row);
                             $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'I'.$row); 
                             
                             $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
                             $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true); 
                             $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth('9');
                             $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth('13');
                             $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth('9');
                             $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth('12');
                             $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth('15');
                             $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth('20');
                             $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth('17');
                                 
                        }
                        
                    }
                }
                else{$error = 1; $mensaje = "The data of BIND has not found, please verify if you are uploaded the first list.";} 
            }

            #GENERAR ARCHIVO:
            if($error == 0){
                
                // Set active sheet index to the first sheet, so Excel opens this as the first sheet
                $objPHPExcel->setActiveSheetIndex(0);
                     
                #CREATE A FILE NAME:
                $info_fecha     = getdate();
                $nombre_archivo = strtoupper($DatosCo['sNombreCompania'])."_".$polizaAl."_".$polizaNo;//"_".$info_fecha['year']."_".$info_fecha['month']."_".$info_fecha['mday'];
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
        }
        
    }
    else{$error = 1; $mensaje = "The company does not have configured any policy, please verify it.";}
    
    if($error != 0){
        echo '<script language="javascript">alert(\''.$mensaje.'\')</script>';
        echo "<script language='javascript'>window.close();</script>"; 
    }

    #FUNCIONES EXTRAS:
    function get_estatus($status){
        switch($status){
             case 'S' : $estado = 'SENT TO SOLO-TRUCKING'; break;
             case 'A' : $estado = 'APPLIED TO THE POLICY'; break;
             case 'D' : $estado = 'CANCELED'; break;
             case 'SB': $estado = 'SENT TO BROKERS'; break;
             case 'P' : $estado = 'IN PROCESS'; break;
        }
        return $estado;
    }

?>
