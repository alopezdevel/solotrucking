<?php
  ini_set('max_execution_time',5000);
  require('lib/fpdf153/fpdf.php');//Cargando  libreria
  include_once("funciones_documentos.php");
  
  #PARAMETROS 
  $error = "0";
  //$consecutivo_doc = $_GET['consecutivo_doc'];
  $_GET['id_compania'] != "" ? $id_compania = $_GET['id_compania'] : $error = '1';
  $_GET['cd'] != '' ? $consecutivo_drivers = $_GET['cd'] : $consecutivo_drivers = ""; 
  $_GET['ce'] != '' ? $consecutivo_equipment = $_GET['ce'] : $consecutivo_equipment = "";
  $_GET['id_comm'] != '' ? $consecutivo_commodities = $_GET['id_comm'] : $consecutivo_commodities = ""; 
  
  #CLASE PDF
  class PDF extends FPDF{ 
       function PDF($orientation='P', $unit='mm', $format='Letter'){
            $this->FPDF($orientation, $unit, $format);
            //Register var stream protocol
            stream_wrapper_register('var', 'VariableStream');
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
            $this->_out(sprintf('%.1F %.1F m',($x+$r)*$k,($hp-$y)*$k ));

            $xc = $x+$w-$r;
            $yc = $y+$r;
            $this->_out(sprintf('%.1F %.1F l', $xc*$k,($hp-$y)*$k ));
            if (strpos($corners, '2')===false)
                $this->_out(sprintf('%.1F %.1F l', ($x+$w)*$k,($hp-$y)*$k ));
            else
                $this->_Arc($xc + $r*$MyArc, $yc - $r, $xc + $r, $yc - $r*$MyArc, $xc + $r, $yc);

            $xc = $x+$w-$r;
            $yc = $y+$h-$r;
            $this->_out(sprintf('%.1F %.1F l',($x+$w)*$k,($hp-$yc)*$k));
            if (strpos($corners, '3')===false)
                $this->_out(sprintf('%.1F %.1F l',($x+$w)*$k,($hp-($y+$h))*$k));
            else
                $this->_Arc($xc + $r, $yc + $r*$MyArc, $xc + $r*$MyArc, $yc + $r, $xc, $yc + $r);

            $xc = $x+$r;
            $yc = $y+$h-$r;
            $this->_out(sprintf('%.1F %.1F l',$xc*$k,($hp-($y+$h))*$k));
            if (strpos($corners, '4')===false)
                $this->_out(sprintf('%.1F %.1F l',($x)*$k,($hp-($y+$h))*$k));
            else
                $this->_Arc($xc - $r*$MyArc, $yc + $r, $xc - $r, $yc + $r*$MyArc, $xc - $r, $yc);

            $xc = $x+$r ;
            $yc = $y+$r;
            $this->_out(sprintf('%.1F %.1F l',($x)*$k,($hp-$yc)*$k ));
            if (strpos($corners, '1')===false) {
                $this->_out(sprintf('%.1F %.1F l',($x)*$k,($hp-$y)*$k ));
                $this->_out(sprintf('%.1F %.1F l',($x+$r)*$k,($hp-$y)*$k ));
            }  else
                $this->_Arc($xc - $r, $yc - $r*$MyArc, $xc - $r*$MyArc, $yc - $r, $xc, $yc - $r);
            $this->_out($op);
       }
       function _Arc($x1, $y1, $x2, $y2, $x3, $y3)  {
            $h = $this->h;
            $this->_out(sprintf('%.1F %.1F %.1F %.1F %.1F %.1F c ', $x1*$this->k, ($h-$y1)*$this->k,
                $x2*$this->k, ($h-$y2)*$this->k, $x3*$this->k, ($h-$y3)*$this->k));
       }  
       //Variables:
       var $DatosCompania;
       var $DatosUnits;
       var $DatosDrivers;
       var $DatosCommodities;
       var $font = "Helvetica";
       var $color = array(0,0,0);
       var $color_content = array(41,125,185);
       var $Y = 0;
       
       function init($id_compania,$consecutivo_drivers,$consecutivo_equipment,$consecutivo_commodities){
            getCompaniaPDF($id_compania,$this->DatosCompania);
            getDriversPDF($consecutivo_drivers,$this->DatosDrivers);  
            getEquipmenTrailertPDF($consecutivo_equipment,$this->DatosUnits);        
            getCommoditiesHauled($consecutivo_commodities,$this->DatosCommodities);   
       }
       function font_default(){
           $this->SetFont($this->font,'',11);
           $this->SetTextColor($this->color[0],$this->color[1],$this->color[2]); 
       }  
       function color_default(){
           $this->SetFillColor(240,240,240);
           $this->SetDrawColor(0,0,0);
       } 
       function print_header(){
           
           $this->font_default();
           $logo = "images/img-logo.jpg";
           $tit  = utf8_decode("SOLO-TRUCKING INSURANCE AGENCY");
           $des  = "UNIVERSAL QUICK QUOTE FORM";
           $x = 10; 
           $y = 13; 
           $w = 60;
           if(!empty($logo)){$this->Image($logo,$x,$y,$w);} 
           $this->SetXY(($x+65),($y+3)); 
           $this->SetFont($this->font,'B',14);
           $this->Cell(120,8,$tit,0,0, 'L');
           $this->SetXY(($x+65),($y+8)); 
           $this->SetFont($this->font,'',10);
           $this->SetTextColor(104, 104, 104);
           $this->Cell(120,8,$des,0,0, 'L');
           
       }  
       function print_data_1(){
           #PLANTILLA
           $bind_date = utf8_decode("Bind Effective: ___ / ____ / ______"); 
           $quot_date = utf8_decode("Quote Needed by: ___ / ____ / ______"); 
           $x = 10; 
           $y = 35; 
           $this->font_default();
           $this->SetXY($x,$y); 
           $this->Cell(70,10,$bind_date,0,0,'L');
           $this->SetXY($x+125,$y); 
           $this->Cell(70,10,$quot_date,0,0,'L');
           #DATOS
           $date_bind = $_GET['dbind']; 
           $date_quot = $_GET['dquot'];
           $this->SetTextColor($this->color_content[0],$this->color_content[1],$this->color_content[2]);  
           if($date_bind != ''){
               $x = 37;
               $date_bind = strtotime($date_bind);
               $this->SetXY($x,$y);
               $this->Write(10,date('m',$date_bind));
               $this->SetXY($x+10,$y);
               $this->Write(10,date('d',$date_bind));
               $this->SetXY($x+22,$y);
               $this->Write(10,date('Y',$date_bind));
           }
           if($date_quot != ''){
               $x = 169;
               $date_quot = strtotime($date_quot);
               $this->SetXY($x,$y);
               $this->Write(10,date('m',$date_quot));
               $this->SetXY($x+10,$y);
               $this->Write(10,date('d',$date_quot));
               $this->SetXY($x+22,$y);
               $this->Write(10,date('Y',$date_quot));
           }
           
           
       }
       function print_data_2(){
           #PLANTILLA:
           $x = 10; 
           $y = 45;  
           $title = utf8_decode("INSURED INFORMATION");
           $this->font_default();
           $this->SetFont($this->font,'B',11);
           $this->SetXY($x,$y);
           $this->Cell(196,10,$title,0,0,'C'); 
           $this->font_default();
           $this->SetXY($x,$y+12); 
           $this->Cell(30,6,utf8_decode("Insured's Name:"),0,0,'L');
           $this->Line($x+31,$y+17,205,$y+17); 
           #DATOS:
           $this->SetTextColor($this->color_content[0],$this->color_content[1],$this->color_content[2]); 
           $this->SetXY($x+31,$y+12);
           $this->Write(6,utf8_decode($this->DatosCompania[0]['compania']));
           $this->font_default(); 
           
           $y = 66;
           $this->SetXY($x,$y); 
           $this->Cell(20,6,utf8_decode("US DOT#:"),0,0,'L');
           $this->Line($x+21,$y+4.5,60,$y+4.5);
           $this->SetXY($x+50,$y); 
           $this->Cell(12,6,utf8_decode("MC#:"),0,0,'L');
           $this->Line($x+62,$y+4.5,101,$y+4.5);
           $this->SetXY($x+91,$y); 
           $this->Cell(20,6,utf8_decode("TX DMV#:"),0,0,'L');
           $this->Line($x+111,$y+4.5,150,$y+4.5);
           $this->SetXY($x+141,$y); 
           $this->Cell(25,6,utf8_decode("FEIN or SS#:"),0,0,'L');
           $this->Line($x+166,$y+4.5,205,$y+4.5); 
           #DATOS:
           $this->SetTextColor($this->color_content[0],$this->color_content[1],$this->color_content[2]); 
           $this->SetXY($x+21,$y);
           $this->Write(6,utf8_decode($this->DatosCompania[0]['us_dot']));
           $this->SetXY($x+62,$y);
           $this->Write(6,utf8_decode($this->DatosCompania[0]['MC']));
           $this->SetXY($x+111,$y);
           $this->Write(6,utf8_decode($this->DatosCompania[0]['DMV']));
           $this->SetXY($x+165,$y);
           $this->Write(6,utf8_decode($this->DatosCompania[0]['FEIN'])); 
           $this->font_default(); 
           
           $y = 75;
           $this->SetXY($x,$y);
           $this->Cell(17,6,utf8_decode('Address:'),0,0,'L');
           $this->Line($x+18,$y+4.5,114,$y+4.5);  
           $this->SetXY($x+104,$y);
           $this->Cell(10,6,utf8_decode('City:'),0,0,'L');
           $this->Line($x+114,$y+4.5,157,$y+4.5); 
           $this->SetXY($x+147,$y);
           $this->Cell(12,6,utf8_decode('State:'),0,0,'L');
           $this->Line($x+159,$y+4.5,205,$y+4.5); 
           #DATOS:
           $this->SetTextColor($this->color_content[0],$this->color_content[1],$this->color_content[2]); 
           $this->SetXY($x+18,$y);
           $this->Write(6,utf8_decode($this->DatosCompania[0]['direccion']));
           $this->SetXY($x+114,$y);
           $this->Write(6,utf8_decode($this->DatosCompania[0]['ciudad']));
           $this->SetXY($x+159,$y);
           $this->Write(6,utf8_decode($this->DatosCompania[0]['Estado']));
           $this->font_default(); 
           
           $y = 85;
           $this->SetXY($x,$y); 
           $this->Cell(18,6,utf8_decode("Zip Code:"),0,0,'L');
           $this->Line($x+19,$y+4.5,49,$y+4.5);
           $this->SetXY($x+39,$y); 
           $this->Cell(10,6,utf8_decode("PH#:"),0,0,'L');
           $this->Line($x+50,$y+4.5,98,$y+4.5);
           $this->SetXY($x+88,$y); 
           $this->Cell(12,6,utf8_decode("FAX#:"),0,0,'L');
           $this->Line($x+100,$y+4.5,148,$y+4.5);
           $this->SetXY($x+138,$y); 
           $this->Cell(14,6,utf8_decode("E-mail:"),0,0,'L');
           $this->Line($x+152,$y+4.5,205,$y+4.5);
           #DATOS:
           $this->SetTextColor($this->color_content[0],$this->color_content[1],$this->color_content[2]); 
           $this->SetXY($x+19,$y);
           $this->Write(6,utf8_decode($this->DatosCompania[0]['codigoPostal']));
           $this->SetXY($x+50,$y-.5);
           $this->Write(6,utf8_decode($this->DatosCompania[0]['telefono_principal']));
           $this->SetXY($x+100,$y-.5);
           $this->Write(6,utf8_decode($this->DatosCompania[0]['Telefono2']));
           $this->SetXY($x+152,$y-.5);
           $this->Write(6,utf8_decode($this->DatosCompania[0]['email']));
           $this->font_default(); 
           
           $y = 95;
           $this->SetXY($x,$y); 
           $this->Cell(82,6,utf8_decode("No. of Years in Business (With own insurance):"),0,0,'L');
           $this->Line($x+83,$y+4.5,102,$y+4.5);
           $this->SetXY($x+92,$y);
           $this->Cell(90,6,utf8_decode('No. of Years’ experience operating like equipment:'),0,0,'L');
           $this->Line($x+182,$y+4.5,205,$y+4.5);
           #DATOS:
           if($_GET['yb'] != '' || $_GET['ybo'] != ''){
               $this->SetTextColor($this->color_content[0],$this->color_content[1],$this->color_content[2]); 
               $this->SetXY($x+83,$y);
               $this->Write(6,utf8_decode($_GET['yb']));
               $this->SetXY($x+182,$y);
               $this->Write(6,utf8_decode($_GET['ybo']));
               $this->font_default();   
           }
            
           $y = 105;
           $this->SetXY($x,$y); 
           $this->Cell(40,6,utf8_decode("Radius of Operation:"),0,0,'L');
           $this->SetXY($x+45,$y-1); 
           $this->Cell(8,8,"",1,0,'L');
           $this->SetXY($x+54,$y); 
           $this->Cell(20,6,utf8_decode("0-200"),0,0,'L');
           $this->SetXY($x+78,$y-1); 
           $this->Cell(8,8,"",1,0,'L');
           $this->SetXY($x+87,$y); 
           $this->Cell(20,6,utf8_decode("0-500"),0,0,'L');
           $this->SetXY($x+111,$y-1); 
           $this->Cell(8,8,"",1,0,'L');
           $this->SetXY($x+120,$y); 
           $this->Cell(20,6,utf8_decode("500+"),0,0,'L'); 
           #DATOS:
           if($_GET['radio'] != ''){
               $this->SetFillColor($this->color_content[0],$this->color_content[1],$this->color_content[2]); 
               switch($_GET['radio']){
                  case "200":  $x = 56;  break;
                  case "500":  $x = 89;  break;
                  case "500p": $x = 122; break;  
               }
               $this->SetXY($x,$y); 
               $this->RoundedRect($x,$y,6,6, 1,'', 'F');
           }
           
           #TABLA DE CARRIERS:
           if($_GET['pc'] != ''){
               $priorCarriers = explode('|',$_GET['pc']);
               $totalC = count($priorCarriers);
               $this->color_default();
               $y = 115;
               $x = 10;
               $this->RoundedRect($x,$y,196, 8, 1, '12', 'FD');
               $this->SetXY($x,$y); 
               $this->Cell(196,8,utf8_decode("Who is the prior carrier?"),0,0,'L');
               $y = 123;
               for($z=0;$z<$totalC;$z++){
                   $this->SetTextColor($this->color_content[0],$this->color_content[1],$this->color_content[2]);
                   $this->SetXY($x,$y); 
                   $this->Cell(196,6,utf8_decode($priorCarriers[$z]),1,0,'L');
                   $y+=6;
               }
               $this->Y=$y+5;  
           }
     
       }
       function print_commodities(){
       
           $this->font_default();
           $x = 10;
           $this->Y != 0 ? $y = $this->Y : $y = 115;  
           $title = utf8_decode("COMMODITIES HAULED:");
           $this->SetFont($this->font,'B',11);
           $this->SetXY($x,$y);
           $this->Cell(196,10,$title,0,0,'L');
           $this->font_default();
           
           $y += 10;
           $this->SetXY($x,$y-1); 
           $this->Cell(8,8,"",1,0,'L');
           $this->SetXY($x+9,$y); 
           $this->Cell(20,6,utf8_decode("Refrigerated"),0,0,'L');
           $this->SetXY($x+42,$y-1); 
           $this->Cell(8,8,"",1,0,'L');
           $this->SetXY($x+51,$y); 
           $this->Cell(20,6,utf8_decode("Dry Van"),0,0,'L');
           $this->SetXY($x+82,$y-1); 
           $this->Cell(8,8,"",1,0,'L');
           $this->SetXY($x+91,$y); 
           $this->Cell(20,6,utf8_decode("Flatbed"),0,0,'L');
           #DATOS: 
           $this->SetFillColor($this->color_content[0],$this->color_content[1],$this->color_content[2]);  
           if($_GET['com_re'] == "1"){$this->RoundedRect($x+1,$y,6,6, 1,'', 'F');}
           if($_GET['com_dv'] == "1"){$this->RoundedRect($x+43,$y,6,6, 1,'', 'F');}
           if($_GET['com_fl'] == "1"){$this->RoundedRect($x+83,$y,6,6, 1,'', 'F');} 
  
           $y += 10;
           $this->color_default();  
           $this->RoundedRect($x,$y,196, 8, 1, '12', 'FD');
           $this->SetXY($x,$y); 
           $this->Cell(70,8,utf8_decode("Name of Commodity"),0,0,'L');
           $this->Cell(40,8,utf8_decode("%Hauled"),1,0,'L'); 
           $this->Cell(43,8,utf8_decode("Minimum Value"),1,0,'L'); 
           $this->Cell(43,8,utf8_decode("Maximum Value"),0,0,'L'); 
           $y += 8;
           $TotalComm = count($this->DatosCommodities);
           $this->SetTextColor($this->color_content[0],$this->color_content[1],$this->color_content[2]);  
           for($z=0;$z<$TotalComm;$z++){
               $this->SetXY($x,$y); 
               $this->Cell(70,6,utf8_decode($this->DatosCommodities[$z]['commoditie']),1,0,'L');
               $this->Cell(40,6,utf8_decode($this->DatosCommodities[$z]['porcentaje_hauled'].' %'),1,0,'C');
               $this->Cell(43,6, '$'.number_format(utf8_decode($this->DatosCommodities[$z]['valor_minimo']), 2, '.', ' '),1,0,'R');
               $this->Cell(43,6, '$'.number_format(utf8_decode($this->DatosCommodities[$z]['valor_maximo']), 2, '.', ' '),1,0,'R');
               $y+=6;
               if($y >= 254){ 
                   $this->pdf_footer();
                   $this->AddPage();
                   $this->print_header();
                   $y = 35;
                   if(($z+1)<$TotalComm){ 
                       //Volvemos a imprimir los headers de la tabla:
                       $this->font_default();
                       $this->color_default();  
                       $this->RoundedRect($x,$y,196, 8, 1, '12', 'FD');
                       $this->SetXY($x,$y); 
                       $this->Cell(70,8,utf8_decode("Name of Commodity"),0,0,'L');
                       $this->Cell(40,8,utf8_decode("%Hauled"),1,0,'L'); 
                       $this->Cell(43,8,utf8_decode("Minimum Value"),1,0,'L'); 
                       $this->Cell(43,8,utf8_decode("Maximum Value"),0,0,'L'); 
                       $y += 8;
                   }
               }
               
           } 
           $this->Y=$y+5; 
       }
       function print_drivers(){
            $this->font_default();
            $x = 10;
            $this->Y != 0 ? $y = $this->Y : $y = 115;  
            $title = utf8_decode("DRIVER(S) INFORMATION:"); 
            $this->SetFont($this->font,'B',11);
            $this->SetXY($x,$y);
            $this->Cell(196,10,$title,0,0,'L'); 
            $this->SetFont($this->font,'',9); 
            $this->SetXY($x+2,$y+8);
            $texto = utf8_decode("Specify the number of year's commercial driving experience each driver has. If there are any drivers with a \"not at fault\" accident, please provide a copy of the policy report with your submission.");
            $this->MultiCell(194,3,$texto,0,'J',false); 
            $this->SetTextColor('238','45','36'); 
            $this->SetXY($x,$y+8);
            $this->Cell(5,3,'*',0,0,'L');
            
            $y += 18;
            $this->font_default();
            $this->color_default();  
            $this->RoundedRect($x,$y,196, 8, 1, '12', 'FD');
            $this->SetXY($x,$y); 
            $this->Cell(110,8,utf8_decode("Full Name"),0,0,'L');
            $this->Cell(30,8,utf8_decode("DOB"),1,0,'L'); 
            $this->Cell(35,8,utf8_decode("License Number"),1,0,'L'); 
            $this->Cell(21,8,utf8_decode("Exp. Years"),0,0,'L');
            $y += 8;
            $TotalD = count($this->DatosDrivers);  
            for($z=0;$z<$TotalD;$z++){
               $this->SetTextColor($this->color_content[0],$this->color_content[1],$this->color_content[2]); 
               $this->SetXY($x,$y); 
               $this->Cell(110,6,utf8_decode($this->DatosDrivers[$z]['nombre']),1,0,'L');
               $this->Cell(30,6,utf8_decode($this->DatosDrivers[$z]['fecha_nacimiento']),1,0,'C');
               $this->Cell(35,6,utf8_decode($this->DatosDrivers[$z]['num_licencia']),1,0,'L');
               $this->Cell(21,6,utf8_decode($this->DatosDrivers[$z]['exp_year']),1,0,'C');
               $y+=6;
               if($y >= 254){ 
                   $this->pdf_footer();
                   $this->AddPage();
                   $this->print_header();
                   $y = 35;
                   if(($z+1)<$TotalD){
                     //Volvemos a imprimir los headers de la tabla:
                     $this->font_default();
                     $this->color_default();  
                     $this->RoundedRect($x,$y,196, 8, 1, '12', 'FD');
                     $this->SetXY($x,$y); 
                     $this->Cell(110,8,utf8_decode("Full Name"),0,0,'L');
                     $this->Cell(30,8,utf8_decode("DOB"),1,0,'L'); 
                     $this->Cell(35,8,utf8_decode("License Number"),1,0,'L'); 
                     $this->Cell(21,8,utf8_decode("Exp. Years"),0,0,'L');  
                     $y += 8;     
                   }
                   
                   
               }
            } 
            $this->Y=$y+5;
       }
       function print_equipment(){ 
            $this->font_default();
            $x = 10;
            $this->Y != 0 ? $y = $this->Y : $y = 115; 
            
            //Verificamos que por lo menos queden  150 pt, si no agregamos una nueva pagina:
            $limite = 260 - $y;
            if($limite < 130){
                $this->pdf_footer();
                $this->AddPage();
                $this->print_header(); 
                $y = 35; 
                $this->font_default();     
            }
             
            $title = utf8_decode("VEHICLE(S) INFORMATION:"); 
            $this->SetFont($this->font,'B',11);
            $this->SetXY($x,$y);
            $this->Cell(196,10,$title,0,0,'L'); 
            $this->SetFont($this->font,'',9); 
            $this->SetXY($x+2,$y+8);
            $texto = utf8_decode("If there are 5 or more power units, please provide a completed ACORD or completed company application instead of this form for quoting.");
            $this->MultiCell(194,3,$texto,0,'J',false); 
            $this->SetTextColor('238','45','36'); 
            $this->SetXY($x,$y+8);
            $this->Cell(5,3,'*',0,0,'L');
            
            $y += 18;
            $this->font_default();
            $this->color_default();  
            $this->RoundedRect($x,$y,196, 8, 1, '12', 'FD');
            $this->SetXY($x,$y); 
            $this->Cell(56,8,utf8_decode("VIN Number"),0,0,'L');
            $this->Cell(20,8,utf8_decode("Year"),1,0,'L'); 
            $this->Cell(27,8,utf8_decode("Make"),1,0,'L'); 
            $this->Cell(20,8,utf8_decode("Type"),1,0,'L');
            $this->Cell(20,8,utf8_decode("Radius"),1,0,'L'); 
            $this->Cell(28,8,utf8_decode("Stated Value"),1,0,'L'); 
            $this->Cell(25,8,utf8_decode("Deductible"),0,0,'L');
            $y += 8;
            $Total = count($this->DatosUnits);  
            for($z=0;$z<$Total;$z++){
               $this->SetTextColor($this->color_content[0],$this->color_content[1],$this->color_content[2]); 
               $this->SetXY($x,$y);                                                           
               $this->Cell(56,6,utf8_decode($this->DatosUnits[$z]['VIN']),1,0,'L');
               $this->Cell(20,6,utf8_decode($this->DatosUnits[$z]['year']),1,0,'C');
               $this->Cell(27,6,utf8_decode($this->DatosUnits[$z]['make']),1,0,'L');
               $this->Cell(20,6,utf8_decode($this->DatosUnits[$z]['body_type']),1,0,'C'); 
               $this->Cell(20,6,utf8_decode($this->DatosUnits[$z]['radio_desc']),1,0,'C');
               $this->DatosUnits[$z]['stated_value'] != "" ? $stated_value  = '$ '.number_format($this->DatosUnits[$z]['stated_value']) : $stated_value = "";
               $this->Cell(28,6,$stated_value,1,0,'R'); 
               $this->DatosUnits[$z]['deductible'] != "" ? $deductible  = '$ '.number_format($this->DatosUnits[$z]['deductible']) : $deductible = "";
               $this->Cell(25,6,$deductible,1,0,'R');
           
               $y+=6;
               
               if($y >= 254){ 
                   $this->pdf_footer();
                   $this->AddPage();
                   $this->print_header();
                   $y = 35;
                   if(($z+1)<$Total){ 
                       //Volvemos a imprimir los headers de la tabla:
                       $this->font_default();
                       $this->color_default();  
                       $this->RoundedRect($x,$y,196, 8, 1, '12', 'FD');
                       $this->SetXY($x,$y); 
                       $this->Cell(60,8,utf8_decode("VIN Number"),0,0,'L');
                       $this->Cell(25,8,utf8_decode("Year"),1,0,'L'); 
                       $this->Cell(35,8,utf8_decode("Make"),1,0,'L'); 
                       $this->Cell(20,8,utf8_decode("Type"),1,0,'L');
                       $this->Cell(30,8,utf8_decode("Stated Value"),1,0,'L'); 
                       $this->Cell(26,8,utf8_decode("Radius"),0,0,'L');
                       $y += 8;
                   }
               }
            } 
            $this->Y=$y+5;
       }  
       function print_data_3(){
           $this->font_default();
           $x = 10;
           $this->Y != 0 ? $y = $this->Y : $y = 115;  
           
           //Verificamos que por lo menos queden  150 pt, si no agregamos una nueva pagina:
           $limite = 260 - $y;
           if($limite < 160){
                $this->pdf_footer();
                $this->AddPage();
                $this->print_header(); 
                $y = 35; 
                $this->font_default();     
           }
           
           $title = utf8_decode("PRIOR INSURANCE HISTORY FOR THE PAST 3 YEARS:"); 
           $this->SetFont($this->font,'B',11);
           $this->SetXY($x,$y);
           $this->Cell(196,10,$title,0,0,'L'); 
           //$this->SetFont($this->font,'',9); 
           $this->font_default(); 
           $this->SetXY($x,$y+8);
           $texto = utf8_decode("Please see the loss runs for any loss over $25,000 for physical Damage or Motor Truck Cargo past 3 years with explanation in the attachments files.");
           $this->MultiCell(196,4,$texto,0,'J',false); 
           
           $y += 20;
           $this->SetFont($this->font,'B',11);
           $this->SetXY($x,$y);
           $this->Cell(196,10,utf8_decode("COVERAGES INFORMATION"),0,0,'C');
           #AUTO LIABILITY:
           $y += 9;
           $this->SetXY($x,$y);
           $this->Cell(196,10,utf8_decode("Auto Liability:"),0,0,'L');
           $this->font_default();
           for($z=0;$z<6;$z++){
              $this->SetXY($x,$y+10);  
              $this->Cell(6,6,"",1,0,'L');
              switch($z){
                  case 0: $txt = utf8_decode('$100K CSL');break;
                  case 1: $txt = utf8_decode('$300K CSL');break; 
                  case 2: $txt = utf8_decode('$500K CSL');break; 
                  case 3: $txt = utf8_decode('$750K CSL');break; 
                  case 4: $txt = utf8_decode('$1M CSL');break; 
                  case 5: $txt = utf8_decode('Other:________');break; 
              }
              $this->SetXY($x+6,$y+10);  
              $this->Cell(20,6,$txt,0,0,'L');
              $x+=32.1;  
           }
           #DATOS- VALORES AUTO LI.
           if($_GET['al'] != '' && $_GET['al'] != 'undefined'){
               $this->SetFillColor($this->color_content[0],$this->color_content[1],$this->color_content[2]); 
               switch($_GET['al']){
                  case "100k":  $x = 11;  break;
                  case "300k":  $x = 43;  break;
                  case "500k":  $x = 75.5;  break;  
                  case "750k":  $x = 107.5; break;
                  case "1m"  :  $x = 139.5; break; 
                  case "other": $x = 171.5; break;  
               }
               $this->RoundedRect($x,$y+11,4,4, 1,'', 'F');
               if($_GET['al'] == 'other'){
                   $this->SetTextColor($this->color_content[0],$this->color_content[1],$this->color_content[2]);  
                   $this->SetXY($x+17,$y+10);
                   $_GET['alo'] != "" ? $othervalue = '$ '.number_format($_GET['alo']) : $othervalue = "";
                   $this->Cell(20,6,$othervalue,0,0,'L');
                   $this->font_default();
               }
           }
           
           $x = 10;
           $y += 20;
           $this->SetXY($x,$y);
           $this->Cell(20,6,utf8_decode("Deductible:"),0,0,'L');
           $this->Line($x+21,$y+4.5,60,$y+4.5); 
           #DATOS AUTO DEDUCTIBLE
           if($_GET['ald'] != ''){
               $this->SetTextColor($this->color_content[0],$this->color_content[1],$this->color_content[2]);  
                   $this->SetXY($x+20,$y);
                   $_GET['alo'] != "" ? $value = '$ '.number_format($_GET['ald']) : $value = "";
                   $this->Cell(20,6,$value,0,0,'L');
                   $this->font_default();
           }
           
           #MORORIST BI: 
           $y += 9;
           $this->SetFont($this->font,'B',11);
           $this->SetXY($x,$y);
           $this->Cell(196,10,utf8_decode("Uninsured Motorist BI:"),0,0,'L');
           $this->font_default();
           $y += 10;
           for($z=0;$z<3;$z++){
              $this->SetXY($x,$y);  
              $this->Cell(6,6,"",1,0,'L');
              switch($z){
                  case 0: $txt = utf8_decode('$15,000 / $30,000');break;
                  case 1: $txt = utf8_decode('$25,000 / $50,000');break; 
                  case 2: $txt = utf8_decode('$30,000 / $60,000');break;  
              }
              $this->SetXY($x+6,$y);  
              $this->Cell(20,6,$txt,0,0,'L');
              $x+=45;  
           }
           if($_GET['mbi'] != '' && $_GET['mbi'] != 'undefined'){
               $this->SetFillColor($this->color_content[0],$this->color_content[1],$this->color_content[2]); 
               switch($_GET['mbi']){
                  case "15k":  $x = 11;  break;
                  case "25k":  $x = 56;  break;
                  case "30k":  $x = 101;  break;   
               }
               $this->RoundedRect($x,$y+1,4,4, 1,'', 'F');
           }
           $x = 10;
           
           #MTC: 
           $y += 10;
           $this->SetFont($this->font,'B',11);
           $this->SetXY($x,$y);
           $this->Cell(196,10,utf8_decode("Cargo:"),0,0,'L');
           $this->font_default();
           $y += 10;
           for($z=0;$z<3;$z++){
              $this->SetXY($x,$y);  
              $this->Cell(6,6,"",1,0,'L');
              switch($z){
                  case 0: $txt = utf8_decode('$100,000');break;
                  case 1: $txt = utf8_decode('$250,000');break; 
                  case 2: $txt = utf8_decode('$ ___________');break;  
              }
              $this->SetXY($x+6,$y);  
              $this->Cell(20,6,$txt,0,0,'L');
              $x+=45;  
           }
           $this->SetXY($x,$y);  
           $this->Cell(20,6,'Deductible:',0,0,'L');
           $this->Line($x+21,$y+4.6,205,$y+4.6); 
           #DATOS CARGO
           if($_GET['c'] != '' && $_GET['c'] != 'undefined'){
               $this->SetFillColor($this->color_content[0],$this->color_content[1],$this->color_content[2]); 
               switch($_GET['c']){
                  case "100k":   $x = 11;  break;
                  case "250k":   $x = 56;  break;
                  case "other":  $x = 101;  break;   
               }
               $this->RoundedRect($x,$y+1,4,4, 1,'', 'F');
               if($_GET['c'] == 'other'){
                   $this->SetTextColor($this->color_content[0],$this->color_content[1],$this->color_content[2]);  
                   $this->SetXY($x+8.5,$y);
                   $_GET['co'] != "" ? $othervalue = number_format($_GET['co']) : $othervalue = "";
                   $this->Cell(20,6,$othervalue,0,0,'L');
                   $this->font_default();
               }
               if($_GET['cde'] != ''){
                    $this->SetTextColor($this->color_content[0],$this->color_content[1],$this->color_content[2]);
                    $this->SetXY(166,$y);
                    $value = '$ '.number_format($_GET['cde']); 
                    $this->Cell(20,6,$value,0,0,'L'); 
                    $this->font_default(); 
               }
           }
           $x = 10;  
           
           #TRAILER INT
           $y += 10;
           $this->SetXY($x,$y);
           $this->SetFont($this->font,'B',11);
           $this->Cell(196,10,utf8_decode("Trailer Interchange:"),0,0,'L');
           $this->font_default();
           $y += 10;
           for($z=0;$z<3;$z++){
              $this->SetXY($x,$y);  
              $this->Cell(6,6,"",1,0,'L');
              switch($z){
                  case 0: $txt = utf8_decode('$15,000');break;
                  case 1: $txt = utf8_decode('$25,000');break; 
                  case 2: $txt = utf8_decode('$ ___________');break;  
              }
              $this->SetXY($x+6,$y);  
              $this->Cell(20,6,$txt,0,0,'L');
              $x+=45;  
           }
           $this->SetXY($x,$y);  
           $this->Cell(20,6,'Deductible:',0,0,'L');
           $this->Line($x+21,$y+4.5,205,$y+4.5);
           #DATOS TI
           if($_GET['ti'] != '' && $_GET['ti'] != 'undefined'){
               $this->SetFillColor($this->color_content[0],$this->color_content[1],$this->color_content[2]); 
               switch($_GET['ti']){
                  case "15k":   $x = 11;  break;
                  case "25k":   $x = 56;  break;
                  case "other":  $x = 101;  break;   
               }
               $this->RoundedRect($x,$y+1,4,4, 1,'', 'F');
               if($_GET['ti'] == 'other'){
                   $this->SetTextColor($this->color_content[0],$this->color_content[1],$this->color_content[2]);  
                   $this->SetXY($x+8.5,$y);
                   $_GET['tio'] != "" ? $othervalue = number_format($_GET['tio']) : $othervalue = "";
                   $this->Cell(20,6,$othervalue,0,0,'L');
                   $this->font_default();
               }
               if($_GET['tid'] != ''){
                    $this->SetTextColor($this->color_content[0],$this->color_content[1],$this->color_content[2]);
                    $this->SetXY(166,$y);
                    $value = '$ '.number_format($_GET['tid']); 
                    $this->Cell(20,6,$value,0,0,'L'); 
                    $this->font_default(); 
               }
           }
           
           $x = 10; 
           
           #PD
           $y += 10;
           $this->SetXY($x,$y);
           $this->SetFont($this->font,'B',11); 
           $this->Cell(196,10,utf8_decode("Physical Damage:"),0,0,'L');
           $this->font_default();
           $y += 10;
           for($z=0;$z<2;$z++){
              $this->SetXY($x,$y);  
              $this->Cell(6,6,"",1,0,'L');
              switch($z){
                  case 0: $txt = utf8_decode('$ ___________');break;
                  case 1: $txt = utf8_decode('per unit $ ___________');break;   
              }
              $this->SetXY($x+6,$y);  
              $this->Cell(20,6,$txt,0,0,'L');
              $x+=45;  
           }
           $x+=45;
           $this->SetXY($x,$y);  
           $this->Cell(20,6,'Deductible:',0,0,'L');
           $this->Line($x+21,$y+4.5,205,$y+4.5);
           #DATOS PD
           if($_GET['pd'] != ''){
               $this->SetFillColor($this->color_content[0],$this->color_content[1],$this->color_content[2]);
               $this->SetTextColor($this->color_content[0],$this->color_content[1],$this->color_content[2]);
               $this->RoundedRect(11,$y+1,4,4, 1,'', 'F'); 
               $pdvalue = number_format($_GET['pd']); 
               $this->SetXY(20.5,$y);
               $this->Cell(20,6,$pdvalue,0,0,'L'); 
               $this->font_default();
           }
           if($_GET['pdu'] != ''){
               $this->SetFillColor($this->color_content[0],$this->color_content[1],$this->color_content[2]);
               $this->SetTextColor($this->color_content[0],$this->color_content[1],$this->color_content[2]);
               $this->RoundedRect(56,$y+1,4,4, 1,'', 'F'); 
               $pdvalue = number_format($_GET['pdu']); 
               $this->SetXY(78,$y);
               $this->Cell(20,6,$pdvalue,0,0,'L'); 
               $this->font_default();
           }
           if($_GET['pdd'] != ''){
               $this->SetTextColor($this->color_content[0],$this->color_content[1],$this->color_content[2]); 
               $pdvalue = '$ '.number_format($_GET['pdd']); 
               $this->SetXY(166,$y);
               $this->Cell(20,6,$pdvalue,0,0,'L'); 
               $this->font_default();
           }
           
           $y+=17;
           $x=10;
           $this->SetXY($x,$y);
           $texto = utf8_decode("Please sign, indicating policy(s) is/are to be bound based on quoted coverage(s). Policy(s) are not bound until premium is received and insured has received a binder reflecting coverage(s) bound. By binding you are becoming a member of Continental Trucking Association. Surplus Lines Tax and Fees are applicable.");
           $this->MultiCell(196,5,$texto,0,'J',false);
           $y+=30;
           $this->SetXY($x,$y);
           $this->Cell(20,6,'X',0,0,'L'); 
           $this->Line($x+5,$y+4.5,80,$y+4.5); 
           $date = utf8_decode("Date: ___ / ____ / ______");
           $this->SetXY($x+148,$y); 
           $this->Cell(70,10,$date,0,0,'L');
           #Fecha de Hoy
           $fecha = getdate();
           $y += 2; 
           $this->SetTextColor($this->color_content[0],$this->color_content[1],$this->color_content[2]); 
           $this->SetXY($x+159,$y);
           $this->Write(5,date('m',$fecha['0']));
           $this->SetXY($x+170,$y);
           $this->Write(5,date('d',$fecha['0']));
           $this->SetXY($x+182,$y);
           $this->Write(5,date('Y',$fecha['0']));
           
           $this->pdf_footer(); 
           
           
           
       }
       function pdf_footer(){
            $this->SetTextColor('169','169','169');
            $this->SetY(-10);
            $this->SetX(6);
            //Arial italic 8
            $this->SetFont('Arial','',6);
            //Page number
            $page = $this->PageNo();
            $totalpaginas='{nb}';
            $txt1 = 'Solo-Trucking Insurance Agency';
            $txt2 = "Universal Quick Quote Form";
            $this->Cell(50,10,utf8_decode($txt2),0,0,'L');
            $this->Cell(106.5,10,utf8_decode($txt1),0,0,'C');
            $this->Cell(50,10,"Page $page of $totalpaginas",0,0,'R'); 
       }
  }
  
  #EJECUTA PDF:
  if($error == "0"){
        
       $pdf = new PDF('P', 'mm', 'Letter');
       $pdf->SetAutoPageBreak(FALSE);
       $pdf->AliasNbPages(); 
       $pdf->AddPage();
       $pdf->SetLineWidth(0.2);
       $pdf->init($id_compania,$consecutivo_drivers,$consecutivo_equipment,$consecutivo_commodities);
       $pdf->print_header();
       $pdf->print_data_1();
       $pdf->print_data_2();
       if($consecutivo_commodities != '') $pdf->print_commodities(); 
       if($consecutivo_drivers != '')     $pdf->print_drivers();  
       if($consecutivo_equipment != '')   $pdf->print_equipment();  
       $pdf->print_data_3();
       $nombre_archivo_pdf = $pdf->DatosCompania['iConsecutivo'].'-quick-quote-form.pdf';
       $pdf->Output($nombre_archivo_pdf,'I');
        
    }else{
        echo '<script language="javascript">alert(\'Please, first select a company to generate the quote format.\')</script>';
        echo "<script language='javascript'>window.close();</script>";
    }
?>
