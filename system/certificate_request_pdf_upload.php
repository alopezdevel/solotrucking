<?php session_start();    
if ( !($_SESSION["acceso"] != '2'  && $_SESSION["usuario_actual"] != "" && $_SESSION["usuario_actual"] != NULL  )  ){ 
    //No ha iniciado session, redirecciona a la pagina de login
    header("Location: login.php");
    exit;
}else{ ?>
<!---- HEADER ----->
<?php include("header.php"); ?> 
<script type="text/javascript"> 
$(document).ready(inicio);
function inicio(){    
    var usuario_actual = <?php echo json_encode($_SESSION['usuario_actual']);?>        
    var tipo_usuario = <?php echo json_encode($_SESSION['acceso']);?> 
    validapantalla(usuario_actual); 
    fn_UsersClients.init();
    fn_UsersClients.fillgrid();  
    $.unblockUI();  
}  
function validapantalla(usuario){if(usuario == ""  || usuario == null){location.href= "login.php";}}                    
var fn_UsersClients = {
        domroot:"#ct_clientusers",
        data_grid: "#data_grid_clientusers",
        filtro : "",
        pagina_actual : "",
        sort : "ASC",
        orden : "C.dFechaActualizacion",
        init : function(){
            $('.num').keydown(fn_solotrucking.inputnumero); 
            $('.decimals').keydown(fn_solotrucking.inputdecimals);
            //Filtrado con la tecla enter
            $(fn_UsersClients.data_grid + ' #grid-head1 input').keyup(function(event){
                if (event.keyCode == '13') {
                    event.preventDefault();
                    fn_UsersClients.filtraInformacion();
                }
                if(event.keyCode == '27'){
                   event.preventDefault();
                   $(this).val(''); 
                   fn_UsersClients.filtraInformacion();
                }
            });
            //INICIALIZA DATEPICKER PARA CAMPOS FECHA
            $(".fecha").datepicker({
                showOn: 'button',
                buttonImage: 'images/layout.png',
                dateFormat : 'mm/dd/yy',
                buttonImageOnly: true
            });
            $(".fecha,.flt_fecha").mask("99/99/9999");
            new AjaxUpload('#btnsCertificatePDF', {
                action: 'funciones_certificate_pdf_upload.php',
                onSubmit : function(file , ext){
                    if (!(ext && /^(pdf)$/i.test(ext))){
                        var mensaje = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>Error: The file format is not valid.</p>';
                        fn_solotrucking.mensaje(mensaje);
                        return false;
                    }else{
                        this.setData({
                            'accion': 'upload_certificate',
                            'iConsecutivoCompania': $("#certificate_edit_form #iConsecutivoCompania").val(),
                            'sNombreCompania': $("#certificate_edit_form #sNombreCompania").val(),
                            'iConsecutivo' : $("#certificate_edit_form #iConsecutivoCertificate").val(),
                            'dFechaVencimiento' : $('#certificate_edit_form #dFechaVencimiento').val()
                        });
                        $('#txtsCertificatePDF').val('loading...');
                        this.disable(); 
                    }
                },
                onComplete : function(file,response){  
                    var respuesta = JSON.parse(response);
                    switch(respuesta.error){
                        case '0':
                            $('#txtsCertificatePDF').val(respuesta.name_file);
                            this.enable();
                            $('#iConsecutivoCertificatePDF').val(respuesta.id_file);
                            fn_solotrucking.mensaje(respuesta.mensaje);
                        break;
                        case '1':
                           fn_solotrucking.mensaje(respuesta.mensaje);
                           $('#txtsCertificatePDF').val(''); 
                           this.enable();
                        break;
                    }   
                }        
            }); 
            new AjaxUpload('#btnsAdditionalPDF', {
                action: 'funciones_certificate_pdf_upload.php',
                onSubmit : function(file , ext){
                    if (!(ext && /^(pdf)$/i.test(ext))){
                        var mensaje = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>Error: The file format is not valid.</p>';
                        fn_solotrucking.mensaje(mensaje);
                        return false;
                    }else{
                        this.setData({
                            'accion': 'upload_additional',
                            'iConsecutivoCompania': $("#certificate_edit_form #iConsecutivoCompania").val(),
                            'sNombreCompania': $("#certificate_edit_form #sNombreCompania").val(),
                            'iConsecutivo' : $("#certificate_edit_form #iConsecutivoCertificate").val()
                        });
                        $('#txtsAdditionalPDF').val('loading...');
                        this.disable(); 
                    }
                },
                onComplete : function(file,response){  
                    var respuesta = JSON.parse(response);
                    switch(respuesta.error){
                        case '0':
                            $('#txtsAdditionalPDF').val(respuesta.name_file);
                            this.enable();
                            fn_solotrucking.mensaje(respuesta.mensaje);
                        break;
                        case '1':
                           fn_solotrucking.mensaje(respuesta.mensaje);
                           $('#txtsAdditionalPDF').val(''); 
                           this.enable();
                        break;
                    }   
                }        
            }); 
            
        },
        fillgrid: function(){
               $.ajax({             
                type:"POST", 
                url:"funciones_certificate_pdf_upload.php", 
                data:{
                    accion:"get_companies_certificates",
                    registros_por_pagina : "15", 
                    pagina_actual : fn_UsersClients.pagina_actual, 
                    filtroInformacion : fn_UsersClients.filtro,  
                    ordenInformacion : fn_UsersClients.orden,
                    sortInformacion : fn_UsersClients.sort,
                },
                async : true,
                dataType : "json",
                success : function(data){                               
                    $(fn_UsersClients.data_grid+" tbody").empty().append(data.tabla);
                    $(fn_UsersClients.data_grid+" tbody tr:even").addClass('gray');
                    $(fn_UsersClients.data_grid+" tbody tr:odd").addClass('white');
                    $(fn_UsersClients.data_grid + " tfoot #paginas_total").val(data.total);
                    $(fn_UsersClients.data_grid + " tfoot #pagina_actual").val(data.pagina);
                    fn_UsersClients.pagina_actual = data.pagina; 
                    fn_UsersClients.edit();
                }
            }); 
        },
        edit: function(){
            $(fn_UsersClients.data_grid + " tbody td .edit").bind("click",function(){
                $('#certificate_edit_form input, #certificate_edit_form select').val('').removeClass('error');
                $("#certificate_edit_form #iConsecutivoCompania").val($(this).parent().parent().find("td:eq(0)").html()); 
                $("#certificate_edit_form #sNombreCompania").val($(this).parent().parent().find("td:eq(1)").text());
                $.post("funciones_certificate_pdf_upload.php",
                {
                    accion:"get_files", 
                    clave:  $("#certificate_edit_form #iConsecutivoCompania").val(), 
                    domroot : "certificate_edit_form"
                },
                function(data){
                    if(data.error == '0'){ 
                       eval(data.fields); 
                       if($('#certificate_edit_form #iConsecutivoCertificate').val() != ''){
                          $('#certificate_edit_form .file_additional').show(); 
                       }else{
                           $('#certificate_edit_form .file_additional').hide();
                       }
                       fn_popups.resaltar_ventana('certificate_edit_form');
                        
                    }else{
                       fn_solotrucking.mensaje(data.msj);  
                    }       
                },"json");
                
            });  
        },
        firstPage : function(){
            if($(fn_UsersClients.data_grid+" #pagina_actual").val() != "1"){
                fn_UsersClients.pagina_actual = "";
                fn_UsersClients.fillgrid();
            }
        },
        previousPage : function(){
            if($(fn_UsersClients.data_grid+" #pagina_actual").val() != "1"){
                fn_UsersClients.pagina_actual = (parseInt($(fn_UsersClients.data_grid+" #pagina_actual").val()) - 1) + "";
                fn_UsersClients.fillgrid();
            }
        },
        nextPage : function(){
            if($(fn_UsersClients.data_grid+" #pagina_actual").val() != $(fn_UsersClients.data_grid+" #paginas_total").val()){
                fn_UsersClients.pagina_actual = (parseInt($(fn_UsersClients.data_grid+" #pagina_actual").val()) + 1) + "";
                fn_UsersClients.fillgrid();
            }
        },
        lastPage : function(){
            if($(fn_UsersClients.data_grid+" #pagina_actual").val() != $(fn_UsersClients.data_grid+" #paginas_total").val()){
                fn_UsersClients.pagina_actual = $(fn_UsersClients.data_grid+" #paginas_total").val();
                fn_UsersClients.fillgrid();
            }
        }, 
        ordenamiento : function(campo,objeto){
            $(fn_UsersClients.data_grid + " #grid-head2 td").removeClass('down').removeClass('up');

            if(campo == fn_UsersClients.orden){
                if(fn_UsersClients.sort == "ASC"){
                    fn_UsersClients.sort = "DESC";
                    $(fn_UsersClients.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('up');
                }else{
                    fn_UsersClients.sort = "ASC";
                    $(fn_UsersClients.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
                }
            }else{
                fn_UsersClients.sort = "ASC";
                fn_UsersClients.orden = campo;
                $(fn_UsersClients.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
            }
            fn_UsersClients.fillgrid();

            return false;
        }, 
        filtraInformacion : function(){
            fn_UsersClients.pagina_actual = 0;
            fn_UsersClients.filtro = "";
            if($(fn_UsersClients.data_grid+" .flt_id").val() != ""){ fn_UsersClients.filtro += "A.iConsecutivo|"+$(fn_UsersClients.data_grid+" .flt_id").val()+","}
            if($(fn_UsersClients.data_grid+" .flt_name").val() != ""){ fn_UsersClients.filtro += "sNombreCompania|"+$(fn_UsersClients.data_grid+" .flt_name").val()+","} 
            if($(fn_UsersClients.data_grid+" .flt_usdot").val() != ""){ fn_UsersClients.filtro += "sUsdot|"+$(fn_UsersClients.data_grid+" .flt_usdot").val()+","} 
            if($(fn_UsersClients.data_grid+" .flt_date").val() != ""){ fn_UsersClients.filtro += "C.dFechaActualizacion|"+$(fn_UsersClients.data_grid+" .flt_date").val()+","}
            if($(fn_UsersClients.data_grid+" .flt_expiredate").val() != ""){ fn_UsersClients.filtro += "C.dFechaVencimiento|"+$(fn_UsersClients.data_grid+" .flt_expiredate").val()+","}  
            fn_UsersClients.fillgrid();
        }, 
           
}
</script>  
<div id="layer_content" class="main-section">
    <div id="ct_clientusers" class="container">
        <div class="page-title">
            <h1>Certificates</h1>
            <h2>Upload Certificates</h2>
        </div>
        <table id="data_grid_clientusers" class="data_grid">
        <thead>
            <tr id="grid-head1">
                <td style='width:45px;'>
                <input class="flt_id" class="numeros" type="text" placeholder="ID:"></td>
                <td><input class="flt_name" type="text" placeholder="Name:"></td>
                <td><input class="flt_usdot" type="text" placeholder="USDOT:"></td> 
                <td><input class="flt_date" type="text" placeholder="Upload Date:"></td>
                <td><input class="flt_expiredate" type="text" placeholder="Expire Date:"></td>   
                <td></td>  
                <td style='width:100px;'>
                    <div class="btn-icon-2 btn-left" title="Search" onclick="fn_UsersClients.filtraInformacion();"><i class="fa fa-search"></i></div>
                </td> 
            </tr>
            <tr id="grid-head2">
                <td class="etiqueta_grid down" onclick="fn_UsersClients.ordenamiento('A.iConsecutivo',this.cellIndex);">ID</td>
                <td class="etiqueta_grid"      onclick="fn_UsersClients.ordenamiento('sNombreCompania',this.cellIndex);">Name</td>
                <td class="etiqueta_grid"      onclick="fn_UsersClients.ordenamiento('sUsdot',this.cellIndex);">USDOT</td>
                <td class="etiqueta_grid"      onclick="fn_UsersClients.ordenamiento('C.dFechaActualizacion',this.cellIndex);">Upload Date</td> 
                <td class="etiqueta_grid"      onclick="fn_UsersClients.ordenamiento('C.dFechaVencimiento',this.cellIndex);">Expire Date</td> 
                <td class="etiqueta_grid"      onclick="fn_UsersClients.ordenamiento('eEstatusCertificadoUpload',this.cellIndex);">Files Status</td>
                <td class="etiqueta_grid"></td>
            </tr>
        </thead>
        <tbody></tbody>
        <tfoot>
            <tr>
                <td colspan="100%">
                    <div id="datagrid-pages">
                        <input id="pagina_actual" type="text" readonly="readonly" size="3">
                        <label> / </label>
                        <input id="paginas_total" type="text" readonly="readonly" size="3">
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="100%">
                    <div id="datagrid-menu-pages">
                        <button id="pgn-inicio"    onclick="fn_UsersClients.firstPage();" title="First page"><span></span></button>
                        <button id="pgn-anterior"  onclick="fn_UsersClients.previousPage();" title="Previous"><span></span></button>
                        <button id="pgn-siguiente" onclick="fn_UsersClients.nextPage();" title="Next"><span></span></button>
                        <button id="pgn-final"     onclick="fn_UsersClients.lastPage();" title="Last Page"><span></span></button>
                    </div>
                </td>
            </tr>
        </tfoot>
        </table>  
    </div>
</div>
<!---- FORMULARIOS ------>
<div id="certificate_edit_form" class="popup-form">
    <div class="p-header">
        <h2>EDIT THE COMPANY CERTIFICATE</h2>
        <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('certificate_edit_form');fn_UsersClients.fillgrid();"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container">
    <div>
        <form>
            <fieldset>
                <legend>General Information</legend>
                <p class="mensaje_valido">&nbsp;To upload the files please click in the right buttons.</p> 
                <div class="field_item">
                    <input id="iConsecutivoCompania" name="iConsecutivo" type="hidden">
                    <label>Company Name:</label> 
                    <input id="sNombreCompania"  type="text" class="readonly" readonly="readonly">
                </div>
                <div class="field_item">
                   <label title="the limit date for certificate layout">Expire Date:</label> 
                   <input id="dFechaVencimiento" type="text" class="fecha">
                </div>
                <div class="file_certificate files"> 
                        <label>Certificate: <span style="color:#9e2e2e;">Please upload a copy of the form in PDF format.</span></label> 
                        <input  id="txtsCertificatePDF" type="text" readonly="readonly" value="" size="40" style="width:85%;" />
                        <button id="btnsCertificatePDF" type="button">Upload Certificate</button>
                        <input  id="iConsecutivoCertificate" type="hidden">
                </div> 
                <div class="file_additional files" style="display:none;"> 
                        <label>Additional: <span style="color:#9e2e2e;">Please upload a copy of the form in PDF format.</span></label> 
                        <input  id="txtsAdditionalPDF" type="text" readonly="readonly" value="" size="40" style="width:85%;" />
                        <button id="btnsAdditionalPDF" type="button">Upload Additional</button>
                </div>
            </fieldset>
        </form>
    </div>
    </div>
</div>
<!--- DIALOGUES --->
<div id="dialog-confirm" title="Delete">
  <p><span class="ui-icon ui-icon-alert" ></span>These items will be permanently deleted and cannot be recovered. Are you sure?</p>
</div>
<!---- FOOTER ----->
<?php include("footer.php"); ?> 
 <div id="dialog-certificate" title="Send Certificate" >
        <fieldset id="sendEmail">
            <form name="emailForm" id="emailForm" method="POST" action="funciones.php"  enctype="multipart/form-data">
                <p class="mensaje_valido">&nbsp;All form fields are required.</p>
                <br />
                <br />
                <label>Description:</label><textarea  name="mensaje" id="mensaje" rows="3" cols="15" placeholder="Description" ></textarea>
                <div ><label>File:</label> <input type="file" id="adjunto" name="adjunto" size="25" class="etiqueta_grid" > *</div>  
                <div align="center"><input type="submit" value="Upload" id="button_submit"  class="btn_2" ></div>  
                <input type="hidden" name="accion" id="accion" value="subir_certificado"  />                    
                <input type="hidden" name="idCertificate" id="idCertificate"   />                    
            </form>
            <div id="loading"></div>                                                                                
            </fieldset>
    </div>
    <div id="dialog-certificate-aditional" title="Send Additional remarks schedule"  >
        <fieldset id="sendEmail">
            <form name="emailFormAdd" id="emailFormAdd" method="POST" action="funciones.php"  enctype="multipart/form-data">
                <p class="mensaje_valido">&nbsp;All form fields are required.</p>
                <br />
                <br />                
                <div ><label>File:</label> <input type="file" id="adjunto_add" name="adjunto_add" size="25" class="etiqueta_grid" > *</div>  
                <div align="center"><input type="submit" value="Upload" id="button_submit"  class="btn_2" ></div>  
                <input type="hidden" name="accion" id="accion" value="subir_aditional"  />                    
                <input type="hidden" name="idCertificateAdd" id="idCertificateAdd"   />                    
            </form>
            <div id="loading"></div>                                                                                
            </fieldset>
    </div>
</body>

</html>
<?php } ?>