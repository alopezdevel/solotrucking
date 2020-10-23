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
            fn_finance.init();
            fn_finance.fillgrid();
            $.unblockUI();
            
            $('#dialog_delete_finance').dialog({
                modal: true,
                autoOpen: false,
                width : 300,
                height : 200,
                resizable : false,
                buttons : {
                    'YES' : function() {
                        clave = $('#dialog_delete_finance #id').val();
                        $(this).dialog('close');
                        fn_finance.delete_data(clave);             
                    },
                     'NO' : function(){
                        $(this).dialog('close');
                    }
                }
            });
            
            
        
    }  
    function validapantalla(usuario){if(usuario == ""  || usuario == null){location.href= "login.php";}}                   
    var fn_finance = {
            domroot:"#ct_finance",
            data_grid: "#data_grid_finance",
            form : "#edit_form",
            filtro : "",
            pagina_actual : "",
            sort : "ASC",
            orden : "A.iConsecutivo",
            init : function(){
                fn_finance.fillgrid();
                $('.num').keydown(fn_solotrucking.inputnumero);  
                $(".phone").mask("(999) 999-9999");
                //Filtrado con la tecla enter
                $(fn_finance.data_grid + ' #grid-head1 input').keyup(function(event){
                    if (event.keyCode == '13') {
                        event.preventDefault();
                        fn_finance.filtraInformacion();
                    }
                    if(event.keyCode == '27'){
                       event.preventDefault();
                       $(this).val(''); 
                       fn_finance.filtraInformacion();
                    }
                }); 
                
                $.ajax({             
                    type:"POST", 
                    url:"catalogos_generales.php", 
                    data:{accion:"get_brokers"},
                    async : true,
                    dataType : "json",
                    success : function(data){
                        $(fn_finance.form + " select[name=iConsecutivoBroker]").empty().append(data.select);
                    }
                });
            },
            fillgrid: function(){
                   $.ajax({             
                    type:"POST", 
                    url:"financiers_server.php", 
                    data:{
                        accion:"get_data",
                        registros_por_pagina : "15", 
                        pagina_actual : fn_finance.pagina_actual, 
                        filtroInformacion : fn_finance.filtro,  
                        ordenInformacion : fn_finance.orden,
                        sortInformacion : fn_finance.sort,
                    },
                    async : true,
                    dataType : "json",
                    success : function(data){                               
                        $(fn_finance.data_grid+" tbody").empty().append(data.tabla);
                        $(fn_finance.data_grid+" tbody tr:even").addClass('gray');
                        $(fn_finance.data_grid+" tbody tr:odd").addClass('white');
                        $(fn_finance.data_grid + " tfoot #paginas_total").val(data.total);
                        $(fn_finance.data_grid + " tfoot #pagina_actual").val(data.pagina);
                        fn_finance.pagina_actual = data.pagina; 
                        fn_finance.edit();
                        fn_finance.delete_confirm();
                        fn_solotrucking.btn_tooltip();
                    }
                }); 
            },
            add : function(){
               $(fn_finance.form + ' input,' + fn_finance.form +' select').val('').removeClass('error');
               $(fn_finance.form+' .mensaje_valido').empty().append('The fields containing an (<span style="color:#ff0000;">*</span>) are required.');
               fn_popups.resaltar_ventana('edit_form');  
            },
            edit : function (){
                $(fn_finance.data_grid + " tbody td .edit").bind("click",function(){
                    var clave = $(this).parent().parent().find("td:eq(0)").html();
                    $.post("financiers_server.php",{accion:"get_finance_data", clave: clave, domroot : "edit_form"},
                    function(data){
                        if(data.error == '0'){
                           $(fn_finance.form+' input, '+fn_finance.form+' select').val('').removeClass('error'); 
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
               var sNombre = $(fn_finance.form + ' #sName');
               var sEmail = $(fn_finance.form + ' #sEmail');
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
                 if($(fn_finance.form + ' #iConsecutivo').val() != ''){struct_data_post.edit_mode = "true";}else{struct_data_post.edit_mode = "false";}  
                 struct_data_post.action="save_insurance";
                 struct_data_post.domroot= fn_finance.form; 
                    $.post("financiers_server.php",struct_data_post.parse(),
                    function(data){
                        switch(data.error){
                         case '0':
                            fn_solotrucking.mensaje(data.msj);
                            fn_finance.fillgrid();
                            fn_popups.cerrar_ventana('edit_form');
                         break;
                         case '1': fn_solotrucking.mensaje(data.msj); break;
                        }
                    },"json");
               }
                
            },
            delete_confirm : function(){
              $(fn_finance.data_grid + " tbody .btn_delete").bind("click",function(){
                   var clave = $(this).parent().parent().find("td:eq(0)").html();
                   $('#dialog_delete_finance #id').val(clave);
                   $('#dialog_delete_finance').dialog( 'open' );
                   return false;
               });  
            },
            delete_data : function(id){
              $.post("financiers_server.php",{accion:"delete_insurance", 'clave': id},
               function(data){
                    fn_solotrucking.mensaje(data.msj);
                    fn_finance.fillgrid();
               },"json");  
            },
            firstPage : function(){
                if($(fn_finance.data_grid+" #pagina_actual").val() != "1"){
                    fn_finance.pagina_actual = "";
                    fn_finance.fillgrid();
                }
            },
            previousPage : function(){
                if($(fn_finance.data_grid+" #pagina_actual").val() != "1"){
                    fn_finance.pagina_actual = (parseInt($(fn_finance.data_grid+" #pagina_actual").val()) - 1) + "";
                    fn_finance.fillgrid();
                }
            },
            nextPage : function(){
                if($(fn_finance.data_grid+" #pagina_actual").val() != $(fn_finance.data_grid+" #paginas_total").val()){
                    fn_finance.pagina_actual = (parseInt($(fn_finance.data_grid+" #pagina_actual").val()) + 1) + "";
                    fn_finance.fillgrid();
                }
            },
            lastPage : function(){
                if($(fn_finance.data_grid+" #pagina_actual").val() != $(fn_finance.data_grid+" #paginas_total").val()){
                    fn_finance.pagina_actual = $(fn_finance.data_grid+" #paginas_total").val();
                    fn_finance.fillgrid();
                }
            }, 
            ordenamiento : function(campo,objeto){
                $(fn_finance.data_grid + " #grid-head2 td").removeClass('down').removeClass('up');

                if(campo == fn_finance.orden){
                    if(fn_finance.sort == "ASC"){
                        fn_finance.sort = "DESC";
                        $(fn_finance.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('up');
                    }else{
                        fn_finance.sort = "ASC";
                        $(fn_finance.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
                    }
                }else{
                    fn_finance.sort = "ASC";
                    fn_finance.orden = campo;
                    $(fn_finance.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
                }
                fn_finance.fillgrid();

                return false;
            }, 
            filtraInformacion : function(){
                fn_finance.pagina_actual = 0;
                fn_finance.filtro        = "";
                if($(fn_finance.data_grid+" .flt_id").val() != ""){ fn_finance.filtro += "A.iConsecutivo|"+$(fn_finance.data_grid+" .flt_id").val()+","}
                if($(fn_finance.data_grid+" .flt_name").val() != ""){ fn_finance.filtro += "A.sName|"+$(fn_finance.data_grid+" .flt_name").val()+","} 
                if($(fn_finance.data_grid+" .flt_broker").val() != ""){ fn_finance.filtro += "B.sName|"+$(fn_finance.data_grid+" .flt_broker").val()+","}
                if($(fn_finance.data_grid+" .flt_email").val() != ""){ fn_finance.filtro += "A.sEmail|"+$(fn_finance.data_grid+" .flt_email").val()+","} 
                if($(fn_finance.data_grid+" .flt_phone").val() != ""){ fn_finance.filtro += "A.sTelefono|"+$(fn_finance.data_grid+" .flt_phone").val()+","}  
                if($(fn_finance.data_grid+" .flt_contact").val() != ""){ fn_finance.filtro += "A.sNombreContacto|"+$(fn_finance.data_grid+" .flt_contact").val()+","} 
                if($(fn_finance.data_grid+" .flt_comment").val() != ""){ fn_finance.filtro += "A.sComentarios|"+$(fn_finance.data_grid+" .flt_comment").val()+","}
                fn_finance.fillgrid();
            },  
                   
    }    
</script> 
<div id="layer_content" class="main-section">
    <div id="ct_finance" class="container">
        <div class="page-title">
            <h1>Catalogs</h1>
            <h2>FINANCIERS OF SOLO-TRUCKING INSURANCE / FINANCIERAS PARA SOLO-TRUCKING</h2>
        </div>
        <table id="data_grid_finance" class="data_grid">
        <thead id="grid-head2">
            <tr id="grid-head1">
                <td style="width:50px!important;"><input class="flt_id" type="text" placeholder="ID:"></td> 
                <td><input class="flt_name"    type="text" placeholder="Name:"></td>
                <td><input class="flt_email"   type="text" placeholder="E-mail(s):"></td>
                <td><input class="flt_contact" type="text" placeholder="Contact Name(s):"></td> 
                <td><input class="flt_phone"   type="text" placeholder="Phone:"></td> 
                <td><input class="flt_broker"  type="text" placeholder="Broker name:"></td> 
                <td><input class="flt_comment" type="text" placeholder="Comments:"></td>
                <td style='width:80px;'>
                    <div class="btn-icon-2 btn-left" title="Search" onclick="fn_finance.filtraInformacion();"><i class="fa fa-search"></i></div>
                    <div class="btn-icon-2 btn-left" title="Add +"  onclick="fn_finance.add();"><i class="fa fa-plus"></i></div>
                </td> 
            </tr>
            <tr id="grid-head2">
                <td class="etiqueta_grid down" onclick="fn_finance.ordenamiento('A.iConsecutivo',this.cellIndex);">ID</td> 
                <td class="etiqueta_grid"      onclick="fn_finance.ordenamiento('A.sName',this.cellIndex);">NAME</td>
                <td class="etiqueta_grid"      onclick="fn_finance.ordenamiento('A.sNombreContacto',this.cellIndex);">CONTACT NAME(S)</td> 
                <td class="etiqueta_grid"      onclick="fn_finance.ordenamiento('A.sEmail',this.cellIndex);">E-mail(s)</td>
                <td class="etiqueta_grid"      onclick="fn_finance.ordenamiento('A.sTelefono',this.cellIndex);">PHONE</td>
                <td class="etiqueta_grid"      onclick="fn_finance.ordenamiento('B.sName',this.cellIndex);">BROKER APPLY</td> 
                <td class="etiqueta_grid"      onclick="fn_finance.ordenamiento('sComentarios',this.cellIndex);">COMMENTS</td>
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
                        <button id="pgn-inicio"    onclick="fn_finance.firstPage();" title="First page"><span></span></button>
                        <button id="pgn-anterior"  onclick="fn_finance.previousPage();" title="Previous"><span></span></button>
                        <button id="pgn-siguiente" onclick="fn_finance.nextPage();" title="Next"><span></span></button>
                        <button id="pgn-final"     onclick="fn_finance.lastPage();" title="Last Page"><span></span></button>
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
        <h2>EDIT / ADD FINANCIER</h2>
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
                            <label>Financier Name: <span style="color:#ff0000;">*</span>:</label>  
                            <input tabindex="1" class="txt-uppercase" id="sName" name="sName" type="text" placeholder="" maxlength="255"> 
                        </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%">
                        <div class="field_item"> 
                            <label>Contact Name(s): </label>
                            <input tabindex="2" id="sNombreContacto" name="sNombreContacto" type="text" class="txt-uppercase" >
                        </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%">
                        <div class="field_item">
                            <label>Email(s): <span style="color:#ff0000;">*</span>:</label> 
                            <input tabindex="3" class="txt-lowercase" id="sEmail" name="sEmail" type="text" placeholder="" maxlength="255" title="If you have more of one emails to send, please separate it by ','.">
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
                            <label>Broker: </label>
                            <select id="iConsecutivoBroker" name="iConsecutivoBroker" tabindex="5"><option value="">Select an option...</option></select>
                        </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%">
                        <div class="field_item"> 
                            <label>Comments: </label>
                            <input id="sComentarios" name="sComentarios" type="text" tabindex="5" maxlength="50" tabindex="6">
                        </div>
                        </td>
                    </tr>
                </table>
                <br>
                <button type="button" class="btn-1" onclick="fn_finance.save();">SAVE</button> 
            </fieldset>
        </form>
    </div>
    </div>
</div>
<!-- DIALOGUES -->
<div id="dialog_delete_finance" title="SYSTEM ALERT" style="display:none;">
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