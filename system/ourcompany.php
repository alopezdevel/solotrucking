<?php 
    session_start();    
    if ($_SESSION["acceso"] != '3' && $_SESSION["acceso"] != '1'  && $_SESSION["usuario_actual"] == "" && $_SESSION["usuario_actual"] == NULL){ 
        //No ha iniciado session, redirecciona a la pagina de login
        header("Location: login.php");
        exit;
    }else{ 
?>
<!-- HEADER -->
<?php include("header.php"); ?> 
<!-- Plugin Color Picker -->
    <script src="lib/jquery_color_picker/jquery.minicolors.js"  type="text/javascript"></script>
    <link  href="lib/jquery_color_picker/jquery.minicolors.css" type="text/css" rel="stylesheet">  
    <script type="text/javascript"> 
    $(document).ready(inicio);
    function inicio(){  
            var usuario_actual = <?php echo json_encode($_SESSION['usuario_actual']);?>        
            var tipo_usuario   = <?php echo json_encode($_SESSION['acceso']);?> 
            validapantalla(usuario_actual);  
            $("#aUpdateAccount").click(function() { actualizarCliente(usuario_actual); });
            fn_company.init();
            $.unblockUI();
    }  
    function validapantalla(usuario){if(usuario == ""  || usuario == null){location.href= "login.php";}}                   
    var fn_company = {
            domroot:"#our_company",
            form : "#company_information",
            filtro : "",
            pagina_actual : "",
            sort : "ASC",
            orden : "iConsecutivo",
            init : function(){
                
                $('.num').keydown(fn_solotrucking.inputnumero()); 
                //INICIALIZA COLOR PICKER :
                $('.color_picker').minicolors({
                        control: $(this).attr('data-control') || 'hue',
                        defaultValue: $(this).attr('data-defaultValue') || '',
                        format: $(this).attr('data-format') || 'rgb',
                        keywords: $(this).attr('data-keywords') || '',
                        inline: $(this).attr('data-inline') === 'true',
                        letterCase: $(this).attr('data-letterCase') || 'lowercase',
                        opacity: $(this).attr('data-opacity'),
                        position: $(this).attr('data-position') || 'top right',
                        change: function(hex, opacity) {
                            var log;
                            try {
                                log = hex ? hex : 'transparent';
                                if( opacity ) log += ', ' + opacity;
                                console.log(log);
                            } catch(e) {}
                        },
                        theme: 'default'
                });
                
                //llenando select de estados:     
                $.post("catalogos_generales.php", { accion: "get_country", country: "USA"},
                function(data){ $(fn_company.form+" #sCveEntidad").empty().append(data.tabla).removeAttr('disabled').removeClass('readonly');},"json");
                
                fn_company.edit(); 
            },
            edit : function (){
                    $.post("funciones_ourcompany.php",{accion:"get_data", domroot : "company_information"},
                    function(data){
                        if(data.error == '0'){
                           $('#company_information :text, #company_information select').val('').removeClass('error'); 
                           eval(data.fields);
                        }else{fn_solotrucking.mensaje(data.msj);}       
                    },"json"); 
            },
            save : function (){
               //Validate Fields:
               var valid = true;
               var message = "";
               $(fn_company.form+" .required-field").removeClass("error");
               $(fn_company.form+" .required-field").each(function(){
                   if($(this).val() == ""){
                       valid   = false;
                       message = '<li> You must write all required fields.</li>';
                       $(this).addClass('error');
                   }
               });
               
               if(valid){
                 if($(fn_company.form+' input[name=iConsecutivo]').val() != ''){struct_data_post.edit_mode = "true";}else{struct_data_post.edit_mode = "false";}  
                    struct_data_post.action  = "save_data";
                    struct_data_post.domroot = "#company_information"; 
                    $.post("funciones_ourcompany.php",struct_data_post.parse(),
                    function(data){
                        switch(data.error){
                         case '0':
                            fn_solotrucking.mensaje(data.msj);
                            fn_company.edit();
                         break;
                         case '1': fn_solotrucking.mensaje(data.msj); break;
                        }
                    },"json");
               }else{
                   fn_solotrucking.mensaje('<p>Please check the following:</p><ul style="padding: 10px;">'+message+'</ul>');
               }
                
            },
    }     
    </script> 
<div id="layer_content" class="main-section">
    <div id="our_company" class="container">
        <div class="page-title">
            <h1>Administrator</h1>
            <h2>Our Company</h2>
        </div>
        <div id="company_information">
            <form class="fedicion">
                <table style="width:100%;">
                    <tr>
                        <td style="vertical-align: top;padding: 0px 10px;">
                        <fieldset>
                        <legend style="padding-bottom: 10px;margin-bottom:5px;"><span style="font-size: 14px;font-weight: 700;text-transform: uppercase;">General Data</span> - <span class="mensaje_valido" style="font-size: 13px;">&nbsp;The fields containing an (<span style="color:#ff0000;">*</span>) are required.</span></legend>
                        <input id="iConsecutivo" name="iConsecutivo" type="hidden"> 
                        <table style="width:100%;margin:5px auto;" cellpadding="0" cellspacing="0">
                            <tr>
                                <td colspan="100%">
                                <div class="field_item">
                                    <label>Company Name <span style="color:#ff0000;">*</span>:</label> 
                                    <input id="sNombreCompleto" tabindex="1" class="txt-uppercase required-field" name="sNombreCompleto" type="text" placeholder="" maxlength="255">
                                </div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="100%">
                                <div class="field_item">
                                    <label>Alias <span style="color:#ff0000;">*</span>:</label> 
                                    <input id="sAlias" tabindex="2" class="txt-uppercase required-field" name="sAlias" type="text" placeholder="" maxlength="50">
                                </div>
                                </td>
                            </tr>
                            <tr>
                                <td style="width:75%;">
                                <div class="field_item">
                                    <label>Street Address <span style="color:#ff0000;">*</span>:</label> 
                                    <input id="sCalle" class="txt-uppercase required-field" tabindex="3" name="sCalle" type="text" placeholder="" maxlength="50" style="width: 97%;">
                                </div>
                                </td>
                                <td style="width:25%;">
                                <div class="field_item">
                                    <label>Suite # <span style="color:#ff0000;">*</span>:</label> 
                                    <input id="sNumExterior" class="txt-uppercase required-field" tabindex="4" name="sNumExterior" type="text" placeholder="" maxlength="50">
                                </div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="100%">
                                    <table style="width: 100%;" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td style="width:33%;">
                                            <div class="field_item">
                                                <label>City <span style="color:#ff0000;">*</span>:</label> 
                                                <input id="sCiudad" class="txt-uppercase required-field" tabindex="5" name="sCiudad" type="text" placeholder="" maxlength="120" style="width: 93%;">
                                            </div>
                                            </td>
                                            <td style="width:33%;">
                                            <div class="field_item">
                                                <label>State <span style="color:#ff0000;">*</span>:</label> 
                                                <select id="sCveEntidad" tabindex="6" class="required-field" name="sCveEntidad" style="height: 25px!important;width: 98%!important;"></select> 
                                            </div>
                                            </td>
                                            <td style="width:33%;">
                                            <div class="field_item">
                                                <label>Zip Code <span style="color:#ff0000;">*</span>:</label> 
                                                <input id="sCodigoPostal" class="txt-uppercase required-field" tabindex="7" name="sCodigoPostal" type="text" placeholder="" maxlength="10" >
                                            </div>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                        </fieldset>  
                        </td>
                        <td style="vertical-align: top;padding: 0px 10px;">
                        <fieldset>
                        <legend style="padding-bottom: 10px;margin-bottom:5px;"><span style="font-size: 14px;font-weight: 700;text-transform: uppercase;">CONFIGURATION TO ELECTRONIC INVOICES</span> - <span class="mensaje_valido" style="font-size: 13px;">&nbsp;The fields containing an (<span style="color:#ff0000;">*</span>) are required.</span></legend>  
                        <table style="width:100%;margin:5px auto;" cellpadding="0" cellspacing="0">
                            <tr>
                                <td colspan="100%">
                                <div class="field_item">
                                    <label>Include Alias or Company Name? <span style="color:#ff0000;">*</span>:</label> 
                                    <select id="eIncluirNombreAlias" tabindex="8" class="required-field" name="eIncluirNombreAlias" style="height:25px!important;">
                                        <option value="NONE">None</option>
                                        <option value="COMP">Company Name</option>   
                                        <option value="ALIAS">Alias</option>
                                    </select>
                                </div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="100%">
                                <div class="field_item">
                                    <label>Include Address? <span style="color:#ff0000;">*</span>:</label> 
                                    <select id="eLugarExpedicion" tabindex="9" class="required-field" name="eLugarExpedicion" style="height:25px!important;"><option value="NO">No</option><option value="SI">Yes</option></select>
                                </div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="100%">
                                <div class="field_item">
                                    <label>Include exchange rate in invoices with currency other than USD?:</label> 
                                    <select id="eIncluirTipoCambio" tabindex="10" name="eIncluirTipoCambio" style="height:25px!important;"><option value="NO">No</option><option value="SI">Yes</option></select>
                                </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                <div class="field_item">
                                    <label>Border color of the invoice:</label><br> 
                                    <input id="sColorPdf" tabindex="11" readonly="readonly" type="text" class="color_picker" data-format="rgb" data-position="top right" style="height:20px!important;">
                                </div>
                                </td>
                                <td style="width: 75%;">
                                <div class="field_item">
                                    <label>E-mail & Password to send invoices:</label><br> 
                                    <input id="sCorreoEmpresa" tabindex="12" type="text" class="txt-lowercase" style="width: 56%;float: left;clear: none;">
                                    <input id="sCorreoContrasena" tabindex="13" type="password" maxlength="8" title="max length 8 characters." style="float: right;width: 40%;clear: none;">  
                                </div>
                                </td>
                            </tr>
                        </table>
                        </fieldset> 
                        </td>
                    </tr>
                </table>               
                <button type="button" class="btn-1" onclick="fn_company.save();">Save</button> 
            </form>
        </div>
    </div>
</div>
<!-- FOOTER -->
<?php include("footer.php"); ?> 
</body>
</html>
<?php } ?>