<?php session_start();    
if ( !($_SESSION["acceso"] != '2'  && $_SESSION["usuario_actual"] != "" && $_SESSION["usuario_actual"] != NULL  )  ){ //No ha iniciado session, redirecciona a la pagina de login
    header("Location: login.php");
    exit;
}else{ ?>
<!-- HEADER -->
<?php include("header.php"); ?>   
<script type="text/javascript"> 
$(document).ready(inicio);
function inicio(){  
        var usuario_actual = <?php echo json_encode($_SESSION['usuario_actual']);?>        
        var tipo_usuario = <?php echo json_encode($_SESSION['acceso']);?> 
        validapantalla(usuario_actual);  
        $("#aUpdateAccount").click(function() { actualizarCliente(usuario_actual); });
        fn_companies.init();
        //$.unblockUI();
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
                    fn_companies.borrar(clave);
                    $(this).dialog('close');
                },
                'CANCEL' : function(){$(this).dialog('close');}
            }
        });
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
            $('.num').keydown(fn_solotrucking.inputnumero); 
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
                url:"companies_disabled_server.php", 
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
                    fn_companies.delete_confirm();
                }
            }); 
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
        delete_confirm : function(){
          $(fn_companies.data_grid + " tbody .btn_active").bind("click",function(){
               var clave = $(this).parent().parent().find("td:eq(0)").html();
               var name  = $(this).parent().parent().find("td:eq(1)").text();
               $('#dialog_delete input[name=iConsecutivo]').val(clave);
               $('#dialog_delete .name').empty().html(name);
               $('#dialog_delete').dialog( 'open' );
               return false;
           });  
        },
        borrar : function(clave){
          $.post("companies_disabled_server.php",{accion:"enable_company", 'clave': clave},
           function(data){
                fn_solotrucking.mensaje(data.msj);
                fn_companies.filtraInformacion();
           },"json");  
        },
}     
</script> 
<div id="layer_content" class="main-section">
    <div id="ct_companies" class="container">
        <div class="page-title">
            <h1>Catalogs</h1>
            <h2>Disabled Insured Companies</h2>
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
                <td style='width:200px;'><input class="flt_phone" type="text" placeholder="Phone(s):"></td> 
                <td style='width:80px;'>
                    <div class="btn-icon-2 btn-left" title="Search" onclick="fn_companies.filtraInformacion();"><i class="fa fa-search"></i></div>
                </td> 
            </tr>
            <tr id="grid-head-tools">
                <td colspan="100%">
                    <ul>
                        <!--<li><div class="btn-icon report btn-left" title="Generate a Report"><i class="fa fa-folder-open"></i></div></li>-->  
                        <li><div class="btn-icon add btn-left" title="Return to Companies" style="width:auto!important;"><a href="companies"><i class="fa fa-external-link"></i><span> Return to Companies</span></a></div></li> 
                    </ul>
                </td>
            </tr>
            <tr id="grid-head2">
                <td class="etiqueta_grid down" onclick="fn_companies.ordenamiento('iConsecutivo',this.cellIndex);">ID</td>
                <td class="etiqueta_grid"      onclick="fn_companies.ordenamiento('sNombreCompania',this.cellIndex);">Company</td>
                <td class="etiqueta_grid"      onclick="fn_companies.ordenamiento('sUsdot',this.cellIndex);">USDOT</td>
                <td class="etiqueta_grid"      onclick="fn_companies.ordenamiento('sDireccion',this.cellIndex);">Address</td>
                <td class="etiqueta_grid"      onclick="fn_companies.ordenamiento('estado',this.cellIndex);">Country</td>
                <td class="etiqueta_grid"      onclick="fn_companies.ordenamiento('sCodigoPostal',this.cellIndex);">ZIP</td>
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
<!-- DIALOGUES -->
<div id="dialog_delete" title="Delete" style="display:none;">
  <p><span class="ui-icon ui-icon-alert" ></span> Are you sure you want to enable the company? <br><span class="name" style="color:#0a87c1;font-weight:600;padding-left:20px;"></span></p>
  <form><div><input type="hidden" name="iConsecutivo" /></div></form>  
</div>
<!-- FOOTER -->
<?php include("footer.php"); ?> 

</body>

</html>
<?php } ?>