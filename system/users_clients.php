<script src="/js/jquery.1.8.3.min.js" type="text/javascript"></script> 
<script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
<script type="text/javascript"> 
$(document).ready(inicio);

function inicio(){   
    llenadoGrid();
    
}                
function llenadoGrid(){      
     var fn_UsersClients = {
        domroot:"#ct_clientusers",
        data_grid: "#data_grid_clientusers",
        fillgrid: function(){
               $.ajax({             
                type:"POST", 
                url:"funciones.php", 
                data:{accion:"get_clientusers"},
                async : true,
                dataType : "json",
                success : function(data){                               
                    $(fn_UsersClients.data_grid+" tbody").empty().append(data.tabla);
                    $(fn_UsersClients.data_grid+" tbody tr:even").addClass('gray');
                    $(fn_UsersClients.data_grid+" tbody tr:odd").addClass('white');
                }
            }); 
        }    
    }
    fn_UsersClients.fillgrid();    
}
function borrarClient(cliente_borrar){
    $.post("funciones.php",{
                               accion : "borrar_cliente", 
                               id : cliente_borrar
                               }, 
                               onRespuestaBorrado);
                               
}

function onRespuestaBorrado(respuesta, estado, xhr ){
    llenadoGrid();   
}
function confirmarBorrar(registro) {
    $( "#dialog-confirm" ).dialog({
      resizable: false,
      height:140,
      modal: true,
      buttons: {
        "Delete all items": function() {
          $( this ).dialog( "close" );
        },
        Cancel: function() {
          $( this ).dialog( "close" );
        }
      }
    });
    confirmacion = confirm ("These items will be permanently deleted and cannot be recovered. Are you sure?");

    return confirmacion;

}




	
</script> 
<!---- HEADER ----->
<?php include("header.php"); ?> 
<script> 
$(document).ready(inicio);
function inicio(){   

    llenadoGrid();  
}
</script>
<div id="layer_content" class="main-section">
	<div id="ct_clientusers" class="container">
        <div class="page-title">
            <h1>Catalogs</h1>
		    <h2>Client Users</h2>
        </div>
		<table id="data_grid_clientusers" class="data_grid">
		<thead id="grid-head2">
			<tr>
				<td class="etiqueta_grid">Name</td>
				<td class="etiqueta_grid">E-mail</td>
				<td class="etiqueta_grid">Description</td>
				<td class="etiqueta_grid">Status</td>
                <td class="etiqueta_grid"></td>
			</tr>
		</thead>
		<tbody></tbody>
		<tfoot>
			<tr>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
                <td></td>
			</tr>
		</tfoot>
		</table>
		
	</div>
</div>
<div id="dialog-confirm" title="alert">
  <p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>These items will be permanently deleted and cannot be recovered. Are you sure?</p>
</div>
<!---- FOOTER ----->
<?php include("footer.php"); ?> 

</body>

</html>
