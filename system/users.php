<?php session_start();    
if ( !($_SESSION["acceso"] != '2'  && $_SESSION["usuario_actual"] != "" && $_SESSION["usuario_actual"] != NULL  )  ){ //No ha iniciado session, redirecciona a la pagina de login
    header("Location: login.php");
    exit;
}else{ ?>
<!---- HEADER ----->
<?php include("header.php"); ?>  
<script type="text/javascript"> 
function inicio(){  
        var usuario_actual = <?php echo json_encode($_SESSION['usuario_actual']);?>        
        var tipo_usuario = <?php echo json_encode($_SESSION['acceso']);?> 
        validapantalla(usuario_actual);
        fn_users.init();
        fn_users.view_password();
        $.unblockUI();
        
        $('#dialog_delete_user').dialog({
            modal: true,
            autoOpen: false,
            width : 300,
            height : 200,
            resizable : false,
            buttons : {
                'YES' : function() {
                    clave = $('#id_user').val();
                    $(this).dialog('close');
                    
                    fn_users.delete_user(clave);             
                },
                 'NO' : function(){
                    $(this).dialog('close');
                }
            }
        });
    
}  
function validapantalla(usuario){if(usuario == ""  || usuario == null){location.href= "login.php";}}                   
var fn_users = {
        domroot:"#ct_users",
        data_grid: "#data_grid_users",
        filtro : "",
        pagina_actual : "",
        sort : "ASC",
        orden : "A.iConsecutivo",
        init : function(){
            $('.num').keydown(fn_solotrucking.inputnumero());  
            //Cargar Tipos de Usuario y Companias:
            $.ajax({             
                type:"POST", 
                url:"funciones_users.php", 
                data:{accion:"get_usertypes_companies"},
                async : true,
                dataType : "json",
                success : function(data){                               
                    $("#users_edit_form #iConsecutivoTipoUsuario").empty().append(data.types);
                    $("#users_edit_form #iConsecutivoCompania").empty().append(data.company); 
                }
            });
            //Filtrado con la tecla enter
            $(fn_users.data_grid + ' #grid-head1 input').keyup(function(event){
                if (event.keyCode == '13') {
                    event.preventDefault();
                    fn_users.filtraInformacion();
                }
                if(event.keyCode == '27'){
                   event.preventDefault();
                   $(this).val(''); 
                   fn_users.filtraInformacion();
                }
            });
            fn_users.fillgrid();  
        },
        fillgrid: function(){
               $.ajax({             
                type:"POST", 
                url:"funciones_users.php", 
                data:{
                    accion:"get_users",
                    registros_por_pagina : "15", 
                    pagina_actual : fn_users.pagina_actual, 
                    filtroInformacion : fn_users.filtro,  
                    ordenInformacion : fn_users.orden,
                    sortInformacion : fn_users.sort,
                },
                async : true,
                dataType : "json",
                success : function(data){                               
                    $(fn_users.data_grid+" tbody").empty().append(data.tabla);
                    $(fn_users.data_grid+" tbody tr:even").addClass('gray');
                    $(fn_users.data_grid+" tbody tr:odd").addClass('white');
                    $(fn_users.data_grid + " tfoot #paginas_total").val(data.total);
                    $(fn_users.data_grid + " tfoot #pagina_actual").val(data.pagina);
                    fn_users.pagina_actual = data.pagina; 
                    fn_users.edit();
                    fn_users.delete_confirm();
                    fn_users.user_email_confirm();
                }
            }); 
        },
        add : function(){
           $('#users_edit_form input, #users_edit_form select').val('').removeClass('error');
           $('#users_edit_form .mensaje_valido').empty().append('The fields containing an (*) are required.');
           $('#hClave').attr('type','password');
           fn_popups.resaltar_ventana('users_edit_form');  
        },
        edit : function (){
            $(fn_users.data_grid + " tbody td .edit").bind("click",function(){
                var clave = $(this).parent().parent().find("td:eq(0)").html();
                $.post("funciones_users.php",
                {
                    accion:"get_user", 
                    clave: clave, 
                    domroot : "users_edit_form"
                },
                function(data){
                    if(data.error == '0'){
                       $('#users_edit_form input, #users_edit_form select').val('').removeClass('error'); 
                       eval(data.fields); 
                       $('#users_edit_form #hClave2').val($('#users_edit_form #hClave').val());
                       fn_users.validate_type();
                       $('#hClave').attr('type','password'); 
                       fn_popups.resaltar_ventana('users_edit_form');
                         
                    }else{
                       fn_solotrucking.mensaje(data.msj);  
                    }       
                },"json"); 
          });  
        },
        save : function (){
           //Validate Fields:
           var emailRegex = /^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/
           var sUsuario = $('#users_edit_form #sUsuario');
           var sCorreo = $('#users_edit_form #sCorreo');
           var hClave = $('#users_edit_form #hClave'); 
           var hClave2 = $('#users_edit_form #hClave2'); 
           var iConsecutivoCompania = $('#users_edit_form #iConsecutivoCompania');
           var iConsecutivoTipoUsuario = $('#users_edit_form #iConsecutivoTipoUsuario');
           todosloscampos = $( [] ).add( sUsuario ).add( sCorreo ).add( hClave ).add( hClave2 ).add( iConsecutivoCompania ).add( iConsecutivoTipoUsuario );
           todosloscampos.removeClass( "error" );
           var valid = true;

           valid = valid && fn_solotrucking.checkLength( sUsuario, "User Name", 1, 50 );
           //valid = valid && fn_solotrucking.checkRegexp( sUsuario, /^[0-9a-zA-ZáéíóúàèìòùÀÈÌÒÙÁÉÍÓÚñÑüÜ_\s]+$/, "The field for the Name must contain only letters." );
           valid = valid && fn_solotrucking.checkLength( sCorreo, "E-mail", 1, 50 );
           valid = valid && fn_solotrucking.checkRegexp( sCorreo, emailRegex, "The field for the e-mail must contain 'Ex: user@domain.com'." );  
           valid = valid && fn_solotrucking.checkLength( hClave, "Password", 6, 9 );
           
           if(hClave.val() != hClave2.val()){fn_solotrucking.actualizarMensajeAlerta('Please make sure that the passwords match.');hClave.addClass('error');hClave2.addClass('error');valid = false;}
           if(iConsecutivoTipoUsuario.val() == ''){fn_solotrucking.actualizarMensajeAlerta('Please check the user type field has a value.');iConsecutivoTipoUsuario.addClass('error');valid=false;}
           if(iConsecutivoTipoUsuario.val() != '' && iConsecutivoTipoUsuario.val() == 2 && iConsecutivoCompania.val() == ''){
               fn_solotrucking.actualizarMensajeAlerta('Please check the company field has a value.');iConsecutivoTipoUsuario.addClass('error');valid=false;
           }
           if(valid){
             if($('#users_edit_form #iConsecutivo').val() != ''){struct_data_post.edit_mode = "true";}else{struct_data_post.edit_mode = "false";}  
             struct_data_post.action="save_user";
             struct_data_post.domroot= "#users_edit_form"; 
                $.post("funciones_users.php",struct_data_post.parse(),
                function(data){
                    switch(data.error){
                     case '0':
                        fn_solotrucking.mensaje(data.msj);
                        fn_users.fillgrid();
                        fn_popups.cerrar_ventana('users_edit_form');
                     break;
                     case '1': fn_solotrucking.mensaje(data.msj); break;
                    }
                },"json");
           }
            
        },
        validate_type : function(){
            if($('#users_edit_form #iConsecutivoTipoUsuario').val() != 2){
                $('#users_edit_form .companies_option').hide();
                $('#users_edit_form #iConsecutivoCompania').val('');
            }else{
                $('#users_edit_form .companies_option').show();
            }
        },
        delete_confirm : function(){
          $(fn_users.data_grid + " tbody .btn_delete").bind("click",function(){
               var clave = $(this).parent().parent().find("td:eq(0)").html();
               $('#dialog_delete_user #id_user').val(clave);
               $('#dialog_delete_user').dialog( 'open' );
               return false;
           });  
        },
        delete_user : function(id){
          $.post("funciones_users.php",{accion:"delete_user", 'clave': id},
           function(data){
                fn_solotrucking.mensaje(data.msj);
                fn_users.fillgrid();
           },"json");  
        },
        user_email_confirm : function(){
            $(fn_users.data_grid + " tbody .btn_confirm_email").bind("click",function(){
               var clave = $(this).parent().parent().find("td:eq(0)").html();
               var user = $(this).parent().parent().find("td:eq(1)").html();
               $.post("funciones_users.php",{accion:"send_email_confirmation", clave: clave},
                function(data){
                    if(data.error == '0'){
                        fn_solotrucking.mensaje('E-mail confirmation to the user '+ user +' was successfully sent.');
                        fn_users.fillgrid();
                    }else{
                        fn_solotrucking.mensaje(data.msj);
                    }     
                },"json");
           });  
        },
        change_user_status : function(clave,value){
            $.post("funciones_users.php",
            {
                accion:"change_user_status", 
                clave: clave,
                status : value 
            },
            function(data){
                switch(data.error){
                 case '0':
                    fn_solotrucking.mensaje(data.msj);
                    fn_users.fillgrid();
                 break;
                 case '1': fn_solotrucking.mensaje(data.msj); break;
                }
            },"json");
        },
        firstPage : function(){
            if($(fn_users.data_grid+" #pagina_actual").val() != "1"){
                fn_users.pagina_actual = "";
                fn_users.fillgrid();
            }
        },
        previousPage : function(){
            if($(fn_users.data_grid+" #pagina_actual").val() != "1"){
                fn_users.pagina_actual = (parseInt($(fn_users.data_grid+" #pagina_actual").val()) - 1) + "";
                fn_users.fillgrid();
            }
        },
        nextPage : function(){
            if($(fn_users.data_grid+" #pagina_actual").val() != $(fn_users.data_grid+" #paginas_total").val()){
                fn_users.pagina_actual = (parseInt($(fn_users.data_grid+" #pagina_actual").val()) + 1) + "";
                fn_users.fillgrid();
            }
        },
        lastPage : function(){
            if($(fn_users.data_grid+" #pagina_actual").val() != $(fn_users.data_grid+" #paginas_total").val()){
                fn_users.pagina_actual = $(fn_users.data_grid+" #paginas_total").val();
                fn_users.fillgrid();
            }
        }, 
        ordenamiento : function(campo,objeto){
            $(fn_users.data_grid + " #grid-head2 td").removeClass('down').removeClass('up');

            if(campo == fn_users.orden){
                if(fn_users.sort == "ASC"){
                    fn_users.sort = "DESC";
                    $(fn_users.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('up');
                }else{
                    fn_users.sort = "ASC";
                    $(fn_users.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
                }
            }else{
                fn_users.sort = "ASC";
                fn_users.orden = campo;
                $(fn_users.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
            }
            fn_users.fillgrid();

            return false;
        }, 
        filtraInformacion : function(){
            fn_users.pagina_actual = 0;
            fn_users.filtro = "";
            if($(fn_users.data_grid+" .flt_id").val() != ""){ fn_users.filtro += "iConsecutivo|"+$(fn_users.data_grid+" .flt_id").val()+","}
            if($(fn_users.data_grid+" .flt_name_user").val() != ""){ fn_users.filtro += "sUsuario|"+$(fn_users.data_grid+" .flt_name_user").val()+","} 
            if($(fn_users.data_grid+" .flt_email_user").val() != ""){ fn_users.filtro += "sCorreo|"+$(fn_users.data_grid+" .flt_email_user").val()+","} 
            //if($(fn_users.data_grid+" .flt_type_user").val() != ""){ fn_users.filtro += "sDescripcionTipo|"+$(fn_users.data_grid+" .flt_type_user").val()+","} 
            if($(fn_users.data_grid+" .flt_company").val() != ""){ fn_users.filtro += "sNombreCompania|"+$(fn_users.data_grid+" .flt_company").val()+","} 
            //if($(fn_users.data_grid+" .flt_status").val() != ""){ fn_users.filtro += "hActivado|"+$(fn_users.data_grid+" .flt_status").val()+","}    
            fn_users.fillgrid();
        },
        view_password : function(){
            $("#view_password").bind("click",function(){ 
                 $('#hClave').attr('type','text');
            });
        }  
            
}    
$(document).ready(inicio);  
</script> 
<div id="layer_content" class="main-section">
    <div id="ct_users" class="container">
        <div class="page-title">
            <h1>Catalogs</h1>
            <h2>USERS</h2>
        </div>
        <table id="data_grid_users" class="data_grid">
        <thead id="grid-head2">
            <tr id="grid-head1">
                <td style='width:45px;'><input class="flt_id" class="numeros" type="text" placeholder="ID:"></td>
                <td><input class="flt_name_user" type="text" placeholder="Name:"></td>
                <td><input class="flt_email_user" type="text" placeholder="E-mail:"></td>
                <!---<td><input class="flt_type_user" type="text" placeholder="User Type:"></td> --->
                <td><input class="flt_company" type="text" placeholder="Company:"></td> 
                <td></td>  
                <td style='width:170px;'>
                    <div class="btn-icon-2 btn-left" title="Search" onclick="fn_users.filtraInformacion();"><i class="fa fa-search"></i></div>
                    <div class="btn-icon-2 btn-left" title="Add +"  onclick="fn_users.add();"><i class="fa fa-plus"></i></div>
                </td> 
            </tr>
            <tr id="grid-head2">
                <td class="etiqueta_grid down" onclick="fn_users.ordenamiento('iConsecutivo',this.cellIndex);">ID</td> 
                <td class="etiqueta_grid"      onclick="fn_users.ordenamiento('sUsuario',this.cellIndex);">Name</td>
                <td class="etiqueta_grid"      onclick="fn_users.ordenamiento('sCorreo',this.cellIndex);">E-mail</td>
                <!--<td class="etiqueta_grid"      onclick="fn_users.ordenamiento('sDescripcionTipo',this.cellIndex);">User Type</td> -->
                <td class="etiqueta_grid"      onclick="fn_users.ordenamiento('sNombreCompania',this.cellIndex);">Company</td>
                <td class="etiqueta_grid"      onclick="fn_users.ordenamiento('hActivado',this.cellIndex);">Status</td>
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
                        <button id="pgn-inicio"    onclick="fn_users.firstPage();" title="First page"><span></span></button>
                        <button id="pgn-anterior"  onclick="fn_users.previousPage();" title="Previous"><span></span></button>
                        <button id="pgn-siguiente" onclick="fn_users.nextPage();" title="Next"><span></span></button>
                        <button id="pgn-final"     onclick="fn_users.lastPage();" title="Last Page"><span></span></button>
                    </div>
                </td>
            </tr>
        </tfoot>
        </table>    
    </div>
</div>
<!---- FORMULARIOS ------>
<div id="users_edit_form" class="popup-form">
    <div class="p-header">
        <h2>EDIT OR ADD USER</h2>
        <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('users_edit_form');"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container">
    <div id="user_information" class="form">
            <fieldset>
                <p class="mensaje_valido">&nbsp;The fields containing an (*) are required.</p> 
                <div class="field_item">
                <input id="iConsecutivo" name="iConsecutivo" type="hidden">
                <label>Name <span style="color:#ff0000;">*</span>:</label> 
                <input class="" tabindex="1" id="sUsuario" name="sUsuario" type="text" placeholder="" maxlength="50">
            </div>
            <div class="field_item"> 
                <label>E-mail <span style="color:#ff0000;">*</span>:</label> 
                <input tabindex="2" id="sCorreo" class="numb" name="sCorreo" type="text" placeholder="" maxlength="50">
            </div>
            <div class="field_item">
                <label>User Type <span style="color:#ff0000;">*</span>:</label>  
                <select id="iConsecutivoTipoUsuario" onblur="fn_users.validate_type();"><option>Select an option...</option></select>
            </div>
            <div class="field_item companies_option" style="display:none;">
                <label>Company <span style="color:#ff0000;">*</span>:</label>  
                <select id="iConsecutivoCompania"><option value="">Select an option...</option></select>
            </div>
            <div class="field_item">
                <label>Password <span style="color:#ff0000;">*</span>: (6 to 8 characters)</label>  
                <input tabindex="3" id="hClave" name="hClave" type="password" maxlength="9" style="width:90%;float:left;margin-right:1%">
                <div id="view_password" class="btn-icon-2 btn-left" title="View Password" style="position: relative;top: 10px;"><i class="fa fa-eye"></i></div> 
            </div>
            <div class="field_item" style="clear:both;">
                <label>Repeat Password <span style="color:#ff0000;">*</span>:</label>  
                <input tabindex="3" id="hClave2" name="hClave2" type="password" maxlength="9">
            </div>
            <button type="button" class="btn-1" onclick="fn_users.save();">Guardar</button> 
            </fieldset>
    </div>
    </div>
</div>
<!--- DIALOGUES --->
<div id="dialog_delete_user" title="SYSTEM ALERT" style="display:none;">
    <p>These items will be permanently deleted and cannot be recovered. Are you sure?</p>
    <form id="elimina" method="post">
           <input type="hidden" name="id_user" id="id_user">
    </form>  
</div>
<!---- FOOTER ----->
<?php include("footer.php"); ?> 

</body>

</html>
<?php } ?>