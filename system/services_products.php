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
                var tipo_usuario   = <?php echo json_encode($_SESSION['acceso']);?> 
                validapantalla(usuario_actual);  
                $("#aUpdateAccount").click(function() { actualizarCliente(usuario_actual); });
                fn_services.init();
                $.unblockUI();
                
                /*---- Dialogos ---*/
                $('#dialog_delete').dialog({
                    modal: true,
                    autoOpen: false,
                    width : 550,
                    height : 200,
                    resizable : false,
                    dialogClass: "without-close-button",
                    buttons : {
                        'CONFIRM' : function() {
                            var clave = $('#dialog_delete input[name=iConsecutivo]').val();
                            fn_services.borrar(clave);
                        },
                        'CANCEL' : function(){$(this).dialog('close');}
                    }
                });
            
        }  
        function validapantalla(usuario){if(usuario == ""  || usuario == null){location.href= "login.php";}}  
        var fn_services = {
            domroot:"#cy_services",
            data_grid: "#data_grid_services",
            filtro : "",
            pagina_actual : "",
            sort : "ASC",
            orden : "sClave",
            init : function(){
                fn_services.fillgrid();

                $('.num').keydown(fn_solotrucking.inputnumero); 
                $('.decimals').keydown(fn_solotrucking.inputdecimals);
                //Filtrado con la tecla enter
                $(fn_services.data_grid + ' #grid-head1 input').keyup(function(event){
                    if (event.keyCode == '13') {
                        event.preventDefault();
                        fn_services.filtraInformacion();
                    }
                    if(event.keyCode == '27'){
                       event.preventDefault();
                       $(this).val(''); 
                       fn_services.filtraInformacion();
                    }
                });  
            },
            fillgrid: function(){
                $.ajax({             
                    type:"POST", 
                    url:"funciones_services.php", 
                    data:{
                        accion:"get_list",
                        registros_por_pagina : "15", 
                        pagina_actual : fn_services.pagina_actual, 
                        filtroInformacion : fn_services.filtro,  
                        ordenInformacion : fn_services.orden,
                        sortInformacion : fn_services.sort,
                    },
                    async : true,
                    dataType : "json",
                    success : function(data){                               
                        $(fn_services.data_grid+" tbody").empty().append(data.tabla);
                        $(fn_services.data_grid+" tbody tr:even").addClass('gray');
                        $(fn_services.data_grid+" tbody tr:odd").addClass('white');
                        $(fn_services.data_grid + " tfoot #paginas_total").val(data.total);
                        $(fn_services.data_grid + " tfoot #pagina_actual").val(data.pagina);
                        fn_services.pagina_actual = data.pagina; 
                        fn_services.edit();
                        fn_services.confirmar_borrar(); 
                    }
                }); 
            },
            add : function(){
               $('#frm_edit_service input, #frm_edit_service select, #frm_edit_service textarea').val('').removeClass('error');
               $('#frm_edit_service select[name=iCategoriaPS]').val('SERVICE'); 
               $('#frm_edit_service select[name=sCveUnidadMedida]').val('N/A');
               $('#frm_edit_service input[name=iPctImpuesto]').val(0); 
               $('#frm_edit_service #sClave').removeAttr('readonly');
               fn_popups.resaltar_ventana('frm_edit_service');  
            },
            edit : function (){
                $(fn_services.data_grid + " tbody td .edit").bind("click",function(){
                    var clave = $(this).parent().parent().find("td:eq(0)").prop('id');
                        clave = clave.split("_");
                        clave = clave[1];
                    
                    //fn_popups.resaltar_ventana("frm_edit_service");
                    $.post("funciones_services.php",
                    {
                        accion:"get_data", 
                        clave: clave, 
                        domroot : "frm_edit_service"
                    },
                    function(data){
                        if(data.error == '0'){
                           $('#frm_edit_service :text, #frm_edit_service select').val('').removeClass('error'); 
                           eval(data.fields);
                           $('#frm_edit_service #sClave').attr('readonly','readonly'); 
                           fn_popups.resaltar_ventana('frm_edit_service');
                             
                        }else{
                           fn_solotrucking.mensaje(data.msj);  
                        }       
                    },"json"); 
              });  
            },
            save : function (){
               
               //Validate Fields:
               var valid   = true;
               var message = "";
               
               $("#frm_edit_service .required-field").each(function(){
                   if($(this).val() == ""){
                       valid   = false;
                       message = '<li> You must write all required fields.</li>';
                       $(this).addClass('error');
                   }
               });
               
               
               if(valid){
                 if($('#frm_edit_service #iConsecutivo').val() != ''){struct_data_post.edit_mode = "true";}else{struct_data_post.edit_mode = "false";}  
                 struct_data_post.action ="save_data";
                 struct_data_post.domroot= "#frm_edit_service"; 
                 
                 $.post("funciones_services.php",struct_data_post.parse(),
                    function(data){
                        switch(data.error){
                         case '0':
                            fn_solotrucking.mensaje(data.msj);
                            fn_services.fillgrid();
                            fn_popups.cerrar_ventana('frm_edit_service');
                         break;
                         case '1': fn_solotrucking.mensaje(data.msj); break;
                        }
                    },"json"); 
               }else{
                   fn_solotrucking.mensaje('<p>Please check the following:</p><ul style="padding: 10px;">'+message+'</ul>');
               }
                
            },
            firstPage : function(){
                if($(fn_services.data_grid+" #pagina_actual").val() != "1"){
                    fn_services.pagina_actual = "";
                    fn_services.fillgrid();
                }
            },
            previousPage : function(){
                if($(fn_services.data_grid+" #pagina_actual").val() != "1"){
                    fn_services.pagina_actual = (parseInt($(fn_services.data_grid+" #pagina_actual").val()) - 1) + "";
                    fn_services.fillgrid();
                }
            },
            nextPage : function(){
                if($(fn_services.data_grid+" #pagina_actual").val() != $(fn_services.data_grid+" #paginas_total").val()){
                    fn_services.pagina_actual = (parseInt($(fn_services.data_grid+" #pagina_actual").val()) + 1) + "";
                    fn_services.fillgrid();
                }
            },
            lastPage : function(){
                if($(fn_services.data_grid+" #pagina_actual").val() != $(fn_services.data_grid+" #paginas_total").val()){
                    fn_services.pagina_actual = $(fn_services.data_grid+" #paginas_total").val();
                    fn_services.fillgrid();
                }
            }, 
            ordenamiento : function(campo,objeto){
                $(fn_services.data_grid + " #grid-head2 td").removeClass('down').removeClass('up');

                if(campo == fn_services.orden){
                    if(fn_services.sort == "ASC"){
                        fn_services.sort = "DESC";
                        $(fn_services.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('up');
                    }else{
                        fn_services.sort = "ASC";
                        $(fn_services.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
                    }
                }else{
                    fn_services.sort = "ASC";
                    fn_services.orden = campo;
                    $(fn_services.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
                }
                fn_services.fillgrid();

                return false;
            }, 
            filtraInformacion : function(){
                fn_services.pagina_actual = 0;
                fn_services.filtro = "";
                if($(fn_services.data_grid+" .flt_id").val() != ""){ fn_services.filtro += "sClave|"+$(fn_services.data_grid+" .flt_id").val()+","}
                if($(fn_services.data_grid+" .flt_name").val() != ""){ fn_services.filtro += "sDescripcion|"+$(fn_services.data_grid+" .flt_name").val()+","} 
                if($(fn_services.data_grid+" .flt_categoria").val() != ""){ fn_services.filtro += "iCategoriaPS|"+$(fn_services.data_grid+" .flt_categoria").val()+","} 
                if($(fn_services.data_grid+" .flt_unit").val() != ""){ fn_services.filtro += "sCveUnidadMedida|"+$(fn_services.data_grid+" .flt_unit").val()+","} 
                if($(fn_services.data_grid+" .flt_price").val() != ""){ fn_services.filtro += "iPrecioUnitario|"+$(fn_services.data_grid+" .flt_price").val()+","}  
                if($(fn_services.data_grid+" .flt_imp").val() != ""){ fn_services.filtro += "iPctImpuesto|"+$(fn_services.data_grid+" .flt_imp").val()+","}    
                fn_services.fillgrid();
            }, 
            confirmar_borrar : function(){
                $(fn_services.data_grid + " tbody td .btn_delete").bind("click",function(){
                    var clave = $(this).parent().parent().find("td:eq(0)").prop('id');
                        clave = clave.split("_");
                        clave = clave[1];
                    $('#dialog_delete p span').empty().append($(this).parent().parent().find("td:eq(1)").html());
                    $('#dialog_delete input[name=iConsecutivo]').val(clave);
                    $('#dialog_delete').dialog("open");
                });  
            },
            borrar : function(iConsecutivo){
                if(iConsecutivo != ""){
                    $.ajax({             
                        type:"POST", 
                        url:"funciones_services.php", 
                        data:{"accion":"delete_record","iConsecutivo" : iConsecutivo},
                        async : true,
                        dataType : "json",
                        success : function(data){                               
                            fn_solotrucking.mensaje(data.msj);
                            switch(data.error){
                                case '0': fn_services.fillgrid(); $("#dialog_delete").dialog('close'); break; 
                            }
                        }
                    }); 
                }
            } 
    }    
    </script>
    <div id="layer_content" class="main-section">
        <div id="cy_services" class="container">
            <div class="page-title">
                <h1>lists</h1>
                <h2>PRODUCTS & SERVICES </h2>
            </div>
            <table id="data_grid_services" class="data_grid">
            <thead>
                <tr id="grid-head1">
                    <td style='width:45px;'>
                        <input class="flt_id" class="txt-uppercase" type="text" placeholder="ID:"></td>
                    <td><input class="flt_name" type="text" placeholder="Description:"></td>
                    <td style='width:80px;'>
                        <select class="flt_categoria">
                            <option value="" selected>All</option> 
                            <option value="SERVICE">Service</option>
                            <option value="PRODUCT">Product</option>
                        </select>
                    </td> 
                    <td><input class="flt_unit" type="text" placeholder="Unit Type:"></td> 
                    <td><input class="flt_price" type="text" placeholder="Unit Price:"></td>
                    <td><input class="flt_imp" type="text" placeholder="% TAX:"></td>
                    <td style='width:80px;'>
                        <div class="btn-icon-2 btn-left" title="Search" onclick="fn_services.filtraInformacion();"><i class="fa fa-search"></i></div>
                        <div class="btn-icon-2 btn-left" title="Add +"  onclick="fn_services.add();"><i class="fa fa-plus"></i></div>
                    </td> 
                </tr>
                <tr id="grid-head2">
                    <td class="etiqueta_grid down" onclick="fn_services.ordenamiento('sClave',this.cellIndex);">ID</td>
                    <td class="etiqueta_grid"      onclick="fn_services.ordenamiento('sDescripcion',this.cellIndex);">Descripcion</td>
                    <td class="etiqueta_grid"      onclick="fn_services.ordenamiento('iCategoriaPS',this.cellIndex);">Category</td>
                    <td class="etiqueta_grid"      onclick="fn_services.ordenamiento('sCveUnidadMedida',this.cellIndex);">Unit Type</td>
                    <td class="etiqueta_grid"      onclick="fn_services.ordenamiento('iPrecioUnitario',this.cellIndex);">Unit price</td>
                    <td class="etiqueta_grid"      onclick="fn_services.ordenamiento('iPctImpuesto',this.cellIndex);">% TAX</td>
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
                            <button id="pgn-inicio"    onclick="fn_services.firstPage();" title="First page"><span></span></button>
                            <button id="pgn-anterior"  onclick="fn_services.previousPage();" title="Previous"><span></span></button>
                            <button id="pgn-siguiente" onclick="fn_services.nextPage();" title="Next"><span></span></button>
                            <button id="pgn-final"     onclick="fn_services.lastPage();" title="Last Page"><span></span></button>
                        </div>
                    </td>
                </tr>
            </tfoot>
            </table>
            
        </div>
    </div>
    <!-- FORMULARIOS -->
    <div id="frm_edit_service" class="popup-form">
        <div class="p-header">
            <h2>EDIT OR ADD SERVICE / PRODUCT</h2>
            <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('frm_edit_service');fn_services.filtraInformacion();"><i class="fa fa-times"></i></div>
        </div>
        <div class="p-container">
        <div id="company_information">
            <form>
                <fieldset>
                    <legend>General Information</legend>
                    <p class="mensaje_valido">&nbsp;The fields containing an (*) are required.</p> 
                    <table style="width:100%;" cellpadding="0" cellspacing="0">
                        <tr>
                            <td style="width:25%;padding: 5px;">
                                <div class="field_item">
                                    <input id="iConsecutivo" name="iConsecutivo" type="hidden">
                                    <label>ID <span style="color:#ff0000;" title="Unique ID to refer the product or service">*</span>:</label> 
                                    <input tabindex="1" class="txt-uppercase required-field"  id="sClave" name="sClave" type="text" placeholder="" maxlength="20">
                                </div>
                            </td>
                            <td style="padding: 5px;">
                                <div class="field_item">
                                    <label>Description <span style="color:#ff0000;" title="Name of the product/service">*</span>:</label> 
                                    <input tabindex="2" class="txt-uppercase required-field"  id="sDescripcion" name="sDescripcion" type="text" placeholder="" maxlength="200">
                                </div>
                            </td>
                        </tr>
                    </table>
                    <table style="width:100%;" cellpadding="0" cellspacing="0">
                        <tr>
                            <td style="width:50%;padding: 5px;">
                                <div class="field_item">
                                    <label>Unit Price <span style="color:#ff0000;">*</span>:</label> 
                                    <input tabindex="3" class="decimals required-field"  id="iPrecioUnitario" name="iPrecioUnitario" type="text" placeholder="$ -----">
                                </div>
                            </td>
                            <td style="padding: 5px;">
                                <div class="field_item">
                                    <label>TAX:</label> 
                                    <input tabindex="4" class="decimals"  id="iPctImpuesto" name="iPctImpuesto" type="text" placeholder="----- %">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="width:50%;padding: 5px;">
                                <div class="field_item">
                                    <label>Type:</label> 
                                    <select tabindex="5" id="iCategoriaPS" name="iCategoriaPS" style="height: 27px!important;">    
                                        <option value="SERVICE" selected="selected">Service</option>
                                        <option value="PRODUCT">Product</option>
                                    </select>
                                </div>
                            </td>
                            <td style="padding: 5px;">
                                <div class="field_item">
                                    <label>Unit Type:</label> 
                                    <select tabindex="6" id="sCveUnidadMedida" name="sCveUnidadMedida" style="height: 27px!important;">
                                        <option value="N/A" selected="selected">Not Apply</option>
                                    </select>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="padding: 5px;">
                                <div class="field_item">
                                    <label>Comments:</label> 
                                    <textarea tabindex="7" id="sComentarios" name="sComentarios" rows="5" maxlength="255" style="resize:none;"></textarea>
                                </div>
                            </td>
                        </tr>
                    </table>
                    <button tabindex="8" type="button" class="btn-1" onclick="fn_services.save();">Save</button> 
                    <button type="button" class="btn-1" onclick="fn_popups.cerrar_ventana('frm_edit_service');fn_services.filtraInformacion();" style="margin-right:10px;background:#e8051b;">CLOSE</button>
                </fieldset>
            </form>
        </div>
        </div>
    </div>
    <!-- DIALOGOS -->
    <div id="dialog_delete" title="SYSTEM MESSAGE" style="display:none;">
        <p>Are you sure you want to delete this record? <br><span style="color: #12b8d6;"></span></p>
        <form><div><input type="hidden" name="iConsecutivo" /></div></form>   
    </div> 
    <!-- FOOTER -->
    <?php include("footer.php"); ?> 
    </body>
</html>
<?php } ?>