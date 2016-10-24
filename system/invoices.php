<?php session_start();    
if ( !($_SESSION["acceso"] != '2'  && $_SESSION["usuario_actual"] != "" && $_SESSION["usuario_actual"] != NULL  )  ){ //No ha iniciado session, redirecciona a la pagina de login
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
        $("#aUpdateAccount").click(function() { actualizarCliente(usuario_actual); });
        fn_invoices.init();
        $.unblockUI();
    
}  
function validapantalla(usuario){if(usuario == ""  || usuario == null){location.href= "login.php";}}                   
var fn_invoices = {
        domroot:"#ct_invoices",
        data_grid: "#data_grid_invoices",
        filtro : "",
        pagina_actual : "",
        sort : "ASC",
        orden : "A.iConsecutivo",
        init : function(){
            fn_invoices.fillgrid();
            $('.num').keydown(fn_solotrucking.inputnumero);
            $('.inputdecimals').keydown(fn_solotrucking.inputdecimals); 
            //Filtrado con la tecla enter
            $(fn_invoices.data_grid + ' #grid-head1 input').keyup(function(event){
                if (event.keyCode == '13') {
                    event.preventDefault();
                    fn_invoices.filtraInformacion();
                }
                if(event.keyCode == '27'){
                   event.preventDefault();
                   $(this).val(''); 
                   fn_invoices.filtraInformacion();
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
            //Cargar Catalogos:
            $.ajax({             
                type:"POST", 
                url:"catalogos_generales.php", 
                data:{accion:"get_companies"},
                async : true,
                dataType : "json",
                success : function(data){                               
                    if(data.error == '0'){$("#iConsecutivoCompania").empty().append(data.select); }
                }
            });
            $.ajax({             
                type:"POST", 
                url:"catalogos_generales.php", 
                data:{accion:"get_services"},
                async : true,
                dataType : "json",
                success : function(data){                               
                    if(data.error == '0'){$("#iTipoInvoice").empty().append(data.select); }
                }
            });  
        },
        fillgrid: function(){
               $.ajax({             
                type:"POST", 
                url:"funciones_invoices.php", 
                data:{
                    accion:"get_invoices",
                    registros_por_pagina : "15", 
                    pagina_actual : fn_invoices.pagina_actual, 
                    filtroInformacion : fn_invoices.filtro,  
                    ordenInformacion : fn_invoices.orden,
                    sortInformacion : fn_invoices.sort,
                },
                async : true,
                dataType : "json",
                success : function(data){                               
                    $(fn_invoices.data_grid+" tbody").empty().append(data.tabla);
                    $(fn_invoices.data_grid+" tbody tr:even").addClass('gray');
                    $(fn_invoices.data_grid+" tbody tr:odd").addClass('white');
                    $(fn_invoices.data_grid + " tfoot #paginas_total").val(data.total);
                    $(fn_invoices.data_grid + " tfoot #pagina_actual").val(data.pagina);
                    fn_invoices.pagina_actual = data.pagina; 
                    fn_invoices.edit();
                }
            }); 
        },
        add : function(){
           $('#edit_form_invoice :text, #edit_form_invoice select').val('').removeClass('error');
           $('#edit_form_invoice .mensaje_valido').empty().append('The fields containing an (<span style="color:#ff0000;">*</span>) are required.');
           $('#edit_form_invoice .p-header h2').empty().append('INVOICES - NEW INVOICE');
           fn_solotrucking.get_date("#dFechaInvoice");
           $('#eStatus').val('UNPAID');
           fn_popups.resaltar_ventana('edit_form_invoice');  
        },
        edit : function (){
            $(fn_invoices.data_grid + " tbody td .edit").bind("click",function(){
                var clave = $(this).parent().parent().find("td:eq(0)").html();
                $('#edit_form_invoice .p-header h2').empty().append('INVOICES - EDIT INVOICE #' + clave); 
                //fn_popups.resaltar_ventana("edit_form_invoice");
                $.post("funciones_invoices.php",
                {
                    accion:"get_company", 
                    clave: clave, 
                    domroot : "edit_form_invoice"
                },
                function(data){
                    if(data.error == '0'){
                       $('#edit_form_invoice :text, #edit_form_invoice select').val('').removeClass('error'); 
                       eval(data.fields);
                       //$('#edit_form_invoice #companies_tabs').show(); 
                       fn_popups.resaltar_ventana('edit_form_invoice');
                         
                    }else{
                       fn_solotrucking.mensaje(data.msj);  
                    }       
                },"json"); 
          });  
        },
        save : function (){
           //Validate Fields:
           var sNombreCompania = $('#edit_form_invoice #sNombreCompania');
           var sUsdot = $('#edit_form_invoice #sUsdot');
           var valid = true;
           //field nombre
           valid = valid && fn_solotrucking.checkLength( sNombreCompania, "Company Name", 1, 255 );
           //valid = valid && fn_solotrucking.checkRegexp( sNombreCompania, /^[0-9a-zA-ZáéíóúàèìòùÀÈÌÒÙÁÉÍÓÚñÑüÜ_\s]+$/, "The field for the Name must contain only letters." );
           valid = valid && fn_solotrucking.checkLength( sUsdot, "Company Name", 1, 10 );
           
           if(valid){
             if($('#edit_form_invoice #iConsecutivo').val() != ''){struct_data_post.edit_mode = "true";}else{struct_data_post.edit_mode = "false";}  
             struct_data_post.action="save_company";
             struct_data_post.domroot= "#edit_form_invoice"; 
                $.post("funciones_invoices.php",struct_data_post.parse(),
                function(data){
                    switch(data.error){
                     case '0':
                        fn_solotrucking.mensaje(data.msj);
                        fn_invoices.fillgrid();
                        fn_popups.cerrar_ventana('edit_form_invoice');
                     break;
                     case '1': fn_solotrucking.mensaje(data.msj); break;
                    }
                },"json");
           }
            
        },
        firstPage : function(){
            if($(fn_invoices.data_grid+" #pagina_actual").val() != "1"){
                fn_invoices.pagina_actual = "";
                fn_invoices.fillgrid();
            }
        },
        previousPage : function(){
            if($(fn_invoices.data_grid+" #pagina_actual").val() != "1"){
                fn_invoices.pagina_actual = (parseInt($(fn_invoices.data_grid+" #pagina_actual").val()) - 1) + "";
                fn_invoices.fillgrid();
            }
        },
        nextPage : function(){
            if($(fn_invoices.data_grid+" #pagina_actual").val() != $(fn_invoices.data_grid+" #paginas_total").val()){
                fn_invoices.pagina_actual = (parseInt($(fn_invoices.data_grid+" #pagina_actual").val()) + 1) + "";
                fn_invoices.fillgrid();
            }
        },
        lastPage : function(){
            if($(fn_invoices.data_grid+" #pagina_actual").val() != $(fn_invoices.data_grid+" #paginas_total").val()){
                fn_invoices.pagina_actual = $(fn_invoices.data_grid+" #paginas_total").val();
                fn_invoices.fillgrid();
            }
        }, 
        ordenamiento : function(campo,objeto){
            $(fn_invoices.data_grid + " #grid-head2 td").removeClass('down').removeClass('up');

            if(campo == fn_invoices.orden){
                if(fn_invoices.sort == "ASC"){
                    fn_invoices.sort = "DESC";
                    $(fn_invoices.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('up');
                }else{
                    fn_invoices.sort = "ASC";
                    $(fn_invoices.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
                }
            }else{
                fn_invoices.sort = "ASC";
                fn_invoices.orden = campo;
                $(fn_invoices.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
            }
            fn_invoices.fillgrid();

            return false;
        }, 
        filtraInformacion : function(){
            fn_invoices.pagina_actual = 0;
            fn_invoices.filtro = "";
            if($(fn_invoices.data_grid+" .flt_id").val() != ""){ fn_invoices.filtro += "iConsecutivo|"+$(fn_invoices.data_grid+" .flt_id").val()+","}
            if($(fn_invoices.data_grid+" .flt_name").val() != ""){ fn_invoices.filtro += "sNombreCompania|"+$(fn_invoices.data_grid+" .flt_name").val()+","} 
            if($(fn_invoices.data_grid+" .flt_contacto").val() != ""){ fn_invoices.filtro += "sNombreContacto|"+$(fn_invoices.data_grid+" .flt_contacto").val()+","} 
            if($(fn_invoices.data_grid+" .flt_address").val() != ""){ fn_invoices.filtro += "sDireccion|"+$(fn_invoices.data_grid+" .flt_address").val()+","} 
            if($(fn_invoices.data_grid+" .flt_country").val() != ""){ fn_invoices.filtro += "estado|"+$(fn_invoices.data_grid+" .flt_country").val()+","} 
            if($(fn_invoices.data_grid+" .flt_zip").val() != ""){ fn_invoices.filtro += "sCodigoPostal|"+$(fn_invoices.data_grid+" .flt_zip").val()+","} 
            if($(fn_invoices.data_grid+" .flt_phone").val() != ""){ fn_invoices.filtro += "sTelefonoPrincipal|"+$(fn_invoices.data_grid+" .flt_phone").val()+","}  
            if($(fn_invoices.data_grid+" .flt_usdot").val() != ""){ fn_invoices.filtro += "sUsdot|"+$(fn_invoices.data_grid+" .flt_usdot").val()+","}    
            fn_invoices.fillgrid();
        },
        valid_type : function(service){
            if(service == '1'){ //ENDORSEMENTS:
              // 1 - Cargar opciones para invoice de endoso:
              fn_invoices.valid_financing($('#iFinanciamiento').val());
              $('#endoso_invoice').show();
                
            }else{
                
            }
        },
        valid_financing : function(financing){
            if(financing == '1'){
                $.ajax({             
                    type:"POST", 
                    url:"catalogos_generales.php", 
                    data:{accion:"get_financieras"},
                    async : true,
                    dataType : "json",
                    success : function(data){                               
                        if(data.error == '0'){$("#iConsecutivoFinanciera").empty().append(data.select); }
                    }
                });
                $('.financing_fields').show();  
            }
        }  
}     
</script> 
<div id="layer_content" class="main-section">
    <div id="ct_invoices" class="container">
        <div class="page-title">
            <h1>Invoices</h1>
            <h2>Invoice Requests</h2>
        </div>
        <table id="data_grid_invoices" class="data_grid">
        <thead>
            <tr id="grid-head1">
                <td style='width:45px;'><input class="flt_id" class="numeros" type="text" placeholder="ID:"></td>
                <td><input class="flt_client" type="text" placeholder="Client:"></td>
                <td><input class="flt_name" type="text" placeholder="Company:"></td> 
                <td><input class="flt_summary" type="text" placeholder="Summary:"></td> 
                <td><input class="flt_date" type="text" placeholder="MM/DD/YY:"></td>
                <td><input class="flt_amount" type="text" placeholder="Payment Amount:"></td> 
                <td><input class="flt_paymentfor" type="text" placeholder="Payment For:"></td>
                <td style='width: 160px;'>
                    <select class="flt_financing">
                        <option value="">Select an option...</option>
                        <option value="0">No</option> 
                        <option value="1">Yes</option> 
                    </select>
                </td>
                <td style='width:100px;'></td> 
                <td style='width:80px;'>
                    <div class="btn-icon-2 btn-left" title="Search" onclick="fn_invoices.filtraInformacion();"><i class="fa fa-search"></i></div>
                    <div class="btn-icon-2 btn-left" title="Add +"  onclick="fn_invoices.add();"><i class="fa fa-plus"></i></div>
                </td> 
            </tr>
            <tr id="grid-head2">
                <td class="etiqueta_grid down" onclick="fn_invoices.ordenamiento('iConsecutivo',this.cellIndex);">ID</td>
                <td class="etiqueta_grid"      onclick="fn_invoices.ordenamiento('sNombreContacto',this.cellIndex);">Client</td>
                <td class="etiqueta_grid"      onclick="fn_invoices.ordenamiento('sNombreCompania',this.cellIndex);">Company</td>
                <td class="etiqueta_grid"      onclick="fn_invoices.ordenamiento('eTipoInvoice',this.cellIndex);">Summary</td>
                <td class="etiqueta_grid"      onclick="fn_invoices.ordenamiento('A.dFechaIngreso',this.cellIndex);">Date</td>
                <td class="etiqueta_grid"      onclick="fn_invoices.ordenamiento('iAmount',this.cellIndex);">Payment Amount</td>
                <td class="etiqueta_grid"      onclick="fn_invoices.ordenamiento('sDescripcion',this.cellIndex);">Payment For</td> 
                <td class="etiqueta_grid"      onclick="fn_invoices.ordenamiento('iFinanciamiento',this.cellIndex);">Is Financing</td>
                <td class="etiqueta_grid"      onclick="fn_invoices.ordenamiento('eStatus',this.cellIndex);">Status</td> 
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
                        <button id="pgn-inicio"    onclick="fn_invoices.firstPage();" title="First page"><span></span></button>
                        <button id="pgn-anterior"  onclick="fn_invoices.previousPage();" title="Previous"><span></span></button>
                        <button id="pgn-siguiente" onclick="fn_invoices.nextPage();" title="Next"><span></span></button>
                        <button id="pgn-final"     onclick="fn_invoices.lastPage();" title="Last Page"><span></span></button>
                    </div>
                </td>
            </tr>
        </tfoot>
        </table>
        
    </div>
</div>
<!---- FORMULARIOS ------>
<div id="edit_form_invoice" class="popup-form">
    <div class="p-header">
        <h2>INVOICES - (EDIT OR ADD)</h2>
        <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('edit_form_invoice');"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container">
    <div id="company_information">
        <form>
            <fieldset>
            <p class="mensaje_valido">&nbsp;The fields containing an (<span style="color:#ff0000;">*</span>) are required.</p> 
            <table style="width: 100%;">
                <tr>
                    <td></td>
                    <td>
                    <div class="field_item"> 
                        <label style="margin-left:15px;">Invoice Date: <span style="color:#ff0000;">*</span>:</label> 
                        <input tabindex="1" id="dFechaInvoice" type="text" class="fecha" style="width: 80%;position: relative;margin-left:15px;">
                    </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="100%">
                    <div class="field_item">
                        <input id="iConsecutivo" name="iConsecutivo" type="hidden">
                        <label>Company: <span style="color:#ff0000;">*</span>:</label> 
                        <select tabindex="2" id="iConsecutivoCompania"><option value="">Select an option...</option></select>
                    </div>
                    </td> 
                </tr>
                <tr>
                    <td style="width:70%;"> 
                    <div class="field_item"> 
                        <label>Service or Product: <span style="color:#ff0000;">*</span>:</label> 
                        <select tabindex="3" id="iTipoInvoice" onblur="fn_invoices.valid_type(this.value);"><option value="">Select an option...</option></select>
                    </div>
                    </td> 
                    <td>
                    <div class="field_item"> 
                        <label>Number: <span style="color:#ff0000;">*</span>:</label> 
                        <input tabindex="4" id="sNumber" type="text" maxlength="6" class="num">
                    </div>
                    </td> 
                </tr>
                <tr>
                    <td style="width:70%;"></td> 
                    <td> 
                    <div class="field_item"> 
                        <label>Invoice Amount: <span style="color:#ff0000;">*</span>:</label> 
                        <input tabindex="5" id="iAmount" type="text" maxlength="10" class="inputdecimals" placeholder="$:">
                    </div>
                    </td>
                </tr>
            </table>
            </fieldset>
            <!------ BEGIN ENDORSEMENT INVOICE ----> 
            <fieldset id="endoso_invoice" style="display:none;">
                <table style="width: 100%;">
                <tr>
                    <td>
                    <div class="field_item"> 
                        <label>Is Financing: <span style="color:#ff0000;">*</span>:</label> 
                        <select tabindex="8" id="iFinanciamiento" onblur="fn_invoices.valid_financing(this.value);">
                            <option value="0">NO</option>
                            <option value="1">YES</option>  
                        </select> 
                    </div>
                    </td>
                    <td>
                      <div class="field_item financing_fields" style="display:none">
                            <label>Financing Company: <span style="color:#ff0000;">*</span>:</label> 
                            <select tabindex="2" id="iConsecutivoFinanciera"><option value="">Select an option...</option></select>
                      </div>
                    </td>
                </tr>
                <tr>
                    <td>
                    <div class="field_item financing_fields" style="display:none"> 
                        <label>Financing Months:</label>  
                        <input tabindex="9" id="sDiasFinanciamiento" name="sDiasFinanciamiento" type="text" class="num" maxlenght="2">
                    </div> 
                    </td>
                    <td>
                    <div class="field_item financing_fields" style="display:none"> 
                        <label>Amount of each pay:</label>  
                        <input tabindex="9" id="iFinancingAmount" name="iFinancingAmount" type="text" class="num" maxlenght="10" placeholder="$:">
                    </div>
                    </td>
                </tr>
                </table>
            </fieldset> 
            <!---- END ENDORSEMENTS INVOICE ---->           
            <button type="button" class="btn-1" onclick="fn_invoices.save();">SAVE</button> 

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

</body>

</html>
<?php } ?>
