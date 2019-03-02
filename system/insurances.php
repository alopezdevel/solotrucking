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
            fn_insurances.init();
            fn_insurances.fillgrid();
            $.unblockUI();
            
            $('#dialog_delete_insurance').dialog({
                modal: true,
                autoOpen: false,
                width : 300,
                height : 200,
                resizable : false,
                buttons : {
                    'YES' : function() {
                        clave = $('#dialog_delete_insurance #id').val();
                        $(this).dialog('close');
                        fn_insurances.delete_data(clave);             
                    },
                     'NO' : function(){
                        $(this).dialog('close');
                    }
                }
            });
            
            
        
    }  
    function validapantalla(usuario){if(usuario == ""  || usuario == null){location.href= "login.php";}}                   
    var fn_insurances = {
            domroot:"#ct_insurance",
            data_grid: "#data_grid_insurances",
            form : "#edit_form",
            filtro : "",
            pagina_actual : "",
            sort : "ASC",
            orden : "iConsecutivo",
            init : function(){
                fn_insurances.fillgrid();
                $('.num').keydown(fn_solotrucking.inputnumero);  
                $(".phone").mask("(999) 999-9999");
                //Filtrado con la tecla enter
                $(fn_insurances.data_grid + ' #grid-head1 input').keyup(function(event){
                    if (event.keyCode == '13') {
                        event.preventDefault();
                        fn_insurances.filtraInformacion();
                    }
                    if(event.keyCode == '27'){
                       event.preventDefault();
                       $(this).val(''); 
                       fn_insurances.filtraInformacion();
                    }
                }); 
            },
            fillgrid: function(){
                   $.ajax({             
                    type:"POST", 
                    url:"funciones_insurances.php", 
                    data:{
                        accion:"get_insurances",
                        registros_por_pagina : "15", 
                        pagina_actual : fn_insurances.pagina_actual, 
                        filtroInformacion : fn_insurances.filtro,  
                        ordenInformacion : fn_insurances.orden,
                        sortInformacion : fn_insurances.sort,
                    },
                    async : true,
                    dataType : "json",
                    success : function(data){                               
                        $(fn_insurances.data_grid+" tbody").empty().append(data.tabla);
                        $(fn_insurances.data_grid+" tbody tr:even").addClass('gray');
                        $(fn_insurances.data_grid+" tbody tr:odd").addClass('white');
                        $(fn_insurances.data_grid + " tfoot #paginas_total").val(data.total);
                        $(fn_insurances.data_grid + " tfoot #pagina_actual").val(data.pagina);
                        fn_insurances.pagina_actual = data.pagina; 
                        fn_insurances.edit();
                        fn_insurances.delete_confirm();
                    }
                }); 
            },
            add : function(){
               $(fn_insurances.form + ' input,' + fn_insurances.form +' select').val('').removeClass('error');
               $(fn_insurances.form+' .mensaje_valido').empty().append('The fields containing an (<span style="color:#ff0000;">*</span>) are required.');
               fn_popups.resaltar_ventana('edit_form');  
            },
            edit : function (){
                $(fn_insurances.data_grid + " tbody td .edit").bind("click",function(){
                    var clave = $(this).parent().parent().find("td:eq(0)").html();
                    $.post("funciones_insurances.php",{accion:"get_insurance", clave: clave, domroot : "edit_form"},
                    function(data){
                        if(data.error == '0'){
                           $(fn_insurances.form+' input, '+fn_insurances.form+' select').val('').removeClass('error'); 
                           eval(data.fields); 
                           fn_popups.resaltar_ventana('edit_form');
                             
                        }else{
                           fn_solotrucking.mensaje(data.msj);  
                        }       
                    },"json"); 
              });  
            },
            save : function (){
               //Validate Fields:
               var sNombre = $(fn_insurances.form + ' #sName');
               var sEmail = $(fn_insurances.form + ' #sEmail');
               todosloscampos = $([]).add(sNombre).add(sEmail);
               todosloscampos.removeClass( "error" );
               var valid = true;
               if(sNombre.val() == ''){
                   fn_solotrucking.mensaje('Please check that Name field has a value.');sNombre.addClass('error');
                   valid=false;
                   return false;
               }
               
               if(sEmail.val() == ''){
                   fn_solotrucking.mensaje('Please check that E-mail field has a value.');sEmail.addClass('error');
                   valid=false;
                   return false;
               }
              
               if(valid){
                 if($(fn_insurances.form + ' #iConsecutivo').val() != ''){struct_data_post.edit_mode = "true";}else{struct_data_post.edit_mode = "false";}  
                 struct_data_post.action="save_insurance";
                 struct_data_post.domroot= fn_insurances.form; 
                    $.post("funciones_insurances.php",struct_data_post.parse(),
                    function(data){
                        switch(data.error){
                         case '0':
                            fn_solotrucking.mensaje(data.msj);
                            fn_insurances.fillgrid();
                            fn_popups.cerrar_ventana('edit_form');
                         break;
                         case '1': fn_solotrucking.mensaje(data.msj); break;
                        }
                    },"json");
               }
                
            },
            delete_confirm : function(){
              $(fn_insurances.data_grid + " tbody .btn_delete").bind("click",function(){
                   var clave = $(this).parent().parent().find("td:eq(0)").html();
                   $('#dialog_delete_insurance #id').val(clave);
                   $('#dialog_delete_insurance').dialog( 'open' );
                   return false;
               });  
            },
            delete_data : function(id){
              $.post("funciones_insurances.php",{accion:"delete_insurance", 'clave': id},
               function(data){
                    fn_solotrucking.mensaje(data.msj);
                    fn_insurances.fillgrid();
               },"json");  
            },
            firstPage : function(){
                if($(fn_insurances.data_grid+" #pagina_actual").val() != "1"){
                    fn_insurances.pagina_actual = "";
                    fn_insurances.fillgrid();
                }
            },
            previousPage : function(){
                if($(fn_insurances.data_grid+" #pagina_actual").val() != "1"){
                    fn_insurances.pagina_actual = (parseInt($(fn_insurances.data_grid+" #pagina_actual").val()) - 1) + "";
                    fn_insurances.fillgrid();
                }
            },
            nextPage : function(){
                if($(fn_insurances.data_grid+" #pagina_actual").val() != $(fn_insurances.data_grid+" #paginas_total").val()){
                    fn_insurances.pagina_actual = (parseInt($(fn_insurances.data_grid+" #pagina_actual").val()) + 1) + "";
                    fn_insurances.fillgrid();
                }
            },
            lastPage : function(){
                if($(fn_insurances.data_grid+" #pagina_actual").val() != $(fn_insurances.data_grid+" #paginas_total").val()){
                    fn_insurances.pagina_actual = $(fn_insurances.data_grid+" #paginas_total").val();
                    fn_insurances.fillgrid();
                }
            }, 
            ordenamiento : function(campo,objeto){
                $(fn_insurances.data_grid + " #grid-head2 td").removeClass('down').removeClass('up');

                if(campo == fn_insurances.orden){
                    if(fn_insurances.sort == "ASC"){
                        fn_insurances.sort = "DESC";
                        $(fn_insurances.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('up');
                    }else{
                        fn_insurances.sort = "ASC";
                        $(fn_insurances.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
                    }
                }else{
                    fn_insurances.sort = "ASC";
                    fn_insurances.orden = campo;
                    $(fn_insurances.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
                }
                fn_insurances.fillgrid();

                return false;
            }, 
            filtraInformacion : function(){
                fn_insurances.pagina_actual = 0;
                fn_insurances.filtro        = "";
                if($(fn_insurances.data_grid+" .flt_id").val() != ""){ fn_insurances.filtro += "iConsecutivo|"+$(fn_insurances.data_grid+" .flt_id").val()+","}
                if($(fn_insurances.data_grid+" .flt_name").val() != ""){ fn_insurances.filtro += "sName|"+$(fn_insurances.data_grid+" .flt_name").val()+","} 
                if($(fn_insurances.data_grid+" .flt_naic").val() != ""){ fn_insurances.filtro += "sNAICNumber|"+$(fn_insurances.data_grid+" .flt_naic").val()+","}
                if($(fn_insurances.data_grid+" .flt_email").val() != ""){ fn_insurances.filtro += "sEmail|"+$(fn_insurances.data_grid+" .flt_email").val()+","} 
                if($(fn_insurances.data_grid+" .flt_phone").val() != ""){ fn_insurances.filtro += "sTelefono|"+$(fn_insurances.data_grid+" .flt_phone").val()+","}  
                if($(fn_insurances.data_grid+" .flt_contact").val() != ""){ fn_insurances.filtro += "sNombreContacto|"+$(fn_insurances.data_grid+" .flt_contact").val()+","} 
                fn_insurances.fillgrid();
            },  
                   
    }    
</script> 
<div id="layer_content" class="main-section">
    <div id="ct_insurance" class="container">
        <div class="page-title">
            <h1>Catalogs</h1>
            <h2>INSURANCES OF SOLO-TRUCKING INSURANCE</h2>
        </div>
        <table id="data_grid_insurances" class="data_grid">
        <thead id="grid-head2">
            <tr id="grid-head1">
                <td style="width:50px!important;"><input class="flt_id" type="text" placeholder="ID:"></td> 
                <td><input class="flt_name" type="text" placeholder="Broker:"></td>
                <td><input class="flt_email" type="text" placeholder="E-mail(s):"></td>
                <td><input class="flt_naic" type="text" placeholder="NAIC#:"></td>
                <td><input class="flt_phone" type="text" placeholder="Phone:"></td> 
                <td><input class="flt_contact" type="text" placeholder="Contact Name(s):"></td>  
                <td style='width:80px;'>
                    <div class="btn-icon-2 btn-left" title="Search" onclick="fn_insurances.filtraInformacion();"><i class="fa fa-search"></i></div>
                    <div class="btn-icon-2 btn-left" title="Add +"  onclick="fn_insurances.add();"><i class="fa fa-plus"></i></div>
                </td> 
            </tr>
            <tr id="grid-head2">
                <td class="etiqueta_grid down" onclick="fn_insurances.ordenamiento('iConsecutivo',this.cellIndex);">ID</td> 
                <td class="etiqueta_grid"      onclick="fn_insurances.ordenamiento('sName',this.cellIndex);">INSURANCE COMPANY</td>
                <td class="etiqueta_grid"      onclick="fn_insurances.ordenamiento('sNAICNumber',this.cellIndex);">NAIC #</td>  
                <td class="etiqueta_grid"      onclick="fn_insurances.ordenamiento('sEmail',this.cellIndex);">E-mail(s)</td>
                <td class="etiqueta_grid"      onclick="fn_insurances.ordenamiento('sTelefono',this.cellIndex);">PHONE</td>
                <td class="etiqueta_grid"      onclick="fn_insurances.ordenamiento('sNombreContacto',this.cellIndex);">CONTACT NAME(S)</td>
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
                        <button id="pgn-inicio"    onclick="fn_insurances.firstPage();" title="First page"><span></span></button>
                        <button id="pgn-anterior"  onclick="fn_insurances.previousPage();" title="Previous"><span></span></button>
                        <button id="pgn-siguiente" onclick="fn_insurances.nextPage();" title="Next"><span></span></button>
                        <button id="pgn-final"     onclick="fn_insurances.lastPage();" title="Last Page"><span></span></button>
                    </div>
                </td>
            </tr>
        </tfoot>
        </table>   
    </div>
</div>
<!-- FORMULARIOS -->
<div id="edit_form" class="popup-form">
    <div class="p-header">
        <h2>EDIT OR ADD A BROKER</h2>
        <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('edit_form');"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container">
    <div id="broker_information">
        <form>
            <fieldset>
                <p class="mensaje_valido">&nbsp;The fields containing an (<span style="color:#ff0000;">*</span>) are required.</p>
                <table style="width: 100%;border-collapse: collapse;">
                    <tr>
                        <td style="width:80%;">
                        <div class="field_item">
                            <input id="iConsecutivo"  name="iConsecutivo"  type="hidden">
                            <label>Name: <span style="color:#ff0000;">*</span>:</label>  
                            <input tabindex="1" class="txt-uppercase" id="sName" name="sName" type="text" placeholder="" maxlength="255"> 
                        </div>
                        </td>
                        <td>
                        <div class="field_item">
                            <label>NAIC #: <span style="color:#ff0000;">*</span>:</label>  
                            <input tabindex="2" class="txt-uppercase" id="sNAICNumber" name="sNAICNumber" type="text" placeholder="" maxlength="10" style="width: 94%;"> 
                        </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%">
                        <div class="field_item">
                            <label>Email(s) to Claims: <span style="color:#ff0000;">*</span>:</label> 
                            <input class="txt-lowercase" tabindex="3" id="sEmailClaims" name="sEmailClaims" type="text" placeholder="" maxlength="255">
                        </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%">
                        <div class="field_item"> 
                            <label>Phone Number: </label>
                            <input id="sTelefono" name="sTelefono" type="text" class="num phone" tabindex="4">
                        </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%">
                        <div class="field_item"> 
                            <label>Contact Name(s): </label>
                            <input id="sNombreContacto" name="sNombreContacto" type="text" class="txt-uppercase" tabindex="5">
                        </div>
                        </td>
                    </tr>
                </table>
                <br>
                <button type="button" class="btn-1" onclick="fn_insurances.save();">SAVE</button> 
            </fieldset>
        </form>
    </div>
    </div>
</div>
<!-- DIALOGUES -->
<div id="dialog_delete_insurance" title="SYSTEM ALERT" style="display:none;">
    <p>This item will be permanently deleted and cannot be recovered. Are you sure??</p>
    <form id="elimina" method="post">
           <input type="hidden" name="id" id="id">
    </form>  
</div>
<!-- FOOTER -->
<?php include("footer.php"); ?> 
</body>
</html>
<?php } ?>