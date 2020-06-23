<?php
    session_start();
    include("cn_usuarios.php"); 
    include("functiones_genericas.php"); 
    require_once('lib/fpdf153/fpdf.php'); 
    
    // PARAMETROS GET:
    isset($_GET['id']) ? $folio = urldecode($_GET['id']) : $folio = "";
    isset($_GET['ds']) ? $ds    = strtoupper(urldecode($_GET['ds'])) : $ds    = "";
    $error = false; 
    $folio = preg_replace("/%u([0-9a-f]{3,4})/i","&#x\\1;",urldecode($folio)); 
    $folio = html_entity_decode($folio,null,'UTF-8');
    
    if($folio == "" || $folio == null){$error = true; $mensaje = "Error when consulting the company data, please try again.";}
    else{
        #CONSULTAR CONFIGURACION DEL CERTIFICADO Y DATOS:
        $sql    = "SELECT iConsecutivo, iConsecutivoCompania,sNoReferencia, DATE_FORMAT(dFechaInvoice,'%m/%d/%Y') AS dFechaInvoice,sReceptorNombre, sReceptorDireccion,sEmisorNombre, sLugarExpedicionDireccion, ".
                  "dSubtotal,rPDescuento, dDescuento, sMotivosDescuento, dPctTax, dTax, dTotal, dAnticipo, dBalance, sCveMoneda, dTipoCambio, sComentarios ".
                  "FROM cb_invoices WHERE iConsecutivo = '".$folio."'";
        $result = $conexion->query($sql);
        $rows   = $result->num_rows;    
        if($rows == 0){ $error = true; $mensaje = "Error when consulting the invoice data, please try again.";}
        else{
           $invoice    = $result->fetch_assoc(); 
           $compania   = $invoice['iConsecutivoCompania'];
           $nombre_pdf = "solo_trucking_invoice_No-".$invoice['iConsecutivo'];
           
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
        var $hdetail;
        var $ytot;
        var $FooterY;
        var $Correo_R;

        var $SecY;
        var $DetailHeight;
        var $TotX;
        var $SymbolX;
        var $TotalsX;
        var $limit;
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

        function init($folio, $invoice, $compania, $nombre_pdf, $ds){

           $this->$display = $ds;
    
           #Datos de configuracion del Emisor:
           $sql    = "SELECT * FROM ct_empresa WHERE iConsecutivo IS NOT NULL LIMIT 1";
           $result = $conexion->query($sql);
           $this->ConfiguracionSeleccionado = $result->fetch_array();

           #Datos del Cliente:
           $sql    = "SELECT iConsecutivo,sNombreCompania,sUsdot,sDireccion,sCiudad,sEstado,sCodigoPostal, sEmailContacto ".
                     "FROM ct_companias WHERE iConsecutivo = '".$compania."'";
           $result = $dbconn->query($sql);
           $this->DatosReceptor = $result->fetch_array();
           

           #Factura Detalle:
           $sql    = "iConsecutivoDetalle, iConsecutivoServicio, sClave, sDescripcion, sCveUnidadMedida, iCantidad, iPrecioUnitario, iPctImpuesto, iImpuesto, iPrecioExtendido, iEndorsementsApply, sComentarios".
                     "FROM cb_invoice_detalle WHERE iConsecutivoInvoice = '".$folio."'";
           $result = $dbconn->query($sql);
           $this->DatosFacturaDetalle= mysql_fetch_all($result);
           
           #Otras Variables:
           $this->decimales = $this->ConfiguracionSeleccionado["Decimales"];

        }

        function pdf_color_define(){
           
           $colorPDF    = $this->ConfiguracionSeleccionado["sColorPdf"];
           $arrRGBColor = explode(",", $colorPDF);
           $this->SetFillColor(intval($arrRGBColor[0]), intval($arrRGBColor[1]), intval($arrRGBColor[2]));
           $this->SetDrawColor(intval($arrRGBColor[0]), intval($arrRGBColor[1]), intval($arrRGBColor[2]));
           
        }

        function pdf_header(){

            $FechaAsignacion = $this->DatosFolios['dFechaAprobacion'];
            $TipoMoneda      = $this->DatosFactura['Moneda'];
            $TipoCambio      = number_format(gpcround($this->DatosFactura['sTipoCambio'],$this->decimales),$this->decimales,'.',',');
            $this->ImprimeTC = $this->ConfiguracionSeleccionado['ImprimirTC'];

            #Datos del Cliente:
            $Receptor       = utf8_decode($this->DatosFactura['sNombreCliente']);
            $Calle_R        = utf8_decode($this->DatosFactura['sCalleCliente']);
            $NumeroExt_R    = $this->DatosFactura['sNumExteriorCliente'];
            $NumeroInt_R    = $this->DatosFactura['sNumInteriorCliente'];
            $Colonia_R      = utf8_decode($this->DatosFactura['sColoniaCliente']);
            $Localidad_R    = utf8_decode($this->DatosFactura['sCiudadCliente']);
            $Pais_R         = $this->DatosFactura['sPaisCliente'];
            $CodigoPostal_R = $this->DatosFactura['sCodigoPostalCliente'];
            $RFC_C          = $this->DatosFactura['sRFCCliente'];
           
            if($this->ConfiguracionSeleccionado['iIncluirDescripcionEntidad'] == '1' AND $this->Instancia != "bd_econta_snl"){
              $Estado_R = $this->DescEntidad;
              switch($Pais_R){
                case "USA" : $Pais_R = "ESTADOS UNIDOS"; break;
                case "MEX" : $Pais_R = "MEXICO"; break;
                case "CAN" : $Pais_R = "CANADA"; break;
              }
            }
            else if($this->Instancia == "bd_econta_snl"){$Estado_R = $this->DescEntidad;}
            else {$Estado_R = utf8_decode($this->DatosFactura['sEstadoCliente']);}

            $this->Correo_R = $this->DatosCorreoCorresponsal['sCorreos'];
            if ($this->Correo_R==""){$this->Correo_R = $this->DatosCorreo['sCorreos'];}
            if ($this->Correo_R==""){$this->Correo_R = $this->DatosFactura['sCorreoCliente'];}

            if($_SERVER["HTTP_HOST"] == 'localhost:8080'){$Estado_R = strtoupper($Estado_R);}
            else{$Estado_R = mb_strtoupper($Estado_R);}
            if($this->OpcionesImpresion["Receptor"]["DomicilioFiscal"]){
                    if ($Calle_R != "")             {$direccion = $direccion."$Calle_R";}
                    if ($NumeroExt_R !="")          {$direccion = $direccion." # $NumeroExt_R";}
                    if($NumeroInt_R != "")          {$direccion = $direccion." INT. $NumeroInt_R";}
                    if($Colonia_R != "")            {$direccion = $direccion.", COL. $Colonia_R\n";}
                    if($this->TelefonoCliente != ""){$direccion = $direccion."TEL: ".$this->TelefonoCliente;}
                    if($CodigoPostal_R != "")       {$direccion = $direccion." C.P. $CodigoPostal_R";}
                    $direccion = $direccion . " $Localidad_R, ".($Estado_R).".";
                    $direccion = $direccion." $Pais_R";
            }

            if(($this->DatosFactura["sNombreDestino"]<>"NINGUNO" AND (strlen($this->DatosFactura["sNombreDestino"])>0)) OR ($this->DatosFactura["sNombreOrigen"]<>"NINGUNO" AND (strlen($this->DatosFactura["sNombreOrigen"])>0)) ){
                    if($this->Instancia=="bd_econta_rfr") {$transp = false;} else { $transp = true;    }
            }

            if($this->Instancia == "disa") {$DatosEmision = "DatosExpedicion";}else{$DatosEmision = "ConfiguracionSeleccionado";}

            $EmpresaNombreComercial = utf8_decode($this->ConfiguracionSeleccionado["sNombre"]);
            $EmpresaRFC             = $this->ConfiguracionSeleccionado["sRFC"];

            if($this->OpcionesImpresion["Emisor"]["DomicilioFiscal"]){
                    $EmpresaCalle = utf8_decode($this->{$DatosEmision}["sCalle"]);
                    $this->{$DatosEmision}["sNumExt"] !="" ? $EmpresaNumExt = " # ".$this->{$DatosEmision}["sNumExt"] : $EmpresaNumExt = "";
                    $this->{$DatosEmision}["sNumInt"] !="" ? $EmpresaNumInt = " INT. ".$this->{$DatosEmision}["sNumInt"]."," : $EmpresaNumInt="";
                    $EmpresaColonia = " ".utf8_decode($this->{$DatosEmision}["sColonia"])."\n";
                    $this->{$DatosEmision}["sLocalidad"]!="" ? $EmpresaLocalidad = utf8_decode($this->{$DatosEmision}["sLocalidad"])."," : $EmpresaLocalidad ="";

                    $this->{$DatosEmision}["sEstado"]!="" ? $EmpresaEstado = " ".$this->{$DatosEmision}["sEstado"]."," : $EmpresaEstado = "";
                    $this->Instancia == "bd_econta_snl" ? $EmpresaEstado = $this->{$DatosEmision}['DescEstado']."," : $EmpresaEstado = $EmpresaEstado;
                    $EmpresaPais = " ".$this->{$DatosEmision}["sPais"]."\n";
                    $this->{$DatosEmision}["sCP"]!="" ? $EmpresaCP = "C.P:".$this->{$DatosEmision}["sCP"] : $EmpresaCP ="";
                    $this->{$DatosEmision}["sTelefono1"] != "" && $this->{$DatosEmision}["sTelefono1"] != '()' ? $EmpresaTelefono1 = " Tel:".$this->{$DatosEmision}["sTelefono1"]."\n" : $EmpresaTelefono1="";
                    $EmpresaTelefono2 = $this->{$DatosEmision}["sTelefono2"];
            }

            $EmpresaRazonSocial       = utf8_decode($this->ConfiguracionSeleccionado["sRazonSocial"]);
            $EmpresaMail              = $this->ConfiguracionSeleccionado["sMail"];
            $EmpresaWebSite           = $this->ConfiguracionSeleccionado["sWebSite"];
            $this->EsquemaFacturacion = $this->ConfiguracionSeleccionado["sEsquemaFacturacion"];
            $DomicilioFiscal          = "$EmpresaCalle$EmpresaNumExt$EmpresaNumInt$EmpresaColonia".
                                        "$EmpresaLocalidad$EmpresaEstado$EmpresaPais".
                                        "$EmpresaCP$EmpresaTelefono1".
                                        "RFC. ".$EmpresaRFC;
            #Logo
            $logo  = $this->ConfiguracionSeleccionado['hLogoEmpresa'];
            $h     = $this->ConfiguracionSeleccionado['iLogoH'];
            $w     = $this->ConfiguracionSeleccionado['iLogoW'];
            if(!empty($logo)){$this->MemImage($logo,8,11,$w,$h,'');}

            $TotalCFD               = number_format(gpcround($this->DatosFactura['rTotal'],$this->decimalesfijos),$this->decimalesfijos,'.','');
            if($EmpresaRazonSocial == $EmpresaNombreComercial AND $this->OpcionesImpresion["Emisor"]["RazonSocial"]){$ImprimeNC = false;}else{$ImprimeNC = true;}

            #PDF:
            $xh = 70;
            $yh = 12;
            $this->SetXY($xh, $yh);
            $this->SetFont('Arial', 'B', 11);
            $this->SetTextColor(0,0,0);
            if($ImprimeNC){
                 $len = strlen($EmpresaNombreComercial);
                 if ($len>40){
                    $this->MultiCell(80, 4, $EmpresaNombreComercial, 0,'J');
                    $yh+= 8;
                } else{
                    $this->Cell(56, 3, $EmpresaNombreComercial, 0, 0, 'J');
                     $yh += 4;
                }

                $this->SetXY($xh,$yh);
                $this->SetFont('Arial', '', 8.5);

            }
            $len = strlen($EmpresaRazonSocial);
            $reng_RS = ($len-($len % 40))/40;

            if (($len%40)>0){$reng_RS ++;}
            if($this->OpcionesImpresion["Emisor"]["RazonSocial"]){
                if ($len>40){
                    $this->SetFont('Arial','B',9);
                    $this->MultiCell(80, 4, $EmpresaRazonSocial, 0,'J');
                    $yh+= ($reng_RS*4);
                } else{
                    $this->Cell(56, 3, $EmpresaRazonSocial, 0, 0, 'J');
                    $yh+= 4;
                }
            }
            $this->SetXY($xh, $yh );
            $this->SetFont('Arial', '', 8.5);


            $this->MultiCell(70, 3.5, $DomicilioFiscal, 0, 'J');

            $cY = $this->getY();
            if($cY<=39){
                $this->SetXY($xh, $cY);
                $this->SetFont('Arial', 'B', 10);
                if($this->Instancia!="bd_econta_smalimentos")
                    $this->SetTextColor(192, 192, 192);
                $this->Cell(56, 3, $EmpresaWebSite, 0, 'J');
            }

            // Serie
            $this->SetFont('Arial', '', 8);
            $this->pdf_color_define();
            $this->RoundedRect(160, 12, 15, 6, 2, '12', 'DF');
            $this->SetXY(160, 12);
            $this->SetTextColor(255, 255, 255);
            $this->Cell(15, 6, 'SERIE', 0, 0, 'C');
            $this->SetXY(162, 19);
            $this->SetTextColor(0, 0, 0);

            // Facutra No.
            $TIPOCF = $this->DatosFactura["tipoCF"]." NO.";
            $this->RoundedRect(178, 12, 32, 6, 2, '12', 'DF');
            $this->SetXY(178, 12);
            $this->SetTextColor(255, 255, 255);
            $this->Cell(32, 6, "FOLIO / $TIPOCF", 0, 0, 'C');
            $this->SetXY(162, 19);
            $this->SetTextColor(0, 0, 0);

            // Serie
            $this->RoundedRect(160, 18, 15, 6, 2, '34', 'D');
            if ($this->DatosFactura['sSerie'] != '_'){
                $this->SetXY(160, 18);
                $this->SetTextColor(0, 0, 0);
                $this->SetFont('Arial', '', 10);
                $this->Cell(15, 6, $this->DatosFactura['sSerie'],0,0,'C');
                $this->SetFont('Arial', '', 8);
            }
            // Factura/Folio No.
            $this->RoundedRect(178, 18, 32, 6, 2, '34', 'D');
            $this->SetXY(178, 18);
            $this->SetTextColor(0, 0, 0);
            $this->SetFont('Arial', '', 10);

            if($this->Instancia=="bd_econta_pericostransfer" OR $this->Instancia=="bd_econta_smalimentos" OR $this->Instancia == "bd_econta_dante" OR $this->Instancia == "bd_econta_jdec" OR $this->Instancia=="devel"){
                $this->FolioImpreso = $this->DatosFactura['iFolioCliente'];
            }else{
                $this->FolioImpreso = $this->DatosFactura['sFolio'];
            }
            $this->Cell(32, 6, $this->FolioImpreso,0,0,'C');
            $this->SetFont('Arial', '', 8);

            // Lugar y fecha
            $this->RoundedRect(160, 25, 50, 4.5, 2, '12', 'DF');
            $this->SetXY(162, 25);
            $this->SetTextColor(255, 255, 255);
            $this->Cell(46, 5, 'EXPEDIDO EN:', 0, 0, 'C');

            #DATOS DEL CLIENTE:
            $this->RoundedRect(6, 44, 150, 24, 2, '1234', 'D');
            $Y = 44;
            //No. Cliente y RFC.
            $this->RoundedRect(6, $Y, 28, 5, 2, '1', 'DF');
            $this->RoundedRect(69, $Y, 25, 5, 2, '', 'DF');
            $this->SetTextColor(255, 255, 255);
            $this->SetXY(6, $Y);
            $this->Cell(28, 5.5, 'NO. CLIENTE:', 0, 0, 'C');
            $this->SetXY(69, $Y);
            $this->Cell(25, 5.5, 'RFC:', 0, 0, 'C');
            $this->RoundedRect(34, $Y, 35, 5, 2, '', 'D');
            $this->RoundedRect(94, $Y, 62, 5, 2, '2', 'D');
            //textos:
            $this->SetFont('Arial', '', 9);
            $this->SetTextColor(0, 0, 0);
            $this->SetXY(34, $Y);

            if( $this->DatosFactura['bImportada'] == 1 && empty($this->DatosFactura['sCveClienteEconta']) ) {
                $this->Cell(34, 5.5, $this->DatosFactura['sCveClientePropia'],0,0,'C');
            }
            else {
                $this->Cell(34, 5.5, $this->DatosFactura['sCveClienteEconta'],0,0,'C');
            }

            $this->SetXY(94, $Y);
            $this->Cell(62, 5.5, $RFC_C,0,0,'C');

            //Razon Social:
            $Y += 6;
            $this->SetXY(8, $Y);
            $this->SetTextColor(0, 0, 0);
            $this->SetFont('Arial', 'B', 9);
            if($this->OpcionesImpresion["Receptor"]["RazonSocial"]){
                $this->MultiCell(150, 4, $Receptor, 0, 'L');
                if(strlen($Receptor) > 72){
                   $Y += 8;
                }else{$Y += 4.5;}

            }
            //Direccion:
            $this->SetXY(8,$Y);
            $this->SetFont('Arial', '', 8);
            $this->MultiCell(145, 4, $direccion , 0, 'L');
            //CBB
            $this->SecY=0;
            $this->DetailHeight=0;
            switch($this->EsquemaFacturacion){
                        case 1:
                                $this->print_HeaderCFD();
                                break;
                        case 2:
                                $this->print_HeaderCBB();
                                $this->DetailHeight = 20;
                                $this->SecY = 23;
                                break;
                        case 3:
                                $this->print_HeaderCFDi();
                                break;
            }
            if($transp){
                $this->print_TranspInfo();
                $this->CurrentY=102;
                $this->SecY+=204;
                $this->DetailHeight += 112;
            }else{
                $this->CurrentY=72;
                $this->SecY+=205;
                $this->DetailHeight += 142;
            }
            $this->TotX=160;
            $this->SymbolX  = 183;
            $this->TotalsX = 190;


        }

        function print_HeaderCFD(){
                $this->SetXY(160, 6);
                $TIPOCF = $this->DatosFactura["tipo"];
                                    $this->SetFont('Arial', '', 8);
                                    $this->MultiCell(50, 3, "COMPROBANTE FISCAL DIGITAL\n$TIPOCF", 0, 'C');

                                    $this->SetFont('Arial', '', 7);
                                    //$this->RoundedRect(160, 33, 50, 6, 2, '34', 'D');
                                    $this->RoundedRect(160, 29.5, 50, 10, 2, '34', 'D');
                                        $this->SetTextColor(0, 0, 0);
                                        $this->SetXY(160, 30);
                                        $this->Cell(50, 4, $this->DatosExpedicion["ExpedidoEn"], 0, 0, 'C');
                                    $this->SetXY(162, 33);
                                    $this->SetTextColor(0, 0, 0);
                                    $this->Cell(46, 6, $this->DatosFactura['dFechaHora'] ,0,0,'C');
                                        // Num. de serie
                                    $this->RoundedRect(160, 44, 50, 6, 2, '12', 'DF');
                                    $this->SetTextColor(255, 255, 255);
                                    $this->SetXY(162, 44);

                                    $this->Cell(46, 6, 'No. de Serie del Certificado', 0, 0, 'C');
                                    $this->SetLineWidth('0.15');
                                    $this->Line(160,56,210,56);

                                    $this->SetXY(160, 50);
                                    $this->SetTextColor(0, 0, 0);
                                    $this->Cell(50, 6, $this->DatosFolios["sSerieCertificado"].$this->echo,0,0,'C');

                                    $this->RoundedRect(160, 48, 50, 20, 2, '34', 'D');

                                    // Num. aprobacion
                                    $this->SetXY(163, 57);
                                    if(!isset($colores)){ $colores = $this->colores; }

                                    $this->SetTextColor($colores[0], $colores[1], $colores[2]);
                                    $this->Cell(30, 6, 'No. DE APROBACIÓN:', 0, 0, 'R');
                                    $this->SetXY(195, 57);
                                    $this->SetTextColor(0, 0, 0);
                                    $this->Cell(15, 6, $this->DatosFolios['sNumeroAprobacion'],0,0,'' );

                                    // Anio. aprobacion
                                    $this->SetXY(163, 61);
                                    $this->SetTextColor($colores[0], $colores[1], $colores[2]);
                                    $this->Cell(30, 6, 'AÑO DE APROBACIÓN:', 0, 0, 'R');
                                    $this->SetXY(190, 61);
                                    $this->SetTextColor(0, 0, 0);
                                    $this->Cell(15, 6, $this->DatosFolios['iAnioAprobacion'],0,0,'R');

        }

        function print_HeaderCFDi(){
            
            $pos = stripos($this->DatosFactElec["sXMLCFDi"],"noCertificadoSAT");
            if ($pos!=false){
                $str = substr($this->DatosFactElec["sXMLCFDi"],$pos+18);
                $pos2=stripos($str," ");
                $str= substr($str,0,$pos2-1);
                $CertificadoSAT= $str;
            }
             $pos = stripos($this->DatosFactElec["sXMLCFDi"],"noCertificado");
            if ($pos!=false){
                $str = substr($this->DatosFactElec["sXMLCFDi"],$pos+15);
                $pos2=stripos($str," ");
                $str= substr($str,0,$pos2-1);
                $CertificadoEmisor= $str;
            }
            $pos = stripos($this->DatosFactElec["sXMLCFDi"],"selloSAT");
            if ($pos!=false){
                $str = substr($this->DatosFactElec["sXMLCFDi"],$pos+10);
                $pos2=stripos($str," ");
                $str= substr($str,0,$pos2-1);
                $SelloSAT= $str;
            }
            $pos = stripos($this->DatosFactElec["sXMLCFDi"],"selloCFD");
            if ($pos!=false){
                $str = substr($this->DatosFactElec["sXMLCFDi"],$pos+10);
                $pos2=stripos($str," ");
                $str= substr($str,0,$pos2-1);
                $SelloCFD= $str;
                $this->TamanoSelloCFD =  strlen($SelloCFD);
            }
            
            //Calcular la posicion del Folio Fiscal en caso de ser una Nota de Credito:
            if($this->DatosFactura['tipo'] == "NOTA DE CREDITO"){
                
                $pos1 = strpos($this->DatosFactElec["sXMLCFDi"],"<cfdi:Complemento>");
                $pos2 = strpos($this->DatosFactElec["sXMLCFDi"],"</cfdi:Complemento>");
                $xmlT = substr($this->DatosFactElec["sXMLCFDi"],$pos1,$pos2); 

                $pos  = strpos($xmlT,'UUID="');
                if ($pos!=false){
                    $str  = substr($xmlT,$pos+6);
                    $pos2 = stripos($str," ");
                    $str  = substr($str,0,$pos2-1);
                    $UUID = $str;
                } 
    
                
            }else{
                $pos = strrpos($this->DatosFactElec["sXMLCFDi"],"UUID=\"");
                if ($pos!=false){
                    $str  = substr($this->DatosFactElec["sXMLCFDi"],$pos+6);
                    $pos2 = stripos($str," ");
                    $str  = substr($str,0,$pos2-1);
                    $UUID = $str;
                } 
            }
            
            $pos = stripos($this->DatosFactElec["sXMLCFDi"],"Fecha");
            if ($pos!=false){
                $str = substr($this->DatosFactElec["sXMLCFDi"],$pos+7);
                $pos2=stripos($str," ");
                $str= substr($str,0,$pos2-1);
                $FT = $str;
                $FechaTmp=explode($FT,"T");
                $FechaT=explode($FechaTmp[0],"-");
                $FechaExp = substr($FT,8,2)."-".substr($FT,5,2)."-".substr($FT,0,4)." ".substr($FT,11,8);
            }

            $pos = stripos($this->DatosFactElec["sXMLCFDi"],"FechaTimbrado");
            if ($pos!=false){
                $str = substr($this->DatosFactElec["sXMLCFDi"],$pos+15);
                $pos2=stripos($str," ");
                $str= substr($str,0,$pos2-1);
                $FT = $str;
                $FechaTmp=explode($FT,"T");
                $FechaT=explode($FechaTmp[0],"-");
                $FechaTimbrado = substr($FT,8,2)."-".substr($FT,5,2)."-".substr($FT,0,4)." ".substr($FT,11,8);
                $FechaTimbrado1 = substr($FT,8,2)."-".substr($FT,5,2)."-".substr($FT,0,4)."T".substr($FT,11,8);
            }

            $this->Cadena_Original = "||1.1|$UUID|$FechaTimbrado1|EME000602QR9|$SelloCFD|$CertificadoSAT||";
            $this->UUID[ $this->DatosFactura['iFolio'] ] = $UUID;
            $this->SelloCFD  = $SelloCFD;
            $this->SelloSAT = $SelloSAT;


            $TIPOCF = $this->DatosFactura["tipo"];
            $this->SetTextColor(205, 205, 205);
            $this->SetXY(160, 6);
            $this->SetFont('Arial', '', 7);
            $this->MultiCell(50, 3, "Comprobante Fiscal Digital por Internet\n$TIPOCF", 0, 'R');
            $hdet = 85;
            $ytotal = 165;
            // Num. de Aprobacion
            $this->RoundedRect(160, 44, 50, 6, 2, '12', 'DF');
            $this->SetTextColor(255, 255, 255);
            $this->SetXY(162, 44);
            $this->SetFont('Arial', '', 7);
            $this->Cell(46, 6, 'No. Serie del Certificado Emisor', 0, 0, 'C');
            $this->SetLineWidth('0.15');
            $this->Line(160,56,210,56);
            $this->RoundedRect(160, 56, 50, 6, 2, '', 'DF');
            $this->SetXY(160, 50);
            $this->SetTextColor(0, 0, 0);
            $this->Cell(50, 6, $CertificadoEmisor,0,0,'C');

            $this->RoundedRect(160, 48, 50, 20, 2, '34', 'D');

            // Anio. aprobacion
            $this->SetXY(162, 56);
            $this->SetTextColor(255, 255, 255);
            $this->Cell(46, 6, 'No. Serie del Certificado SAT', 0, 0, 'C');
            $this->SetXY(162, 62);
            $this->SetTextColor(0, 0, 0);
            $this->Cell(46, 6, $CertificadoSAT,0,0,'C');

            $this->RoundedRect(160, 29.5, 50,13, 2, '34', 'D');

            //$this->RoundedRect(160, 33, 50,9, 2, '34', 'D');
            $this->SetTextColor(0, 0, 0);
            $this->SetFontSize(7);
            $this->SetTextColor(0, 0, 0);
            $this->SetXY(160, 30);
            $this->Cell(50, 4, strtoupper($this->DatosExpedicion["ExpedidoEn"]), 0, 0, 'C');
            $this->SetXY(160, 34);
            $this->Cell(30, 3, "Fecha Emisión:" ,0,0,'L');
            $this->SetXY(185, 34);
            $this->Cell(46, 3, $FechaExp ,0,0,'L');
            $this->SetXY(160, 38);
            $this->Cell(30, 3, "Fecha Certificación:" ,0,0,'L');
            $this->SetXY(185, 38);
            $this->Cell(46, 3, $FechaTimbrado ,0,0,'L');

        }

        function print_Totals(){
            // GENERICO //
            $x_cant     = $this->CantX;
            $x_impu     = $this->ImpUX;
            $x_iva      = $this->IVAX;
            $x_imptot   = $this->ImpTotX;
            $x_tot      = $this->TotX;
            $x_totals   = $this->TotalsX;
            $x_symbol   = $this->SymbolX;
            $y          = $this->CurrentY;
            $height     = $this->DetailHeight;
            $TipoMoneda = $this->DatosFactura["Moneda"];
            $MonedaDesc = $this->DescMoneda['sDescripcion'];
            $TipoCambio = number_format(gpcround(($this->DatosFactura["sTipoCambio"]),2),2,'.','');
            $iva_11     = $this->IVA11;
            $iva_16     = $this->IVA16;
            $iva_5      = $this->IVA5;
            $iva_8      = $this->IVA8;
            $iva_0      = $this->IVA0;

            #Recuadro de descripciones:
            $this-> pdf_color_define();
            $this->RoundedRect(6  ,$y, 204,$height, 2, '12', 'D');
            $this->RoundedRect(6  ,$y, 204, 6, 2, '12', 'DF');
            $this->RoundedRect(182,$y, 28, $height, 2, '12', 'D');

            $this->SetTextColor(255, 255, 255);
            $this->SetXY(6, $y);
            $this->Cell(50, 6, 'DESCRIPCIÓN DEL SERVICIO', 0, 0, 'C');
            $this->SetXY($x_cant, $y);
            $this->Cell(10, 6, 'CANTIDAD', 0, 0, 'C');
            $this->SetXY(140, $y);
            $this->Cell(4, 6, 'U.M.', 0, 0, 'C');
            $this->SetXY($x_impu, $y);
            $this->Cell(30, 6, 'PRECIO U.', 0, 0, 'C');
            $this->SetXY($x_iva, $y);
            $this->Cell(18, 6, 'IVA', 0, 0, 'C');
            $this->SetXY($x_imptot, $y);
            $this->Cell(40, 6, 'IMPORTE', 0, 0, 'C');

            if( $this->MostrarTotales == 1 ) {

                #VARIABLES:
                $y           = $this->SecY;
                $Comentarios = trim($this->DatosFactura['sComentarios']);
                $Plazo       = $this->DatosFactura['Plazo'];
                $yTmp        = $y-18; // para los comentarios y tipo de cambio etc...

                if($this->UUID[ $this->DatosFactura['iFolio']] == ""){
                   $yTmp -= 10;
                }

                if(!isset($colores)){$colores = $this->colores;}
                $this->SetTextColor($colores[0], $colores[1], $colores[2]);
                $this->SetFont('Arial', '', 7);

                #COMENTARIOS / TIPO DE CAMBIO / TOTAL EN LETRA:
                if ($Plazo>0) { $MsgPlazo = "Esta factura deberá ser pagada en un plazo de $Plazo días"; $y-=3;}
                if ($TipoCambio > 0  && $TipoMoneda !="MXP" && $TipoMoneda !="MXN" && $TipoMoneda !="XXX" && $this->ImprimeTC=='SI'){
                    $ImpTipoCambio  = "Tipo de Cambio: \$ $TipoCambio";
                    $ImpDescTCambio = "Moneda: $TipoMoneda - $MonedaDesc";

                }
                //imprime comentarios:
                if (utf8_decode($Comentarios) AND $Comentarios != "NULL" AND $this->EsquemaFacturacion != 2) {

                    $Comentarios = utf8_decode($Comentarios);
                    if(strlen($Comentarios) > 345){
                        $Comentarios =  substr($Comentarios,0,345);
                    }
                    $lCom = strlen($Comentarios);
                    if($lCom > 150){$this->SetFont('Arial','',6.5);}

                    $RengCom = ($lCom-($lCom % 75))/75;
                    if (($lCom % 75) > 0 ){$RengCom++;}
                    $CalReng = $RengCom * 3;
                    $this->SetXY(12, $yTmp);
                    $this->MultiCell(120, 3,$Comentarios, 0,'J');

                    $lCom > 150 ? $yTmp += 9 : $yTmp += 6;
                    $this->RengCom = $CalReng;
                }
                else {
                    $yTmp += 8;
                }
                if (strlen(utf8_decode($ImpTipoCambio)) > 0) {

                    $this->SetXY(12, $yTmp);
                    $this->Cell(75, 3,"$ImpDescTCambio",0,0,'L');
                    $this->Cell(45, 3,"$ImpTipoCambio" ,0,0,'R');
                    $yTmp += 4;
                }
                if (strlen(utf8_decode($Plazo)) > 0) {
                    $this->SetFont('Arial','B',8);
                    $this->SetXY(12, $yTmp);
                    $this->MultiCell(120, 3,"$MsgPlazo", 0,'L');
                    $yTmp += 5;
                }

                $this->SetFillColor('239','239','239');//< --- Background importe con letra titulo.
                $this->RoundedRect(6,$yTmp,125,5, 2, '23', 'F');
                $this->SetFont('Arial', 'B', 8);
                $this->SetXY(12, $yTmp);
                $this->SetTextColor($colores[0], $colores[1], $colores[2]);
                $this->Cell(40, 5, 'IMPORTE CON LETRA', 0, 0, 'L');
                $yTmp += 5;
                $this->SetXY(12, $yTmp);
                $this->SetTextColor(0, 0, 0);
                $this->SetFont('Arial', '', 7);
                $importe_con_letra = getCantidadDineroEnLetra(number_format(gpcround($this->DatosFactura["rTotal"],$this->decimalesfijos),$this->decimalesfijos,'.',''), $TipoMoneda);
                $this->MultiCell(100, 4, $importe_con_letra, 0, 'L');
                
                //Calculamos Y
                if ($this->DatosFactura["rDescuento"] != 0){ $y += -13.5; }else { $y+=-4;}
                if ($this->DatosFactura["rRetencionesIVA"] != 0){ $y += -4;}
                if ($this->DatosFactura["rRetenciones"] != 0)   { $y += -4;}
                if ($iva_11 == 0 && $iva_16 == 0 && $iva_5 == 0 && $iva_8 == 0) { $y -=  4;}
                if ($iva_16<>0){ $y+= -4; }
                if ($iva_11<>0){ $y+= -4; }
                if ($iva_5 <>0){ $y+= -4; }
                if ($iva_8 <>0){ $y+= -4; }
                if ( $this->ISH <> 0 ) { $y+= -4; }
                /*if(count($this->IEPS)>0){
                    #$y += (count($this->IEPS)*-4.5);
                    $y += -4.5;
                }*/

                $num_IEPS = 0;
                foreach( $this->IEPS as $k => $v ) {
                    if( $k != 0 ) {
                        $num_IEPS++;
                    }
                }
                if( $num_IEPS > 0 ) {

                    $y += -4.5;
                }

                #SUBTOTAL:
                $this->SetFont('Arial', '', 9);
                $this->SetXY($x_tot, $y);
                $this->SetTextColor($colores[0], $colores[1], $colores[2]);
                $this->Cell(20, 6, "SUBTOTAL", 0, 0, 'R');
                $this->SetXY($x_symbol,$y);
                $this->Cell(2,6,"$",0,0,'L');
                $this->SetXY($x_totals, $y);
                $this->SetTextColor(0, 0, 0);
                //$SubTotalBalin = round($this->DatosFactura["rSubtotal"],2,PHP_ROUND_HALF_UP);
                //$SubTotalBalin = round($SubTotalBalin,2);
                $this->Cell(20, 6,number_format(gpcround($this->DatosFactura["rSubtotal"],2),2,'.',','), 0, 0, 'R');

                #DESCUENTO:
                if ($this->DatosFactura["rDescuento"] <> 0 ) {
                    $y += 5;
                    $this->SetXY($x_tot, $y);
                    $this->Cell(20, 6, number_format(gpcround($this->DatosFactura["rPDescuento"],$this->decimales),$this->decimales,'.','').' % DESCUENTO',0,0, 'R');
                    $this->SetXY($x_symbol,$y);
                    $this->Cell(2,6,"$",0,0,'L');
                    $this->SetXY($x_totals, $y);
                    $this->SetTextColor(255, 0, 0);
                    $this->Cell(20, 6, number_format(gpcround($this->DatosFactura["rDescuento"],$this->decimalesfijos),$this->decimalesfijos,'.',','), 0,0, 'R');
                    $y += 5;
                    $this->SetTextColor(0, 0, 0);
                    $this->SetXY($x_tot, $y);
                    $this->Cell(20, 6, 'SUBTOTAL NETO',0,0, 'R');
                    $SubtotalNeto = $this->DatosFactura["rSubtotal"] - $this->DatosFactura["rDescuento"];
                    $this->SetXY($x_symbol,$y);
                    $this->Cell(2,6,"$",0,0,'L');
                    $this->SetXY($x_totals, $y);
                    $this->Cell(20, 6, number_format(gpcround($SubtotalNeto,$this->decimalesfijos),$this->decimalesfijos,'.',','), 0,0, 'R');
                }

                #IVA - IEPS
                #if(count($this->IEPS)>0){
                if($num_IEPS>0){

                    if( $num_IEPS == 1 ) {

                        foreach($this->IEPS as $k=>$v){
                            if($k != 0){
                                $tasaIEPS = number_format($k,2,'.','');
                                $importeIEPS = number_format($v,2,'.','');
                                $y += 5;
                                $this->SetXY($x_tot, $y);
                                $this->SetTextColor(0,0,0);
                                $this->Cell(20, 6, "IEPS", 0, 0, 'R');
                                $this->SetXY(150, $y);
                                $this->SetTextColor(0, 0, 0);
                                $this->Cell(20, 6,"$tasaIEPS %", 0, 0, 'R');
                                $this->SetXY($x_symbol,$y);
                                $this->Cell(2,6,"$",0,0,'L');
                                $this->SetXY($x_totals, $y);
                                $this->SetTextColor(0, 0, 0);
                                $this->Cell(20, 6, number_format(gpcround($importeIEPS,$this->decimalesfijos),$this->decimalesfijos,'.',','), 0, 0,'R');

                            }
                        }
                    }
                    else {

                        $totalIEPS = 0;

                        foreach($this->IEPS as $k=>$v){

                            if($k != 0){
                                $importeIEPS = number_format($v,2,'.','');
                                $totalIEPS += $importeIEPS;
                            }
                        }

                        $y += 5;
                        $this->SetXY($x_tot, $y);
                        $this->SetTextColor(0,0,0);
                        $this->Cell(20, 6, "TASA MÚLTIPLE IEPS", 0, 0, 'R');
                        /*$this->SetXY(140, $y);
                        $this->SetTextColor(0, 0, 0);
                        $this->Cell(20, 6,"TASA MÚLTIPLE", 1, 0, 'R');*/
                        $this->SetXY($x_symbol,$y);
                        $this->Cell(2,6,"$",0,0,'L');
                        $this->SetXY($x_totals, $y);
                        $this->SetTextColor(0, 0, 0);
                        $this->Cell(20, 6, number_format(gpcround($totalIEPS,$this->decimalesfijos),$this->decimalesfijos,'.',','), 0, 0,'R');
                    }

                    /*foreach($this->IEPS as $k=>$v){
                        if($k != 0){
                            $tasaIEPS = number_format($k,2,'.','');
                            $importeIEPS = number_format($v,2,'.','');
                            $y += 5;
                            $this->SetXY($x_tot, $y);
                            $this->SetTextColor(0,0,0);
                            $this->Cell(20, 6, "IEPS", 0, 0, 'R');
                            $this->SetXY(150, $y);
                            $this->SetTextColor(0, 0, 0);
                            $this->Cell(20, 6,"$tasaIEPS %", 0, 0, 'R');
                            $this->SetXY($x_symbol,$y);
                            $this->Cell(2,6,"$",0,0,'L');
                            $this->SetXY($x_totals, $y);
                            $this->SetTextColor(0, 0, 0);
                            $this->Cell(20, 6, number_format(gpcround($importeIEPS,$this->decimalesfijos),$this->decimalesfijos,'.',','), 0, 0,'R');

                        }
                    }*/
                }
                #IVAS
                if ($iva_11 == 0 && $iva_16 == 0 && $iva_5==0 && $iva_0==0 && $iva_8==0){
                    $y += 5;
                    $this->SetXY($x_tot, $y);
                    $this->SetTextColor(0,0,0);
                    $this->Cell(20, 6, "I.V.A.", 0, 0, 'R');
                    $this->SetXY(150, $y);
                    $this->SetTextColor(0, 0, 0);
                    $this->Cell(20, 6,'', 0, 0, 'R');
                    $this->SetXY($x_symbol,$y);
                    $this->Cell(2,6,"$",0,0,'L');
                    $this->SetXY($x_totals, $y);
                    $this->SetTextColor(0, 0, 0);
                    $this->Cell(20, 6, 0.00, 0, 0,'R');
                }
                else{
                    if($iva_11<>0){
                        $y += 5;
                        $this->SetXY($x_tot, $y);
                        $this->SetTextColor(0,0,0);
                        $this->Cell(20, 6, "I.V.A.", 0, 0, 'R');
                        $this->SetXY(150, $y);
                        $this->SetTextColor(0, 0, 0);
                        $this->Cell(20, 6,'11 %', 0, 0, 'R');
                        $this->SetXY($x_symbol,$y);
                        $this->Cell(2,6,"$",0,0,'L');
                        $this->SetXY($x_totals, $y);
                        $this->SetTextColor(0, 0, 0);
                        $this->Cell(20, 6, number_format(gpcround($iva_11,$this->decimalesfijos),$this->decimalesfijos,'.',','), 0, 0,'R');
                    }
                    if($iva_16<>0){
                        $y += 5;
                        $this->SetXY($x_tot, $y);
                        $this->SetTextColor(0,0,0);
                        $this->Cell(20, 6, "I.V.A.", 0, 0, 'R');
                        $this->SetXY(150, $y);
                        $this->SetTextColor(0, 0, 0);
                        $this->Cell(20, 6,'16 %', 0, 0, 'R');
                        $this->SetXY($x_symbol,$y);
                        $this->Cell(2,6,"$",0,0,'L');
                        $this->SetXY($x_totals, $y);
                        $this->SetTextColor(0, 0, 0);
                        $this->Cell(20, 6, number_format(gpcround($iva_16,$this->decimalesfijos),$this->decimalesfijos,'.',','), 0, 0,'R');
                    }
                    if($iva_5<>0){
                        $y += 5;
                        $this->SetXY($x_tot, $y);
                        $this->SetTextColor(0,0,0);
                        $this->Cell(20, 6, "I.V.A.", 0, 0, 'R');
                        $this->SetXY(150, $y);
                        $this->SetTextColor(0, 0, 0);
                        $this->Cell(20, 6,'5 %', 0, 0, 'R');
                        $this->SetXY($x_symbol,$y);
                        $this->Cell(2,6,"$",0,0,'L');
                        $this->SetXY($x_totals, $y);
                        $this->SetTextColor(0, 0, 0);
                        $this->Cell(20, 6, number_format(gpcround($iva_5,$this->decimalesfijos),$this->decimalesfijos,'.',','), 0, 0,'R');
                    }
                    if($iva_8<>0){
                        $y += 5;
                        $this->SetXY($x_tot, $y);
                        $this->SetTextColor(0,0,0);
                        $this->Cell(20, 6, "I.V.A.", 0, 0, 'R');
                        $this->SetXY(150, $y);
                        $this->SetTextColor(0, 0, 0);
                        $this->Cell(20, 6,'8 %', 0, 0, 'R');
                        $this->SetXY($x_symbol,$y);
                        $this->Cell(2,6,"$",0,0,'L');
                        $this->SetXY($x_totals, $y);
                        $this->SetTextColor(0, 0, 0);
                        $this->Cell(20, 6, number_format(gpcround($iva_8,$this->decimalesfijos),$this->decimalesfijos,'.',','), 0, 0,'R');
                    }
                    if($iva_0 > 0 && $iva_11 == 0 && $iva_16 == 0 && $iva_5 == 0 && $iva_8==0){
                        $y += 5;
                        $this->SetXY($x_tot, $y);
                        $this->SetTextColor(0,0,0);
                        $this->Cell(20, 6, "I.V.A.", 0, 0, 'R');
                        $this->SetXY(150, $y);
                        $this->SetTextColor(0, 0, 0);
                        $this->Cell(20, 6,' 0%', 0, 0, 'R');
                        $this->SetXY($x_symbol,$y);
                        $this->Cell(2,6,"$",0,0,'L');
                        $this->SetXY($x_totals, $y);
                        $this->SetTextColor(0, 0, 0);
                        $this->Cell(20, 6, 0.00, 0, 0,'R');
                    }
                }
                
                #Retenciones ISR
                if ($this->DatosFactura["rRetenciones"]<>0){
                    $y += 5;
                    $this->SetXY($x_symbol,$y);
                    $this->Cell(2,6,"$",0,0,'L');
                    $this->SetXY($x_tot, $y);
                    $this->SetTextColor(0, 0, 0);
                    $this->Cell(20, 6, $this->DatosFactura["rPRetenciones"].' % RETENCION ISR', 0, 0, 'R');

                    $this->SetXY($x_totals, $y);
                    $this->SetTextColor(0, 0, 0);
                    $this->Cell(20, 6, number_format(gpcround($this->DatosFactura["rRetenciones"],$this->decimalesfijos),$this->decimalesfijos,'.',','), 0, 0, 'R');
                }

                #Retenciones IVA
                if ($this->DatosFactura["rRetencionesIVA"]<>0){
                    $y += 5;
                    $this->SetXY($x_symbol,$y);
                    $this->Cell(2,6,"$",0,0,'L');
                    $this->SetXY($x_tot, $y);
                    $this->SetTextColor(0, 0, 0);
                    if($this->Instancia == "bd_econta_tsieven"){
                        $this->Cell(20, 6, $this->DatosFactura["rPRetencionesIVA"].' % RETENCION', 0, 0, 'R');
                    } else {
                        $this->Cell(20, 6, $this->DatosFactura["rPRetencionesIVA"].' % RETENCION I.V.A.', 0, 0, 'R');
                    }
                    $this->SetXY($x_totals, $y);
                    $this->SetTextColor(0, 0, 0);
                    $this->Cell(20, 6, number_format(gpcround($this->DatosFactura["rRetencionesIVA"],$this->decimalesfijos),$this->decimalesfijos,'.',','), 0, 0, 'R');
                }

                # ISH 
                if( !empty( $this->ISH ) ) {
                    $y += 5;
                    $this->SetXY($x_symbol,$y);
                    $this->Cell(2,6,"$",0,0,'L');
                    $this->SetXY($x_tot, $y);
                    $this->SetTextColor(0, 0, 0);
                    $this->Cell(20, 6, $this->PISH.' % I.S.H.', 0, 0, 'R');

                    $this->SetXY($x_totals, $y);
                    $this->SetTextColor(0, 0, 0);
                    $this->Cell(20, 6, number_format(gpcround($this->ISH,$this->decimalesfijos),$this->decimalesfijos,'.',','), 0, 0, 'R');
                }
                
                #TOTAL
                $y += 5;
                $this->SetXY($x_tot, $y);
                //$this->SetTextColor($colores[0], $colores[1], $colores[2]);
                //$this->SetTextColor(19, 110, 170);
                $this->Cell(20, 6, "TOTAL", 0, 0, 'R');
                $this->SetXY($x_symbol,$y);
                $this->Cell(2,6,"$",0,0,'L');
                $this->SetXY($x_totals, $y);
                
                $this->Cell(20, 6, number_format(gpcround($this->DatosFactura["rTotal"],$this->decimalesfijos),$this->decimalesfijos,'.',','),0,0,'R');
                
                //FIN GENERICO/*/
                $this->SetFont('Arial', '', 8);
                $this->SetTextColor(0, 0, 0);
            }
        }

        function Detail(){

            switch($this->EsquemaFacturacion){
                case 1:
                        $limit_page = 190 - $this->RengCom;
                        if ($this->DatosFactura["rDescuento"]<>0){ $limit_page += -15; } else { $limit_page+=-5;}
                        if ($this->DatosFactura["rRetencionesIVA"]<>0){ $limit_page+= -5;}
                        if ($this->DatosFactura["rRetenciones"]<>0){ $limit_page+= -5; }
                        $this->limit = $limit_page;
                        $this->print_DetailCFD_CBB();

                        break;
                case 2:
                        $limit_page = 214;
                        if ($this->DatosFactura["rDescuento"]<>0){ $limit_page += -10; } else { $limit_page+=-5;}
                        if ($this->DatosFactura["rRetencionesIVA"]<>0){ $limit_page+= -5;}
                        if ($this->DatosFactura["rRetenciones"]<>0){ $limit_page+= -5; }
                        $this->limit = $limit_page;
                        $this->print_DetailCFD_CBB();
                        break;
                case 3:
                        $limit_page = 180 - $this->RengCom;
                        if ($this->DatosFactura["rDescuento"]<>0){ $limit_page += -10; } else { $limit_page+=-5;}
                        //if ($this->DatosFactura["rRetencionesIVA"]<>0){ $limit_page+= -5;}
                        if ($this->DatosFactura["rRetenciones"]<>0){ $limit_page+= -5; }
                        $this->limit = $limit_page;
                        $this->print_DetailCFDi();
                        break;
            }

        }

        function print_DetailCFDi(){
             $limit_page = $this->limit;

             if( $this->MostrarTotales == 0 ) {
                $limit_page += 35;
             }

             $this->DetailY = $this->CurrentY+8;
             $y             = $this->DetailY;
             $x_cant        = $this->CantX;
             $x_impu        = $this->ImpUX;
             $x_tot         = $this->TotX;
             $x_totals      = $this->TotalsX;
             $x_iva         = $this->IVAX;
             #$desc_limit    = 58;
             $total_detalle = count($this->DatosFacturaDetalle);

            for($w=0; $w < $total_detalle; $w++) {
                   $this->SetFontSize(8);
                   $this->SetXY(6, $y);
                   $this->MultiCell(8, 4, $w+1+$ww, 0, 'R');
                   $y_pos = null;

                   #$this->DatosFacturaDetalle[$w]["sDescripcion"] = $this->DatosFacturaDetalle[$w]["sDescripcion"] . ' 01234 56789 01234 56789 0123456789 012 3456789 012345 6789 01234567 9 0123456 789 0123 01234 56789 0123456789 012 3456789 012345 6789 01234567 9 0123456 789 0123';
                   #$this->DatosFacturaDetalle[$w]["sComentarios"] = $this->DatosFacturaDetalle[$w]["sComentarios"] . ' 01234 56789 01234 56789 0123456789 012 3456789 012345 6789 01234567 9 0123456 789 0123 01234 56789 0123456789 012 3456789 012345 6789 01234567 9 0123456 789 0123';

                   if( empty( $this->DatosFactura['sUsoCFDI'] ) ) {

                       $desc_limit    = 58;

                       $length = strlen($this->DatosFacturaDetalle[$w]["iProductoServicio"]) + strlen(utf8_decode($this->DatosFacturaDetalle[$w]["sDescripcion"]));
                       $renglones = ($length-($length % $desc_limit))/$desc_limit;
                       $reng_aux = $renglones;
                       if (($length % $desc_limit) > 0 ){ $renglones++;}

                       $this->SetXY(15, $y);
                       $this->MultiCell(107,4, $this->DatosFacturaDetalle[$w]["iProductoServicio"].' - '.utf8_decode($this->DatosFacturaDetalle[$w]["sDescripcion"]), 0, 'L');

                       #if ($renglones>1){ $y = $y + ($renglones*2.5); }

                       $y_pos = $this->GetY();
                   }
                   else {

                       $desc_limit    = 51;

                       $length = strlen($this->DatosFacturaDetalle[$w]["iProductoServicio"]) + strlen(utf8_decode($this->DatosFacturaDetalle[$w]["sDescripcion"]));
                       $renglones = ($length-($length % $desc_limit))/$desc_limit;
                       $reng_aux = $renglones;
                       if (($length % $desc_limit) > 0 ){ $renglones++;}

                       $this->SetXY(15, $y);
                       $this->MultiCell(87,4, $this->DatosFacturaDetalle[$w]["iProductoServicio"].' - '.utf8_decode($this->DatosFacturaDetalle[$w]["sDescripcion"]), 0, 'L');

                       #if ($renglones>1){ $y = $y + ($renglones*2.5); }

                       $y_pos = $this->GetY();

                       $this->SetXY(102,$y);
                       $this->SetFontSize(6);
                       $this->Cell(13, 4, '(' . $this->DatosFacturaDetalle[$w]['sClavePSSAT'] . ')' , 0, 0, 'R');
                       $this->SetFontSize(8);
                   }

                   $this->SetXY($x_cant, $y);
                   $Cantidad = NUMBER_FORMAT($this->DatosFacturaDetalle[$w]["rCantidad"],4,'.',',');
                   $this->Cell(10, 4, $Cantidad, 0, 0, 'R');
                   $UMe = trim($this->DatosFacturaDetalle[$w]["UnidadMedida"]);
                   $this->SetXY($x_cant+3, $y);
                   $this->Cell(20, 4, $UMe, 0, 0, 'R');
                   $this->SetXY(146, $y);
                   $this->Cell(3, 4, "\$ ", 0, 0, 'L');
                   $this->SetXY($x_impu, $y);
                   $this->Cell(23, 4, NUMBER_FORMAT(gpcround($this->DatosFacturaDetalle[$w]["rPrecioUnitario"],$this->decimales),$this->decimales,'.',','), 0, 0, 'R');



                   //Revisamos % de IVA:
                   if($this->DatosFacturaDetalle[$w]["iPctIVA"] != -1){
                      $this->SetXY($x_iva, $y);
                      $this->Cell(13, 4, "%", 0, 0, 'R');
                      $this->SetXY($x_iva, $y);
                      $DetiPctIVA = NUMBER_FORMAT($this->DatosFacturaDetalle[$w]["iPctIVA"],0,'.',',');
                   }else{
                      $this->SetXY($x_iva, $y);
                      $this->Cell(13, 4, "", 0, 0, 'R');
                      $this->SetXY(($x_iva+3), $y);
                      $DetiPctIVA = "Exento";
                   }

                   $this->Cell(10, 4,$DetiPctIVA, 0, 0, 'R');
                   $this->SetXY(183, $y);
                   $this->Cell(2, 4, "$", 0, 0, 'L');
                   $this->SetXY(190, $y);
                   $this->Cell(20, 4, number_format(gpcround($this->DatosFacturaDetalle[$w]["rPrecioExtendido"],$this->decimalesfijos),$this->decimalesfijos,'.',','), 0, 0, 'R');
                   #$y = $y + 4;
                   $y = $y_pos;
                   $total_no_comprobados = $total_no_comprobados + $this->DatosFacturaDetalle[$w]["rPrecioExtendido"];
                   $this->SetFontSize(7);
                   $length_comentario = strlen(utf8_decode($this->DatosFacturaDetalle[$w]["sComentarios"]));

                   if( !empty( $this->DatosFacturaDetalle[$w]['iPctIEPS'] ) ) {

                       if( $length_comentario > 0 ) {
                           $this->DatosFacturaDetalle[$w]["sComentarios"] .= ' (' . number_format( $this->DatosFacturaDetalle[$w]['iPctIEPS'], 2, '.', ',' ) . ' % IEPS - $ ' . number_format( $this->DatosFacturaDetalle[$w]['rIEPS'] , 2, '.', ',' ) . ')';
                       }
                       else {
                           $this->DatosFacturaDetalle[$w]["sComentarios"] = '(' . number_format( $this->DatosFacturaDetalle[$w]['iPctIEPS'], 2, '.', ',' ) . ' % IEPS - $ ' . number_format( $this->DatosFacturaDetalle[$w]['rIEPS'] , 2, '.', ',' ) . ')';
                       }

                   }

                   $length_comentario = strlen(utf8_decode($this->DatosFacturaDetalle[$w]["sComentarios"]));

                   if ($length_comentario > 0 AND $this->DatosFacturaDetalle[$w]["sComentarios"] != "NULL") {
                        $reng = ($length_comentario-($length_comentario % 50))/50;
                        if (($length_comentario % 50) > 0 ){ $reng++;}

                        if( empty( $this->DatosFactura['sUsoCFDI'] ) ) {
                            $this->SetXY(30, $y);
                            $this->MultiCell(91, 3, utf8_decode($this->DatosFacturaDetalle[$w]["sComentarios"]), 0, 'L');
                        }
                        else {
                            $this->SetXY(25, $y);
                            $this->MultiCell(81, 3, utf8_decode($this->DatosFacturaDetalle[$w]["sComentarios"]), 0, 'L');
                        }

                        #$this->SetXY(30, $y);
                        #$this->MultiCell(91, 3, utf8_decode($this->DatosFacturaDetalle[$w]["sComentarios"]), 0, 'L');
                        #$y = $y + ($reng*3);
                        $y = $this->GetY();
                   }
                   $Aduana = $this->DatosFacturaDetalle[$w]['sAduanaSAT'];
                   $Pedimento = $this->DatosFacturaDetalle[$w]['sPedimento'];
                   $FechaPedimento = $this->DatosFacturaDetalle[$w]['FechaPedimento'];
                   $str="";
                   if ($Aduana!="" AND $Aduana != '0'){ $str = "Aduana: $Aduana";}
                   if ($Pedimento!=""){ $str = "$str Pedimento: $Pedimento";}
                   if ($FechaPedimento!="00-00-0000" AND $FechaPedimento!="") {$str = "$str Fecha: $FechaPedimento";}
                   if (strlen($str)>0){$y+=1; $this->SetXY(30, $y); $this->Cell(91,3,$str,0,0,'L'); $y+=4; }

                   $PctIVA =  $this->DatosFacturaDetalle[$w]["iPctIVA"];
                   switch($PctIVA){
                            case 16:    $iva_16 += $this->DatosFacturaDetalle[$w]["rIVA"]; break;
                            case 11:    $iva_11 += $this->DatosFacturaDetalle[$w]["rIVA"]; break;
                            case  0:    $iva_0 ++; break;
                   }
                   if( ($y + 5) >= $limit_page){

                        if( $w != ( count($this->DatosFacturaDetalle) - 1 ) ) {

                            $this->print_FE_Elements();
                            $this->print_Totals();

                            $this->AddPage();
                            $this->Customized_Header($bd_empresa_host);

                            $y = $this->DetailY;
                        }

                        /*if( $this->MostrarTotales == 1 ) { // Se muestra el total en todas las p?ginas.

                            $this->print_FE_Elements();
                            $this->print_Totals();

                            // P?gina nueva.
                            $this->AddPage();
                            $this->Customized_Header($bd_empresa_host);

                            $y = $this->DetailY;
                        }
                        else { # Se muestra el total en la ?ltima p?gina.

                            // Averiguar si hay espacio en la ?ltima p?gina.
                            $iva_11 = $this->IVA11;
                            $iva_16 = $this->IVA16;
                            $iva_5 = $this->IVA5;

                            if ($this->DatosFactura["rDescuento"]<>0){ $ye += -15; } else { $ye +=- 5; }
                            if ($this->DatosFactura["rRetencionesIVA"]<>0){ $ye += -5; }
                            if ($this->DatosFactura["rRetenciones"]<>0){ $ye += -5; }
                            if ($iva_11 == 0 AND $iva_16 == 0 AND $iva_5==0){ $ye -= 5; }
                            if ($iva_16<>0){ $ye += -5; }
                            if ($iva_11<>0){ $ye += -5; }
                            if ($iva_5<>0){ $ye += -5; }
                            if(count($this->IEPS)>0){
                                $ye += count($this->IEPS) * -5;
                            }

                            $ye = abs($ye);

                            if( ( $y + $ye ) >=  ) {

                            }
                            else {

                            }

                            $this->print_FE_Elements();
                            $this->print_Totals();
                            $this->AddPage();
                            $this->Customized_Header($bd_empresa_host);

                            $y = $this->DetailY;
                        }*/
                   }
            }

            //VERIFICAMOS SI EXISTEN PRODUCTOS CON IVA%0:
            if($iva_0 > 0){$this->IVA0 = $iva_0;}
            // Ya termin? de imprimir items.
            $w = 0;

            // Determinar si hay espacio para los totales en la ?ltima p?gina.
            if( $this->MostrarTotales == 0 ) {

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
                /*if(count($this->IEPS)>0){
                    #$ye += (count($this->IEPS)*-5);
                    $ye += -5;
                }*/

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
            }
        }

        function print_FE_Elements(){
               switch($this->EsquemaFacturacion){
                            case 1:
                                    $this->FE_Elements_CFD();
                                    break;
                            case 2:
                                    $this->FE_Elements_CBB();
                                    break;
                            case 3:
                                    $this->FE_Elements_CFDi();
                                    break;
                }

        }

        function FE_Elements_CFDi(){

                #INFO DEL CFDI:
                $yy=215;
                $this->pdf_color_define();
                $this->SetFont($this->font, 'B', 6.5);
                $this->RoundedRect(6,$yy,165,5, 2, '12','DF');
                $this->SetTextColor(255, 255, 255);
                $this->SetXY(6,$yy);
                $titulo = 'ESTE DOCUMENTO ES UNA REPRESENTACIÓN IMPRESA DE UN CFDI';
                $this->Cell(165,5,strtoupper($titulo), 0, 0, 'C');
                $this->RoundedRect(6,($yy+5),165,15, 2, '34','D');
                //lineas grises:
                $this->SetTextColor(0, 0, 0);
                $this->SetFillColor('201','201','201');
                $this->SetDrawColor('201','201','201');
                $this->Line(82.5,($yy+15),82.5,($yy+5.5));
                $this->Line(6,($yy+15),171,($yy+15));

                $this->SetXY(65,($yy+15));
                $this->SetFont($this->font, '', 6.5);
                $this->Cell(15,6,'Emitido desde:',0,0, 'L');
                #logo econta - proveedor:
                $logo  = "images/img-home/img-logofoot.png";
                $x = 83; $w = 15;
                if(!empty($logo)){$this->Image($logo,$x,($yy+15.5),$w);}

                #Datos dentro de la info del CFDi:
                $MetodoPago = $this->MetodoPago;
                $RegimenFis = $this->Regimen;
                $FolioFis   = $this->UUID[ $this->DatosFactura['iFolio'] ];;
                $FormaPago  = $this->FormaPago;
                $UsoCFDi    = $this->UsoCfdi;
                if($this->CuentaBancaria != ""){$CuentaBancaria = $this->CuentaBancaria;}

                $yTmp = $yy+5.5;
                $this->SetFont($this->font, '', 6.5);
                $this->SetXY(6, $yTmp);
                $this->Cell (20, 3, 'Folio Fiscal',0,0, 'L');
                $this->SetXY(25, $yTmp);
                $this->Cell (2, 3, ':',0,0, 'L');
                $this->SetXY(27, $yTmp);
                $this->Cell (55, 3,$FolioFis,0,0, 'L');

                $this->SetXY(83, $yTmp);
                $this->Cell (20, 3, 'Forma de Pago',0,0, 'L');
                $this->SetXY(104, $yTmp);
                $this->Cell (2, 3, ':',0,0, 'L');
                $this->SetXY(106, $yTmp);
                $this->Cell (65, 3,substr($FormaPago, 0, 52),0,0, 'L');

                $yTmp += 3;
                $this->SetXY(6, $yTmp);
                $this->Cell (20, 3, 'Régimen Fiscal',0,0, 'L');
                $this->SetXY(25, $yTmp);
                $this->Cell (2, 3, ':',0,0, 'L');
                $this->SetXY(27, $yTmp);
                $this->Cell (55, 3,substr($RegimenFis, 0, 46),0,0, 'L');

                $yTmp += 3;
                $this->SetXY(6, $yTmp);
                $this->Cell (20, 3, 'Método de Pago',0,0, 'L');
                $this->SetXY(25, $yTmp);
                $this->Cell (2, 3, ':',0,0, 'L');
                $this->SetXY(27, $yTmp);
                $this->Cell (55, 3,substr($MetodoPago, 0, 46),0,0, 'L');

                if($CuentaBancaria != ""){
                   $this->SetXY(83, $yTmp-3);
                   $this->Cell (20, 3, 'Cuenta Bancaria',0,0, 'L');
                   $this->SetXY(104, $yTmp-3);
                   $this->Cell (2, 3, ':',0,0, 'L');
                   $this->SetXY(106, $yTmp-3);
                   $this->Cell (65, 3,$CuentaBancaria,0,0, 'L');
                }else{
                   $yTmp -= 3;
                }

                $this->SetXY(83, $yTmp);
                $this->Cell (20, 3, 'Uso del CFDi',0,0, 'L');
                $this->SetXY(104, $yTmp);
                $this->Cell (2, 3, ':',0,0, 'L');
                $this->SetXY(106, $yTmp);
                $this->Cell (65, 3,substr($UsoCFDi, 0, 52),0,0, 'L');

                #CADENA ORIGINAL:
               /*$this->SetFillColor(90,90,90);
                $this->SetDrawColor(90,90,90);*/
                $this->pdf_color_define();

                $yy+=21;
                $this->SetTextColor(255, 255, 255);
                $this->SetFont($this->font, 'B',6);
                $this->RoundedRect(6,$yy,165,3, 2, '12','DF');
                $this->RoundedRect(6,$yy,165,13, 2, '1234', 'D');
                $this->SetXY(6, $yy);
                $this->Cell(165,3,strtoupper(utf8_decode('Cadena Original')), 0, 0, 'C');

                $yy+=3.5;
                $this->SetXY(6, $yy);
                $this->SetTextColor(0, 0, 0);
                if(strlen($this->Cadena_Original)> 200){$tamano_letraCO = 5; $yyy=-2;}
                else{$tamano_letraCO = 6;$yyy=0;}
                $this->SetFont('Arial', '', $tamano_letraCO);
                $this->MultiCell(164,3, $this->Cadena_Original,0,'L',0);

                #Codigo QR:
                $this->SetFont('Arial', '', 8);
                $EmpresaRFC = $this->ConfiguracionSeleccionado["sRFC"];
                $RFC_C      = $this->DatosFactura['sRFCCliente'];
                $UUID       = $this->UUID[ $this->DatosFactura['iFolio'] ];
                $TotalCFD   = number_format($this->DatosFactura['rTotal'],2,'.','');
                $SelloE_qr  = substr($this->SelloCFD, -8);

                strlen($RFC_C) == 9 ? $RFC_C = "XEXX010101000" : "";

                if($UUID != ""){

                    if($_SERVER["HTTP_HOST"] == 'localhost:8080'){$url = $_SERVER['DOCUMENT_ROOT'].'\images\\';}
                    else{$url = "/tmp/";}
                    $CBB_Data="https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx?&id=$UUID&re=$EmpresaRFC&rr=$RFC_C&tt=$TotalCFD&fe=$SelloE_qr";
                    QRcode::png($CBB_Data,$url."$UUID.png",'H',4,2);
                    $this->Image($url."$UUID.png",176.5,$yy-25,35,35,'PNG');
                    unlink($url."$UUID.png"); // Eliminar temporal del QR.
                }
                else{
                    $QRTest  = "images/img_QR_vista_previa.png";
                    $x = 176.5; $w = 35;
                    if(!empty($QRTest)){$this->Image($QRTest,$x,($yy-25),$w,35,'PNG');}
                }

                #Sello Digital del Emisor
                $yy+=10.5;
                $this->RoundedRect(6,$yy,205,3,  2, '12','DF');
                $this->RoundedRect(6,$yy,205,11, 2,'1234','D');
                $this->SetFont($this->font, 'B', 6);
                $this->SetTextColor(255, 255, 255);
                $this->SetXY(6, $yy);
                $this->Cell(205, 3, 'SELLO DIGITAL DEL EMISOR', 0,0, 'C');

                $yy+=3.5;
                $this->SetTextColor(0, 0, 0);
                if(strlen($this->SelloCFD)> 200){$tamano_letra_Sello_CO = 5; $yyy=0;}else{$tamano_letra_Sello_CO = 6;$yyy=0;}
                $this->SetXY(6, $yy+$yyy);
                $this->SetFont('Arial', '',$tamano_letra_Sello_CO);
                $this->MultiCell(205,3,$this->SelloCFD,0,'L',0);

                #Sello Digital SAT
                $yy+=8.5;
                $this->RoundedRect(6,$yy,205, 3, 2, '12','DF');
                $this->RoundedRect(6,$yy,205,11, 2, '1234','D');
                $this->SetFont($this->font, 'B', 6);
                $this->SetTextColor(255, 255, 255);
                $this->SetXY(6, $yy);
                $this->Cell(205,3, 'SELLO DIGITAL DEL SAT', 0,0, 'C');

                $yy+=3.5;
                $this->SetTextColor(0, 0, 0);
                if(strlen($this->SelloSAT) > 200){$this->SetFont('Arial', '', 5);}else {$this->SetFont('Arial', '', 6);}
                $this->SetXY(6, $yy);
                $this->MultiCell(205, 3, $this->SelloSAT,0,'L',0);
                $yy+=4;

                /*$yy+=9;
                $this->SetXY(6, $yy);
                $this->SetTextColor($colores[0], $colores[1], $colores[2]);
                $this->SetFont('Arial', 'B', 8);
                $this->Cell(40, 5, 'FOLIO FISCAL', 0, 0, 'J');
                $yy+=5;//207
                $this->RoundedRect(6, $yy, 60, 11, 2, '1234', 'D');
                $this->RoundedRect(69, $yy, 140, 11, 2, '1234', 'D');
                $yy+=1;//208
                $this->SetXY(6, $yy);
                $this->SetTextColor(0, 0, 0);
                $this->SetFont('Arial', 'B', 7);
                $this->MultiCell(60, 3,"\n". $UUID,0,'L',0);
                $this->SetFont('Arial', '', 8);
                $this->SetXY(69, $yy);
                $this->MultiCell(120, 3.25, $Cta_Regimen,0,'L',0);
                //CBB
                $this->SetFont('Arial', 'B', 9);
                if ($this->DatosFactura["tipoCF"]=="CREDITO"){$Devolucion=" DEVOLUCION";}else{$Devolucion = "";}
                //$mensaje = "EFECTOS FISCALES AL PAGO\nPAGO EN UNA SOLA EXHIBICI?N$Devolucion";
                //$mensaje2 = "ESTE DOCUMENTO ES UNA REPRESENTACI?N IMPRESA DE UN CFDI";

                $yy+=11;
                $this->SetFont('Arial', '', 5);
                $this->SetXY(6, $yy);
                $this->MultiCell(175, 2, $mensaje, 0, 'J', 0);

                //$yy+=10;
                $this->SetFont('Arial', '', 7);
                $this->SetXY(120, $yy+1);
                $this->Cell(60, 2, $mensaje2, 0,0, 'L', 0);*/

        }

        function Footer()   {

                //Position at 1.5 cm from bottom
                $this->SetY(-7);
                $this->SetFont('Arial','',6);
                $this->SetTextColor(90, 90, 90);
                $page         = $this->PageNo();
                $this->UsoCfdi != "" ? $version = '3.3' : $version = '3.2';
                $totalpaginas = '{nb}';

                $this->Cell(50,10,"Versión del comprobante: ".$version,0,0,'L');
                $this->Cell(0,10,"Página $page de $totalpaginas",0,0,'R');

                //Verificar si es Vista previa (aun no esta timbrada la nomina) Tiene folio vacio;
                if($this->FolioImpreso == "" && $this->DatosFactura['bEstatus'] == 0){
                     //Put the watermark
                     $this->SetFont('Arial','B',50);
                     $this->SetAlpha(0.6);
                     $this->SetTextColor(212,212,212);
                     $txt = utf8_decode('V i s t a  P r e v i a  F a c t u r a');
                     $this->RotatedText(20,230,$txt,48);
                }else if($this->DatosFactura['bEstatus'] == 3){
                     //Put the watermark
                     $this->SetFont('Arial','B',55);
                     $this->SetAlpha(0.6);
                     $this->SetTextColor(223,23,23);
                     $txt = utf8_decode(' F a c t u r a   C a n c e l a d a ');
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
        
        $ifolio = $F[$i]["iFolio"];
        $pdf->AddPage();
        $pdf->SetLineWidth(0.2);
        $pdf->init($folio, $invoice, $compania, $nombre_pdf, $ds);
        $pdf->pdf_header();
        //$pdf->Detail(); 
        
    }
    else{
        echo '<script language="javascript">alert(\''.$mensaje.'\')</script>';
        echo "<script language='javascript'>window.close();</script>"; 
    }    

?>

