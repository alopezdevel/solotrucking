<?php session_start();    
if ( !($_SESSION["acceso"] == 'C'  && $_SESSION["usuario_actual"] != "" && $_SESSION["usuario_actual"] != NULL  )  ){ //No ha iniciado session, redirecciona a la pagina de login
    header("Location: login.php");
    exit;
}else{ ?>
<script src="/js/jquery.1.8.3.min.js" type="text/javascript"></script> 

<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/cupertino/jquery-ui.css">          
<script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
<script src="/js/jquery.blockUI.js" type="text/javascript"></script>
<script src="/js/jquery.form.js" type="text/javascript"></script> 
<script type="text/javascript"> 
$(document).ready(inicio);

function inicio(){  
    //archivo 

        $.blockUI();
        var usuario_actual = <?php echo json_encode($_SESSION['usuario_actual']);?>        
        var tipo_usuario = <?php echo json_encode($_SESSION['acceso']);?> 
        validapantalla(usuario_actual);
        if(tipo_usuario == "C"){
            validarLoginCliente(usuario_actual);
        }  
        $("#aUpdateAccount").click(function() { actualizarCliente(usuario_actual); }); 
        llenadoGrid();  
        $.unblockUI();
        $("#buton_download").click(abrirDescargaPopUp);
    
}  

function validapantalla(usuario){
        
        if(usuario == ""  || usuario == null){
            location.href= "login.php";
        }
        
    }              
function llenadoGrid(){      
     var fn_UsersClients = {
        domroot:"#ct_clientusers",
        data_grid: "#data_grid_clientusers",
        fillgrid: function(){
               $.ajax({             
                type:"POST", 
                url:"funciones.php", 
                data:{accion:"get_company_certificate"},
                async : true,
                dataType : "json",
                success : function(data){                               
                    $(fn_UsersClients.data_grid+" tbody").empty().append(data.tabla);
                    $(fn_UsersClients.data_grid+" tbody tr:even").addClass('gray');
                    $(fn_UsersClients.data_grid+" tbody tr:odd").addClass('white');
                }
            }); 
        }    
    }
    fn_UsersClients.fillgrid();    
}
function borrarClient(cliente_borrar){
    $.post("funciones.php",{
                               accion : "borrar_cliente", 
                               id : cliente_borrar
                               }, 
                               onRespuestaBorrado);
                               
}

function onRespuestaBorrado(respuesta, estado, xhr ){
    llenadoGrid();   
}
function confirmarBorrar(registro,id) {
    $( "#dialog-confirm" ).dialog({
      resizable: false,
      height:200,
      width:500,
      show: {effect: 'fade', speed: 2000},
      modal: true,
      buttons: {
        "Delete item": function() {
          
          borrarClient(id) ;
          $( this ).dialog( "close" );
           
        },
        Cancel: function() {
          $( this ).dialog( "close" );
          return false;
        }
      }
    });
    
    //return confirmacion;

}
function validarLoginCliente(usuario){
        //$.blockUI({ message: $('#domMessage') });
        $.post("funciones.php", { accion: "validar_cliente_acceso", usuario: usuario},
        function(data){ 
                                // 
         if(data.error == "0"){  
             switch(data.estatus){                                                                                                
                case "0":  
                                    //location.href= "login.php";
                                    break;
                                    
                case "1":           $.unblockUI();                          
                                    codigo_1 = data.codigo.substring(0, 10);
                                    codigo_2 = data.codigo.substring(5, 15);
                                    total_len = data.consecutivo.length;
                                    location.href= "company_register.php?ref="+ total_len + '_'  +  codigo_1 +  data.consecutivo;                                    
                                    break;
                case "2":          $.unblockUI();                          
                                    /*codigo_1 = data.codigo.substring(0, 10);
                                    codigo_2 = data.codigo.substring(5, 15);
                                    total_len = data.consecutivo.length;
                                    location.href= "company_register.php?ref="+ total_len + '_'  +  codigo_1 +  data.consecutivo;  */
                                    break;
                 
             }
         }else{
             //error
         }   
         
     }
     ,"json");
        
        
    }

  function actualizarCliente(usuario){
        $.post("funciones.php", { accion: "validar_cliente_acceso", usuario: usuario},
        function(data){ 
                                // 
         if(data.error == "0"){
             codigo_1 = data.codigo.substring(0, 10);
             codigo_2 = data.codigo.substring(5, 15);
             total_len = data.consecutivo.length;
             location.href= "company_register.php?ref="+ total_len + '_'  +  codigo_1 +  data.consecutivo;
         }else{
             //error
         }   
         
     }
     ,"json");
    }

 function  onAbrirDialog(id){    
    var dialogo;
    var div =  $("fn_request_certificate");
    dialogo = $( "#dialog-certificate" ).dialog({
      autoOpen: false,
      height: 500,
      width: 527,                                 
      modal: true,      
      close: function() {
        //form[ 0 ].reset();
        //allFields.removeClass( "ui-state-error" );
      }
    }); 
    $("#idCertificate").val(id);
    dialogo.dialog("open");
}   
function abrirDescargaPopUp(){
     
     var id = $("#idCertificate").val();
     $( "#dialog-certificate" ).dialog("close");
     window.open('pdf_certificate.php?id='+id,'_blank');
} 
    
</script> 
<!---- HEADER ----->
<?php include("header.php"); ?> 
<script> 
$(document).ready(inicio);
function inicio(){   

    llenadoGrid();  
}   
</script>
<div id="layer_content" class="main-section">
    <div id="ct_clientusers" class="container">
        <div class="page-title">
            <h1>Certificates</h1>
            <h2>Download Certificates</h2>
        </div>
        <table id="data_grid_clientusers" class="data_grid">
        <thead id="grid-head2">
            <tr>
                <td class="etiqueta_grid">Name</td>
                <td class="etiqueta_grid">E-mail</td>
                <td class="etiqueta_grid">Description</td>
                <td class="etiqueta_grid">Status </td>
                <td class="etiqueta_grid"></td>
            </tr>
        </thead>
        <tbody></tbody>
        <tfoot>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        </tfoot>
        </table>
        
    </div>
</div>
<div id="dialog-confirm" title="Delete">
  <p><span class="ui-icon ui-icon-alert" ></span>These items will be permanently deleted and cannot be recovered. Are you sure?</p>
</div>
<!---- FOOTER ----->
<?php include("footer.php"); ?> 
 <div id="dialog-certificate" title="Send Certificate" >
        <fieldset id="sendEmail">
            <form name="emailForm" id="emailForm" method="POST"  enctype="multipart/form-data">
                <p class="mensaje_valido">&nbsp;All form fields are required.</p>
                <br />
                <br />
                <label>Campo A:</label><input type="text" id="campoA" name="campoA"></input>
                <label>Campo B:</label><input type="text" id="campoB" name="campoB"></input>
                <label>Campo C:</label><input type="text" id="campoC" name="campoC"></input>
                <div align="center"><input type="button" id="buton_download" value="Download" id="button_submit"  class="btn_2" ></div>  
                <input type="hidden" name="accion" id="accion" value="subir_certificado"  />                    
                <input type="hidden" name="idCertificate" id="idCertificate"   />                    
            </form>
            <div id="loading"></div>                                                                                
            </fieldset>
    </div>
</body>

</html>
<?php } ?>