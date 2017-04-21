<?php
  function getDocumentosPDF($consecutivo, &$arr_){
      $query_sql_consecutivo = "";
      if($consecutivo != ""){
        $query_sql_consecutivo = " WHERE iConsecutivo = '".$consecutivo."' ";
      }
      include("cn_usuarios.php");
      $query_sql = "SELECT iConsecutivo, sNombreJPGSistema, sTituloArchivoEmpresa, sNombreArchivoEmpresa, sComentarios, sRuta, sPDFRelacionFormatoSistema ".  
                   " FROM ct_formatos_PDF ".$query_sql_consecutivo;
      $result = $conexion->query($query_sql);  
      $rows = $result->num_rows;    
      if ($rows > 0) {
           while ($Recordset = $result->fetch_assoc()) {
               $arr_[] = array("consecutivo" => $Recordset['iConsecutivo'],
                            "nombre_JPG_sistema" => stripslashes($Recordset['sNombreJPGSistema']),
                            "titulo_archivo_empresa" => $Recordset['sTituloArchivoEmpresa'],
                            "nombre_archivo_empresa" => $Recordset['sNombreArchivoEmpresa'],
                            "nombre_contacto" => stripslashes($Recordset['sNombreContacto']),
                            "comentarios" => stripslashes($Recordset['sComentarios']),
                            "ruta" => $Recordset['sRuta'],
                            "PDF_relacion_formato_sistema" => stripslashes($Recordset['sPDFRelacionFormatoSistema']));
           }
      }
      mysql_free_result($result);
      mysql_close($dbconn);      
      
  }
  function getCompaniaPDF($consecutivo, &$arr_){
      include("cn_usuarios.php");
      $query_sql = "SELECT iConsecutivo, sNombreCompania, sTelefonoPrincipal, sDireccion ".  
                   " FROM ct_companias WHERE iConsecutivo = '".$consecutivo."' ";
      $result = $conexion->query($query_sql);  
      $rows = $result->num_rows;    
      if ($rows > 0) {
           while ($Recordset = $result->fetch_assoc()) {
               $arr_[] = array("consecutivo" => $Recordset['iConsecutivo'],
                            "compania" => stripslashes($Recordset['sNombreCompania']),
                            "telefono_principal" => $Recordset['sTelefonoPrincipal'],
                            "direccion" => $Recordset['sDireccion']);
           }
      }
      mysql_free_result($result);
      mysql_close($dbconn);            
      
  }
  function getDriversPDF($consecutivo, &$arr_){
      include("cn_usuarios.php");
      $query_sql = "SELECT  ct_operadores.sNombre, ct_operadores.iExperienciaYear,ct_operadores.sAccidentesNum ".  
                   " FROM cb_quote_format_operadores
                     LEFT JOIN ct_operadores ON ct_operadores.iConsecutivo = cb_quote_format_operadores.iConsecutivoOperador 
                     WHERE  cb_quote_format_operadores.iConsecutivo = '".$consecutivo."' ";
                     
      $result = $conexion->query($query_sql);  
      $rows = $result->num_rows;    
      if ($rows > 0) {
           while ($Recordset = $result->fetch_assoc()) {
               $arr_[] = array("nombre" => $Recordset['sNombre'],
                            "exp_year" => stripslashes($Recordset['iExperienciaYear']),
                            "accidentes" => $Recordset['sAccidentesNum']);
           }
      }
      mysql_free_result($result);
      mysql_close($dbconn);
      
      
  }
  function getEquipmenTrailertPDF($consecutivo, &$arr_){
      include("cn_usuarios.php");
      $query_sql = "SELECT  ct_unidades.iYear, ct_unidad_modelo.sDescripcion, ct_unidades.sTipo, ct_unidades.iTotalPremiumPD   ".  
                   " FROM cb_quote_format_operadores
                     LEFT JOIN ct_unidades ON ct_unidades.iConsecutivo = cb_quote_format_operadores.iConsecutivoOperador
                     LEFT JOIN ct_unidad_modelo ON ct_unidad_modelo.iConsecutivo = ct_unidades.iModelo 
                     WHERE  cb_quote_format_operadores.iConsecutivo = '".$consecutivo."' ";
                     
      $result = $conexion->query($query_sql);  
      $rows = $result->num_rows;    
      if ($rows > 0) {
           while ($Recordset = $result->fetch_assoc()) {
               $arr_[] = array("year" => $Recordset['iYear'],
                            "make" => stripslashes($Recordset['sDescripcion']),
                            "body_type" => $Recordset['sTipo'],
                            "deductible" => $Recordset['iTotalPremiumPD']);
           }
      }
      mysql_free_result($result);
      mysql_close($dbconn);
      
      
  }
  
?>
