<?php
   session_start();
  // Generic functions lib 
  include("functiones_genericas.php"); 
  $_POST["accion"] and  $_POST["accion"]!= "" ? call_user_func_array($_POST["accion"],array()) : ""; 
 
  
  $array1    = array("5", "10", "20");
  $array2    = array("20","5");
  $resultado = array_diff($array1, $array2);
  $resultado = implode(',',$resultado);
  echo $resultado;
?>
