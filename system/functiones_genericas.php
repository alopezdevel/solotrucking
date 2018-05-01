<?php
    function mysql_fetch_all($query, $kind = 'assoc') {
        $result = array();
        $kind = $kind === 'assoc' ? $kind : 'row';
        eval('while ($r->fetch_'.$kind.'()){array_push($result, $r);} ');
        return $result;
    }
    function createCodeSerie(){
        $Key="";
        while(strlen($Key)<=15)
        {
            srand((double)microtime()*1000000);
            $number = rand(50,150);
            if($number>=65 && $number<=78)
                $Key = $Key.chr($number);
            elseif($number>=80 && $number<=90)
                $Key = $Key.chr($number);
            elseif($number>=49 && $number<=57)
                $Key = $Key.chr($number);
        }
        return trim($Key);
    }
    function createKeyWS(){
      $Key="";
      while(strlen($Key)<=29)
      {
        srand((double)microtime()*1000000);
        $number = rand(50,150);
        if($number>=65 && $number<=78)
            if($number%2 != 0){
                $Key = strtolower($Key.chr($number));
            }else{
                $Key = $Key.chr($number);
            }elseif($number>=80 && $number<=90)
                $Key = $Key.chr($number);
          elseif($number>=49 && $number<=57)
            if($number%2 != 0){
                    $Key = strtolower($Key.chr($number));
              }else{
                    $Key = $Key.chr($number);
              }
      }
      return trim(md5(sha1($Key)));
    }
    function randomPassword() { 
        $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
        //$alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789``-=~!@#$%^&*()_+,./<>?;:[]{}\|";
        $pass = array();
        $alphaLength = strlen($alphabet) - 1;
        for ($i = 0; $i < 15; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass);
    }
    function response_eval($ars,$domroot){
        if(count($ars)>0){
            $llaves  = array_keys($ars[0]);
            $datos   = $ars[0];
            foreach($datos as $i=> $b){
                //echo trim($datos[$i])!= "" ? "$('#$domroot :input[id=".$i."]').val('".fix_string($datos[$i])."');" : "";
                echo "$('#$domroot :input[id=".$i."]').val('".fix_string($datos[$i])."');";
            }
        }else{
            echo "";
        }
     }
    function response_eval_general($ars,$domroot){
        if(count($ars)>0){
            $llaves  = array_keys($ars[0]);
            $datos   = $ars[0];
            foreach($datos as $i=> $b){
                //echo trim($datos[$i])!= "" ? "$('#$domroot :input[id=".$i."]').val('".fix_string($datos[$i])."');" : "";
                $var_general.= "$('#$domroot :input[id=".$i."]').val('".fix_string($datos[$i])."');";
            }
        }else{
            echo "";
        }
        return $var_general;
     }
    function fix_string($text){
        if (preg_match('/^(\d\d\d\d)-(\d\d?)-(\d\d?)$/', $text)) {
            /* fix when insert '' in date field */
            if($text=="0000-00-00"){
                return("");
            }else{
                $tmp = explode("-",$text);
                return($tmp[2]."/".$tmp[1]."/".$tmp[0]);
            }
        }
        else {
            $tmp2 =  str_replace("\n","\\n",$text);
            $tmp3 =  str_replace("'","\\'",$tmp2);
            $tmp4 =  str_replace('"','\\"',$tmp3);
            //$tmp5 =  str_replace("&","\\&",$tmp4);

            return($tmp4);
        }
     }
    function xml2array($xml) {
            $arXML=array();
            $arXML['name']=trim($xml->getName());
            $arXML['value']=trim((string)$xml);
            $t=array();
            foreach($xml->attributes() as $name => $value) $t[$name]=trim($value);
            $arXML['attr']=$t;
            $t=array();
            foreach($xml->children() as $name => $xmlchild) $t[$name]=xml2array($xmlchild); 
            $arXML['children']=$t;
            return($arXML);
        }
    function date_to_server($text){
            $order   = array("'", "\"","%","�","�","�","\$","~","�");
            $replace = " ";
            $text = str_replace($order," ",trim($text));

            $find=true;
            while($find){
                  $text = str_replace(array("  ","\ ")," ",$text);
                  $pos = strpos($text,"  ");
                  if($pos===false){
                        $find=false;
                  }
            }

            //if (preg_match("/^(\d\d?)\D(\d\d?)\D(\d\d\d\d)$/", $text)) {
            if (preg_match("/^(0[1-9]|[1-2][0-9]|3[0-1])\D(0[1-9]|1[0-2])\D([0-9]{4})$/", $text)) {
                if($text=="0000-00-00"){
                    return("");
                }else{
                    $tmp = explode("/",$text);
                    return($tmp[2]."-".$tmp[1]."-".$tmp[0]);
                }
            } else {
                $tmp2 =  str_replace("\n","\\n",$text);
                $tmp3 =  str_replace("'","\\'",$tmp2);
                $tmp4 =  str_replace('"','\\"',$tmp3);
                $tmp5 =  str_replace("&","\\&",$tmp4);

                return($tmp4);
            }
        }   
    function inserta_accion_bitacora($login, $token, $accion, $dbconn){  
      //$dml = "INSERT INTO CfgLogActividad (sLogin, sToken, tAccion,IPUsuario) VALUES('$login','$token','$accion','".date_to_server($_SERVER['REMOTE_ADDR'])."')";
      $sql = "INSERT INTO cu_intentos_acceso (sUsuario,sClave,dFechaIngreso,sIP,bEntroSistema) ".
             "VALUES ('$usuario','$clave','".date("Y-m-d H:i:s")."','".$_SERVER['REMOTE_ADDR']."','$acceso')";
      $result = mysql_query($dml,$dbconn);
      if($result){return true;}else{return false;}
    }
    function get_user_token($token){
         
        $query = "SELECT IdUsuario FROM CfgUsuario WHERE sToken = '".$token."'";
        $result = mysql_query($query,CONN) or die(mysql_error());
        $user_data = mysql_fetch_all($result); 
        return $user_data[0]['IdUsuario'];
    }
    function format_date($date){
        $date_array = explode('/',$date);
        $date_new =  $date_array[2].'-'.$date_array[0].'-'.$date_array[1];
        return $date_new;
    }
    function array2json($arr) { 
        if(function_exists('json_encode')) return json_encode($arr); //Lastest versions of PHP already has this functionality.
        $parts = array(); 
        $is_list = false; 

        //Find out if the given array is a numerical array 
        $keys = array_keys($arr); 
        $max_length = count($arr)-1;                                                                                       
        if(($keys[0] == 0) and ($keys[$max_length] == $max_length)) {//See if the first key is 0 and last key is length - 1 
            $is_list = true; 
            for($i=0; $i<count($keys); $i++) { //See if each key correspondes to its position 
                if($i != $keys[$i]) { //A key fails at position check. 
                    $is_list = false; //It is an associative array. 
                    break; 
                } 
            } 
        } 

        foreach($arr as $key=>$value) { 
            if(is_array($value)) { //Custom handling for arrays 
                if($is_list) $parts[] = array2json($value); /* :RECURSION: */ 
                else $parts[] = '"' . $key . '":' . array2json($value); /* :RECURSION: */ 
            } else { 
                $str = ''; 
                if(!$is_list) $str = '"' . $key . '":'; 

                //Custom handling for multiple data types 
                if(is_numeric($value)) $str .= $value; //Numbers 
                elseif($value === false) $str .= 'false'; //The booleans 
                elseif($value === true) $str .= 'true'; 
                else $str .= '"' . addslashes($value) . '"'; //All other things 
                // :TODO: Is there any more datatype we should be in the lookout for? (Object?) 

                $parts[] = $str; 
            } 
        } 
        $json = implode(',',$parts); 
         
        if($is_list) return '[' . $json . ']';//Return numerical JSON 
        return '{' . $json . '}';//Return associative JSON 
    } 
    function valida_email($email){
         $emailRegex   = "/^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/"; 
         $validaemail  = preg_match($emailRegex,trim($email)); 
         return $validaemail;
    }
    /*----- POLIZAS ---*/
    function get_policy_type($iTipo){
        switch($iTipo){
                   case '1' : $tipoPoliza = "PD"; break;
                   case '2' : $tipoPoliza = "MTC"; break;
                   case '3' : $tipoPoliza = "AL"; break;
                   case '5' : $tipoPoliza = "MTC"; break;
        } 
        return $tipoPoliza;
    }
    function filtro_polizas_endosos(){
        return "AND (D.iConsecutivo = '1' OR D.iConsecutivo = '3' OR D.iConsecutivo = '5' OR D.iConsecutivo = '2') ";
    }
    function filtro_polizas_claims($tableAlias){
        return "AND ($tableAlias.iConsecutivo = '1' OR $tableAlias.iConsecutivo = '2' OR $tableAlias.iConsecutivo = '3' OR $tableAlias.iConsecutivo = '5') ";
    }
?>
