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
        fn_companies.init();
        $.unblockUI();
    
}  
function validapantalla(usuario){if(usuario == ""  || usuario == null){location.href= "login.php";}}                   
var fn_companies = {
        domroot:"#ct_companies",
        data_grid: "#data_grid_companies",
        filtro : "",
        pagina_actual : "",
        sort : "ASC",
        orden : "iConsecutivo",
        init : function(){
            fn_companies.fillgrid();
            //llenando select de estados:     
            $.post("catalogos_generales.php", { 
                accion: "get_country", 
                country: "USA"
            },
            function(data){ 
                $("#edit_form #sEstado").empty().append(data.tabla).removeAttr('disabled').removeClass('readonly');
            },"json");
            $('.num').keydown(fn_solotrucking.inputnumero()); 
            //Filtrado con la tecla enter
            $(fn_companies.data_grid + ' #grid-head1 input').keyup(function(event){
                if (event.keyCode == '13') {
                    event.preventDefault();
                    fn_companies.filtraInformacion();
                }
                if(event.keyCode == '27'){
                   event.preventDefault();
                   $(this).val(''); 
                   fn_companies.filtraInformacion();
                }
            });  
        },
        fillgrid: function(){
               $.ajax({             
                type:"POST", 
                url:"funciones_companies.php", 
                data:{
                    accion:"get_companies",
                    registros_por_pagina : "15", 
                    pagina_actual : fn_companies.pagina_actual, 
                    filtroInformacion : fn_companies.filtro,  
                    ordenInformacion : fn_companies.orden,
                    sortInformacion : fn_companies.sort,
                },
                async : true,
                dataType : "json",
                success : function(data){                               
                    $(fn_companies.data_grid+" tbody").empty().append(data.tabla);
                    $(fn_companies.data_grid+" tbody tr:even").addClass('gray');
                    $(fn_companies.data_grid+" tbody tr:odd").addClass('white');
                    $(fn_companies.data_grid + " tfoot #paginas_total").val(data.total);
                    $(fn_companies.data_grid + " tfoot #pagina_actual").val(data.pagina);
                    fn_companies.pagina_actual = data.pagina; 
                    fn_companies.edit();
                }
            }); 
        },
        add : function(){
           $('#edit_form :text, #edit_form select').val('').removeClass('error');
           $('#edit_form .mensaje_valido').empty().append('The fields containing an (*) are required.');
           $('#edit_form #sUsdot').removeAttr('readonly');
           //$('#edit_form #companies_tabs').hide();
           fn_popups.resaltar_ventana('edit_form');  
        },
        edit : function (){
            $(fn_companies.data_grid + " tbody td .edit").bind("click",function(){
                var clave = $(this).parent().parent().find("td:eq(0)").html();
                
                //fn_popups.resaltar_ventana("edit_form");
                $.post("funciones_companies.php",
                {
                    accion:"get_company", 
                    clave: clave, 
                    domroot : "edit_form"
                },
                function(data){
                    if(data.error == '0'){
                       $('#edit_form :text, #edit_form select').val('').removeClass('error'); 
                       eval(data.fields);
                       $('#edit_form #sUsdot').attr('readonly','readonly'); 
                       //$('#edit_form #companies_tabs').show(); 
                       fn_popups.resaltar_ventana('edit_form');
                         
                    }else{
                       fn_solotrucking.mensaje(data.msj);  
                    }       
                },"json"); 
          });  
        },
        save : function (){
           //Validate Fields:
           var sNombreCompania = $('#edit_form #sNombreCompania');
           var sUsdot = $('#edit_form #sUsdot');
           var valid = true;
           //field nombre
           valid = valid && fn_solotrucking.checkLength( sNombreCompania, "Company Name", 1, 255 );
           //valid = valid && fn_solotrucking.checkRegexp( sNombreCompania, /^[0-9a-zA-ZáéíóúàèìòùÀÈÌÒÙÁÉÍÓÚñÑüÜ_\s]+$/, "The field for the Name must contain only letters." );
           valid = valid && fn_solotrucking.checkLength( sUsdot, "Company Name", 1, 10 );
           
           if(valid){
             if($('#edit_form #iConsecutivo').val() != ''){struct_data_post.edit_mode = "true";}else{struct_data_post.edit_mode = "false";}  
             struct_data_post.action="save_company";
             struct_data_post.domroot= "#edit_form"; 
                $.post("funciones_companies.php",struct_data_post.parse(),
                function(data){
                    switch(data.error){
                     case '0':
                        fn_solotrucking.mensaje(data.msj);
                        fn_companies.fillgrid();
                        fn_popups.cerrar_ventana('edit_form');
                     break;
                     case '1': fn_solotrucking.mensaje(data.msj); break;
                    }
                },"json");
           }
            
        },
        firstPage : function(){
            if($(fn_companies.data_grid+" #pagina_actual").val() != "1"){
                fn_companies.pagina_actual = "";
                fn_companies.fillgrid();
            }
        },
        previousPage : function(){
            if($(fn_companies.data_grid+" #pagina_actual").val() != "1"){
                fn_companies.pagina_actual = (parseInt($(fn_companies.data_grid+" #pagina_actual").val()) - 1) + "";
                fn_companies.fillgrid();
            }
        },
        nextPage : function(){
            if($(fn_companies.data_grid+" #pagina_actual").val() != $(fn_companies.data_grid+" #paginas_total").val()){
                fn_companies.pagina_actual = (parseInt($(fn_companies.data_grid+" #pagina_actual").val()) + 1) + "";
                fn_companies.fillgrid();
            }
        },
        lastPage : function(){
            if($(fn_companies.data_grid+" #pagina_actual").val() != $(fn_companies.data_grid+" #paginas_total").val()){
                fn_companies.pagina_actual = $(fn_companies.data_grid+" #paginas_total").val();
                fn_companies.fillgrid();
            }
        }, 
        ordenamiento : function(campo,objeto){
            $(fn_companies.data_grid + " #grid-head2 td").removeClass('down').removeClass('up');

            if(campo == fn_companies.orden){
                if(fn_companies.sort == "ASC"){
                    fn_companies.sort = "DESC";
                    $(fn_companies.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('up');
                }else{
                    fn_companies.sort = "ASC";
                    $(fn_companies.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
                }
            }else{
                fn_companies.sort = "ASC";
                fn_companies.orden = campo;
                $(fn_companies.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
            }
            fn_companies.fillgrid();

            return false;
        }, 
        filtraInformacion : function(){
            fn_companies.pagina_actual = 0;
            fn_companies.filtro = "";
            if($(fn_companies.data_grid+" .flt_id").val() != ""){ fn_companies.filtro += "iConsecutivo|"+$(fn_companies.data_grid+" .flt_id").val()+","}
            if($(fn_companies.data_grid+" .flt_name").val() != ""){ fn_companies.filtro += "sNombreCompania|"+$(fn_companies.data_grid+" .flt_name").val()+","} 
            if($(fn_companies.data_grid+" .flt_contacto").val() != ""){ fn_companies.filtro += "sNombreContacto|"+$(fn_companies.data_grid+" .flt_contacto").val()+","} 
            if($(fn_companies.data_grid+" .flt_address").val() != ""){ fn_companies.filtro += "sDireccion|"+$(fn_companies.data_grid+" .flt_address").val()+","} 
            if($(fn_companies.data_grid+" .flt_country").val() != ""){ fn_companies.filtro += "estado|"+$(fn_companies.data_grid+" .flt_country").val()+","} 
            if($(fn_companies.data_grid+" .flt_zip").val() != ""){ fn_companies.filtro += "sCodigoPostal|"+$(fn_companies.data_grid+" .flt_zip").val()+","} 
            if($(fn_companies.data_grid+" .flt_phone").val() != ""){ fn_companies.filtro += "sTelefonoPrincipal|"+$(fn_companies.data_grid+" .flt_phone").val()+","}  
            if($(fn_companies.data_grid+" .flt_usdot").val() != ""){ fn_companies.filtro += "sUsdot|"+$(fn_companies.data_grid+" .flt_usdot").val()+","}    
            fn_companies.fillgrid();
        },  
}     
</script> 
<div id="layer_content" class="main-section">
    <div id="ct_companies" class="container">
        <div class="page-title">
            <h1>QUOTES</h1>
            <h2>APPLICATION FORMATS</h2>
        </div>
        <table id="data_grid_companies" class="data_grid">
        <thead>
            <tr id="grid-head1">
                <td style='width:45px;'><input class="flt_id" class="numeros" type="text" placeholder="ID:"></td>
                <td><input class="flt_name" type="text" placeholder="Name:"></td>
                <td style='width:80px;'><input class="flt_usdot" type="text" placeholder="USDOT:"></td> 
                <td><input class="flt_address" type="text" placeholder="Address:"></td> 
                <td><input class="flt_country" type="text" placeholder="Country:"></td>
                <td style='width:50px;'><input class="flt_zip" type="text" placeholder="Zip Code:"></td> 
                <td><input class="flt_contacto" type="text" placeholder="Contact Name:"></td>
                <td style='width:100px;'><input class="flt_phone" type="text" placeholder="Phone(s):"></td> 
                <td style='width:80px;'>
                    <div class="btn-icon-2 btn-left" title="Search" onclick="fn_companies.filtraInformacion();"><i class="fa fa-search"></i></div>
                    <div class="btn-icon-2 btn-left" title="Add +"  onclick="fn_companies.add();"><i class="fa fa-plus"></i></div>
                </td> 
            </tr>
            <tr id="grid-head2">
                <td class="etiqueta_grid down" onclick="fn_companies.ordenamiento('iConsecutivo',this.cellIndex);">ID</td>
                <td class="etiqueta_grid"      onclick="fn_companies.ordenamiento('sNombreCompania',this.cellIndex);">Company</td>
                <td class="etiqueta_grid"      onclick="fn_companies.ordenamiento('sUsdot',this.cellIndex);">USDOT</td>
                <td class="etiqueta_grid"      onclick="fn_companies.ordenamiento('sDireccion',this.cellIndex);">Address</td>
                <td class="etiqueta_grid"      onclick="fn_companies.ordenamiento('estado',this.cellIndex);">Country</td>
                <td class="etiqueta_grid"      onclick="fn_companies.ordenamiento('sCodigoPostal',this.cellIndex);">Zip Code</td>
                <td class="etiqueta_grid"      onclick="fn_companies.ordenamiento('sNombreContacto',this.cellIndex);">Contact Name</td> 
                <td class="etiqueta_grid"      onclick="fn_companies.ordenamiento('sTelefonoPrincipal',this.cellIndex);">Phone(s)</td>
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
                        <button id="pgn-inicio"    onclick="fn_companies.firstPage();" title="First page"><span></span></button>
                        <button id="pgn-anterior"  onclick="fn_companies.previousPage();" title="Previous"><span></span></button>
                        <button id="pgn-siguiente" onclick="fn_companies.nextPage();" title="Next"><span></span></button>
                        <button id="pgn-final"     onclick="fn_companies.lastPage();" title="Last Page"><span></span></button>
                    </div>
                </td>
            </tr>
        </tfoot>
        </table>
        
    </div>
</div>
<!---- FORMULARIOS ------>
<div id="form_commercial_auto_quick" class="popup-form" style="display:block;width:1000px">
    <div class="p-header">
        <h2>QUOTE FORMAT - COMMERCIAL AUTO QUICK QUOTE FORM</h2>
        <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('edit_form');"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container">
    <div id="company_information">
        <form>
            <fieldset>
                <p class="mensaje_valido">&nbsp;The fields containing an (*) are required.</p> 
                <table style="width:100%">
                    <tr>
                        <td>
                            <div class="field_item">
                                <input id="iConsecutivo" name="iConsecutivo" type="hidden">
                                <label>Named Insured <span style="color:#ff0000;">*</span>:</label> 
                                <select tabindex="1" id="iConsecutivoCompania"><option value="">Select an option...</option></select>
                            </div>
                        </td>
                        <td>
                            <div class="field_item"> 
                                <label>Ph#:</label>  
                                <input tabindex="2" id="sTelefonoPrincipal" class="numb" name="sTelefonoPrincipal" type="text" readonly="readonly">
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%">
                            <div class="field_item">
                                <label>Garaging Address:</label> 
                                <input tabindex="3" id="sDireccion" name="sDireccion" type="text" readonly="readonly">
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="width:80%">
                            <div class="field_item">
                                <label>No. Of Years In Business (With own insurance):</label> 
                                <input tabindex="4" id="sYearsExperiencia" class="numb" name="sYearsExperiencia" type="text" maxlength="2" style="width: 99%;"> 
                            </div>
                        </td>
                        <td>
                            <div class="field_item">
                                <label>FEIN#:</label> 
                                <input tabindex="5" id="iFEINNumb" class="numb" name="iFEINNumb" type="text" maxlength="15"> 
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%">
                            <div class="field_item">
                                <label>Commodities Hauled (Be specific about percent of time):</label> 
                                <input tabindex="6" id="sCommodities" name="sCommodities" type="text">
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%">
                            <div class="field_item">
                                <label>Filings Required:</label> 
                                <label class="lbl-check"><input tabindex="7"  id="" name="filings_none" type="checkbox" disabled="disabled"> None</label>
                                <label class="lbl-check"><input tabindex="8"  id="" name="" type="checkbox"> ICC</label>
                                                         <input tabindex="9" id="" name="" type="text" style="height: 27px;width: auto;">  
                                <label class="lbl-check"><input tabindex="10"  id="" name="" type="checkbox"> DMV</label>
                                                         <input tabindex="11" id="" name="" type="text" style="height: 27px;width: auto;"> 
                                <label class="lbl-check"><input tabindex="12" id="" name="" type="checkbox"> OTHER</label>
                                                         <input tabindex="13" id="" name="" type="text" style="height: 27px;width: auto;">   
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%">
                            <div class="field_item">
                                <label>Radius:</label><br> 
                                <label class="lbl-check"><input tabindex="14"  id="" name="" type="checkbox"> Intrastate (CA Only)</label>
                                <label class="lbl-check"><input tabindex="15"  id="" name="" type="checkbox"> 0-100 Miles</label>
                                <label class="lbl-check"><input tabindex="16"  id="" name="" type="checkbox"> 101-200 Miles</label>
                                <label class="lbl-check"><input tabindex="17" id="" name="" type="checkbox"> 201-300 Miles</label>
                                <label class="lbl-check"><input tabindex="18" id="" name="" type="checkbox"> 301-500 Miles</label> 
                                <label class="lbl-check"><input tabindex="19" id="" name="" type="checkbox"> Interstate - Exactly Where?</label>
                                <input tabindex="20" id="" name="" type="text" style="height: 27px;width: 120px;">    
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%">
                            <div class="field_item">
                                <label>Driver(s):</label> 
                                <table style="width:100%;" class="popup-datagrid">
                                <thead>
                                    <tr id="grid-head2">
                                        <td class="etiqueta_grid">Name</td>
                                        <td class="etiqueta_grid">YRS EXP</td>
                                        <td class="etiqueta_grid">ACCIDENTS</td>
                                        <td class="etiqueta_grid" style="width: 100px;text-align: center;"></td>
                                    </tr>
                                </thead>
                                <tbody><tr><td style="text-align:center; font-weight: bold;" colspan="100%">No data available.</td></tr></tbody>
                                </table>
                                <p style="font-size: 10px;">*Specify the number of year's commercial driving experience each driver as. If there are any drivers with a "not at foult" accident, please provide a copy of the policy report with your submission.</p>    
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%">
                            <div class="field_item">
                                <label>Equipment:</label> 
                                <table style="width:100%;" class="popup-datagrid">
                                <thead>
                                    <tr id="grid-head2">
                                        <td class="etiqueta_grid">YEAR</td>
                                        <td class="etiqueta_grid">MAKE</td>
                                        <td class="etiqueta_grid">BODY TYPE</td>
                                        <td class="etiqueta_grid">GBW</td>
                                        <td class="etiqueta_grid">STATED VALUE</td> 
                                        <td class="etiqueta_grid">DEDUCTIBLE</td> 
                                        <td class="etiqueta_grid" style="width: 100px;text-align: center;"></td>
                                    </tr>
                                </thead>
                                <tbody><tr><td style="text-align:center; font-weight: bold;" colspan="100%">No data available.</td></tr></tbody>
                                </table>
                                <p style="font-size: 10px;">*If there are 5 or more power units, please provide a completed ACORD or completed company application instead of this form for quoting.</p>    
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%">
                            <div class="field_item">
                                <label>Trailer(s):</label> 
                                <table style="width:100%;" class="popup-datagrid">
                                <thead>
                                    <tr id="grid-head2">
                                        <td class="etiqueta_grid">YEAR</td>
                                        <td class="etiqueta_grid">MAKE</td>
                                        <td class="etiqueta_grid">BODY TYPE</td>
                                        <td class="etiqueta_grid">GBW</td>
                                        <td class="etiqueta_grid">STATED VALUE</td> 
                                        <td class="etiqueta_grid">DEDUCTIBLE</td> 
                                        <td class="etiqueta_grid" style="width: 100px;text-align: center;"></td>
                                    </tr>
                                </thead>
                                <tbody><tr><td style="text-align:center; font-weight: bold;" colspan="100%">No data available.</td></tr></tbody>
                                </table>
                                <p style="font-size: 10px;">*Please specify if applicant is pulling non-owned trailers and if applicant is pulling doubles.</p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%">
                            <div class="field_item">
                                <label>Coverages:</label>
                            </div>
                            <div class="field_item">
                                <label style="float: left;padding: 12px 0px 0px;display: block;width: 150px;">Auto Liability:</label> 
                                <label class="lbl-check"><input tabindex="21"  id="" name="" type="checkbox"> $100K CSL</label>
                                <label class="lbl-check"><input tabindex="22"  id="" name="" type="checkbox"> $300K CSL</label>
                                <label class="lbl-check"><input tabindex="23"  id="" name="" type="checkbox"> $500K CSL</label>
                                <label class="lbl-check"><input tabindex="24"  id="" name="" type="checkbox"> $750 CSL</label>
                                <label class="lbl-check"><input tabindex="25"  id="" name="" type="checkbox"> $1M CSL</label> 
                                <label class="lbl-check"><input tabindex="26"  id="" name="" type="checkbox"> Other</label>
                                <input tabindex="27" id="" name="" type="text" style="height: 27px;width: 120px;">
                            </div> 
                            <div class="field_item"> 
                                <label style="float: left;padding: 12px 0px 0px;display: block;width: 150px;">Auto Liability Deductible:</label> 
                                <label class="lbl-check"><input tabindex="28"  id="" name="" type="checkbox"> $500</label> 
                            </div> 
                            <div class="field_item">
                                <label style="float: left;padding: 12px 0px 0px;display: block;width: 150px;">Ininsured Motorist BI:</label> 
                                <label class="lbl-check"><input tabindex="29"  id="" name="" type="checkbox"> $15,000/30,000</label>
                                <label class="lbl-check"><input tabindex="23"  id="" name="" type="checkbox"> $25,000/50,000</label> 
                                <label class="lbl-check"><input tabindex="31"  id="" name="" type="checkbox"> $30,000/60,000</label> 
                            </div> 
                            <div class="field_item">
                                <label style="float: left;padding: 12px 0px 0px;display: block;width: 150px;">Cargo:</label> 
                                <label class="lbl-check"><input tabindex="32"  id="" name="" type="checkbox"> $25,000</label> 
                                <label class="lbl-check"><input tabindex="33"  id="" name="" type="checkbox"> $50,000</label> 
                                <label class="lbl-check"> Deductible <input tabindex="34" id="" name="" type="text" style="height: 27px;width: 120px;"></label>   
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%">
                            <div class="field_item">
                                <label>Other Coverage:</label><br> 
                                <input tabindex="27" id="" name="" type="text" style="height: 27px;width: 120px;">  
                                <label class="lbl-check"><input tabindex="15"  id="" name="" type="checkbox"></label>
                                <input tabindex="27" id="" name="" type="text" style="height: 27px;width: 120px;">  
                                <label class="lbl-check"><input tabindex="17" id="" name="" type="checkbox"></label>
                                <input tabindex="27" id="" name="" type="text" style="height: 27px;width: 120px;">  
                                <label class="lbl-check"> Deductible</label> 
                                <input tabindex="34" id="" name="" type="text" style="height: 27px;width: 120px;">    
                            </div>
                        </td>
                    </tr>
                </table>
                <button type="button" class="btn-1" onclick="fn_companies.save();">SAVE</button> 
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

</body>

</html>
<?php } ?>