<?php
  require('lib/fpdf153/fpdf.php');//Cargando  libreria
  include_once("funciones_documentos.php");
  //FORZADO
  $consecutivo_doc = "5";
  $id_compania = "2";
  //FORZADO
  if($consecutivo_doc != ""){
      getDocumentosPDF($consecutivo_doc,$arr_formato);      
  }
  if($id_compania != ""){
      getCompaniaPDF($id_compania,$arr_compania);      
  }
  
  //Variables
  $consecutivo_drivers = "13";//$_GET['consecutivo_drivers']; 
  $consecutivo_equipment = "37";
  $consecutivo_trailer = "37";
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
  $consecutivo_commodities = "1";
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
  $RADIUS_OPERATION = "500p";
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
      $y =92;
      $pdf->SetXY($x,$y);
      $pdf->Write(5, "X"   );  
}
if($com_dv == "1"){
      $x = 22;
      $y =100;
      $pdf->SetXY($x,$y);
      $pdf->Write(5, "X"   ); 
}
if($com_fl == "1"){ 
      $x = 22;
      $y =107;
      $pdf->SetXY($x,$y);
      $pdf->Write(5, "X"   );  
}

  
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
  $pd_d = "1";
  if($pd_d == "1"){
      $x = 60;
      $y = 219;
      $pdf->SetXY($x,$y);
      $pdf->Write(5, "X"   );
      $x = 129;
      $y = 219;
      $pdf->SetXY($x,$y);
      $pdf->Write(5, $pd_t ="12345"   );

      $x = 159;
      $y = 219;
      $pdf->SetXY($x,$y);
      $pdf->Write(5, $r_sv = "1234"  );
      
  }
$mtc = "100";
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
                 $pdf->Write(5, "X"   );
              break;
}
if($mtc != ""){
      $x = 159;
      $y = 228;
      $pdf->SetXY($x,$y);
      $pdf->Write(5, $r_p1 = "1234"  );

}
$ntl = "1";
if($ntl == "1"){
      $x = 69;
      $y = 236;
      $pdf->SetXY($x,$y);
      $pdf->Write(5,"X"  );
      $x = 159;
      $y = 236;
      $pdf->SetXY($x,$y);
      $pdf->Write(5, $r_p2 = "1234"  );
      
}
$ti = "5000";
switch($ti){
    case "400":
           $x = 62;
                      $y = 246;           
           $pdf->SetXY($x,$y);
                 $pdf->Write(5, "X"   );
              break;
    default:   if($ti != ""){
               $x = 89;
                          $y = 246;           
               $pdf->SetXY($x,$y);
                     $pdf->Write(5, "X"   );    
               $x = 99;
                          $y = 246;           
               $pdf->SetXY($x,$y);
                     $pdf->Write(5, $ti   );
                  break;
            }
}
if( $ti != "" ){
      $x = 159;
      $y = 246;
      $pdf->SetXY($x,$y);
      
      
      $pdf->Write(5, $r_de = "1234"  );

}
$fecha = getdate();

      $x = 159;
      $y = 271;
      $pdf->SetXY($x,$y);
      $pdf->Write(5, $fecha['mon']   );
      $x = 147;
      $y = 271;
      $pdf->SetXY($x,$y);
      $pdf->Write(5, $fecha['mday']   );
      $x = 169;
      $y = 271;
      $pdf->SetXY($x,$y);
      $pdf->Write(5, $fecha['year']   );



      



  
  
  
  
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
      $x = 20;
      $y = 40;
      if(count($arr_drivers)>0){
                $pdf->SetFont('Arial','',10);  
                $pdf->SetXY($x, $y);
                $pdf->Cell(30,5,"Driver Information:",0,0,'',0);
                $y += 5;                    
                $pdf->SetFont('Arial','',10);  
                $pdf ->SetLineWidth(.3);
                $pdf->SetXY($x, $y);
                $pdf->Cell(50,15,"Driver Name",1,0,'C',1);
                $x += 50;
                $pdf->SetXY($x, $y);
                $pdf->Cell(20,15,"DOB",1,0,'C',1);
                $x += 20;
                $pdf->SetXY($x, $y);
                $pdf->Cell(25,15,"License #",1,0,'C',1);    
                $x += 25;
                $pdf->SetXY($x, $y);
                $pdf->MultiCell(25,15,"License Type",1,'L',1);    
                $x += 25;
                $pdf->SetXY($x, $y);
                $pdf->Cell(15,15,"Yrs Exp",1,0,'C',1); 
                $x += 15;
                $pdf->SetXY($x, $y);
                $pdf->MultiCell(25,5,"Moving Violation last 3 years",1,'C',1);
                $x += 25;
                $pdf->SetXY($x, $y);
                $pdf->MultiCell(20,7.5,"# of accidents",1,'C',1);   
                //CUERPO
                       
                 $pdf->SetFont('Arial','',8);
                 if(count($arr_drivers)>0){
                    $x = 20;
                    $y += 15;
                    foreach($arr_drivers as $driver){
                        $x = 20;
                        $pdf->SetXY($x, $y);
                        $pdf->Cell(50,5,$driver["nombre"],1,0,'L',0);
                        $x += 50;
                        $pdf->SetXY($x, $y);
                        $pdf->Cell(20,5,$driver["fecha_nacimiento"],1,0,'L',0);
                        $x += 20;
                        $pdf->SetXY($x, $y);
                        $pdf->Cell(25,5,$driver["num_licencia"],1,0,'L',0);
                        $x += 25;
                        $pdf->SetXY($x, $y);
                        $pdf->Cell(25,5,$driver["tipo_licencia"],1,0,'L',0);
                        $x += 25;
                        $pdf->SetXY($x, $y);
                        $pdf->Cell(15,5,$driver["exp_year"],1,0,'L',0);
                        $x += 15;
                        $pdf->SetXY($x, $y);
                        $pdf->Cell(25,5,$driver["accidentes"],1,0,'L',0);
                        $x += 25;
                        $pdf->SetXY($x, $y);
                        $pdf->Cell(20,5,$driver["accidentes"],1,0,'L',0);
                        $y += 5;
                    }
                    
                }else{
                }
      }
      //EQUIPMENT
      if(count($arr_equipment)>0){
          $x = 20;        
          $y += 5;
          $pdf->SetFont('Arial','',10);  
          $pdf->SetXY($x, $y);
          $pdf->Cell(30,5,"Vehicle Information:",0,0,'',0);
          $y += 5;
          $pdf->SetFont('Arial','',10);  
          $pdf ->SetLineWidth(.3);
          $pdf->SetXY($x, $y);
          $pdf->Cell(25,10,"Year",1,0,'C',1);
          $x += 25;
          $pdf->SetXY($x, $y);
          $pdf->Cell(25,10,"Make",1,0,'C',1);
          $x += 25;
          $pdf->SetXY($x, $y);
          $pdf->Cell(20,10,"Type #",1,0,'C',1);    
          $x += 20;
          $pdf->SetXY($x, $y);
          $pdf->MultiCell(20,10,"GVW",1,'C',1);    
          $x += 20;
          $pdf->SetXY($x, $y);
          $pdf->Cell(40,10,"VIN #",1,0,'C',1); 
          $x += 40;
          $pdf->SetXY($x, $y);
          $pdf->MultiCell(20,5,"Stated Value",1,'C',1);
          $x += 20;
          $pdf->SetXY($x, $y);
          $pdf->MultiCell(30,10,"Radius",1,'C',1);
          $pdf->SetFont('Arial','',8);
                 if(count($arr_equipment)>0){
                    $x = 20;
                    $y += 10;
                    foreach($arr_equipment as $equipment){
                        $x = 20;
                        $pdf->SetXY($x, $y);
                        $pdf->Cell(25,5,$equipment["year"],1,0,'L',0);
                        $x += 25;
                        $pdf->SetXY($x, $y);
                        $pdf->Cell(25,5,$equipment["make"],1,0,'L',0);
                        $x += 25;
                        $pdf->SetXY($x, $y);
                        $pdf->Cell(20,5,$equipment["body_type"],1,0,'L',0);
                        $x += 20;
                        $pdf->SetXY($x, $y);
                        $pdf->Cell(20,5,$equipment["peso"],1,0,'L',0);
                        $x += 20;
                        $pdf->SetXY($x, $y);
                        $pdf->Cell(40,5,$equipment["VIN"],1,0,'L',0);
                        $x += 40;
                        $pdf->SetXY($x, $y);
                        $pdf->Cell(20,5,$equipment["deductible"],1,0,'L',0);
                        $x += 20;
                        $pdf->SetXY($x, $y);
                        $pdf->Cell(30,5,$equipment["radio_desc"],1,0,'L',0);
                        $y += 5;
                    }
                    
                }else{                                
                }     
      }
      //COMMODITIES HAULED
      if(count($arr_commodities_hauled)>0){
          $x = 20;        
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
                    $x = 20;
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
