<script type="text/javascript">
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
</script> 
<!---- HEADER ----->
<?php include("header.php"); ?> 

<div id="layer_content" class="main-section">
	<div id="ct_clientusers" class="container">
		<h2>Catalogs - Client Users</h2>
		<table id="data_grid_clientusers" class="data_grid">
		<thead id="grid-head2">
			<tr>
				<td class="etiqueta_grid">Name</td>
				<td class="etiqueta_grid">E-mail</td>
				<td class="etiqueta_grid">Description</td>
				<td class="etiqueta_grid">Status</td>
			</tr>
		</thead>
		<tbody></tbody>
		<tfoot>
			<tr>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
			</tr>
		</tfoot>
		</table>
		
	</div>
</div>
<!---- FOOTER ----->
<?php include("footer.php"); ?> 

</body>

</html>
