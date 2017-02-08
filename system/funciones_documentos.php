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
      $query_sql = "SELECT sUsdot, 
      sMC, 
      iConsecutivo, 
      sFEIN,       
      sNombreCompania, 
      sTelefonoPrincipal, 
      sDireccion, 
      sCiudad, 
      sCodigoPostal, 
      sEstado, 
      sTelefonoPrincipal,
      sTelefono2, 
      sEmailContacto  ".  
      " FROM ct_companias WHERE iConsecutivo = '".$consecutivo."' ";
      $result = $conexion->query($query_sql);  
      $rows = $result->num_rows;    
      if ($rows > 0) {
           while ($Recordset = $result->fetch_assoc()) {
               $arr_[] = array("consecutivo" => $Recordset['iConsecutivo'],          
                            "us_dot" => $Recordset['sUsdot'], 
                            "MC" => $Recordset['sMC'],                             
                            "FEIN" => $Recordset['sFEIN'], 
                            "ciudad" => $Recordset['sCiudad'], 
                            "telefono_principal" => $Recordset['sTelefonoPrincipal'], 
                            "codigoPostal" => $Recordset['sCodigoPostal'], 
                            "Estado" => $Recordset['sEstado'], 
                            "TelefonoPrincipal" => $Recordset['sTelefonoPrincipal'], 
                            "Telefono2" => $Recordset['sTelefono2'], 
                            "email" => $Recordset['sEmailContacto'],                             
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
      $query_sql = "SELECT  ct_operadores.sNombre, ct_operadores.iExperienciaYear,ct_operadores.sAccidentesNum, iNumLicencia, eTipoLicencia, if(dFechaNacimiento != '0000-00-00', DATE_FORMAT(ct_operadores.dFechaNacimiento,'%m/%d/%Y'), '') AS dFechaNacimiento ".  
                   " FROM cb_quote_format_operadores
                     LEFT JOIN ct_operadores ON ct_operadores.iConsecutivo = cb_quote_format_operadores.iConsecutivoOperador 
                     WHERE  cb_quote_format_operadores.iConsecutivo = '".$consecutivo."' ";
                     
      $result = $conexion->query($query_sql);  
      $rows = $result->num_rows;    
      if ($rows > 0) {
           while ($Recordset = $result->fetch_assoc()) {
               $arr_[] = array("nombre" => $Recordset['sNombre'],
                            "exp_year" => stripslashes($Recordset['iExperienciaYear']),  
                            "fecha_nacimiento" => $Recordset['dFechaNacimiento'],
                            "tipo_licencia" => stripslashes($Recordset['eTipoLicencia']),
                            "num_licencia" => stripslashes($Recordset['iNumLicencia']),
                            "accidentes" => $Recordset['sAccidentesNum']);
           }
      }
      mysql_free_result($result);
      mysql_close($dbconn);
      
      
  } 
  
  function getEquipmenTrailertPDF($consecutivo, &$arr_){
      include("cn_usuarios.php");
      $query_sql = "SELECT  ct_unidad_radio.sDescripcion as radio_desc, ct_unidades.iYear, ct_unidad_modelo.sDescripcion, ct_unidades.sTipo, ct_unidades.iTotalPremiumPD, sPeso, sVIN   ".  
                   " FROM cb_quote_format_operadores
                     LEFT JOIN ct_unidades ON ct_unidades.iConsecutivo = cb_quote_format_operadores.iConsecutivoOperador
                     LEFT JOIN ct_unidad_modelo ON ct_unidad_modelo.iConsecutivo = ct_unidades.iModelo 
                     LEFT JOIN ct_unidad_radio ON ct_unidad_radio.iConsecutivo = ct_unidades.iConsecutivoRadio 
                     WHERE  cb_quote_format_operadores.iConsecutivo = '".$consecutivo."' ";
                     
      $result = $conexion->query($query_sql);  
      $rows = $result->num_rows;    
      if ($rows > 0) {
           while ($Recordset = $result->fetch_assoc()) {
               $arr_[] = array("year" => $Recordset['iYear'],
                            "make" => stripslashes($Recordset['sDescripcion']),
                            "body_type" => $Recordset['sTipo'],   
                            "peso" => $Recordset['sPeso'],
                            "radio_desc" => $Recordset['radio_desc'],
                            "VIN" => $Recordset['sVIN'],
                            "deductible" => $Recordset['iTotalPremiumPD']);
           }
      }
      mysql_free_result($result);
      mysql_close($dbconn);
      
      
  }
  function getCommoditiesHauled($consecutivo, &$arr_){
      include("cn_usuarios.php");
      $query_sql = "SELECT  ct_quotes_default_commodities.sCommodities, 
                            cb_quote_format_commodities.iConsecutivo, 
                            cb_quote_format_commodities.iConsecutivoCommodity, 
                            cb_quote_format_commodities.iPorcentajeHauled,
                            cb_quote_format_commodities.iValorMinimo, 
                            cb_quote_format_commodities.iValorMaximo   ".  
                   " FROM cb_quote_format_commodities
                     LEFT JOIN ct_quotes_default_commodities ON cb_quote_format_commodities.iConsecutivoCommodity = ct_quotes_default_commodities.iConsecutivo
                     WHERE  cb_quote_format_commodities.iConsecutivo = '".$consecutivo."' ";                     
                     
      $result = $conexion->query($query_sql);  
      $rows = $result->num_rows;    
      if ($rows > 0) {
           while ($Recordset = $result->fetch_assoc()) {
               $arr_[] = array("commoditie" => $Recordset['sCommodities'],
                            "consecutivo" => stripslashes($Recordset['iConsecutivo']),
                            "porcentaje_hauled" => $Recordset['iPorcentajeHauled'],   
                            "valor_minimo" => $Recordset['iValorMinimo'],
                            "valor_maximo" => $Recordset['iValorMaximo']);
           }
      }
      mysql_free_result($result);
      mysql_close($dbconn);
      
      
  }
?>
