<?php
  require('lib/fpdf153/fpdf.php');//Cargando  libreria
  include_once("funciones_documentos.php");
  //forzado
  /*$_GET['consecutivo_doc'] = "1";
  $_GET['id_compania'] = "2";
  $_GET['consecutivo_drivers'] = "1";
  $_GET['consecutivo_equipment'] = "2";
  $_GET['consecutivo_trailer'] = "3"; */
  //Variables 0
  $error = 0;
  $mensaje = "";
  $id_compania = $_GET['id_compania'];
  $consecutivo_doc = $_GET['consecutivo_doc'];
  $consecutivo_drivers = $_GET['consecutivo_drivers']; 
  $consecutivo_equipment = $_GET['consecutivo_equipment'];
  $consecutivo_trailer = $_GET['consecutivo_trailer'];
  $arr_formato = NULL;                  
  $arr_compania = NULL;   
  $arr_drivers = NULL;  
  $arr_equipment = NULL;
  $arr_trailer = NULL;
  getDriversPDF($consecutivo_drivers,$arr_drivers);    
  getEquipmenTrailertPDF($consecutivo_equipment,$arr_equipment);        
  getEquipmenTrailertPDF($consecutivo_trailer,$arr_trailer);        
  
  //funciones    
  if($consecutivo_doc != ""){
      getDocumentosPDF($consecutivo_doc,$arr_formato);      
  }
  if($id_compania != ""){
      getCompaniaPDF($id_compania,$arr_compania);      
  }
  
  
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
  $pdf->SetFont('Arial','',10);  
  $ruta_archivo = $arr_formato[0]['ruta']; 
  $pdf->Image($ruta_archivo,'0','0','215','290','JPG');
  //Variables  
  $NAMED_INSURED = $arr_compania[0]['compania'];
  $GARAGING_ADDRESS = $arr_compania[0]['direccion'];
  $PH = $arr_compania[0]['telefono_principal'];
  
  
  $YEARS_IN_BUSSINES = $_GET['year_in_bussines'];
  $FEIN = $_GET['fein'] ;
  $COMMODITIES_HAULED = $_GET['commodities_hauled'] ;
  if($_GET['filing_required'] == "1" || $_GET['filing_required'] == true || $_GET['filing_required'] != "" ){
      $CHECK_ICC = "X";      
  }else{
      $CHECK_NONE = "X";      
  }  
  if($_GET['radio'] != ""){
      switch($_GET['radio']){
          CASE "250" : $CHECK_RADIUS1 = "X"; break;
          CASE "500" : $CHECK_RADIUS2 = "X";break;
          CASE "500p" : $CHECK_RADIUS3 = "X";  break;
          default  : "" ; break;
          
      }
      
  }else{
      $mensaje = "The field (filing-required) was not captured. Please check your information and try again.";
      $error +=1;                
  }
  if($error >0){
      echo '<script language="javascript">alert(\''.$mensaje.'\'); window.close();</script>';  
       exit;       
  }
  if($_GET['coverages_auto_liability'] != ""){
      switch($_GET['coverages_auto_liability']){
          CASE "100" : $COVERAGES_100 = "X"; break;
          CASE "300" : $COVERAGES_300 = "X";break;
          CASE "500" : $COVERAGES_500 = "X";  break;
          CASE "750" : $COVERAGES_750 = "X";  break;
          CASE "1m" : $COVERAGES_1M = "X";  break;
          CASE "other" : $COVERAGES_OTHER = "X";  break;
          default  : "" ; break;
          
      }
      
  }      
  
  if($_GET['auto_liability_deductible'] != ""){
      switch($_GET['auto_liability_deductible']){
          CASE "500" : $COVERAGES_ALD_500 = "X"; break;         
          default  : "" ; break;
          
      }
      
  }
  if($_GET['coverages_uninsured_mot'] != ""){
      switch($_GET['coverages_uninsured_mot']){
          CASE "15" : $COVERAGES_UMB_15 = "X"; break;         
          CASE "25" : $COVERAGES_UMB_25 = "X"; break;         
          CASE "30" : $COVERAGES_UMB_30 = "X"; break;         
          default  : "" ; break;
          
      }
      
  }  
  if($_GET['coverages_cargo'] != ""){
      switch($_GET['coverages_cargo']){
          CASE "25" : $CARGO_25 = "X"; break;         
          CASE "50" : $CARGO_50 = "X"; break;                   
          default  : "" ; break;
          
      }
      
  }  
  
  
  //$ICC = "ICC";
  //$CHECK_DMV = "X";
 // $DMV = "DMV";  
  //$CHECK_OTHER = "X";
  
  //$CHECK_RADIUS4 = "X";
  //$CHECK_RADIUS5 = "X";
  //$CHECK_RADIUS6 = "X";
  //$RADIUSEX = "RADIUS";
  /*$COVERAGES_100 = "X";
  $COVERAGES_300 = "X";
  $COVERAGES_500 = "X";
  $COVERAGES_750 = "X";
  $COVERAGES_1M = "X";
  $COVERAGES_OTHER = "X";
  $COVERAGES_OTHER_LABEL = "COVERAGE";
  $COVERAGES_ALD_500 = "X";
  $COVERAGES_UMB_15 = "X";
  $COVERAGES_UMB_25 = "X";
  $COVERAGES_UMB_30 = "X";   */
  //DEMO DRIVERS
   
  
 
  
  
  //---------------------------------------------------------CAMPOS-----------------------------------------------------
    //---------------------------------------------------------HEAD-----------------------------------------------------
        //NAME INSURED
        $x = 50;
        $y =22;
        $pdf->SetXY($x,$y);
        $pdf->Write(5,$NAMED_INSURED);        
        //Ph#
        $pdf->SetXY($x + 115,$y);
        $pdf->Write(5,$PH);
        //GARAGING ADDRESS
        $x += 5;
        $y += 7;   
        $pdf->SetXY($x,$y);
        $pdf->Write(5,$GARAGING_ADDRESS);
        //YEARS_IN_BUSSINES
        $x += 50;
        $y += 7;   
        $pdf->SetXY($x,$y);
        $pdf->Write(5,$YEARS_IN_BUSSINES);
        //FEIN                
        $pdf->SetXY($x + 38,$y);
        $pdf->Write(5,$FEIN);
        //COMMODITIES_HAULED
        if(strlen($COMMODITIES_HAULED)>32){
            $COMMODITIES_HAULED1 = substr($COMMODITIES_HAULED,0,33);
            $COMMODITIES_HAULED2 = substr($COMMODITIES_HAULED,33);
            $x += 10;
            $y += 7;   
            $pdf->SetXY($x,$y);
            $pdf->Write(5,$COMMODITIES_HAULED1); 
            $x = 19;
            $y += 7;   
            $pdf->SetXY($x,$y);
            $pdf->Write(5,$COMMODITIES_HAULED2);           
        }else{
            $x += 10;
            $y += 7;   
            $pdf->SetXY($x,$y);
            $pdf->Write(5,$COMMODITIES_HAULED);
            
        }
        //FILLINGS REQUIRED     
        $x = 52;        
        $y = 58;        
        $pdf->SetXY($x,$y);
        $pdf->Write(5,$CHECK_NONE);
        //ICC        
        $x =70;  
        $y = 58;              
        $pdf->SetXY($x,$y);
        $pdf->Write(5,$CHECK_ICC);
        //ICC        
        $x += 12;                
        $pdf->SetXY($x,$y);
        $pdf->Write(5,$ICC);
        //DMV        
        $x += 22;                
        $pdf->SetXY($x,$y);
        $pdf->Write(5,$CHECK_DMV);
        //DMV        
        $x += 15;                
        $pdf->SetXY($x,$y);
        $pdf->Write(5,$DMV);
        //OTHER        
        $x += 23;                
        $pdf->SetXY($x,$y);
        $pdf->Write(5,$CHECK_OTHER);
        //OTHER        
        $x += 18;                
        $pdf->SetXY($x,$y);
        $pdf->Write(5,$CHECK_OTHER);
        //RADIUS
        $x = 35;        
        $y += 8;        
        $pdf->SetXY($x,$y);
        $pdf->Write(5,$CHECK_RADIUS1);
        $x += 42;                     
        $pdf->SetXY($x,$y);
        $pdf->Write(5,$CHECK_RADIUS2);
        $x += 28;                     
        $pdf->SetXY($x,$y);
        $pdf->Write(5,$CHECK_RADIUS3);
        $x += 30;                     
        $pdf->SetXY($x,$y);
        $pdf->Write(5,$CHECK_RADIUS4);
        $x += 31;                     
        $pdf->SetXY($x,$y);
        $pdf->Write(5,$CHECK_RADIUS5);
        //RADIUS
        $x = 35;        
        $y += 7;        
        $pdf->SetXY($x,$y);
        $pdf->Write(5,$CHECK_RADIUS6);
        $y -= 1; 
        $x += 57;                
        $pdf->SetXY($x,$y);
        $pdf->Write(5,$RADIUSEX);
        //COVERAGES AUTO-LIABILITY
        //100 CSL
        $y += 114; 
        $x = 44;                
        $pdf->SetXY($x,$y);
        $pdf->Write(5,$COVERAGES_100);
        //300 CSL
        $x += 22;                
        $pdf->SetXY($x,$y);
        $pdf->Write(5,$COVERAGES_300);
        //500
        $x += 22;                
        $pdf->SetXY($x,$y);
        $pdf->Write(5,$COVERAGES_500);
        //750
        $x += 22;                
        $pdf->SetXY($x,$y);
        $pdf->Write(5,$COVERAGES_750);
        //1M
        $x += 22;                
        $pdf->SetXY($x,$y);
        $pdf->Write(5,$COVERAGES_1M);
        //OTHER
        $x += 20;                
        $pdf->SetXY($x,$y);
        $pdf->Write(5,$COVERAGES_OTHER);
        //OTHER LABEL
        $x += 16;     
        $y -= 1;           
        $pdf->SetXY($x,$y);
        $pdf->Write(5,$COVERAGES_OTHER_LABEL);
        //COVERAGE AUTO LIABILITY DEDUCTIBLE
        $x = 62;     
        $y += 6;           
        $pdf->SetXY($x,$y);
        $pdf->Write(5,$COVERAGES_ALD_500);
        //COVERAGE AUTO LIABILITY DEDUCTIBLE
        $x = 59;     
        $y += 6;           
        $pdf->SetXY($x,$y);
        $pdf->Write(5,$COVERAGES_UMB_15); 
        //COVERAGE AUTO LIABILITY DEDUCTIBLE    Z
        $x = 96;             
        $pdf->SetXY($x,$y);
        $pdf->Write(5,$COVERAGES_UMB_25); 
        //COVERAGE AUTO LIABILITY DEDUCTIBLE
        $x = 135;             
        $pdf->SetXY($x,$y);
        $pdf->Write(5,$COVERAGES_UMB_30);  
        //CARGO
        
        $x = 30;             
        $y = 203;
        $pdf->SetXY($x,$y);
        $pdf->Write(5,$CARGO_25); 
        $x = 63;             
        $y = 203;
        $pdf->SetXY($x,$y);
        $pdf->Write(5,$CARGO_50); 
        $CARGO_DEDUCTIBLE = $_GET['cargo_deductible'];
        $x = 113;             
        $y = 203;
        $pdf->SetXY($x,$y);
        $pdf->Write(5,$CARGO_DEDUCTIBLE); 
        
        //DATOS FIJOS
        $x_con = 33;      
        $y_con = 252;       
        $pdf->SetXY($x_con,$y_con);
        $pdf->Write(5,$AGENCY = "SOLO TRUCKING INSURANCE ");
        
        $x_con = 117;      
        $y_con = 252;       
        $pdf->SetXY($x_con,$y_con);
        $pdf->Write(5,$PHONE = "9567916511 ");
        
        $x_con = 31;      
        $y_con = 258;       
        $pdf->SetXY($x_con,$y_con);
        $pdf->Write(5,$AGENT = "JUAN C SANCHEZ ");
        
        $x_con = 115;      
        $y_con = 258;       
        $pdf->SetXY($x_con,$y_con);
        $pdf->Write(5,$FAX = "9564674440 ");
        
        
        
        
        $pdf->AddPage(); // Agregando pagina
        //Pagina de Anexos
        $pdf->SetFont('Arial','B',19);  
        $x = 5;
        $y = 20;
        $pdf->SetXY($x, $y);    
        $pdf->Cell(210,5,"Commercial Auto Quick Quote Form",0,0,'C',0);
         
        $pdf->SetFillColor(232);
        $x = 20;
        $y = 35;
        if(count($arr_drivers)>0){
            $pdf->SetFont('Arial','',10);  
            $pdf->SetXY($x, $y);
            $pdf->Cell(30,5,"DRIVER(S)",0,0,'L',0);
            $y += 5;                    
            $pdf->SetFont('Arial','B',10);  
            $pdf ->SetLineWidth(.5);
            $pdf->SetXY($x, $y);
            $pdf->Cell(60,5,"NAME",1,0,'L',1);
            $x += 60;
            $pdf->SetXY($x, $y);
            $pdf->Cell(30,5,"YRS_EXP",1,0,'L',1);
            $x += 30;
            $pdf->SetXY($x, $y);
            $pdf->Cell(30,5,"ACCIDENTS",1,0,'L',1);    
            //CUERPO
                   
             $pdf->SetFont('Arial','',10);
            if(count($arr_drivers)>0){
                $x = 20;
                $y += 5;
                foreach($arr_drivers as $driver){
            $x = 20;
                    $pdf->SetXY($x, $y);
                    $pdf->Cell(60,5,$driver["nombre"],1,0,'L',0);
                    $x += 60;
                    $pdf->SetXY($x, $y);
                    $pdf->Cell(30,5,$driver["exp_year"],1,0,'L',0);
                    $x += 30;
                    $pdf->SetXY($x, $y);
                    $pdf->Cell(30,5,$driver["accidentes"],1,0,'L',0);
                    $y += 5;
                    
                }
                
            }
            else{
                    $x = 20;
                    $y += 5;
                    $pdf->SetXY($x, $y);
                    $pdf->Cell(60,5,"",1,0,'L',0);
                    $x += 60;
                    $pdf->SetXY($x, $y);
                    $pdf->Cell(30,5,"",1,0,'L',0);
                    $x += 30;
                    $pdf->SetXY($x, $y);
                    $pdf->Cell(30,5,"",1,0,'L',0);
                    $y += 5;
                
            }
        }
        //EQUIPMENT   
        $pdf ->SetLineWidth(.5); 
        $x = 20;        
        $y += 5;
        $pdf->SetXY($x, $y);
        $pdf->SetFont('Arial','B',10);  
        $pdf->Cell(175,5,"EQUIPMENT:",1,0,'L',1); 
        $y += 5;       
        $pdf->SetXY($x, $y);
        $pdf->Cell(15,5,"YEAR",1,0,'L',1);
        $x += 15;
        $pdf->SetXY($x, $y);
        $pdf->Cell(50,5,"MAKE",1,0,'L',1);
        $x += 50;
        $pdf->SetXY($x, $y);
        $pdf->Cell(25,5,"BODY TYPE",1,0,'L',1); 
        
        $x += 25;
        $pdf->SetXY($x, $y);
        $pdf->Cell(25,5,"GVW",1,0,'L',1);         
        
        $x += 25;
        $pdf->SetXY($x, $y);
        $pdf->Cell(30,5,"STATED VALUE",1,0,'L',1);         
        
        $x += 30;
        $pdf->SetXY($x, $y);
        $pdf->Cell(30,5,"DEDUCTIBLE",1,0,'L',1); 
        $pdf->SetFont('Arial','',10); 
           
        //CUERPO        
         $pdf->SetFont('Arial','',10);
        if(count($arr_equipment)>0){
            $x = 20;       
            $y += 5;     
            foreach($arr_equipment as $equipment){
        $x = 20;
                $pdf->SetXY($x, $y);
                $pdf->Cell(15,5,$equipment["year"],1,0,'L',0);
                $x += 15;
                $pdf->SetXY($x, $y);
                $pdf->Cell(50,5,$equipment["make"],1,0,'L',0);
                $x += 50;
                $pdf->SetXY($x, $y);
                $pdf->Cell(25,5,$equipment["body_type"],1,0,'L',0);
                $x += 25;
                $pdf->SetXY($x, $y);
                $pdf->Cell(25,5,$equipment["GVW"],1,0,'L',0);
                $x += 25;
                $pdf->SetXY($x, $y);
                $pdf->Cell(30,5,$equipment["STATED VALUE"],1,0,'L',0);
                $x += 30;
                $pdf->SetXY($x, $y);
                $pdf->Cell(30,5,$equipment["deductible"],1,0,'L',0);
                $y += 5;
                
            }
            
        }
        
        //EQUIPMENT   
        $pdf ->SetLineWidth(.5); 
        $x = 20;        
        $y += 5;
        $pdf->SetXY($x, $y);
        $pdf->SetFont('Arial','B',10);  
        $pdf->Cell(175,5,"TRAILER(S):",1,0,'L',1); 
        $y += 5;       
        $pdf->SetXY($x, $y);
        $pdf->Cell(15,5,"YEAR",1,0,'L',1);
        $x += 15;
        $pdf->SetXY($x, $y);
        $pdf->Cell(50,5,"MAKE",1,0,'L',1);
        $x += 50;
        $pdf->SetXY($x, $y);
        $pdf->Cell(25,5,"BODY TYPE",1,0,'L',1); 
        
        $x += 25;
        $pdf->SetXY($x, $y);
        $pdf->Cell(25,5,"GVW",1,0,'L',1);         
        
        $x += 25;
        $pdf->SetXY($x, $y);
        $pdf->Cell(30,5,"STATED VALUE",1,0,'L',1);         
        
        $x += 30;
        $pdf->SetXY($x, $y);
        $pdf->Cell(30,5,"DEDUCTIBLE",1,0,'L',1); 
        $pdf->SetFont('Arial','',10); 
           
        //CUERPO        
         $pdf->SetFont('Arial','',10);
        if(count($arr_trailer)>0){
            $x = 20;       
            $y += 5;     
            foreach($arr_trailer as $trailer){
        $x = 20;
                $pdf->SetXY($x, $y);
                $pdf->Cell(15,5,$trailer["year"],1,0,'L',0);
                $x += 15;
                $pdf->SetXY($x, $y);
                $pdf->Cell(50,5,$trailer["make"],1,0,'L',0);
                $x += 50;
                $pdf->SetXY($x, $y);
                $pdf->Cell(25,5,$trailer["body_type"],1,0,'L',0);
                $x += 25;
                $pdf->SetXY($x, $y);
                $pdf->Cell(25,5,$trailer["GVW"],1,0,'L',0);
                $x += 25;
                $pdf->SetXY($x, $y);
                $pdf->Cell(30,5,$trailer["STATED VALUE"],1,0,'L',0);
                $x += 30;
                $pdf->SetXY($x, $y);
                $pdf->Cell(30,5,$trailer["deductible"],1,0,'L',0);
                $y += 5;
                
            }
            
        }
        
        





        
$pdf->Output();

?>
 