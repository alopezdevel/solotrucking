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
                $("#companies_edit_form #sEstado").empty().append(data.tabla).removeAttr('disabled').removeClass('readonly');
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
           $('#companies_edit_form :text, #companies_edit_form select').val('').removeClass('error');
           $('#companies_edit_form .mensaje_valido').empty().append('The fields containing an (*) are required.');
           $('#companies_edit_form #sUsdot').removeAttr('readonly');
           //$('#companies_edit_form #companies_tabs').hide();
           fn_popups.resaltar_ventana('companies_edit_form');  
        },
        edit : function (){
            $(fn_companies.data_grid + " tbody td .edit").bind("click",function(){
                var clave = $(this).parent().parent().find("td:eq(0)").html();
                
                //fn_popups.resaltar_ventana("companies_edit_form");
                $.post("funciones_companies.php",
                {
                    accion:"get_company", 
                    clave: clave, 
                    domroot : "companies_edit_form"
                },
                function(data){
                    if(data.error == '0'){
                       $('#companies_edit_form :text, #companies_edit_form select').val('').removeClass('error'); 
                       eval(data.fields);
                       $('#companies_edit_form #sUsdot').attr('readonly','readonly'); 
                       //$('#companies_edit_form #companies_tabs').show(); 
                       fn_popups.resaltar_ventana('companies_edit_form');
                         
                    }else{
                       fn_solotrucking.mensaje(data.msj);  
                    }       
                },"json"); 
          });  
        },
        save : function (){
           //Validate Fields:
           var sNombreCompania = $('#companies_edit_form #sNombreCompania');
           var sUsdot = $('#companies_edit_form #sUsdot');
           var valid = true;
           //field nombre
           valid = valid && fn_solotrucking.checkLength( sNombreCompania, "Company Name", 1, 255 );
           //valid = valid && fn_solotrucking.checkRegexp( sNombreCompania, /^[0-9a-zA-ZáéíóúàèìòùÀÈÌÒÙÁÉÍÓÚñÑüÜ_\s]+$/, "The field for the Name must contain only letters." );
           valid = valid && fn_solotrucking.checkLength( sUsdot, "Company Name", 1, 10 );
           
           if(valid){
             if($('#companies_edit_form #iConsecutivo').val() != ''){struct_data_post.edit_mode = "true";}else{struct_data_post.edit_mode = "false";}  
             struct_data_post.action="save_company";
             struct_data_post.domroot= "#companies_edit_form"; 
                $.post("funciones_companies.php",struct_data_post.parse(),
                function(data){
                    switch(data.error){
                     case '0':
                        fn_solotrucking.mensaje(data.msj);
                        fn_companies.fillgrid();
                        fn_popups.cerrar_ventana('companies_edit_form');
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
            <h1>Catalogs</h1>
            <h2>Insured Companies</h2>
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
<div id="companies_edit_form" class="popup-form">
    <div class="p-header">
        <h2>EDIT OR ADD COMPANY</h2>
        <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('companies_edit_form');"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container">
    <div id="company_information">
        <form>
            <fieldset>
                <legend>General Information</legend>
                <p class="mensaje_valido">&nbsp;The fields containing an (*) are required.</p> 
                <div class="field_item">
                <input id="iConsecutivo" name="iConsecutivo" type="hidden">
                <label>Company Name <span style="color:#ff0000;">*</span>:</label> 
                <input class="txt-uppercase" tabindex="1" id="sNombreCompania" name="sNombreCompania" type="text" placeholder="" maxlength="255">
            </div>
            <div class="field_item"> 
                <label>USDOT <span style="color:#ff0000;">*</span>:</label> 
                <input tabindex="2" id="sUsdot" class="numb" name="sUsdot" type="text" placeholder="" maxlength="10">
            </div>
            <div class="field_item">
                <label>Address:</label>  
                <input tabindex="3" id="sDireccion" name="sDireccion" type="text" placeholder="" maxlength="300">
            </div>
            <div class="field_item">
                <div class="col_3 left ">
                    <label>City:</label>  
                    <input tabindex="4" id="sCiudad" name="sCiudad" type="text"  placeholder="" maxlength="200"> 
                </div> 
                <div class="col_3 left ">  
                    <label>Zip Code:</label>   
                    <input tabindex="5" id="sCodigoPostal" class="numb" name="sCodigoPostal" type="text" maxlength="5" placeholder="" style="margin-left: 3px;">                
                </div>
                <div class="col_3 left "> 
                    <label>State:</label>  
                    <select tabindex="6" id="sEstado" name="sEstado" style="height: 41px!important;margin-left: 6px;"></select>
                </div>
            </div>
            <div class="field_item">
                <div class="col_2 left "> 
                <label>Contact Name:</label>  
                <input tabindex="2" id="sNombreContacto" name="sNombreContacto" type="text" placeholder="" maxlength="255">
                </div>
                <div class="col_2 left "> 
                <label>Phone:</label>  
                <input tabindex="7" id="sTelefonoPrincipal" class="numb" name="sTelefonoPrincipal" type="text" placeholder="" maxlength="25" style="margin-left: 7px;">
                </div>
            </div>
            <div class="field_item" style="clear: both;">
                <label>Red List:</label>  
                <select tabindex="3" id="iOnRedList" name="iOnRedList">
                    <option value="0">No</option>
                    <option value="1">Yes</option> 
                </select>
            </div>
            <button type="button" class="btn-1" onclick="fn_companies.save();">Guardar</button> 
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