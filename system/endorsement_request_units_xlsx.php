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
  /*error_reporting(E_ALL);
  ini_set('display_errors', TRUE);
  ini_set('display_startup_errors', TRUE); */
  //date_default_timezone_set('Europe/London');

  if (PHP_SAPI == 'cli')
  die('This example should only be run from a Web Browser');

  /** Include PHPExcel */
  require_once dirname(__FILE__) . '/lib/PHPExcel-1.8/Classes/PHPExcel.php';

  session_start();   
  include("cn_usuarios.php");
  include("functiones_genericas.php"); 
  
  #PARAMETERS:
  isset($_GET['reporte_tipo'])   ? $flt_tipo     = urldecode($_GET['reporte_tipo'])    : $flt_tipo = ""; 
  isset($_GET['flt_dateFrom'])   ? $fecha_inicio = urldecode($_GET['flt_dateFrom'])    : $fecha_inicio = "";
  isset($_GET['flt_dateTo'])     ? $fecha_fin    = urldecode($_GET['flt_dateTo'])      : $fecha_fin = "";
  isset($_GET['reporte_company'])? $flt_company  = urldecode($_GET['reporte_company']) : $flt_company = "";
  isset($_GET['reporte_policy']) ? $flt_policy   = urldecode($_GET['reporte_policy'])  : $flt_policy = "";
  isset($_GET['name'])           ? $flt_name     = urldecode($_GET['name'])            : $flt_name = "";
  isset($_GET['reporte_broker']) ? $flt_broker   = urldecode($_GET['reporte_broker'])  : $flt_broker = "";
  
  $fecha_inicio     = substr($fecha_inicio,6,4).'-'.substr($fecha_inicio,0,2).'-'.substr($fecha_inicio,3,2); 
  $fecha_fin        = substr($fecha_fin,6,4).'-'.substr($fecha_fin,0,2).'-'.substr($fecha_fin,3,2);
  $nombre_parametro = "";
  
  // Filtros de informacion //
  $filtroQuery   = "WHERE A.eStatus != 'E' AND iConsecutivoTipoEndoso = '1' AND A.iDeleted='0' ";
  $filtroJoin    = ""; 
  
  // X COMPANIA
  if($flt_tipo == 'company'){ 
    #FILTRO X COMPANIA  
    if($flt_company != ""){
        $filtroQuery    .= "AND A.iConsecutivoCompania='".$flt_company."' ";
        $nombre_parametro= strtolower($flt_name)." - ";
    } 
    #FILTRO X POLIZA
    if($flt_policy != ""){
        $filtroJoin  .= "LEFT JOIN cb_endoso_estatus AS B ON A.iConsecutivo = B.iConsecutivoEndoso ";
        $filtroQuery .= "AND B.iConsecutivoPoliza='".$flt_policy."' ";
    }   
  }
  // X BROKER
  else if($flt_tipo == 'broker'){
     #FILTRO X BROKER:
     if($flt_broker != ""){
        $filtroJoin      .= "LEFT JOIN cb_endoso_estatus AS B ON A.iConsecutivo       = B.iConsecutivoEndoso ".
                            "LEFT JOIN ct_polizas        AS C ON B.iConsecutivoPoliza = C.iConsecutivo ";
        $filtroQuery     .= "AND C.iConsecutivoBrokers='".$flt_broker."' ";
        $nombre_parametro = strtolower($flt_name)." - "; 
     }     
  }
  
  $filtroQuery .= "AND A.dFechaAplicacion BETWEEN '$fecha_inicio' AND '$fecha_fin' ";
  
  // ordenamiento//
  $ordenQuery = " ORDER BY A.dFechaAplicacion DESC ";
  
  // Contando Registros //
  $query = "SELECT A.iConsecutivo, DATE_FORMAT(A.dFechaAplicacion,'%m/%d/%Y') AS dFechaAplicacion FROM cb_endoso AS A ".$filtroJoin.$filtroQuery.$ordenQuery;
  $result= $conexion->query($query);   
  $rows  = $result->num_rows; 

  if($rows == 0){
    $mensaje = "No results found.";
    echo '<script language="javascript">alert(\''.$mensaje.'.\')</script>'; 
    echo "<script language='javascript'>window.close();</script>";
  }
  else{ 

      #DATOS DEL DETALLE DEL REPORTE
      #----------------------------------------------- EXCEL BEGINS ---------------------------------------------------------------#
        $objPHPExcel = new PHPExcel();  // Create new PHPExcel object
        
        // Set document properties
        $objPHPExcel->getProperties()
        ->setCreator("Solo-Trucking Insurance System")
        ->setLastModifiedBy("Solo-Trucking Insurance System")
        ->setTitle("Solo-Trucking Insurance On-line Reports")
        ->setSubject("Endorsements")
        ->setDescription("Report of endorsement registered in the system.")
        ->setKeywords("office 2007 openxml php")
        ->setCategory("result file"); 
      
        #ESTILOS 
        $EstiloEncabezado = new PHPExcel_Style();
        $EstiloEncabezado->applyFromArray(array(
            'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID,'color' => array('argb' => 'FF538DD5')),
            'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN),'color' => array('argb' => 'FF215698')),
            'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER),
            'font' => array('bold' => true)
        ));
        $EstiloEncabezado2 = new PHPExcel_Style();
        $EstiloEncabezado2->applyFromArray(array(
            'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID,'color' => array('argb' => 'FFF2F2F2')),
            'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN,'color' => array('argb' => 'FFB8B8B8'))),
            'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER),
            'font' => array('color' => array('argb' => 'FF515151'),'size'=> 10,)
        ));
        $EstiloEncabezado3 = new PHPExcel_Style();
        $EstiloEncabezado3->applyFromArray(array(
            'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID,'color' => array('argb' => 'FF8FB1DB')),
            'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN,'color' => array('argb' => 'FF6489b5'))),
            'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER),
            'font' => array('bold' => true)
        ));
        $EstiloContenido = new PHPExcel_Style();
        $EstiloContenido->applyFromArray(array(
            'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN,'color' => array('argb' => 'FFababab'))),
            'alignment' => array('vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER),
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
        $EstiloAlignR = new PHPExcel_Style();
        $EstiloAlignR->applyFromArray(array(
            'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT),
            'borders'   => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN,'color' => array('argb' => 'FFababab'))),
        ));
        
        //Encabezado del reporte.
        $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado, "A1:K1");
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', 'SOLO-TRUCKING INSURANCE COMPANY');
        $objPHPExcel->setActiveSheetIndex(0)->mergeCells("A1:K1");
        $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(40);
        
        //Subtitulo del Reporte:
        $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado2, "A2:K2");
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A2', 'Results of the On-line Report: '.date('m/d/Y',strtotime($fecha_inicio))." - ".date('m/d/Y',strtotime($fecha_fin))." ");
        $objPHPExcel->setActiveSheetIndex(0)->mergeCells("A2:K2");
        $objPHPExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(25);
        
        //Columnas:
        $row = 3;
        $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloEncabezado3, "A".$row.":K".$row);
        $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(35);
        $objPHPExcel->setActiveSheetIndex(0)
        ->setCellValue('A'.$row, 'COMPANY NAME')
        ->setCellValue('B'.$row, 'ACTION')
        ->setCellValue('C'.$row, 'YEAR')
        ->setCellValue('D'.$row, 'MAKE')
        ->setCellValue('E'.$row, 'VIN')
        ->setCellValue('F'.$row, 'RADIUS')
        ->setCellValue('G'.$row, 'WEIGHT')
        ->setCellValue('H'.$row, 'TYPE')
        ->setCellValue('I'.$row, 'PD AMOUNT')
        ->setCellValue('J'.$row, 'APP DATE')
        ->setCellValue('K'.$row, 'POLICIES'); 
        
        //Revisamos si hay que filtrar por broker a nivel polizas:
        $filtro_detalle = "";
        if($flt_tipo == 'broker' && $flt_broker != ""){
             $filtro_detalle .= "AND B.iConsecutivoBrokers='".$flt_broker."' ";
        }
        else if($flt_tipo == 'company' && $flt_policy != ""){ 
             $filtro_detalle .= "AND A.iConsecutivoPoliza='".$flt_policy."' ";
        }
        
        $endoso = mysql_fetch_all($result);
        $contador = count($endoso);
        
        for($x=0;$x<$contador;$x++){
            $iConsecutivo = $endoso[$x]['iConsecutivo']; 
            $polizas      = ""; 
            
            #CONSULTAR DATOS DE LA POLIZA(S)
            $query = "SELECT  A.iConsecutivoPoliza, B.sNumeroPoliza, D.sAlias, C.sName AS 'sBroker', A.dFechaAplicacion
                      FROM      cb_endoso_estatus AS A
                      LEFT JOIN ct_polizas        AS B ON A.iConsecutivoPoliza   = B.iConsecutivo AND B.iDeleted = '0'
                      LEFT JOIN ct_brokers        AS C ON B.iConsecutivoBrokers  = C.iConsecutivo
                      LEFT JOIN ct_tipo_poliza    AS D ON B.iTipoPoliza          = D.iConsecutivo
                      WHERE A.iConsecutivoEndoso = '$iConsecutivo' ".$filtro_detalle;
            $r     = $conexion->query($query);  
            
            if($r->num_rows > 0){
                while ($poli = $r->fetch_assoc()){
                    $polizadesc = $poli['sNumeroPoliza']." - ".$poli['sBroker']." (".$poli['sAlias'].")";
                    $polizas   == "" ? $polizas.= $polizadesc : $polizas.="       |       ".$polizadesc;
                }
            }
            
            #CONSULTAR DATOS DEL OPERADOR/UNIDAD
            $query  = "SELECT C.iConsecutivo AS iConsecutivoCompania, C.sNombreCompania, iConsecutivoUnidad, eAccion, B.sVIN, D.sDescripcion AS sRadio, 
                       A.iTotalPremiumPD, B.iYear, E.sAlias AS sModelo, B.sTipo, B.sPeso
                       FROM      cb_endoso_unidad   AS A
                       LEFT JOIN ct_unidades        AS B ON A.iConsecutivoUnidad   = B.iConsecutivo
                       LEFT JOIN ct_companias       AS C ON B.iConsecutivoCompania = C.iConsecutivo AND C.iDeleted ='0'
                       LEFT JOIN ct_unidad_radio    AS D ON A.iConsecutivoRadio    = D.iConsecutivo
                       LEFT JOIN ct_unidad_modelo   AS E ON B.iModelo              = E.iConsecutivo 
                       WHERE A.iConsecutivoEndoso = '$iConsecutivo'";
            $r      = $conexion->query($query); 
            $rows   = $result->num_rows; 
            
            while ($detalle = $r->fetch_assoc()) {

                $row++;
                $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloContenido, "A".$row.":J".$row); 
                $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(20);
                
                $detalle['iTotalPremiumPD'] > 0 ? $value = "\$ ".number_format($detalle['iTotalPremiumPD'],2,'.',',') : $value = ""; 
                if($detalle['eAccion'] == "ADDSWAP"){$action = "ADD SWAP";}else
                if($detalle['eAccion'] == "DELETESWAP"){$action = "DELETE SWAP";}
                else{$action = $detalle['eAccion'];}
                
                //Reporte contenido:
                $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A'.$row, $detalle['sNombreCompania'])
                ->setCellValue('B'.$row, $action)
                ->setCellValue('C'.$row, $detalle['iYear'])
                ->setCellValue('D'.$row, $detalle['sModelo'])
                ->setCellValue('E'.$row, $detalle['sVIN'])
                ->setCellValue('F'.$row, $detalle['sRadio'])
                ->setCellValue('G'.$row, $detalle['sPeso'])
                ->setCellValue('H'.$row, $detalle['sTipo'])
                ->setCellValue('I'.$row, $value)
                ->setCellValue('J'.$row, $endoso[$x]['dFechaAplicacion'])
                ->setCellValue('K'.$row, $polizas);

                
                // Aplicar formatos/estilos:
                $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'C'.$row);
                $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'F'.$row);
                $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignC,'G'.$row);
                $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignR,'H'.$row);
                $objPHPExcel->getActiveSheet()->setSharedStyle($EstiloAlignL,'E'.$row);
                
                $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth('27');

            }   
        }
        
        //Ajustar la dimension de las columnas:
        foreach(range('A','K') as $columnID) {
            $objPHPExcel->getActiveSheet()->getColumnDimension($columnID)->setAutoSize(true);
        }   
        
        // Rename worksheet
        $objPHPExcel->getActiveSheet()->setTitle('ENDORSEMENTS OF DRIVERS');
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $objPHPExcel->setActiveSheetIndex(0); 

        #CREATE A FILE NAME:
        $nombre_archivo = 'Report_of_endorsements_from_'.$fecha_inicio."_to_".$fecha_fin;
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
  
?>
