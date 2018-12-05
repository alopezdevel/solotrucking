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
    //Consultar polzias del cliente:
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
            
            #-------------------- EXCEL BEGINS ---------------------------------#
            $objPHPExcel = new PHPExcel();  // Create new PHPExcel object
            
            // Set document properties
            $objPHPExcel->getProperties()->setCreator("Solo-Trucking Insurance System")->setLastModifiedBy("Solo-Trucking Insurance System")
            ->setTitle("Solo-Trucking Insurance On-line Reports")->setSubject("List Drivers/Vehicles")
            ->setDescription("Report of history list of drivers and/or vehicles of a company.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("result file"); 
            
            #ESTILOS 
            $EstiloEncabezado  = new PHPExcel_Style();
            $EstiloEncabezado2 = new PHPExcel_Style();
            $EstiloEncabezado3 = new PHPExcel_Style();
            $EstiloContenido   = new PHPExcel_Style();
            $EstiloEncabezado->applyFromArray(array(
                'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID,'color' => array('argb' => 'FF538DD5')),
                'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN),'color' => array('argb' => 'FF215698')),
                'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER),
                'font' => array('bold' => true)
            ));
            $EstiloEncabezado2->applyFromArray(array(
                'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID,'color' => array('argb' => 'FFF2F2F2')),
                'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN,'color' => array('argb' => 'FFB8B8B8'))),
                'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER),
                'font' => array('color' => array('argb' => 'FF515151'),'size'=> 10,)
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
            if($reporttype == "" || $reporttype == 'all' || $reporttype == '2'){
            
                #GET BIND SHEET:
                $query  = "SELECT B.iConsecutivo, B.sNombre, DATE_FORMAT(B.dFechaNacimiento, '%m/%d/%Y') AS dFechaNacimiento, DATE_FORMAT(B.dFechaExpiracionLicencia, '%m/%d/%Y') AS dFechaExpiracionLicencia,iExperienciaYear,iNumLicencia,(CASE eTipoLicencia WHEN 1 THEN 'Federal / B1' WHEN 2 THEN 'Commercial / CDL - A' END) AS TipoLicencia,
                          DATE_FORMAT(A.dFechaIngreso, '%m/%d/%Y') AS dFechaIngreso, A.iDeleted
                          FROM cb_poliza_operador AS A 
                          LEFT JOIN ct_operadores AS B ON A.iConsecutivoOperador = B.iConsecutivo
                          WHERE A.iConsecutivoPoliza = '$polizaId' AND B.iConsecutivoCompania='$iConsecutivoCompania'  AND A.eModoIngreso='AMIC'
                          ORDER BY B.sNombre ASC"; 
                $result = $conexion->query($query);
                $rows   = $result->num_rows;  
                
                if($rows > 0){
                    
                    // Rename worksheet
                    $objPHPExcel->setActiveSheetIndex(0);
                    $objPHPExcel->getActiveSheet()->setTitle('BIND LIST');
                     
                    $items              = mysql_fetch_all($result);
                    $descripcionReporte = 'Driver bind list from: '.strtoupper($DatosCo['sNombreCompania'])." / $polizaNo / $polizaTy - ".$items[0]['dFechaIngreso'];
                     //Encabezado del reporte.
                     $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado, "A1:G1");
                     $objPHPExcel->getActiveSheet()->setCellValue('A1', 'SOLO-TRUCKING INSURANCE COMPANY');
                     $objPHPExcel->getActiveSheet()->mergeCells("A1:G1");
                     $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(40);
                     //Subtitulo del Reporte:
                     /*$objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado2, "A2:G2");
                     $objPHPExcel->getActiveSheet()->setCellValue('A2', $descripcionReporte);
                     $objPHPExcel->getActiveSheet()->mergeCells("A2:G2");
                     $objPHPExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(25); */
                     
                     //Columnas:
                    /* $row = 3;
                     $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado3, "A".$row.":G".$row);
                     $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(35);
                     $objPHPExcel->getActiveSheet()
                            ->setCellValue('A'.$row, 'NAME')
                            ->setCellValue('B'.$row, 'DOB')
                            ->setCellValue('C'.$row, 'LICENSE #')
                            ->setCellValue('D'.$row, 'LICENSE TYPE')
                            ->setCellValue('E'.$row, 'EXPIRE DATE')
                            ->setCellValue('F'.$row, 'YOE')
                            ->setCellValue('G'.$row, 'APPLICATION DATE');
                            
                     $countD = count($items);
                     for($d=0;$d<$countD;$d++){
                         
                         $row++;
                         $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloContenido, "A".$row.":G".$row); 
                         $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(20);
        
                         //Reporte contenido:
                         $objPHPExcel->getActiveSheet()
                                     ->setCellValue('A'.$row, $items[$d]['sNombre']); 
                                     ->setCellValue('B'.$row, $items[$d]['dFechaNacimiento'])
                                     ->setCellValue('C'.$row, $items[$d]['iNumLicencia'])
                                     ->setCellValue('D'.$row, $items[$d]['TipoLicencia'])
                                     ->setCellValue('E'.$row, $items[$d]['dFechaExpiracionLicencia'])
                                     ->setCellValue('F'.$row, $items[$d]['iExperienciaYear'])
                                     ->setCellValue('G'.$row, $items[$d]['dFechaIngreso']);
                             
                     }
                     */
                     //Ajustar la dimension de las columnas:
                     foreach(range('A','G') as $columnID) {
                        $objPHPExcel->getActiveSheet()->getColumnDimension($columnID)->setAutoSize(true);
                     }
                            
                }else{$error = 1; $mensaje = "The data of BIND has not found, please verify if you are uploaded the first list.";}              
            }

            #GENERAR ARCHIVO:
            if($error == 0){
                
                // Set active sheet index to the first sheet, so Excel opens this as the first sheet
                $objPHPExcel->setActiveSheetIndex(0);
                     
                #CREATE A FILE NAME:
                $info_fecha     = getdate();
                $nombre_archivo = strtoupper($DatosCo['sNombreCompania'])."_".$polizaAl."-".$polizaNo."_".$info_fecha['year']."_".$info_fecha['month']."_".$info_fecha['mday'];
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
    
    if($errror != 0){
        echo '<script language="javascript">alert(\''.$mensaje.'\')</script>';
        echo "<script language='javascript'>window.close();</script>"; 
    }

    #REPORTE UNIDADES
    /*else if($reporttype == "" || $reporttype == 'all' || $reporttype == '1'){
        if($policy == 'all'){
             $query  = "SELECT A.iConsecutivo, C.sAlias AS Make, C.sDescripcion AS sMakeDescription, B.sDescripcion AS Radio, iYear, sVIN, sPeso, ".
                       "sTipo, sModelo, siConsecutivosPolizas, eModoIngreso, iTotalPremiumPD ".
                       "FROM ct_unidades A ".
                       "LEFT JOIN ct_unidad_radio B ON A.iConsecutivoRadio = B.iConsecutivo ".
                       "LEFT JOIN ct_unidad_modelo C ON A.iModelo = C.iConsecutivo ".$filtro_query.$orden_query;
         }else{
             
         }
         $result = $conexion->query($query);
         $rows   = $result->num_rows;
         if($rows > 0){
             
             $descripcionReporte = 'Vehicles list from '.strtoupper($DatosCo['sNombreCompania']);
             //Encabezado del reporte.
             $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado, "A1:I1");
             $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', 'SOLO-TRUCKING INSURANCE COMPANY');
             $objPHPExcel->setActiveSheetIndex(0)->mergeCells("A1:I1");
             $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(40);
            
             //Subtitulo del Reporte:
             $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado2, "A2:I2");
             $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A2', 'Results of the On-line Report: '.$descripcionReporte);
             $objPHPExcel->setActiveSheetIndex(0)->mergeCells("A2:I2");
             $objPHPExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(25);
             
             //Columnas:
             $row = 3;
             $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado3, "A".$row.":I".$row);
             $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(35);
             $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('A'.$row, 'YEAR')
                    ->setCellValue('B'.$row, 'MAKE')
                    ->setCellValue('C'.$row, 'VIN')
                    ->setCellValue('D'.$row, 'RADIUS')
                    ->setCellValue('E'.$row, 'CAPACITY')
                    ->setCellValue('F'.$row, 'TYPE')
                    ->setCellValue('G'.$row, 'TOTAL PREMIUM')
                    ->setCellValue('H'.$row, 'APPLICATION DATE')
                    ->setCellValue('I'.$row, 'IS IN POLICIES');   

             while ($items = $result->fetch_assoc()){
                 $row++;
                 $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloContenido, "A".$row.":I".$row); 
                 $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(20);
                 
                 //Revisar modo ingreso:
                 $modoIngreso = $items['eModoIngreso'];
                
                 if($modoIngreso == 'EXCEL'){$textoIngreso = "AMIC";}else
                 if($modoIngreso == 'ENDORSEMENT'){
                    #CONSULTAR DATOS DEL ENDOSO:
                    $query = "SELECT DATE_FORMAT(A.dFechaAplicacion,'%m/%d/%Y') AS dFechaAplicacion, iConsecutivo ".
                             "FROM cb_endoso AS A ".
                             "WHERE A.iConsecutivoUnidad='".$items['iConsecutivo']."' AND eStatus='A' ORDER BY dFechaAplicacion DESC LIMIT 1";
                    $res   = $conexion->query($query);
                    $endoso= $res->fetch_assoc();
                    $textoIngreso = "END - ".$endoso['dFechaAplicacion'];
                 }
                 
                 //Revisar polizas:
                 $query  = "SELECT iConsecutivoPoliza, B.sNumeroPoliza, C.sDescripcion AS sTipoPoliza, C.sAlias ".
                           "FROM cb_poliza_unidad AS A ".
                           "INNER JOIN ct_polizas   AS B ON A.iConsecutivoPoliza = B.iConsecutivo AND B.iDeleted = '0' AND B.dFechaCaducidad >= CURDATE() ".
                           "LEFT JOIN  ct_tipo_poliza AS C ON B.iTipoPoliza = C.iConsecutivo ".
                           "WHERE A.iConsecutivoUnidad = '".$items['iConsecutivo']."' ";
                 $r      = $conexion->query($query);
                 $total  = $r->num_rows;
                 $polizas= "";
                 $PDApply= false; 
                  
                 if($total > 0){
                    while ($poli = $r->fetch_assoc()){
                       $polizas == "" ? $polizas .= $poli['sNumeroPoliza']." - ".$poli['sAlias'] : $polizas .= " 
                       ".$poli['sNumeroPoliza']." - ".$poli['sAlias']; 
                       if($poli['sAlias'] == "PD"){$PDApply = true;}
                    }
                 }
                 
                 $PDApply && $items['iTotalPremiumPD'] > 0 ? $value = "\$ ".number_format($items['iTotalPremiumPD'],2,'.',',') : $value = "";
                    
                 //Reporte contenido:
                 $objPHPExcel->setActiveSheetIndex(0)
                             ->setCellValue('A'.$row, $items['iYear'])
                             ->setCellValue('B'.$row, $items['Make'])
                             ->setCellValue('C'.$row, $items['sVIN'])
                             ->setCellValue('D'.$row, $items['Radio'])
                             ->setCellValue('E'.$row, $items['sPeso'])
                             ->setCellValue('F'.$row, $items['sTipo'])
                             ->setCellValue('G'.$row, $value)
                             ->setCellValue('H'.$row, $textoIngreso)
                             ->setCellValue('I'.$row, $polizas); 
                 
             }
             
             //Ajustar la dimension de las columnas:
             foreach(range('A','I') as $columnID) {
                $objPHPExcel->getActiveSheet()->getColumnDimension($columnID)->setAutoSize(true);
             }
             // Rename worksheet
             $objPHPExcel->getActiveSheet()->setTitle('DRIVERS');
             // Set active sheet index to the first sheet, so Excel opens this as the first sheet
             $objPHPExcel->setActiveSheetIndex(0);
        
         }    
    } */

?>
