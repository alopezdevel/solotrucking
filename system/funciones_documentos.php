<?php
  function getDocumentos($consecutivo, &$arr_){
      $query_sql_consecutivo = "";
      if($consecutivo != ""){
        $query_sql_consecutivo = " WHERE iConsecutivo = '".$consecutivo."' ";
      }
      include("cn_usuarios_2.php");
      $query_sql = "SELECT iConsecutivo, sNombreJPGSistema, sTituloArchivoEmpresa, sNombreArchivoEmpresa, sComentarios, sRuta, sPDFRelacionFormatoSistema ".  
                   " FROM ct_formatos_PDF ".$query_sql_consecutivo;
      $result = mysql_query($sql, $dbconn);
      if (mysql_num_rows($result) > 0) {
           while ($Recordset = mysql_fetch_array($result)) {
               $arr_[] = array("consecutivo" => $Recordset['iConsecutivo'],
                            "nombre_JPG_sistema" => stripslashes($Recordset['sNombreJPGSistema']),
                            "titulo_archivo_empresa" => $Recordset['sTituloArchivoEmpresa'],
                            "nombre_archivo_empresa" => $Recordset['sNombreArchivoEmpresa'],
                            "nombre_contacto" => stripslashes($Recordset['sNombreContacto']),
                            "comentarios" => stripslashes($Recordset['sComentarios']),
                            "ruta" => stripslashes($Recordset['sRuta']),
                            "PDF_relacion_formato_sistema" => stripslashes($Recordset['sPDFRelacionFormatoSistema']));
           }
      }
      mysql_free_result($result);
      mysql_close($dbconn);      
      
  }
?>
