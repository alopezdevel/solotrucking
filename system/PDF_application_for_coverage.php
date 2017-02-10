<?php
  require('lib/fpdf153/fpdf.php');//Cargando  libreria
  include_once("funciones_documentos.php");
  //FORZADO
  $consecutivo_doc = $_GET['consecutivo_doc'];
  $id_compania = $_GET['id_compania'];
  //FORZADO
  if($consecutivo_doc != ""){
      getDocumentosPDF($consecutivo_doc,$arr_formato);      
  }
  if($id_compania != ""){
      getCompaniaPDF($id_compania,$arr_compania);      
  }
  
  //Variables
  $consecutivo_drivers = $_GET['cd']; 
  $consecutivo_equipment = $_GET['ce'];
  //Fechas Bind y Quote needed:
  $date_bind = $_GET['dbind']; 
  $date_quot = $_GET['dquot']; 
  //$consecutivo_trailer = "37";
  $NAMED_INSURED = $arr_compania[0]['compania'];
  $FEIN = $arr_compania[0]['FEIN'];
  $USDOT = $arr_compania[0]['us_dot'];
  $MC = $arr_compania[0]['MC'];
  $ADDRESS = $arr_compania[0]['direccion'];
  $CITY = $arr_compania[0]['ciudad'];
  $ST = $arr_compania[0]['Estado'];
  $ZIP = $arr_compania[0]['codigoPostal'];
  $PHONE = $arr_compania[0]['TelefonoPrincipal'];
  $PHONE2 = $arr_compania[0]['Telefono2'];
  $EMAIL = $arr_compania[0]['email'];
  $RADIUS_OPERATION = $arr_compania[0]['radio'];
  //Variables  
  $NAMED_INSURED = $arr_compania[0]['compania'];
  $ADDRESS = $arr_compania[0]['direccion'];
  $PH = $arr_compania[0]['telefono_principal'];   
  $consecutivo_commodities = $_GET['id_comm'];
  //funciones
  $arr_drivers = NULL;
  $arr_equipment = NULL;
  $arr_trailer = NULL;
  $arr_commodities_hauled = NULL;
  getDriversPDF($consecutivo_drivers,$arr_drivers);  
  getEquipmenTrailertPDF($consecutivo_equipment,$arr_equipment);        
  getCommoditiesHauled($consecutivo_commodities,$arr_commodities_hauled);            
  //validaciones
  if(count($arr_compania)== 0){
      $mensaje = "Company not found. Please check your information and try again.";
      $error +=1;          
  }
  if(count($arr_formato)== 0){
      $mensaje = "Document not found. Please check your information and try again.";
      $error +=1;          
  }
  if($error >0){
      echo '<script language="javascript">alert(\''.$mensaje.'\'); window.close();</script>';  
      exit;       
  }
  $pdf = new FPDF(); //Declarando clase FPDF
  $pdf->AddPage(); // Agregando pagina
  $pdf->SetFont('Arial','',9);  
  $ruta_archivo = $arr_formato[0]['ruta']; 
  $pdf->Image($ruta_archivo,'0','0','215','290','JPG');
  //CUERPO DEL PDF
  if($date_bind != ""){
      $x = 22.5;
      $y = 30.5;
      $pdf->SetXY($x,$y);
      $pdf->Write(5,"X");
      $date_bind = strtotime($date_bind);
      $x = 53;
      $pdf->SetXY($x,$y);
      $pdf->Write(5,date('m',$date_bind));
      $pdf->SetXY($x+10,$y);
      $pdf->Write(5,date('d',$date_bind));
      $pdf->SetXY($x+19,$y);
      $pdf->Write(5,date('Y',$date_bind));
      
  }
  if($date_quot != ""){
      $x = 111.5;
      $y = 30.5;
      $pdf->SetXY($x,$y);
      $pdf->Write(5,"X");
      $date_quot = strtotime($date_quot);
      $x = 149;
      $pdf->SetXY($x,$y);
      $pdf->Write(5,date('m',$date_quot));
      $pdf->SetXY($x+10,$y);
      $pdf->Write(5,date('d',$date_quot));
      $pdf->SetXY($x+19,$y);
      $pdf->Write(5,date('Y',$date_quot));
      
  }
  $x = 50;
  $y =46;
  $pdf->SetXY($x,$y);
  $pdf->Write(5,$NAMED_INSURED );
  $x = 140;
  $y =46;
  $pdf->SetXY($x,$y);
  $pdf->Write(5,$FEIN );
  $x = 110;
  $y =38;
  $pdf->SetXY($x,$y);
  $pdf->Write(5,$USDOT  );
  $x = 157;
  $y =38;
  $pdf->SetXY($x,$y);
  $pdf->Write(5,$MC   );  
  $x = 40;
  $y =54;
  $pdf->SetXY($x,$y);
  $pdf->Write(5,$ADDRESS  );
  $x = 100;
  $y =54;
  $pdf->SetXY($x,$y);
  $pdf->Write(5,$CITY  );
  $x = 150;
  $y =54;
  $pdf->SetXY($x,$y);
  $pdf->Write(5,$ST  );  
  $x = 168;
  $y =54;
  $pdf->SetXY($x,$y);
  $pdf->Write(5,$ZIP  );
  $x = 37;
  $y =62;
  $pdf->SetXY($x,$y);
   
  $PHONE_EXT = substr($PHONE,0,3);
  $PHONE_NUM1 = substr($PHONE,3,3);
  $PHONE_NUM2 = substr($PHONE,6,4);
  $pdf->Write(5,$PHONE_EXT );
  $x = 49;
  $y =62;
  $pdf->SetXY($x,$y);
  $pdf->Write(5,$PHONE_NUM1 );
  $x = 62;
  $y =62;
  $pdf->SetXY($x,$y);
  $pdf->Write(5,$PHONE_NUM2 );
   
  $x = 86;
  $y =62;
  $pdf->SetXY($x,$y);
   
  $PHONE_EXT = substr($PHONE,0,3);
  $PHONE_NUM1 = substr($PHONE,3,3);
  $PHONE_NUM2 = substr($PHONE,6,4);
  $pdf->Write(5,$PHONE_EXT );
  $x = 98;
  $y =62;
  $pdf->SetXY($x,$y);
  $pdf->Write(5,$PHONE_NUM1 );
  $x = 111;
  $y =62;
  $pdf->SetXY($x,$y);
  $pdf->Write(5,$PHONE_NUM2 );
  
  $x = 137;
  $y =62;
  $pdf->SetXY($x,$y);
  $pdf->Write(5,$EMAIL   );     
  
  $y =70;
  $RADIUS_OPERATION = $_GET['radio'];
  SWITCH($RADIUS_OPERATION){
      CASE "200": $x = 60;
                  $pdf->SetXY($x,$y);
                  $pdf->Write(5,"X" );
      BREAK;
      CASE "500": $x = 78;
                  $pdf->SetXY($x,$y);
                  $pdf->Write(5,"X" );
                  
      BREAK;
      CASE "500p":$x = 96;
                  $pdf->SetXY($x,$y);
                  $pdf->Write(5,"X" );
      BREAK;
      
  }
  $com_re = $_GET['com_re'];
  $com_dv = $_GET['com_dv'];
  $com_fl = $_GET['com_fl'];
      
  if($com_re == "1"){
      $x = 22;
      $y = 92;
      $pdf->SetXY($x,$y);
      $pdf->Write(5,"X");  
  }
  if($com_dv == "1"){
      $x = 22;
      $y = 99.5;
      $pdf->SetXY($x,$y);
      $pdf->Write(5,"X"); 
  }
  if($com_fl == "1"){ 
      $x = 22;
      $y = 107;
      $pdf->SetXY($x,$y);
      $pdf->Write(5,"X");  
  }
  $er = $_GET['er'];
  if($er == 'lp'){
      $x = 89;
      $y =211;
      $pdf->SetXY($x,$y);
      $pdf->Write(5, "X"   );      
  }
  if($er == 'ai'){
      $x = 129;
      $y =211;
      $pdf->SetXY($x,$y);
      $pdf->Write(5, "X"   );
  }
  $pd_d = $_GET['pd_d'];
  if($pd_d == "1"){
      $x = 60;
      $y = 219;
      $pdf->SetXY($x,$y);
      $pdf->Write(5, "X"   );
      $x = 129;
      $y = 219;
      $pdf->SetXY($x,$y);
      $pdf->Write(5, $pd_t = $_GET['pd_t']);

      $x = 159;
      $y = 219;
      $pdf->SetXY($x,$y);
      $pdf->Write(5, $r_sv = $_GET['r_sv']);
      
  }
  #MTC
  $mtc = $_GET['mtc'];
  switch($mtc){
    case "100":
           $x = 77;
                      $y = 228;           
           $pdf->SetXY($x,$y);
                 $pdf->Write(5, "X"   );
              break;
    case "150":
           $x = 102;
                      $y = 228;           
           $pdf->SetXY($x,$y);
                 $pdf->Write(5, "X"   );
           break;
    case "250":
           $x = 129;
           $y = 228;           
           $pdf->SetXY($x,$y);
           $pdf->Write(5, "X");
           break;
  }
  if($mtc != ""){
      $x = 159;
      $y = 228;
      $pdf->SetXY($x,$y);
      $pdf->Write(5, $r_p1 = $_GET['r_p1']);
  }
    $ntl = $_GET['ntl'];
    if($ntl == "1"){
          $x = 69;
          $y = 236;
          $pdf->SetXY($x,$y);
          $pdf->Write(5,"X"  );
          $x = 159;
          $y = 236;
          $pdf->SetXY($x,$y);
          $pdf->Write(5, $r_p2 = $_GET['r_p2']);
    }
    $ti = $_GET['ti'];
    switch($ti){
        case "400":
               $x = 62;
                          $y = 246;           
               $pdf->SetXY($x,$y);
                     $pdf->Write(5,"X");
                  break;
        default:   if($ti != ""){
                   $x = 89;
                              $y = 246;           
                   $pdf->SetXY($x,$y);
                         $pdf->Write(5,"X");    
                   $x = 99;
                              $y = 246;           
                   $pdf->SetXY($x,$y);
                         $pdf->Write(5,$ti);
                      break;
                }
    }
    if( $ti != "" ){
          $x = 159;
          $y = 246;
          $pdf->SetXY($x,$y);
          $pdf->Write(5, $r_de = $_GET['r_de']);
    }   
      $fecha = getdate(); 
      $x = 147;
      $y = 271;
      $pdf->SetXY($x,$y);
      $pdf->Write(5,date('m',$fecha['0']));
      $x = 159;
      $y = 271;
      $pdf->SetXY($x,$y);
      $pdf->Write(5,date('d',$fecha['0']));
      $x = 169;
      $y = 271;
      $pdf->SetXY($x,$y);
      $pdf->Write(5,date('Y',$fecha['0']));
 
  if(count($arr_trailer)>0 || count($arr_drivers)>0 || count($arr_equipment)>0){
      $pdf->AddPage(); // Agregando pagina
      //Pagina de Anexos
      $pdf->SetFont('Arial','',17);  
      $x = 5;
      $y = 20;
      $pdf->SetXY($x, $y);    
      $pdf->Cell(210,5,"Application For Coverage",0,0,'C',0);
      $x = 5;
      $y = 27;
      $pdf->SetXY($x, $y);    
      $pdf->Cell(210,5,"Physical Damage/ Motor Truck Cargo/ Non Trucking Liability",0,0,'C',0);
      $pdf->SetFillColor(232);
      $x = 15;
      $y = 40;
      if(count($arr_drivers)>0){
                $pdf->SetFont('Arial','',10);  
                $pdf->SetXY($x, $y);
                $pdf->Cell(30,5,"Driver Information:",0,0,'',0);
                $y += 5;                    
                $pdf->SetFont('Arial','',10);  
                $pdf ->SetLineWidth(.3);
                $pdf->SetXY($x, $y);
                $pdf->Cell(95,8,"Name",1,0,'C',1);
                $x += 95;
                $pdf->SetXY($x, $y);
                $pdf->Cell(20,8,"DOB",1,0,'C',1);
                $x += 20;
                $pdf->SetXY($x, $y);
                $pdf->Cell(25,8,"License #",1,0,'C',1);    
                $x += 25;
                $pdf->SetXY($x, $y);
                $pdf->MultiCell(32,8,"License Type",1,'L',1);    
                $x += 32;
                $pdf->SetXY($x, $y);
                $pdf->Cell(15,8,"Yrs Exp",1,0,'C',1); 
                /*$x += 15;
                $pdf->SetXY($x, $y);
                $pdf->MultiCell(25,5,"Moving Violation last 3 years",1,'C',1);
                $x += 25;
                $pdf->SetXY($x, $y);
                $pdf->MultiCell(20,7.5,"# of accidents",1,'C',1);  */ 
                //CUERPO
                       
                 $pdf->SetFont('Arial','',8);
                 if(count($arr_drivers)>0){
                    $y += 8;
                    foreach($arr_drivers as $driver){
                        $x = 15;
                        $pdf->SetXY($x, $y);
                        $pdf->Cell(95,5,$driver["nombre"],1,0,'L',0);
                        $x += 95;
                        $pdf->SetXY($x, $y);
                        $pdf->Cell(20,5,$driver["fecha_nacimiento"],1,0,'C',0);
                        $x += 20;
                        $pdf->SetXY($x, $y);
                        $pdf->Cell(25,5,$driver["num_licencia"],1,0,'L',0);
                        $x += 25;
                        $pdf->SetXY($x, $y);
                        $pdf->Cell(32,5,$driver["tipo_licencia"],1,0,'L',0);
                        $x += 32;
                        $pdf->SetXY($x, $y);
                        $pdf->Cell(15,5,$driver["exp_year"],1,0,'C',0);
                        /*$x += 15;
                        $pdf->SetXY($x, $y);
                        $pdf->Cell(25,5,$driver["accidentes"],1,0,'L',0);
                        $x += 25;
                        $pdf->SetXY($x, $y);
                        $pdf->Cell(20,5,$driver["accidentes"],1,0,'L',0); */
                        $y += 5;
                    }
                    
                }
      }
      //EQUIPMENT
      if(count($arr_equipment)>0){
          $x = 15;        
          $y += 5;
          $pdf->SetFont('Arial','',10);  
          $pdf->SetXY($x, $y);
          $pdf->Cell(30,5,"Vehicle Information:",0,0,'',0);
          $y += 5;
          $pdf->SetFont('Arial','',10);  
          $pdf ->SetLineWidth(.3);
          $pdf->SetXY($x, $y);
          $pdf->Cell(40,8,"VIN #",1,0,'C',1);
          $x += 40;
          $pdf->SetXY($x, $y);
          $pdf->Cell(20,8,"Year",1,0,'C',1);
          $x += 20;
          $pdf->SetXY($x, $y);
          $pdf->Cell(20,8,"Make #",1,0,'C',1);    
          $x += 20;
          $pdf->SetXY($x, $y);
          $pdf->MultiCell(20,8,"Type",1,'C',1);    
          $x += 20;
          $pdf->SetXY($x, $y);
          $pdf->Cell(20,8,"GVW",1,0,'C',1); 
          $x += 20;
          $pdf->SetXY($x, $y);
          $pdf->MultiCell(20,8,"Radius",1,'C',1);
          $x += 20;
          $pdf->SetXY($x, $y);
          $pdf->MultiCell(30,8,"Stated Value",1,'C',1);
          /*$x += 30;
          $pdf->SetXY($x, $y);
          $pdf->MultiCell(15,8,"Deductible",1,'C',1);*/
          $pdf->SetFont('Arial','',8);
                 if(count($arr_equipment)>0){
                    $y += 8;
                    foreach($arr_equipment as $equipment){
                        $x = 15;
                        $pdf->SetXY($x, $y);
                        $pdf->Cell(40,5,$equipment["VIN"],1,0,'L',0);
                        $x += 40;
                        $pdf->SetXY($x, $y);
                        $pdf->Cell(20,5,$equipment["year"],1,0,'L',0);
                        $x += 20;
                        $pdf->SetXY($x, $y);
                        $pdf->Cell(20,5,$equipment["make"],1,0,'L',0);
                        $x += 20;
                        $pdf->SetXY($x, $y);
                        $pdf->Cell(20,5,$equipment["body_type"],1,0,'L',0);
                        $x += 20;
                        $pdf->SetXY($x, $y);
                        $pdf->Cell(20,5,$equipment["peso"],1,0,'L',0);
                        $x += 20;
                        $pdf->SetXY($x, $y);
                        $pdf->Cell(20,5,$equipment["radio_desc"],1,0,'C',0);
                        $x += 20;
                        $pdf->SetXY($x, $y);
                        $equipment["stated_value"] != "" ? $stated_value  = '$ '.number_format($equipment["stated_value"]) : $stated_value = "";
                        $pdf->Cell(30,5,$stated_value,1,0,'R',0);
                        $y += 5;
                    }
                    
                }     
      }
      //COMMODITIES HAULED
      if(count($arr_commodities_hauled)>0){
          $x = 15;        
          $y += 5;
          $pdf->SetFont('Arial','',10);  
          $pdf->SetXY($x, $y);
          $pdf->Cell(30,5,"Commodities hauled:",0,0,'',0);
          $y += 5;
          $pdf->SetFont('Arial','',10);  
          $pdf ->SetLineWidth(.3);
          $pdf->SetXY($x, $y);
          $pdf->Cell(40,5,"Commodity",1,0,'C',1);
          $x += 40;
          $pdf->SetXY($x, $y);
          $pdf->Cell(40,5,"% Hauled.",1,0,'C',1);
          $x += 40;
          $pdf->SetXY($x, $y);
          $pdf->Cell(35,5,'$ Minimum Value',1,0,'C',1);    
          $x += 35;
          $pdf->SetXY($x, $y);
          $pdf->Cell(35,5,'$ Maximum Value',1,0,'C',1);   
          
          $pdf->SetFont('Arial','',8);
                 if(count($arr_commodities_hauled)>0){
                    $x = 15;
                    $y += 5;
                    foreach($arr_commodities_hauled as $commodities){
                        $x = 20;
                        $pdf->SetXY($x, $y);
                        $pdf->Cell(40,5,$commodities["commoditie"],1,0,'L',0);
                        $x += 40;
                        $pdf->SetXY($x, $y);
                        $pdf->Cell(40,5,$commodities["porcentaje_hauled"].'%',1,0,'R',0);
                        $x += 40;
                        $pdf->SetXY($x, $y);
                        $pdf->Cell(35,5, '$'.number_format($commodities["valor_minimo"], 2, '.', ' ') ,1,0,'R',0);
                        $x += 35;
                        $pdf->SetXY($x, $y);
                        $pdf->Cell(35,5, '$'.number_format($commodities["valor_maximo"], 2, '.', ' ') ,1,0,'R',0);                       
                        $y += 5;
                    }
                    
                }else{                                
                }     
      }
  }
  $pdf->Output();
?>
