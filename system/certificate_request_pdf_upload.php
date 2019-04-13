<?php 
    session_start();    
    if ( !($_SESSION["acceso"] != '2'  && $_SESSION["usuario_actual"] != "" && $_SESSION["usuario_actual"] != NULL  )  ){ //No ha iniciado session, redirecciona a la pagina de login
        header("Location: login.php");
        exit;
    }else{ 
?>
<!-- HEADER -->
<?php include("header.php"); ?> 
<script type="text/javascript"> 
    $(document).ready(inicio);
    function inicio(){  
        $.unblockUI();    
        var usuario_actual = <?php echo json_encode($_SESSION['usuario_actual']);?>        
        var tipo_usuario = <?php echo json_encode($_SESSION['acceso']);?> 
        validapantalla(usuario_actual); 
        fn_certificate.init();
        fn_certificate.fillgrid();  
    }  
    function validapantalla(usuario){if(usuario == ""  || usuario == null){location.href= "login.php";}}                    
    var fn_certificate = {
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
                $(fn_certificate.data_grid + ' #grid-head1 input').keyup(function(event){
                    if (event.keyCode == '13') {
                        event.preventDefault();
                        fn_certificate.filtraInformacion();
                    }
                    if(event.keyCode == '27'){
                       event.preventDefault();
                       $(this).val(''); 
                       fn_certificate.filtraInformacion();
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
                                'sNombreCompania'     : $("#certificate_edit_form #sNombreCompania").val(),
                                'iConsecutivo'        : $("#certificate_edit_form #iConsecutivo").val(),
                                'dFechaVencimiento'   : $('#certificate_edit_form #dFechaVencimiento').val(),
                                'eOrigenCertificado'  : $("#certificate_edit_form #eOrigenCertificado").val()
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
                /*new AjaxUpload('#btnsAdditionalPDF', {
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
                                'iConsecutivo' : $("#certificate_edit_form #iConsecutivo").val()
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
                }); */
                
            },
            fillgrid: function(){
                   $.ajax({             
                    type:"POST", 
                    url:"funciones_certificate_pdf_upload.php", 
                    data:{
                        accion:"get_companies_certificates",
                        registros_por_pagina : "15", 
                        pagina_actual : fn_certificate.pagina_actual, 
                        filtroInformacion : fn_certificate.filtro,  
                        ordenInformacion : fn_certificate.orden,
                        sortInformacion : fn_certificate.sort,
                    },
                    async : true,
                    dataType : "json",
                    success : function(data){                               
                        $(fn_certificate.data_grid+" tbody").empty().append(data.tabla);
                        $(fn_certificate.data_grid+" tbody tr:even").addClass('gray');
                        $(fn_certificate.data_grid+" tbody tr:odd").addClass('white');
                        $(fn_certificate.data_grid + " tfoot #paginas_total").val(data.total);
                        $(fn_certificate.data_grid + " tfoot #pagina_actual").val(data.pagina);
                        fn_certificate.pagina_actual = data.pagina; 
                        fn_certificate.edit();
                    }
                }); 
            },
            edit: function(){
                $(fn_certificate.data_grid + " tbody td .edit").bind("click",function(){
                    $('#certificate_edit_form input, #certificate_edit_form select').val('').removeClass('error');
                    $("#certificate_edit_form #iConsecutivoCompania").val($(this).parent().parent().find("td:eq(0)").html()); 
                    $("#certificate_edit_form #sNombreCompania").val($(this).parent().parent().find("td:eq(1)").text());
                    $.post("funciones_certificate_pdf_upload.php",
                    {
                        "accion"  :"get_files", 
                        "clave"   : $("#certificate_edit_form #iConsecutivoCompania").val(), 
                        "domroot" : "certificate_edit_form"
                    },
                    function(data){
                        if(data.error == '0'){ 
                           eval(data.fields); 
                           fn_certificate.valida_tipo_certificado();
                           fn_popups.resaltar_ventana('certificate_edit_form');
                            
                        }else{
                           fn_solotrucking.mensaje(data.msj);  
                        }       
                    },"json");
                    
                });  
            },
            firstPage : function(){
                if($(fn_certificate.data_grid+" #pagina_actual").val() != "1"){
                    fn_certificate.pagina_actual = "";
                    fn_certificate.fillgrid();
                }
            },
            previousPage : function(){
                if($(fn_certificate.data_grid+" #pagina_actual").val() != "1"){
                    fn_certificate.pagina_actual = (parseInt($(fn_certificate.data_grid+" #pagina_actual").val()) - 1) + "";
                    fn_certificate.fillgrid();
                }
            },
            nextPage : function(){
                if($(fn_certificate.data_grid+" #pagina_actual").val() != $(fn_certificate.data_grid+" #paginas_total").val()){
                    fn_certificate.pagina_actual = (parseInt($(fn_certificate.data_grid+" #pagina_actual").val()) + 1) + "";
                    fn_certificate.fillgrid();
                }
            },
            lastPage : function(){
                if($(fn_certificate.data_grid+" #pagina_actual").val() != $(fn_certificate.data_grid+" #paginas_total").val()){
                    fn_certificate.pagina_actual = $(fn_certificate.data_grid+" #paginas_total").val();
                    fn_certificate.fillgrid();
                }
            }, 
            ordenamiento : function(campo,objeto){
                $(fn_certificate.data_grid + " #grid-head2 td").removeClass('down').removeClass('up');

                if(campo == fn_certificate.orden){
                    if(fn_certificate.sort == "ASC"){
                        fn_certificate.sort = "DESC";
                        $(fn_certificate.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('up');
                    }else{
                        fn_certificate.sort = "ASC";
                        $(fn_certificate.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
                    }
                }else{
                    fn_certificate.sort = "ASC";
                    fn_certificate.orden = campo;
                    $(fn_certificate.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
                }
                fn_certificate.fillgrid();

                return false;
            }, 
            filtraInformacion : function(){
                fn_certificate.pagina_actual = 0;
                fn_certificate.filtro = "";
                if($(fn_certificate.data_grid+" .flt_id").val() != ""){ fn_certificate.filtro += "A.iConsecutivo|"+$(fn_certificate.data_grid+" .flt_id").val()+","}
                if($(fn_certificate.data_grid+" .flt_name").val() != ""){ fn_certificate.filtro += "sNombreCompania|"+$(fn_certificate.data_grid+" .flt_name").val()+","} 
                if($(fn_certificate.data_grid+" .flt_usdot").val() != ""){ fn_certificate.filtro += "sUsdot|"+$(fn_certificate.data_grid+" .flt_usdot").val()+","} 
                if($(fn_certificate.data_grid+" .flt_date").val() != ""){ fn_certificate.filtro += "C.dFechaActualizacion|"+$(fn_certificate.data_grid+" .flt_date").val()+","}
                if($(fn_certificate.data_grid+" .flt_expiredate").val() != ""){ fn_certificate.filtro += "C.dFechaVencimiento|"+$(fn_certificate.data_grid+" .flt_expiredate").val()+","}  
                fn_certificate.fillgrid();
            },
            valida_tipo_certificado : function(){
                var tipo = $("#certificate_edit_form #eOrigenCertificado").val();
                if(tipo == 'LAYOUT'){
                    $("#certificate_edit_form .campos-layout").show();
                    $("#certificate_edit_form .campos-database, #info_policies").hide();
                }else{
                    fn_certificate.get_datapolicies();
                    $("#certificate_edit_form .campos-database").show();
                    $("#certificate_edit_form .campos-layout").hide();
                }
            },
            get_datapolicies : function(){
                $.ajax({             
                    type:"POST", 
                    url:"funciones_certificate_pdf_upload.php", 
                    data:{'accion':"get_policies",'iConsecutivoCompania':$("#certificate_edit_form #iConsecutivoCompania").val()},
                    async : false,
                    dataType : "json",
                    success : function(data){                               
                        if(data.error == '0'){
                            $("#info_policies").show();
                            $("#info_policies table tbody").empty().append(data.policies_information);
                        }
                    }
                });
            },
            save : function(){
                var valid = true;
                var msj   = "";
                $("#certificate_edit_form .required-field").removeClass("error");
                //Revisamos campos obligatorios: 
                $("#certificate_edit_form  input.required-field, #certificate_edit_form  select.required-field").each(function(){
                   if($(this).val() == ""){valid = false; $(this).addClass('error');msj = "<li>You must capture the required fields.</li>";}
                });
                
                if(valid){ 
                    
                  if($("#certificate_edit_form #iConsecutivo").val() != ""){struct_data_post.edit_mode = "true";}else{struct_data_post.edit_mode = "false";}
                  struct_data_post.action  = "upload_certificate";
                  struct_data_post.domroot = "#certificate_edit_form";
                  $.ajax({             
                    type  : "POST", 
                    url   : "funciones_certificate_pdf_upload.php", 
                    data  : struct_data_post.parse(),
                    async : true,
                    dataType : "json",
                    success  : function(data){                               
                        switch(data.error){ 
                         case '0':
                            fn_solotrucking.mensaje(data.mensaje);
                            fn_certificate.filtraInformacion();
                         break;
                         case '1': fn_solotrucking.mensaje(data.mensaje); break;
                        }
                    }
                  }); 
                    
                }else{fn_solotrucking.mensaje('<p>Please check the following::</p><ul>'+msj+'</ul>');}
            }, 
            preview_certificate : function(){
                var company = $("#certificate_edit_form #iConsecutivoCompania").val() ;
                if(company != ""){
                    window.open('pdf_certificate.php?id='+company+'&ca=COMPANY%20NAME&cb=NUMBER%20AND%20ADDRESS&cc=CITY&cd=STATE&ce=ZIPCODE&ds=PREVIEW');
                }
            }   
    }
</script>  
<div id="layer_content" class="main-section">
    <div id="ct_clientusers" class="container">
        <div class="page-title">
            <h1>Certificates</h1>
            <h2>configuration for certificates</h2>
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
                    <div class="btn-icon-2 btn-left" title="Search" onclick="fn_certificate.filtraInformacion();"><i class="fa fa-search"></i></div>
                </td> 
            </tr>
            <tr id="grid-head2">
                <td class="etiqueta_grid down" onclick="fn_certificate.ordenamiento('A.iConsecutivo',this.cellIndex);">ID</td>
                <td class="etiqueta_grid"      onclick="fn_certificate.ordenamiento('sNombreCompania',this.cellIndex);">Name</td>
                <td class="etiqueta_grid"      onclick="fn_certificate.ordenamiento('sUsdot',this.cellIndex);">USDOT</td>
                <td class="etiqueta_grid"      onclick="fn_certificate.ordenamiento('C.dFechaActualizacion',this.cellIndex);">Last update</td> 
                <td class="etiqueta_grid"      onclick="fn_certificate.ordenamiento('C.dFechaVencimiento',this.cellIndex);">Expire Date</td> 
                <td class="etiqueta_grid"      onclick="fn_certificate.ordenamiento('eEstatusCertificadoUpload',this.cellIndex);">Status</td>
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
                        <button id="pgn-inicio"    onclick="fn_certificate.firstPage();" title="First page"><span></span></button>
                        <button id="pgn-anterior"  onclick="fn_certificate.previousPage();" title="Previous"><span></span></button>
                        <button id="pgn-siguiente" onclick="fn_certificate.nextPage();" title="Next"><span></span></button>
                        <button id="pgn-final"     onclick="fn_certificate.lastPage();" title="Last Page"><span></span></button>
                    </div>
                </td>
            </tr>
        </tfoot>
        </table>  
    </div>
</div>
<!-- FORMULARIOS -->
<div id="certificate_edit_form" class="popup-form">
    <div class="p-header">
        <h2>EDIT THE COMPANY CERTIFICATE</h2>
        <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('certificate_edit_form');fn_certificate.fillgrid();"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container">
    <div>
        <form>
            <fieldset>
                <legend>GENERAL DATA FOR CERTIFICATE</legend>
                 <table style="width: 100%;border-collapse: collapse;">
                    <tr><td colspan="100%"><p class="mensaje_valido">&nbsp;The fields containing an (<span style="color:#ff0000;">*</span>) are required.</p></td></tr>
                    <tr>
                        <td colspan="100%">
                            <label class="required-field" title="the limit date for certificate layout">Set certificate from <span style="color:#ff0000;">*</span>:</label> 
                            <select id="eOrigenCertificado" class="required-field" style="height: 27px!important;" onblur="fn_certificate.valida_tipo_certificado();">
                                <option value="">Select an option...</option>
                                <option value="LAYOUT">PDF - Layout uploaded.</option>
                                <option value="DATABASE">DATA BASE - Data from company data policies in the system.</option>
                            </select>   
                        </td>
                    </tr>
                    <tr>
                        <td style="width:60%;">
                        <div class="field_item">
                            <input id="iConsecutivoCompania" name="iConsecutivoCompania" type="hidden" value="">
                            <label>Company Name <span style="color:#ff0000;">*</span>:</label> 
                            <input id="sNombreCompania"  type="text" class="readonly" readonly="readonly" style="width: 97%;">
                        </div>
                        </td>
                        <td>
                        <div class="field_item">
                           <label title="the limit date for certificate layout">Expire Date <span style="color:#ff0000;">*</span>:</label> 
                           <input id="dFechaVencimiento" type="text" class="fecha required-field" style="width:90%;" value="">
                        </div>
                        </td>
                    </tr>  
                    <tr>
                        <td colspan="100%">
                        <div id="info_policies">
                            <table class="popup-datagrid" style="margin-bottom: 10px;width: 100%;" cellpadding="0" cellspacing="0">
                                <thead>
                                    <tr id="grid-head2">
                                        <td class="etiqueta_grid">Type</td>
                                        <td class="etiqueta_grid">Policy Number</td>
                                        <td class="etiqueta_grid">POLICY EFF </td>
                                        <td class="etiqueta_grid">POLICY EXP</td>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        </td>
                    </tr>
                    <tr class="campos-database">
                        <td colspan="100%">
                        <div class="field_item">
                           <label title="DESCRIPTION OF OPERATIONS / LOCATIONS / VEHICLES (ACORD 101, Additional Remarks Schedule, may be attached if more space is required)">Descriptions of operations:</label> 
                           <textarea id="sDescripcionOperaciones" style="width:99%;"></textarea>
                        </div>
                        </td>
                    </tr>
                 </table>
                <br>
                <button type="button" class="btn-1 campos-database" onclick="fn_certificate.save();">SAVE</button> 
                <button type="button" class="btn-1 campos-database" onclick="fn_certificate.preview_certificate();" style="margin-right:10px;background:#5ec2d4;width: 180px;">PREVIEW CERTIFICATE</button> 
                <button type="button" class="btn-1 campos-database" onclick="fn_popups.cerrar_ventana('certificate_edit_form');fn_certificate.fillgrid();" style="margin-right:10px;background:#e8051b;">CLOSE</button> 
            </fieldset>
            <fieldset class="campos-layout">
                <legend>UPLOAD PDF LAYOUT FROM INSURED HUB</legend>
                <p class="mensaje_valido">&nbsp;The fields containing an (<span style="color:#ff0000;">*</span>) are required.</p>
                <table style="width: 100%;border-collapse: collapse;">
                    <tr>
                        <td colspan="100%">
                        <div class="file_certificate files"> 
                            <label>Certificate: <span style="color:#9e2e2e;">Please upload a copy of the form in PDF format.</span></label> 
                            <input  id="txtsCertificatePDF" type="text" readonly="readonly" value="" size="40" style="width:83%;" />
                            <button id="btnsCertificatePDF" type="button">Upload Certificate</button>
                            <input  id="iConsecutivo" type="hidden">
                        </div> 
                        </td>
                    </tr>
                </table>
            </fieldset> 
        </form>
    </div>
    </div>
</div>
<!-- FOOTER -->
<?php include("footer.php"); ?> 
</body>
</html>
<?php } ?>