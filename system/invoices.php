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
            var usuario_actual = <?php echo json_encode($_SESSION['usuario_actual']);?>        
            var tipo_usuario = <?php echo json_encode($_SESSION['acceso']);?> 
            validapantalla(usuario_actual);  
            $("#aUpdateAccount").click(function() { actualizarCliente(usuario_actual); });
            fn_invoices.init();
            $.unblockUI();
            
            $('#dialog-confirm').dialog({
                modal: true,
                autoOpen: false,
                width : 550,
                height : 200,
                resizable : false,
                dialogClass: "without-close-button",
                buttons : {
                    'CONFIRM' : function() {
                        var clave = $('#dialog-confirm input[name=iConsecutivo]').val();
                        //fn_companies.borrar(clave);
                    },
                    'CANCEL' : function(){$(this).dialog('close');}
                }
            });
        
    }  
    function validapantalla(usuario){if(usuario == ""  || usuario == null){location.href= "login.php";}}                   
    var fn_invoices = {
            domroot:"#ct_invoices",
            data_grid: "#data_grid_invoices",
            form : "#invoice_data",
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
               $(fn_invoices.form+' #sNoReferencia').removeProp('readonly').removeClass("readonly");
               fn_solotrucking.get_date("#dFechaInvoice");
               fn_popups.resaltar_ventana('edit_form_invoice');  
            },
            edit : function (){
                $(fn_invoices.data_grid + " tbody td .edit").bind("click",function(){
                    var clave = $(this).parent().parent().find("td:eq(0)").prop('id');
                        clave = clave.split("_");
                        clave = clave[1];
                    
                    $('#edit_form_invoice .p-header h2').empty().append('INVOICES - EDIT INVOICE #' + clave); 
                    $.post("funciones_invoices.php",
                    {accion:"get_data", clave: clave, domroot : fn_invoices.form},
                    function(data){
                        if(data.error == '0'){
                           $('#edit_form_invoice :text, #edit_form_invoice select').val('').removeClass('error'); 
                           $(fn_invoices.form+' #sNoReferencia').prop('readonly','readonly').addClass("readonly");
                           eval(data.fields);
                           fn_popups.resaltar_ventana('edit_form_invoice');
                        }
                        else{fn_solotrucking.mensaje(data.msj);  }       
                    },"json"); 
              });  
            },
            save : function (){
    
               //Validate Fields:
               var valid = true;
               var message = "";
               $(fn_invoices.form+" .required-field").removeClass("error");
               $(fn_invoices.form+" .required-field").each(function(){
                   if($(this).val() == ""){
                       valid   = false;
                       message = '<li> You must write all required fields.</li>';
                       $(this).addClass('error');
                   }
               });
               
               if(valid){
                 if($(fn_invoices.form+' input[name=iConsecutivo]').val() != ''){struct_data_post.edit_mode = "true";}else{struct_data_post.edit_mode = "false";}  
                    struct_data_post.action  = "save_data";
                    struct_data_post.domroot = "#invoice_data"; 
                    $.post("funciones_invoices.php",struct_data_post.parse(),
                    function(data){
                        switch(data.error){
                         case '0':
                            fn_solotrucking.mensaje(data.msj);
                            fn_invoices.edit();
                         break;
                         case '1': fn_solotrucking.mensaje(data.msj); break;
                        }
                    },"json");
               }else{
                   fn_solotrucking.mensaje('<p>Please check the following:</p><ul style="padding: 10px;">'+message+'</ul>');
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
                    <td style='width:120px;'><input class="flt_id" class="numeros" type="text" placeholder="No. Reference:"></td>
                    <td><input class="flt_name" type="text" placeholder="Company:"></td> 
                    <td><input class="flt_date" type="text" placeholder="MM/DD/YY:"></td>
                    <td style='width: 160px;'>
                        <select class="flt_financing">
                            <option value="">Select an option...</option>
                            <option value="0">No</option> 
                            <option value="1">Yes</option> 
                        </select>
                    </td>
                    <td><input class="flt_amount" type="text" placeholder="Payment Amount:"></td> 
                    <td style='width:80px;'>
                        <div class="btn-icon-2 btn-left" title="Search" onclick="fn_invoices.filtraInformacion();"><i class="fa fa-search"></i></div>
                        <div class="btn-icon-2 btn-left" title="Add +"  onclick="fn_invoices.add();"><i class="fa fa-plus"></i></div>
                    </td> 
                </tr>
                <tr id="grid-head2">
                    <td class="etiqueta_grid down" onclick="fn_invoices.ordenamiento('sNoReferencia',this.cellIndex);">Reference</td>
                    <td class="etiqueta_grid"      onclick="fn_invoices.ordenamiento('sNombreCompania',this.cellIndex);">Company</td>
                    <td class="etiqueta_grid"      onclick="fn_invoices.ordenamiento('A.dFechaInvoice',this.cellIndex);">Date</td> 
                    <td class="etiqueta_grid"      onclick="fn_invoices.ordenamiento('iFinanciamiento',this.cellIndex);">Is Financing</td>
                    <td class="etiqueta_grid"      onclick="fn_invoices.ordenamiento('dTotal',this.cellIndex);">Payment Amount</td>
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
    <!-- FORMULARIOS -->
    <div id="edit_form_invoice" class="popup-form" style="height: 97%;">
        <div class="p-header">
            <h2>INVOICES - (EDIT OR ADD)</h2>
            <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('edit_form_invoice');"><i class="fa fa-times"></i></div>
        </div>
        <div class="p-container" style="height: 94%;overflow-y: auto;">
        <div>
            <form id="invoice_data">
                <fieldset style="padding-bottom: 5px;">
                <legend>General Data</legend>
                <p class="mensaje_valido">&nbsp;The fields containing an (<span style="color:#ff0000;">*</span>) are required.</p>
                <input id="iConsecutivo" name="iConsecutivo" type="hidden" value=""> 
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 60%;">
                        <div class="field_item"> 
                            <label for="sNoReferencia">No. Reference: <span style="color:#ff0000;">*</span>:</label> 
                            <input tabindex="1" id="sNoReferencia" type="text" class="txt-uppercase required-field" style="width: 97%;">
                        </div>
                        </td>
                        <td>
                        <div class="field_item"> 
                            <label>Invoice Date: <span style="color:#ff0000;">*</span>:</label> 
                            <input tabindex="2" id="dFechaInvoice" type="text" class="fecha required-field" style="width: 85%;position: relative;margin-left:15px;">
                        </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%">
                        <div class="field_item">
                            <label>Company: <span style="color:#ff0000;">*</span>:</label> 
                            <select tabindex="3" id="iConsecutivoCompania" class="required-field"><option value="">Select an option...</option></select>
                        </div>
                        </td> 
                    </tr>
                    <tr>
                        <td colspan="100%">
                        <div class="field_item">
                            <label>Comments:</label> 
                            <textarea tabindex="4" id="sComentarios" style="height:50px!important;"></textarea>
                        </div>
                        </td> 
                    </tr>
                    <!--
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
                    </tr> -->
                </table>
                </fieldset>
                <!-- BEGIN ENDORSEMENT INVOICE  
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
                </fieldset>--> 
                <div id="invoice_detalle" style="width: 99%;margin: 0 auto 25px;">
                    <h5 class="data-grid-header">Products & Services Summary</h5>
                    <table class="popup-datagrid" style="width: 100%;">
                        <thead>
                            <tr id="grid-head2"> 
                                <td class="etiqueta_grid">No.</td>
                                <td class="etiqueta_grid">Description</td> 
                                <td class="etiqueta_grid">Qty.</td>
                                <td class="etiqueta_grid">Unit price</td>
                                <td class="etiqueta_grid">TAX %</td>
                                <td class="etiqueta_grid" style="width: 150px;">Total price</td>
                                <td></td>
                            </tr>
                        </thead>
                        <tbody><tr><td style="text-align:center; font-weight: bold;" colspan="100%">No data available.</td></tr></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4"></td>
                                <td><div class="data-grid-totales"><label>Subtotal: </label></div></td>
                                <td><div class="data-grid-totales"><input id="dSubtotal" type="text" readonly="readonly" value=""></div></td>  
                            </tr>
                            <tr>
                                <td colspan="4"></td>
                                <td><div class="data-grid-totales"><label>Advance payment: </label></div></td>
                                <td><div class="data-grid-totales"><input id="dAnticipo" type="text" readonly="readonly" value="" onblur="fn_invoices.actualizaTotales();"></div></td>  
                            </tr>
                            <tr>
                                <td colspan="4"></td>
                                <td><div class="data-grid-totales"><label>Tax: </label></div></td>
                                <td><div class="data-grid-totales"><input id="dTax" type="text" readonly="readonly" value=""></div></td>  
                            </tr>
                            <tr>
                                <td colspan="4"></td>
                                <td><div class="data-grid-totales"><label>Total: </label></div></td>
                                <td><div class="data-grid-totales"><input type="text" id="dTotal" readonly="readonly" value=""></div></td>  
                            </tr>
                            <tr>
                                <td colspan="4"></td>
                                <td><div class="data-grid-totales"><label>Balance: </label></div></td>
                                <td><div class="data-grid-totales"><input id="dBalance" type="text" readonly="readonly" value=""></div></td>  
                            </tr>
                        </tfoot>
                    </table>   
                </div>
            </form>
            <button type="button" class="btn-1" onclick="fn_invoices.save();">SAVE</button>  
            <button type="button" class="btn-1" onclick="" style="margin-right:10px;background:#5ec2d4;">Preview</button>         
            <button type="button" class="btn-1" onclick="fn_popups.cerrar_ventana('edit_form_invoice');" style="margin-right:10px;background:#e8051b;">Cancel</button> 
        </div>
        </div>
    </div>
    <!-- DIALOGUES -->
    <div id="dialog-confirm" title="Delete">
      <p><span class="ui-icon ui-icon-alert" ></span>These items will be permanently deleted and cannot be recovered. Are you sure?</p>
    </div>
    <!-- FOOTER -->
    <?php include("footer.php"); ?> 
    </body>
</html>
<?php } ?>
