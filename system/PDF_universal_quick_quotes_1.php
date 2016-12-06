<?php
  require('lib/fpdf153/fpdf.php');//Cargando  libreria
  include_once("funciones_documentos.php");
  $_POST['consecutivo'] = "1";
  $arr_formato = NULL;
  getDocumentos($_POST['consecutivo'],$arr_formato);
  $pdf = new FPDF(); //Declarando clase FPDF
  $pdf->AddPage(); // Agregando pagina
  $pdf->SetFont('Arial','',10);  
  $ruta_archivo = $arr_formato[0]['ruta']; 
  $pdf->Image($ruta_archivo,'0','0','215','290','JPG');
  //Variables
  $NAMED_INSURED = 'NAMED INSURED';
  $GARAGING_ADDRESS = 'GARAGING ADDRESS';
  $PH = 'PH';
  $YEARS_IN_BUSSINES = '25';
  $COMMODITIES_HAULED = 'COMMODITIES_HAUL COMMODITIES_HAUL COMMODITIES_HAUL COMMODITIES_HAUL';
  $FEIN = "FEIN";
  $CHECK_NONE = "X";
  $CHECK_ICC = "X";
  //$ICC = "ICC";
  //$CHECK_DMV = "X";
 // $DMV = "DMV";  
  //$CHECK_OTHER = "X";
  $CHECK_RADIUS1 = "X";
  $CHECK_RADIUS2 = "X";
  $CHECK_RADIUS3 = "X";
  //$CHECK_RADIUS4 = "X";
  //$CHECK_RADIUS5 = "X";
  //$CHECK_RADIUS6 = "X";
  //$RADIUSEX = "RADIUS";
  $COVERAGES_100 = "X";
  $COVERAGES_300 = "X";
  $COVERAGES_500 = "X";
  $COVERAGES_750 = "X";
  $COVERAGES_1M = "X";
  $COVERAGES_OTHER = "X";
  $COVERAGES_OTHER_LABEL = "COVERAGE";
  $COVERAGES_ALD_500 = "X";
  $COVERAGES_UMB_15 = "X";
  $COVERAGES_UMB_25 = "X";
  $COVERAGES_UMB_30 = "X";
  //DEMO DRIVERS
  $arr_drivers[0]['NAME'] = "NAME TEST 1";
  $arr_drivers[1]['NAME'] =  "NAME TEST 2";
  $arr_drivers[2]['NAME'] = "NAME TEST 3";
  $arr_drivers[0]['YRS_EXP'] = "YRS TEST 1";
  $arr_drivers[1]['YRS_EXP'] = "YRS TEST 2";
  $arr_drivers[2]['YRS_EXP'] = "YRS TEST 3";
  $arr_drivers[0]['ACCIDENTS'] = "ACCIDENTS 1";
  $arr_drivers[1]['ACCIDENTS'] = "ACCIDENTS 2";
  $arr_drivers[2]['ACCIDENTS'] = "ACCIDENTS 3";
  
  //DEMO EQUIPMENT
  $arr_equipment[0]['YEAR'] = "2000";
  $arr_equipment[0]['MAKE'] =  "MAKE";
  $arr_equipment[0]['BODY TYPE'] = "BODY 1";
  $arr_equipment[0]['GVW'] = "GVW 1";
  $arr_equipment[0]['STATED VALUE'] = "YYY";
  $arr_equipment[0]['DEDUCTIBLE'] = "1000,000";
  
  $arr_equipment[1]['YEAR'] = "2000";
  $arr_equipment[1]['MAKE'] =  "MAKE";
  $arr_equipment[1]['BODY TYPE'] = "BODY 1";
  $arr_equipment[1]['GVW'] = "GVW 1";
  $arr_equipment[1]['STATED VALUE'] = "YYY";
  $arr_equipment[1]['DEDUCTIBLE'] = "1000,000";
  
  $arr_equipment[2]['YEAR'] = "2000";
  $arr_equipment[2]['MAKE'] =  "MAKE";
  $arr_equipment[2]['BODY TYPE'] = "BODY 1";
  $arr_equipment[2]['GVW'] = "GVW 1";
  $arr_equipment[2]['STATED VALUE'] = "YYY";
  $arr_equipment[2]['DEDUCTIBLE'] = "1000,000";
  
  $arr_equipment[3]['YEAR'] = "2000";
  $arr_equipment[3]['MAKE'] =  "MAKE";
  $arr_equipment[3]['BODY TYPE'] = "BODY 1";
  $arr_equipment[3]['GVW'] = "GVW 1";
  $arr_equipment[3]['STATED VALUE'] = "YYY";
  $arr_equipment[3]['DEDUCTIBLE'] = "1000,000";
  
  $arr_equipment[4]['YEAR'] = "2000";
  $arr_equipment[4]['MAKE'] =  "MAKE";
  $arr_equipment[4]['BODY TYPE'] = "BODY 1";
  $arr_equipment[4]['GVW'] = "GVW 1";
  $arr_equipment[4]['STATED VALUE'] = "YYY";
  $arr_equipment[4]['DEDUCTIBLE'] = "1000,000";
  
  //TRAILER
  
  //DEMO EQUIPMENT
  $arr_trailer[0]['YEAR'] = "2001";
  $arr_trailer[0]['MAKE'] =  "MAKE2";
  $arr_trailer[0]['BODY TYPE'] = "BODY 2";
  $arr_trailer[0]['GVW'] = "GVW 1";
  $arr_trailer[0]['STATED VALUE'] = "YsYY";
  $arr_trailer[0]['DEDUCTIBLE'] = "1s000,000";
  
 
  
  
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
        $x += 33;        
        $y += 8;        
        $pdf->SetXY($x,$y);
        $pdf->Write(5,$CHECK_NONE);
        //ICC        
        $x += 18;                
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
        $x = 40;     
        $y += 6;           
        $pdf->SetXY($x,$y);
        $pdf->Write(5,$COVERAGES_ALD_500);
        //COVERAGE AUTO LIABILITY DEDUCTIBLE
        $x = 40;     
        $y += 5;           
        $pdf->SetXY($x,$y);
        $pdf->Write(5,$COVERAGES_UMB_15); 
        //COVERAGE AUTO LIABILITY DEDUCTIBLE    Z
        $x += 10;             
        $pdf->SetXY($x,$y);
        $pdf->Write(5,$COVERAGES_UMB_25); 
        //COVERAGE AUTO LIABILITY DEDUCTIBLE
        $x += 10;             
        $pdf->SetXY($x,$y);
        $pdf->Write(5,$COVERAGES_UMB_30); 
        
        
        
        
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
                $pdf->Cell(60,5,$driver["NAME"],1,0,'L',0);
                $x += 60;
                $pdf->SetXY($x, $y);
                $pdf->Cell(30,5,$driver["YRS_EXP"],1,0,'L',0);
                $x += 30;
                $pdf->SetXY($x, $y);
                $pdf->Cell(30,5,$driver["ACCIDENTS"],1,0,'L',0);
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
                $pdf->Cell(15,5,$equipment["YEAR"],1,0,'L',0);
                $x += 15;
                $pdf->SetXY($x, $y);
                $pdf->Cell(50,5,$equipment["MAKE"],1,0,'L',0);
                $x += 50;
                $pdf->SetXY($x, $y);
                $pdf->Cell(25,5,$equipment["BODY TYPE"],1,0,'L',0);
                $x += 25;
                $pdf->SetXY($x, $y);
                $pdf->Cell(25,5,$equipment["GVW"],1,0,'L',0);
                $x += 25;
                $pdf->SetXY($x, $y);
                $pdf->Cell(30,5,$equipment["STATED VALUE"],1,0,'L',0);
                $x += 30;
                $pdf->SetXY($x, $y);
                $pdf->Cell(30,5,$equipment["DEDUCTIBLE"],1,0,'L',0);
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
                $pdf->Cell(15,5,$trailer["YEAR"],1,0,'L',0);
                $x += 15;
                $pdf->SetXY($x, $y);
                $pdf->Cell(50,5,$trailer["MAKE"],1,0,'L',0);
                $x += 50;
                $pdf->SetXY($x, $y);
                $pdf->Cell(25,5,$trailer["BODY TYPE"],1,0,'L',0);
                $x += 25;
                $pdf->SetXY($x, $y);
                $pdf->Cell(25,5,$trailer["GVW"],1,0,'L',0);
                $x += 25;
                $pdf->SetXY($x, $y);
                $pdf->Cell(30,5,$trailer["STATED VALUE"],1,0,'L',0);
                $x += 30;
                $pdf->SetXY($x, $y);
                $pdf->Cell(30,5,$trailer["DEDUCTIBLE"],1,0,'L',0);
                $y += 5;
                
            }
            
        }
        
        





        
$pdf->Output();

?>
 