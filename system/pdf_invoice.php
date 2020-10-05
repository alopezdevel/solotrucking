<?php
    
    isset($envio_correo) ? $envio_correo = 1 : $envio_correo = 0;
    
    if($envio_correo == 0){
        session_start();
        include("cn_usuarios.php"); 
        include("functiones_genericas.php");
    }  
     
    require_once('lib/fpdf153/fpdf.php'); 
    
    // PARAMETROS GET:
    if($envio_correo == 0){
        isset($_GET['id']) ? $folio = urldecode($_GET['id']) : $folio = "";
        isset($_GET['ds']) ? $ds    = strtoupper(urldecode($_GET['ds'])) : $ds    = "";    
    } 
    
    $error        = false; 
    $folio        = preg_replace("/%u([0-9a-f]{3,4})/i","&#x\\1;",urldecode($folio)); 
    $folio        = html_entity_decode($folio,null,'UTF-8');
  
    if($folio == "" || $folio == null){$error = true; $mensaje = "Error when consulting the company data, please try again.";}
    else{
        #CONSULTAR INVOICE:
        $sql    = "SELECT iConsecutivo, iConsecutivoCompania,sNoReferencia, DATE_FORMAT(dFechaInvoice,'%m/%d/%Y') AS dFechaInvoice,sReceptorNombre, sReceptorDireccion,sEmisorNombre, sLugarExpedicionDireccion, ".
                  "dSubtotal,rPDescuento,eStatus, dDescuento, sMotivosDescuento, dPctTax, dTax, dTotal, dAnticipo, dBalance, sCveMoneda, dTipoCambio, sComentarios ".
                  "FROM cb_invoices WHERE iConsecutivo = '".$folio."'";
        $result = $conexion->query($sql);
        $rows   = $result->num_rows;    
        if($rows == 0){ $error = true; $mensaje = "Error when consulting the invoice data, please try again.";}
        else{
           $invoice    = $result->fetch_assoc(); 
           $compania   = $invoice['iConsecutivoCompania'];
           $nombre_pdf = "solo-trucking-invoice_".$invoice['sNoReferencia'];
           
        }
    }
    
    #PDF CLASS
    class VariableStream {
        var $varname;
        var $position;

        function stream_open($path, $mode, $options, &$opened_path){
            $url = parse_url($path);
            $this->varname = $url['host'];
            if(!isset($GLOBALS[$this->varname]))
            {
                trigger_error('Global variable '.$this->varname.' does not exist', E_USER_WARNING);
                return false;
            }
            $this->position = 0;
            return true;
        }
        function stream_read($count){
            $ret = substr($GLOBALS[$this->varname], $this->position, $count);
            $this->position += strlen($ret);
            return $ret;
        }
        function stream_eof(){
            return $this->position >= strlen($GLOBALS[$this->varname]);
        }
        function stream_tell(){
            return $this->position;
        }
        function stream_seek($offset, $whence){
            if($whence==SEEK_SET)
            {
                $this->position = $offset;
                return true;
            }
            return false;
        }
        function stream_stat(){
            return array();
        }
    }  
    
    class PDF extends FPDF  {

        function PDF($orientation='P', $unit='mm', $format='Letter'){
            $this->FPDF($orientation, $unit, $format);
            //Register var stream protocol
            stream_wrapper_register('var', 'VariableStream');
        }
        function MemImage($data, $x=null, $y=null, $w=0, $h=0, $link=''){
            //Display the image contained in $data
             $v = 'img'.md5($data);
             $GLOBALS[$v] = $data;
             $a = getimagesize('var://'.$v);
             if(!$a)
                $this->Error('Invalid image data');
                $type = substr(strstr($a['mime'],'/'),1);
                $this->Image('var://'.$v, $x, $y, $w, $h, $type, $link);
                unset($GLOBALS[$v]);
        }
        function GDImage($im, $x=null, $y=null, $w=0, $h=0, $link=''){
            //Display the GD image associated to $im
            ob_start();
            imagepng($im);
            $data = ob_get_clean();
            $this->MemImage($data, $x, $y, $w, $h, $link);
         }
        function RoundedRect($x, $y, $w, $h, $r, $corners = '1234', $style = '') {
            $k = $this->k;
            $hp = $this->h;
            if($style=='F')
                $op='f';
            elseif($style=='FD' || $style=='DF')
                $op='B';
            else
                $op='S';
            $MyArc = 4/3 * (sqrt(2) - 1);
            $this->_out(sprintf('%.2F %.2F m',($x+$r)*$k,($hp-$y)*$k ));

            $xc = $x+$w-$r;
            $yc = $y+$r;
            $this->_out(sprintf('%.2F %.2F l', $xc*$k,($hp-$y)*$k ));
            if (strpos($corners, '2')===false)
                $this->_out(sprintf('%.2F %.2F l', ($x+$w)*$k,($hp-$y)*$k ));
            else
                $this->_Arc($xc + $r*$MyArc, $yc - $r, $xc + $r, $yc - $r*$MyArc, $xc + $r, $yc);

            $xc = $x+$w-$r;
            $yc = $y+$h-$r;
            $this->_out(sprintf('%.2F %.2F l',($x+$w)*$k,($hp-$yc)*$k));
            if (strpos($corners, '3')===false)
                $this->_out(sprintf('%.2F %.2F l',($x+$w)*$k,($hp-($y+$h))*$k));
            else
                $this->_Arc($xc + $r, $yc + $r*$MyArc, $xc + $r*$MyArc, $yc + $r, $xc, $yc + $r);

            $xc = $x+$r;
            $yc = $y+$h-$r;
            $this->_out(sprintf('%.2F %.2F l',$xc*$k,($hp-($y+$h))*$k));
            if (strpos($corners, '4')===false)
                $this->_out(sprintf('%.2F %.2F l',($x)*$k,($hp-($y+$h))*$k));
            else
                $this->_Arc($xc - $r*$MyArc, $yc + $r, $xc - $r, $yc + $r*$MyArc, $xc - $r, $yc);

            $xc = $x+$r ;
            $yc = $y+$r;
            $this->_out(sprintf('%.2F %.2F l',($x)*$k,($hp-$yc)*$k ));
            if (strpos($corners, '1')===false) {
                $this->_out(sprintf('%.2F %.2F l',($x)*$k,($hp-$y)*$k ));
                $this->_out(sprintf('%.2F %.2F l',($x+$r)*$k,($hp-$y)*$k ));
            }  else
                $this->_Arc($xc - $r, $yc - $r*$MyArc, $xc - $r*$MyArc, $yc - $r, $xc, $yc - $r);
            $this->_out($op);
        }
        function _Arc($x1, $y1, $x2, $y2, $x3, $y3)  {
            $h = $this->h;
            $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c ', $x1*$this->k, ($h-$y1)*$this->k,
                $x2*$this->k, ($h-$y2)*$this->k, $x3*$this->k, ($h-$y3)*$this->k));
        }
        function Rotate($angle,$x=-1,$y=-1){
            if($x==-1)$x=$this->x;
            if($y==-1)$y=$this->y;
            if($this->angle!=0)$this->_out('Q');
            $this->angle=$angle;
            if($angle!=0){
                $angle*=M_PI/180;
                $c=cos($angle);
                $s=sin($angle);
                $cx=$x*$this->k;
                $cy=($this->h-$y)*$this->k;
                $this->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',$c,$s,-$s,$c,$cx,$cy,-$cx,-$cy));
            }
        }
        function RotatedText($x, $y, $txt, $angle){
            //Text rotated around its origin
            $this->Rotate($angle,$x,$y);
            $this->Text($x,$y,$txt);
            $this->Rotate(0);
        }

        var $extgstates = array();
        function SetAlpha($alpha, $bm='Normal'){
            // set alpha for stroking (CA) and non-stroking (ca) operations
            $gs = $this->AddExtGState(array('ca'=>$alpha, 'CA'=>$alpha, 'BM'=>'/'.$bm));
            $this->SetExtGState($gs);
        }
        function AddExtGState($parms){
            $n = count($this->extgstates)+1;
            $this->extgstates[$n]['parms'] = $parms;
            return $n;
        }
        function SetExtGState($gs){
            $this->_out(sprintf('/GS%d gs', $gs));
        }
        function _enddoc(){
            if(!empty($this->extgstates) && $this->PDFVersion<'1.4')
                $this->PDFVersion='1.4';
            parent::_enddoc();
        }
        function _putextgstates(){
            for ($i = 1; $i <= count($this->extgstates); $i++)
            {
                $this->_newobj();
                $this->extgstates[$i]['n'] = $this->n;
                $this->_out('<</Type /ExtGState');
                $parms = $this->extgstates[$i]['parms'];
                $this->_out(sprintf('/ca %.3F', $parms['ca']));
                $this->_out(sprintf('/CA %.3F', $parms['CA']));
                $this->_out('/BM '.$parms['BM']);
                $this->_out('>>');
                $this->_out('endobj');
            }
        }
        function _putresourcedict(){
            parent::_putresourcedict();
            $this->_out('/ExtGState <<');
            foreach($this->extgstates as $k=>$extgstate)
                $this->_out('/GS'.$k.' '.$extgstate['n'].' 0 R');
            $this->_out('>>');
        }
        function _putresources(){
            $this->_putextgstates();
            parent::_putresources();
        }

        var $DatosFactura;
        var $ConfiguracionSeleccionado;
        var $DatosFacturaDetalle;
        var $DatosReceptor;
        var $CurrentY;
        var $DetailY;
        var $hdetail = 165;
        var $ytot;
        var $FooterY;
        var $Correo_R;

        var $SecY;
        var $DetailHeight;
        var $TotX;
        var $SymbolX;
        var $TotalsX;
        var $limit   = 200;
        var $CantX   = 122;
        var $ImpUX   = 144;
        var $IVAX    = 167;
        var $ImpTotX = 180;
        var $ImprimeTC;
        var $MostrarTotales = 1;
        var $TAX;
        var $decimales = 2;
        var $FolioImpreso;
        var $OpcionesImpresion = array("Emisor"=>array("RazonSocial"=>true,"DomicilioFiscal"=>true),"Receptor"=>array("RazonSocial"=>true, "DomicilioFiscal"=>true));
        var $colores=array(0,0,0);
        var $display;
        var $conexion;

        function init($folio, $invoice, $compania, $conexion, $ds){

           $this->display      = $ds;
           $this->DatosFactura = $invoice;
           $this->conexion     = $conexion;
           
           #Datos de configuracion del Emisor:
           $sql    = "SELECT * FROM ct_empresa WHERE iConsecutivo IS NOT NULL LIMIT 1";
           $result = $conexion->query($sql);
           $this->ConfiguracionSeleccionado = $result->fetch_array();
          
           #Datos del Cliente:
           $sql    = "SELECT iConsecutivo,sNombreCompania,sUsdot,sDireccion,sCiudad,sEstado,sCodigoPostal, sEmailContacto ".
                     "FROM ct_companias WHERE iConsecutivo = '".$compania."'";
           $result = $conexion->query($sql);
           $this->DatosReceptor = $result->fetch_array();

           #Factura Detalle:
           $sql    = "SELECT iConsecutivoDetalle, iConsecutivoServicio, sClave, sDescripcion,iMostrarEndorsements, sCveUnidadMedida, iCantidad, iPrecioUnitario, iPctImpuesto, iImpuesto, iPrecioExtendido, iEndorsementsApply, sComentarios ".
                     "FROM cb_invoice_detalle WHERE iConsecutivoInvoice = '".$folio."'";
           $result = $conexion->query($sql);
           $this->DatosFacturaDetalle= mysql_fetch_all($result);
           
           #Otras Variables:
           $this->decimales = $this->ConfiguracionSeleccionado["iDecimales"];

        }

        function pdf_color_define(){
           
           $colorPDF    = $this->ConfiguracionSeleccionado["sColorPdf"];
           $arrRGBColor = explode(",", $colorPDF);
           $this->SetFillColor(intval($arrRGBColor[0]), intval($arrRGBColor[1]), intval($arrRGBColor[2]));
           $this->SetDrawColor(intval($arrRGBColor[0]), intval($arrRGBColor[1]), intval($arrRGBColor[2]));
           
        }
        
        function pdf_font_define(){
            $colorPDF    = $this->ConfiguracionSeleccionado["sColorPdf"];
            $arrRGBColor = explode(",", $colorPDF);
            $this->SetTextColor(intval($arrRGBColor[0]), intval($arrRGBColor[1]), intval($arrRGBColor[2]));
              
        }

        function pdf_header(){

            $inv_date  = $this->DatosFactura['dFechaInvoice'];
            $inv_number= $this->DatosFactura['sNoReferencia'];
            
            #Datos Emisor: 
            $emisor_nombre = "";
            if($this->ConfiguracionSeleccionado["eIncluirNombreAlias"] != 'NONE'){
               switch($this->ConfiguracionSeleccionado["eIncluirNombreAlias"]){
                   case 'COMP'  : $emisor_nombre = $this->ConfiguracionSeleccionado['sNombreCompleto']; break;
                   case 'ALIAS' : $emisor_nombre = $this->ConfiguracionSeleccionado['sAlias']; break;
               }    
            }
            
            $emisor_direccion = "";
            $emisor_ciudad    = "";
            if($this->ConfiguracionSeleccionado["eLugarExpedicion"] == 'SI'){
                
                $emisor_direccion = $this->ConfiguracionSeleccionado['sCalle'];
                if($this->ConfiguracionSeleccionado['sNumExterior'] != ""){ $emisor_direccion .= " Suite. ".$this->ConfiguracionSeleccionado['sNumExterior'];}
                
                if($this->ConfiguracionSeleccionado['sCiudad']       != ""){$emisor_ciudad = $this->ConfiguracionSeleccionado['sCiudad'];}
                if($this->ConfiguracionSeleccionado['sCveEntidad']   != ""){$emisor_ciudad != "" ? $emisor_ciudad .= " ".$this->ConfiguracionSeleccionado['sCveEntidad'] : $emisor_ciudad = $this->ConfiguracionSeleccionado['sCveEntidad'];}
                if($this->ConfiguracionSeleccionado['sCodigoPostal'] != ""){$emisor_ciudad != "" ? $emisor_ciudad .= " ".$this->ConfiguracionSeleccionado['sCodigoPostal'] : $emisor_ciudad = $this->ConfiguracionSeleccionado['sCodigoPostal'];}
                
            }
            
            #Datos Receptor:
            $receptor_nombre    = $this->DatosReceptor['sNombreCompania'];
            $receptor_direccion = $this->DatosReceptor['sDireccion'];
            $receptor_direccion2= $this->DatosReceptor['sCiudad'].", ".$this->DatosReceptor['sEstado']." ".$this->DatosReceptor['sCodigoPostal'];
            
            #Logo
            /*$logo  = $this->ConfiguracionSeleccionado['hLogoEmpresa'];
            $h     = $this->ConfiguracionSeleccionado['iLogoH'];
            $w     = $this->ConfiguracionSeleccionado['iLogoW'];
            if(!empty($logo)){$this->MemImage($logo,8,11,$w,$h,'');}*/

            $this->Image('images/img-logo.jpg',10,10,50,0,'JPG');

            #PDF:
            $xh = 60;
            $yh = 12;
            
            //IZQUIERDA
            $this->SetFont('Arial', 'B', 11);
            $this->SetTextColor(0,0,0);
            $this->SetXY($xh, $yh);
            $this->Cell(70, 3, $emisor_nombre, 0, 0, 'J');
            
            $yh+= 4;
            //Imprimir direccion:
            if($this->ConfiguracionSeleccionado["eLugarExpedicion"] == 'SI'){
                $this->SetFont('Arial', '', 10); 
                $this->SetXY($xh, $yh);
                $this->Cell(70, 3, $emisor_direccion, 0, 0, 'J');
                
                if($emisor_ciudad != ""){
                    $yh+= 3;
                    $this->SetXY($xh, $yh);
                    $this->Cell(70, 3, $emisor_ciudad, 0, 0, 'J');    
                }
              
            }
            
            $xh = 10;
            $yh+= 15;
            
            $this->SetFont('Arial', 'B', 10);
            $this->SetTextColor(0,0,0);
            $this->SetXY($xh, $yh);
            $this->Cell(70, 3, 'BILL TO :', 0, 0, 'J');
            $yh+= 5;
            $this->SetFont('Arial', '', 11);
            $this->SetXY($xh+1, $yh);
            $this->Cell(70, 3, utf8_decode($receptor_nombre), 0, 0, 'J');
            
            $yh+= 4.5;
            $this->SetFont('Arial', '', 10);
            $this->SetXY($xh+1, $yh);
            $this->Cell(70, 3, utf8_decode($receptor_direccion), 0, 0, 'J');
            $yh+= 3;
            $this->SetXY($xh+1, $yh);
            $this->Cell(70, 3, utf8_decode($receptor_direccion2), 0, 0, 'J');
            
            //DERECHA
            $xR = 130;
            $yR = 13;
            $this->SetFont('Arial', 'B', 20);
            $this->SetXY($xR, $yR);
            $this->Cell(70, 7, 'INVOICE', 0, 0, 'R');
            
            $yR += 12;
            $xR  = 160;
            $this->SetFont('Arial', '', 10); 
            $this->SetXY($xR, $yR);
            $this->Cell(23, 4, 'INVOICE # : ', 0, 0, 'J');
            $this->SetXY($xR+23, $yR);
            $this->Cell(25, 4, $inv_number, 0, 0, 'J');
            
            $yR += 5;
            $this->SetXY($xR, $yR);
            $this->Cell(23, 4, 'DATE : ', 0, 0, 'J');
            $this->SetXY($xR+23, $yR);
            $this->Cell(25, 4, $inv_date, 0, 0, 'J');
            
            #PRODUCTOS / SERVICIOS TABLA:
            $this->pdf_color_define();
            $yD = 60;
            $this->RoundedRect(10,$yD, 190, $this->hdetail, 1, '12', 'D');
            $this->RoundedRect(10,$yD, 190, 7, 1, '12', 'F');
            
            $yD += 0.5;
            $this->SetTextColor(255,255,255);
            $this->SetFont('Arial', '', 9);
            $this->SetXY(10, $yD);
            $this->Cell(25,6,"Quantity",0,0,'C');
            $this->SetXY(35, $yD);
            $this->Cell(105,6,"Description",0,0,'C');
            $this->SetXY(140, $yD);
            $this->Cell(30,6,"Unit Price",0,0,'C');
            $this->SetXY(170, $yD);
            $this->Cell(30,6,"Total",0,0,'C');
            
            //lineas divisoras:
            $yD += 6;
            $this->Line(35, $yD, 35, 225);
            $this->Line(140, $yD, 140, 225);
            $this->Line(170, $yD, 170, 225);
            
            $this->CurrentY = $yD;
            
            $yD += 160;
            $this->SetTextColor(0,0,0);
            $this->SetXY(140, $yD);
            $this->Cell(25,3,"Subtotal",0,0,'R');
            
            $this->SetXY(140, $yD+5);
            $this->Cell(25,3,"Sales Tax",0,0,'R');
            
            $this->SetFont('Arial', 'B', 9);
            $this->SetXY(140, $yD+10);
            $this->Cell(25,3,"Total",0,0,'R');
            
            $yD += 28;
            $this->SetTextColor(79,79,79);
            $this->SetFont('Arial', '', 10);
            $this->SetXY(10, $yD); 
            $this->Cell(190,3,"Make all checks payable to ".$emisor_nombre,0,0,'C');
            
            $yD += 5;
            $this->pdf_font_define();
            $this->SetFont('Arial', '', 10);
            $this->SetXY(10, $yD); 
            $this->Cell(190,3,"THANK YOU FOR YOUR BUSINESS!",0,0,'C');
            
        }

        function pdf_detail(){
         
            $limit_page = $this->limit;
            
            $this->SetFont('Arial', '', 10);
            $this->SetTextColor(0,0,0);
            
            if( $this->MostrarTotales == 0 ) {$limit_page += 35;}

            $this->DetailY = $this->CurrentY+2;
            $y             = $this->DetailY;
            $total_detalle = count($this->DatosFacturaDetalle);

            for($w=0; $w < $total_detalle; $w++) {
                
                $pCantidad = $this->DatosFacturaDetalle[$w]['iCantidad'];
                $pDescrip  = $this->DatosFacturaDetalle[$w]['sClave']." - ".$this->DatosFacturaDetalle[$w]['sDescripcion'];
                $pUnitPrice= number_format($this->DatosFacturaDetalle[$w]["iPrecioUnitario"],$this->decimales,'.',',');
                $pTotPrice = number_format(($pUnitPrice*$pCantidad),$this->decimales,'.',',');
                $pTax      = number_format($this->DatosFacturaDetalle[$w]["iImpuesto"],$this->decimales,'.',',');
                
                if($this->DatosFacturaDetalle[$w]['iMostrarEndorsements'] == 1 && $this->DatosFacturaDetalle[$w]['iEndorsementsApply'] == 1){
                    $query = "SELECT A.iConsecutivo,A.iConsecutivoTipoEndoso,DATE_FORMAT( A.dFechaAplicacion, '%m/%d/%Y' ) AS dFechaAplicacion,C.sDescripcion,A.eStatus,eAccion, A.sVINUnidad AS sVIN,A.sNombreOperador AS sNombre, A.iEndosoMultiple ".
                             "FROM       cb_endoso                 AS A ".
                             "INNER JOIN cb_invoice_detalle_endoso AS B ON B.iConsecutivoEndoso = A.iConsecutivo ".
                             "LEFT  JOIN ct_tipo_endoso            AS C ON A.iConsecutivoTipoEndoso = C.iConsecutivo ".
                             "WHERE B.iConsecutivoDetalle='".$this->DatosFacturaDetalle[$w]['iConsecutivoDetalle']."' AND A.eStatus != 'E' AND A.eStatus != 'S' AND A.iDeleted='0' ".
                             "ORDER BY LEFT(A.dFechaAplicacion,10) DESC";
                    $result = $this->conexion->query($query);
                    $rows   = $result->num_rows;   
                    
                    if($rows > 0){
                        while($items = $result->fetch_assoc()){ 
                            
                            if($items['iConsecutivoTipoEndoso'] == '1'){
                                 $tipo = "VEHICLE";
                                 #CONSULTAR DETALLE DEL ENDOSO:
                                 $query = "SELECT A.sVIN, (CASE 
                                            WHEN A.eAccion = 'ADDSWAP'    THEN 'ADD SWAP'
                                            WHEN A.eAccion = 'DELETESWAP' THEN 'DELETE SWAP'
                                            WHEN A.eAccion = 'CHANGEPD'   THEN 'CHANGE PD'
                                            ELSE A.eAccion
                                            END) AS eAccion FROM cb_endoso_unidad AS A WHERE A.iConsecutivoEndoso = '".$items['iConsecutivo']."' ORDER BY sVIN ASC";
                                 $r     = $this->conexion->query($query);
                                 
                                 while($item = $r->fetch_assoc()){
                                    $endosos == "" ? $endosos .= $item['eAccion']." ".$tipo." ".$item['sVIN'] : $endosos .= "\n".$item['eAccion']." ".$tipo." ".$item['sVIN']; 
                                 }
                                    
                            }
                            else{
                                 $tipo = "DRIVER";
                                 #CONSULTAR DETALLE DEL ENDOSO:
                                 $query = "SELECT A.sNombre, (CASE 
                                            WHEN A.eAccion = 'ADDSWAP'    THEN 'ADD SWAP'
                                            WHEN A.eAccion = 'DELETESWAP' THEN 'DELETE SWAP'
                                            ELSE A.eAccion
                                            END) AS eAccion FROM cb_endoso_operador AS A WHERE A.iConsecutivoEndoso = '".$items['iConsecutivo']."' ORDER BY sNombre ASC";
                                 $r     = $this->conexion->query($query);
                                 
                                 while($item = $r->fetch_assoc()){
                                    $endosos == "" ?  $endosos .= $item['eAccion']." ".$tipo." ".$item['sNombre'] : $endosos .= "\n".$item['eAccion']." ".$tipo." ".$item['sNombre'];
                                 }
                            }
                            
                            
                        }
                    } 
                }
                else{$endosos = "";}
                  
                $this->SetFontSize(8);
                $this->SetXY(10, $y);
                $this->Cell(25, 6, $pCantidad, 0, 0, 'C');
                $this->SetXY(35, $y);
                $this->MultiCell(105, 6, $pDescrip, 0, 'J',false);
                $this->SetXY(140, $y);
                $this->Cell(30,6,"\$ ".$pUnitPrice,0,0,'R');
                $this->SetXY(170, $y);
                $this->Cell(30,6,"\$ ".$pTotPrice,0,0,'R');
                
                $this->SetFontSize(6);
                $this->SetXY(35, $y+4);
                $this->MultiCell(105,4, $endosos, 0, 'J',false);
                
                $this->TAX += $pTax;
                if($endosos != ""){$y += 4;}
                
                $y += 6;
            } 

        
            // Ya termin? de imprimir items.
            $w = 0;

            // Determinar si hay espacio para los totales en la ?ltima p?gina.
            /*if( $this->MostrarTotales == 0 ) {

                // Averiguar si hay espacio en la ?ltima p?gina.
                $iva_11      = $this->IVA11;
                $iva_16      = $this->IVA16;
                $iva_5       = $this->IVA5;

                $TipoMoneda  = $this->DatosFactura["Moneda"];
                $MonedaDesc  = $this->DescMoneda['sDescripcion'];
                $TipoCambio  = number_format(gpcround(($this->DatosFactura["sTipoCambio"]),2),2,'.','');
                $ye          = $this->SecY;
                $Comentarios = trim($this->DatosFactura['sComentarios']);
                $Plazo       = $this->DatosFactura['Plazo'];

                if ($Plazo>0) { $MsgPlazo = "Esta factura deberá ser pagada en un plazo de $Plazo días"; $ye-=3;}
                if ($TipoCambio > 0  && $TipoMoneda !="MXP" && $TipoMoneda !="MXN" && $TipoMoneda !="XXX" && $this->ImprimeTC=='SI'){

                    $ImpTipoCambio  = "Tipo de Cambio: \$ $TipoCambio";
                    $ImpDescTCambio = "Moneda: $TipoMoneda - $MonedaDesc";
                    $y-=4;
                }
                if (utf8_decode($Comentarios) AND $Comentarios != "NULL" AND $this->EsquemaFacturacion<>2) {
                    $lCom = strlen($Comentarios);
                    $RengCom = ($lCom-($lCom % 75))/75;
                    if (($lCom % 75) > 0 ){ $RengCom++;}
                    $CalReng = $RengCom * 3;
                    $ye -= $CalReng;
                    $ye+=2;
                    $ye+= $CalReng;
                    $this->RengCom = $CalReng;
                }
                if (strlen(utf8_decode($ImpTipoCambio)) > 0) {
                    $ye+=3;

                }
                if (strlen(utf8_decode($Plazo)) > 0) {$ye+=3;}

                $ye += 5;
                $importe_con_letra = getCantidadDineroEnLetra(number_format(gpcround($this->DatosFactura["rTotal"],$this->decimalesfijos),$this->decimalesfijos,'.',''), $TipoMoneda);
                #$this->MultiCell(100, 4, $importe_con_letra, 0, 'L');
                if ($this->DatosFactura["rDescuento"]<>0)      { $ye += -15;} else { $ye +=- 5;}
                if ($this->DatosFactura["rRetencionesIVA"]<>0) { $ye += -5;}
                if ($this->DatosFactura["rRetenciones"]<>0)    { $ye += -5; }
                if($iva_11 == 0 && $iva_16 == 0 && $iva_5==0 && $iva_0==0){ $ye -= 5; }
                if ($iva_16<>0){ $ye += -5; }
                if ($iva_11<>0){ $ye += -5; }
                if ($iva_5<>0) { $ye += -5; }
                if ($iva_0<>0) { $ye += -5; }
                
                $num_IEPS = 0;
                foreach( $this->IEPS as $k => $v ) {
                    if( $k != 0 ) {
                        $num_IEPS++;
                    }
                }
                if( $num_IEPS > 0 ) {$y += -4.5;}
                if( $y > $ye ) { // No hay espacio para los totales se muestran en otra p?gina.

                    $this->print_Totals();
                    $this->print_FE_Elements();
                    $this->AddPage();
                    $this->Customized_Header($bd_empresa_host);
                }
                else{

                    $this->MostrarTotales = 1;
                    $this->print_Totals();
                    $this->print_FE_Elements();

                }

            }
            else {
                $this->print_Totals();
                $this->print_FE_Elements();
            }  */
        }
        
        function pdf_totals(){
            
            $y = 226.5;
            $this->SetTextColor(0,0,0);
            $this->SetXY(170, $y);
            $this->Cell(30,3, "\$ ".number_format($this->DatosFactura['dSubtotal'],$this->decimales,'.',','),0,0,'R');
            
            $this->SetXY(170, $y+5);
            $this->Cell(30,3, "\$ ".number_format($this->DatosFactura['dTax'],$this->decimales,'.',','),0,0,'R');
            
            $this->SetFont('Arial', 'B', 9);
            $this->SetXY(170, $y+10);
            $this->Cell(30,3, "\$ ".number_format($this->DatosFactura['dTotal'],$this->decimales,'.',','),0,0,'R');
        
        }

        function Footer(){

                //Position at 1.5 cm from bottom
                $this->SetY(-7);
                $this->SetFont('Arial','',6);
                $this->SetTextColor(90, 90, 90);
                $page         = $this->PageNo();
                $totalpaginas = '{nb}';

                $this->Cell(50,10,"www.solo-trucking.com ",0,0,'L');
                $this->Cell(0,10,"Page $page of $totalpaginas",0,0,'R');

                //Verificar si es Vista previa (aun no esta timbrada la nomina) Tiene folio vacio;
                if($this->DatosFactura['eStatus'] == 'EDITABLE'){
                     //Put the watermark
                     $this->SetFont('Arial','B',50);
                     $this->SetAlpha(0.6);
                     $this->SetTextColor(212,212,212);
                     $txt = utf8_decode(' I n v o i c e   P r e v i e w ');
                     $this->RotatedText(20,230,$txt,48);
                }
                else if($this->DatosFactura['eStatus'] == 'CANCELED'){
                     //Put the watermark
                     $this->SetFont('Arial','B',55);
                     $this->SetAlpha(0.6);
                     $this->SetTextColor(223,23,23);
                     $txt = utf8_decode(' C a n c e l e d  B i l l ');
                     $this->RotatedText(20,230,$txt,48);
                }
        }

        /*******************************************************************************
        *                                                                              *
        *                               Public methods                                 *
        *                                                                              *
        *******************************************************************************/
        function Image($file, $x=null, $y=null, $w=0, $h=0, $type='', $link='', $isMask=false, $maskImg=0){
            //Put an image on the page
            if(!isset($this->images[$file]))
            {
                //First use of this image, get info
                if($type=='')
                {
                    $pos=strrpos($file,'.');
                    if(!$pos)
                        $this->Error('Image file has no extension and no type was specified: '.$file);
                    $type=substr($file,$pos+1);
                }
                $type=strtolower($type);
                if($type=='png'){
                    $info=$this->_parsepng($file);
                    if($info=='alpha')
                        return $this->ImagePngWithAlpha($file,$x,$y,$w,$h,$link);
                }
                else
                {
                    if($type=='jpeg')
                        $type='jpg';
                    $mtd='_parse'.$type;
                    if(!method_exists($this,$mtd))
                        $this->Error('Unsupported image type: '.$type);
                    $info=$this->$mtd($file);
                }
                if($isMask){
                    if(in_array($file,$this->tmpFiles))
                        $info['cs']='DeviceGray'; //hack necessary as GD can't produce gray scale images
                    if($info['cs']!='DeviceGray')
                        $this->Error('Mask must be a gray scale image');
                    if($this->PDFVersion<'1.4')
                        $this->PDFVersion='1.4';
                }
                $info['i']=count($this->images)+1;
                if($maskImg>0)
                    $info['masked'] = $maskImg;
                $this->images[$file]=$info;
            }
            else
                $info=$this->images[$file];
            //Automatic width and height calculation if needed
            if($w==0 && $h==0)
            {
                //Put image at 72 dpi
                $w=$info['w']/$this->k;
                $h=$info['h']/$this->k;
            }
            elseif($w==0)
                $w=$h*$info['w']/$info['h'];
            elseif($h==0)
                $h=$w*$info['h']/$info['w'];
            //Flowing mode
            if($y===null)
            {
                if($this->y+$h>$this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AcceptPageBreak())
                {
                    //Automatic page break
                    $x2=$this->x;
                    $this->AddPage($this->CurOrientation,$this->CurPageFormat);
                    $this->x=$x2;
                }
                $y=$this->y;
                $this->y+=$h;
            }
            if($x===null)
                $x=$this->x;
            if(!$isMask)
                $this->_out(sprintf('q %.2F 0 0 %.2F %.2F %.2F cm /I%d Do Q',$w*$this->k,$h*$this->k,$x*$this->k,($this->h-($y+$h))*$this->k,$info['i']));
            if($link)
                $this->Link($x,$y,$w,$h,$link);
            return $info['i'];
        }

        // needs GD 2.x extension
        // pixel-wise operation, not very fast
        function ImagePngWithAlpha($file,$x,$y,$w=0,$h=0,$link=''){
            #$tmp_alpha = tempnam('.', 'mska');
            $tmp_alpha = tempnam('/tmp', 'mska');
            $this->tmpFiles[] = $tmp_alpha;
            //$tmp_plain = tempnam('.', 'mskp');
            $tmp_plain = tempnam('/tmp', 'mskp');
            $this->tmpFiles[] = $tmp_plain;

            list($wpx, $hpx) = getimagesize($file);
            $img = imagecreatefrompng($file);
            $alpha_img = imagecreate( $wpx, $hpx );

            // generate gray scale pallete
            for($c=0;$c<256;$c++)
                ImageColorAllocate($alpha_img, $c, $c, $c);

            // extract alpha channel
            $xpx=0;
            while ($xpx<$wpx){
                $ypx = 0;
                while ($ypx<$hpx){
                    $color_index = imagecolorat($img, $xpx, $ypx);
                    $col = imagecolorsforindex($img, $color_index);
                    imagesetpixel($alpha_img, $xpx, $ypx, $this->_gamma( (127-$col['alpha'])*255/127) );
                    ++$ypx;
                }
                ++$xpx;
            }

            imagepng($alpha_img, $tmp_alpha);
            imagedestroy($alpha_img);

            // extract image without alpha channel
            $plain_img = imagecreatetruecolor ( $wpx, $hpx );
            imagecopy($plain_img, $img, 0, 0, 0, 0, $wpx, $hpx );
            imagepng($plain_img, $tmp_plain);
            imagedestroy($plain_img);

            //first embed mask image (w, h, x, will be ignored)
            $maskImg = $this->Image($tmp_alpha, 0,0,0,0, 'PNG', '', true);

            //embed image, masked with previously embedded mask
            $this->Image($tmp_plain,$x,$y,$w,$h,'PNG',$link, false, $maskImg);
        }

        function Close(){
            parent::Close();
            // clean up tmp files
            foreach($this->tmpFiles as $tmp)
                @unlink($tmp);
        }

        /*******************************************************************************
        *                                                                              *
        *                               Private methods                                *
        *                                                                              *
        *******************************************************************************/
        function _putimages(){
            $filter=($this->compress) ? '/Filter /FlateDecode ' : '';
            reset($this->images);
            while(list($file,$info)=each($this->images))
            {
                $this->_newobj();
                $this->images[$file]['n']=$this->n;
                $this->_out('<</Type /XObject');
                $this->_out('/Subtype /Image');
                $this->_out('/Width '.$info['w']);
                $this->_out('/Height '.$info['h']);

                if(isset($info['masked']))
                    $this->_out('/SMask '.($this->n-1).' 0 R');

                if($info['cs']=='Indexed')
                    $this->_out('/ColorSpace [/Indexed /DeviceRGB '.(strlen($info['pal'])/3-1).' '.($this->n+1).' 0 R]');
                else
                {
                    $this->_out('/ColorSpace /'.$info['cs']);
                    if($info['cs']=='DeviceCMYK')
                        $this->_out('/Decode [1 0 1 0 1 0 1 0]');
                }
                $this->_out('/BitsPerComponent '.$info['bpc']);
                if(isset($info['f']))
                    $this->_out('/Filter /'.$info['f']);
                if(isset($info['parms']))
                    $this->_out($info['parms']);
                if(isset($info['trns']) && is_array($info['trns']))
                {
                    $trns='';
                    for($i=0;$i<count($info['trns']);$i++)
                        $trns.=$info['trns'][$i].' '.$info['trns'][$i].' ';
                    $this->_out('/Mask ['.$trns.']');
                }
                $this->_out('/Length '.strlen($info['data']).'>>');
                $this->_putstream($info['data']);
                unset($this->images[$file]['data']);
                $this->_out('endobj');
                //Palette
                if($info['cs']=='Indexed')
                {
                    $this->_newobj();
                    $pal=($this->compress) ? gzcompress($info['pal']) : $info['pal'];
                    $this->_out('<<'.$filter.'/Length '.strlen($pal).'>>');
                    $this->_putstream($pal);
                    $this->_out('endobj');
                }
            }
        }

        // GD seems to use a different gamma, this method is used to correct it again
        function _gamma($v){
            return pow ($v/255, 2.2) * 255;
        }

        // this method overriding the original version is only needed to make the Image method support PNGs with alpha channels.
        // if you only use the ImagePngWithAlpha method for such PNGs, you can remove it from this script.
        function _parsepng($file){
            //Extract info from a PNG file
            $f=fopen($file,'rb');
            if(!$f)
                $this->Error('Can\'t open image file: '.$file);
            //Check signature
            if($this->_readstream($f,8)!=chr(137).'PNG'.chr(13).chr(10).chr(26).chr(10))
                $this->Error('Not a PNG file: '.$file);
            //Read header chunk
            $this->_readstream($f,4);
            if($this->_readstream($f,4)!='IHDR')
                $this->Error('Incorrect PNG file: '.$file);
            $w=$this->_readint($f);
            $h=$this->_readint($f);
            $bpc=ord($this->_readstream($f,1));
            if($bpc>8)
                $this->Error('16-bit depth not supported: '.$file);
            $ct=ord($this->_readstream($f,1));
            if($ct==0)
                $colspace='DeviceGray';
            elseif($ct==2)
                $colspace='DeviceRGB';
            elseif($ct==3)
                $colspace='Indexed';
            else {
                fclose($f);      // the only changes are
                return 'alpha';  // made in those 2 lines
            }
            if(ord($this->_readstream($f,1))!=0)
                $this->Error('Unknown compression method: '.$file);
            if(ord($this->_readstream($f,1))!=0)
                $this->Error('Unknown filter method: '.$file);
            if(ord($this->_readstream($f,1))!=0)
                $this->Error('Interlacing not supported: '.$file);
            $this->_readstream($f,4);
            $parms='/DecodeParms <</Predictor 15 /Colors '.($ct==2 ? 3 : 1).' /BitsPerComponent '.$bpc.' /Columns '.$w.'>>';
            //Scan chunks looking for palette, transparency and image data
            $pal='';
            $trns='';
            $data='';
            do
            {
                $n=$this->_readint($f);
                $type=$this->_readstream($f,4);
                if($type=='PLTE')
                {
                    //Read palette
                    $pal=$this->_readstream($f,$n);
                    $this->_readstream($f,4);
                }
                elseif($type=='tRNS')
                {
                    //Read transparency info
                    $t=$this->_readstream($f,$n);
                    if($ct==0)
                        $trns=array(ord(substr($t,1,1)));
                    elseif($ct==2)
                        $trns=array(ord(substr($t,1,1)), ord(substr($t,3,1)), ord(substr($t,5,1)));
                    else
                    {
                        $pos=strpos($t,chr(0));
                        if($pos!==false)
                            $trns=array($pos);
                    }
                    $this->_readstream($f,4);
                }
                elseif($type=='IDAT')
                {
                    //Read image data block
                    $data.=$this->_readstream($f,$n);
                    $this->_readstream($f,4);
                }
                elseif($type=='IEND')
                    break;
                else
                    $this->_readstream($f,$n+4);
            }
            while($n);
            if($colspace=='Indexed' && empty($pal))
                $this->Error('Missing palette in '.$file);
            fclose($f);
            return array('w'=>$w, 'h'=>$h, 'cs'=>$colspace, 'bpc'=>$bpc, 'f'=>'FlateDecode', 'parms'=>$parms, 'pal'=>$pal, 'trns'=>$trns, 'data'=>$data);
        }

        protected function _readstream($f, $n){
            // Read n bytes from stream
            $res = '';
            while($n>0 && !feof($f))
            {
                $s = fread($f,$n);
                if($s===false)
                    $this->Error('Error while reading stream');
                $n -= strlen($s);
                $res .= $s;
            }
            if($n>0)
                $this->Error('Unexpected end of stream');
            return $res;
        }

        protected function _readint($f){
            // Read a 4-byte integer from stream
            $a = unpack('Ni',$this->_readstream($f,4));
            return $a['i'];
        }

    }
    #TERMINA PDF CLASS
     
    if(!($error)){
        
        $pdf = new PDF('P', 'mm', 'Letter');
        $pdf->SetAutoPageBreak(FALSE);
        $pdf->AliasNbPages(); 
        $pdf->AddPage();
        $pdf->SetLineWidth(0.2);
        $pdf->init($folio, $invoice, $compania, $conexion, $ds);
        $pdf->pdf_header();
        $pdf->pdf_detail(); 
        $pdf->pdf_totals();
        $nombre_archivo_pdf = $nombre_pdf.".pdf";
        
        if($envio_correo == 1){
            $pdf->Output("tmp/".$nombre_archivo_pdf,"F");
            $pdfnew = "tmp/".$nombre_archivo_pdf;
            return $pdfnew;
        
        }
        else{$pdf->Output($nombre_archivo_pdf,'I');} 
    }
    else{
        echo '<script language="javascript">alert(\''.$mensaje.'\')</script>';
        echo "<script language='javascript'>window.close();</script>"; 
    }    

?>

