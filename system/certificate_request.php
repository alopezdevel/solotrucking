<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/cupertino/jquery-ui.css">
<script src="/js/jquery.1.8.3.min.js" type="text/javascript"></script> 
<script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
    <!---- Fancybox -------->
    <script type="text/javascript" src="../fancybox/lib/jquery.mousewheel-3.0.6.pack.js"></script>
    <script type="text/javascript" src="../fancybox/source/jquery.fancybox.js"></script>
    <link rel="stylesheet" type="text/css" href="../fancybox/source/jquery.fancybox.css" media="screen">
     <script type="text/javascript" src="../fancybox/fancy.js"></script>
     <link rel="stylesheet" href="/resources/demos/style.css">      
     <script src="/js/jquery.form.js" type="text/javascript"></script>  
       
<script type="text/javascript">                 
function llenadoGrid(){
    var filtro = "";  //DATE_FORMAT(dFechaIngreso,  '%m/%d/%Y')         
    if($('#filtro_CreatedDate').val() !=""){     
        filtro += "DATE_FORMAT(dFechaIngreso,  '%m/%d/%Y')|" + $("#filtro_CreatedDate").val() + ",*"
    }
    if($('#filtro_InsuredName').val() !=""){     
        filtro += "sInnsuredName|" + $("#filtro_InsuredName").val() + ",*"
    }
    if($('#filtro_email').val() !=""){     
        filtro += "email|" + $("#filtro_email").val() + ",*"
    }
    if($('#filtro_CertificateHolder').val() !=""){     
        filtro += "sCholder|" + $("#filtro_CertificateHolder").val() + ",*"
    }     
    if($('#filtro_DescriptionOperations').val() !=""){                                             
        filtro += "sDescription|" + $("#filtro_DescriptionOperations").val() + ",*"
    }
    if($('#filtro_SendingDate').val() !=""){     
        filtro += "DATE_FORMAT(dFechaArchivo,  '%m/%d/%Y')|" + $("#filtro_SendingDate").val() + ",*"
    }
    if($('#filtro_Status').val() !=""){     
        filtro += "eEstatus|" + $("#filtro_Status").val() + ",*"
    }
    
        var fn_request_certificate = {
        domroot:"#fn_request_certificate",
        data_grid: "#data_grid_certificate",
        fillgrid: function(){
               $.ajax({             
                type:"POST", 
                url:"funciones.php", 
                data:{accion:"get_request_certificate", filtroInformacion : filtro},
                async : true,
                dataType : "json",
                success : function(data){                               
                    $(fn_request_certificate.data_grid+" tbody").empty().append(data.tabla);
                    $(fn_request_certificate.data_grid+" tbody tr:even").addClass('gray');
                    $(fn_request_certificate.data_grid+" tbody tr:odd").addClass('white');
                                }
            }); 
        }    
    }
    fn_request_certificate.fillgrid();
}
</script> 

<script> 
$(document).ready(inicio);
function inicio(){ 
//archivo 
$('#emailForm').ajaxForm({
            beforeSubmit: validate,
            success: function(data, statusText, xhr, form) {
                $('#loading').html('El email se envio correctamente');
                alert("El email se envio correctamente");
                $('#loading').hide();
                $("#asunto").val("");
                $("#para").val("");
                $("#mensaje").val("");
                $("#adjunto").val("");
            }
    });     
    
    function validate(formData, jqForm, options) {
            var form =  jqForm[0]; 
            $('#loading').html('Loading...').show();
        }   
       
    $( "#filtro_Status" ).selectmenu();
    $(".date").datepicker({
        onSelect: function() {
            $(this).change();
        }
    });                                                                                    
    //fechas
    $( "#filtro_CreatedDate" ).datepicker({onSelect: function(){ onkeyup()}});
    $( "#filtro_SendingDate" ).datepicker({onSelect: function(){ onkeyup()}});
    //filtros
    $( "#filtro_CreatedDate" ).keyup(onkeyup);
    $( "#filtro_SendingDate" ).keyup(onkeyup);
    $("#filtro_InsuredName").keyup(onkeyup);
    $("#filtro_email").keyup(onkeyup);           
    $("#filtro_CertificateHolder").keyup(onkeyup);
    $("#filtro_DescriptionOperations").keyup(onkeyup);
    $("#filtro_Status" ).selectmenu({ change: function( event, ui ) { onkeyup(); }});
    $( "#boton_uploadFile" ).click(onAbrirDialog); 
    llenadoGrid();  
    
}
function onkeyup(){
    llenadoGrid();
}

function  onAbrirDialog(){    
    var dialogo;
    var div =  $("fn_request_certificate");
    dialogo = $( "#dialog-certificate" ).dialog({
      autoOpen: false,
      height: 300,
      width: 400,                                 
      modal: true,      
      close: function() {
        //form[ 0 ].reset();
        //allFields.removeClass( "ui-state-error" );
      }
    });
    $("#para").val("alopez@globalpc.net");
    dialogo.dialog("open");
}    



</script> 
<!---- HEADER ----->
<?php include("header.php"); ?> 
<div id="layer_content" class="main-section">
    <div id="fn_request_certificate" class="container">
        <div class="page-title">
            <h1>Certificates</h1>
            <h2>Requests of Certificates</h2>
        </div>
        <table id="data_grid_certificate" class="data_grid">
        <thead id="grid-head2">                                                                                       
            <tr>                            
                <td align="center" class="etiqueta_grid" nowrap="nowrap" ><input class="inp"  id="filtro_CreatedDate" type="text"></td>
                <td align="center" class="etiqueta_grid"><input class="inp"  id="filtro_InsuredName" type="text"></td>
                <td align="center" class="etiqueta_grid"><input class="inp"  id="filtro_email" type="text"></td>
                <td align="center" class="etiqueta_grid"><input class="inp"  id="filtro_CertificateHolder" type="text"></td>
                <td align="center" class="etiqueta_grid"><input class="inp"  align="center" id="filtro_DescriptionOperations" type="text"></td>
                <td align="center" class="etiqueta_grid" nowrap="nowrap"><select   id="filtro_Status" ><option value="">Select<option value="0">IN PROCESS</option><option value="1">COMPLETE</option></td>
                <td align="center" class="etiqueta_grid"><input class="inp"   id="filtro_SendingDate" type="text"></td>
                <td></td> 
            </tr>
            <tr>                            
                <td align="center" class="etiqueta_grid" nowrap="nowrap" >Created Date</td>
                <td align="center" class="etiqueta_grid">Insured Name</td>
                <td align="center" class="etiqueta_grid">E-mail</td>
                <td align="center" class="etiqueta_grid">Certificate Holder</td>
                <td align="center" class="etiqueta_grid"  nowrap="nowrap" >Description of Operations</td>
                <td align="center" class="etiqueta_grid">Status</td>
                <td align="center" class="etiqueta_grid" nowrap="nowrap">Sending Date </td>
                <td></td> 
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
    
   
<!---- FOOTER ----->
<?php include("footer.php"); ?> 
</div>
 <div id="dialog-certificate" title="Send Certificate" >
        <fieldset id="sendEmail">
            <form name="emailForm" id="emailForm" method="POST" action="funciones.php"  enctype="multipart/form-data">
                <p class="mensaje_valido">&nbsp;All form fields are required.</p>
                <br />
                <br />
                <label>to:</label><input  id="para" class="user" name="para" type="text" placeholder="to:" readonly="readonly">
                <label>Description:</label><textarea  name="mensaje" id="mensaje" rows="3" cols="15" placeholder="Description" ></textarea>
                <div ><label>File:</label> <input type="file" id="adjunto" name="adjunto" size="25" class="etiqueta_grid" > *</div>  
                <div align="center"><input type="submit" value="Enviar Correo" id="button_submit"  class="btn_2" ></div>
                <input type="hidden" name="accion" id="accion" value="enviar_certificado"  />                    
            </form>
            <div id="loading"></div>                                                                                
            </fieldset>
    </div>
</body>

</html>
