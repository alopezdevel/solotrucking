var STR_PAD_LEFT = 1;
var STR_PAD_RIGHT = 2;
var STR_PAD_BOTH = 3;
var updateTimer = 0;
$(function() {
    
    //inicio();
    $("#mensaje").dialog({
        autoOpen: false,
        bgiframe: true,
        modal: true,
        buttons: {
            Ok: function() {
                $(this).dialog('close');
            }
        }
    });
    $(document).tooltip();
    $('#Wait').dialog({
        modal: true,
        autoOpen: false,
        width : 350,
        height : 140,
        resizable : false,
        closeOnEscape: false,
        open: function(event, ui) { $(".ui-dialog-titlebar-close").hide();}
   });
   
   $("#accordion_help li").click(function(){
        $(this).toggleClass("active");
        $(this).next("div").stop('true','true').slideToggle("slow");
   });

}); 
    
    
    
    var fn_popups = {

    centrar_ventana : function(ventana){
        
        var windowWidth = document.documentElement.clientWidth;
        var windowHeight = document.documentElement.clientHeight;
        var popupHeight = $("#"+ventana).height();
        var popupWidth = $("#"+ventana).width();
        //centrando
        $("#"+ventana).css({
            "position": "absolute",
            "top": 20,
            "left": windowWidth/2-popupWidth/2
        });
    },
    resaltar_ventana : function(ventana){
        $(".overlay-background").show();
        $("#"+ventana).show();
        $('body').css('overflow','hidden');
        $("html, body").animate({ scrollTop: 0 }, 200);     
    },
    cerrar_ventana : function(ventana){
        $(".overlay-background").hide();
        $("#"+ventana).hide();
        $('body').css('overflow','auto');    
    },
    cargando: function(cmd){
        if(cmd == 1){
            $("#wait_container").show();
        }else{
            $("#wait_container").hide();
        }
    },
    mensaje : function(texto){
        $("#mensaje").empty().append(texto);
        $("#mensaje").dialog('open');
    }

}
var fn_solotrucking = {
     onFocus : function(){
        $(this).css("background-color","#FFFFC0");
     },
     onBlur : function(){
        $(this).css("background-color","#FFFFFF");
     },
     actualizarMensajeAlerta : function (t) {
            mensaje = $('.mensaje_valido');
            mensaje.text(t).addClass( "alertmessage" );
            setTimeout(function() {
                mensaje.removeClass( "alertmessage", 2500 );
            }, 700 );
     },
     checkRegexp : function( o, regexp, n ) {
        if ( !( regexp.test( o.val() ) ) ) {
            fn_solotrucking.actualizarMensajeAlerta( n );
            o.addClass( "error" );
            o.focus();
            return false;
        } else {                     
            return true;        
        }
     },
     checkLength : function( o, n, min, max ) {
        if ( o.val().length > max || o.val().length < min ) {
            fn_solotrucking.actualizarMensajeAlerta( "Length of " + n + " must be between " + min + " and " + max + "."  );
            o.addClass( "error" );
            o.focus();
            return false;    
        } else {             
            return true;                     
        }                    
     },
     inputnumero : function(){
        if(event.shiftKey)
                {
                    event.preventDefault();
                }
             if (event.keyCode == 46 || event.keyCode == 8 || event.keyCode == 9){}
             else {
                    if (event.keyCode < 95) {
                        if (event.keyCode < 48 || event.keyCode > 57) {
                            event.preventDefault();
                        }
                    }
                    else {
                        if (event.keyCode < 96 || event.keyCode > 105) {
                            event.preventDefault();
                        }
                    }
             }
     }, 
     inputdecimals : function(){
         if(event.shiftKey){event.preventDefault();}
          if (event.keyCode != 46 && event.keyCode != 8 && event.keyCode != 9 && event.keyCode != 110 && event.keyCode != 190){
             if (event.keyCode < 95) {
                 if (event.keyCode < 48 || event.keyCode > 57) {event.preventDefault();}
             }else{
                 if (event.keyCode < 96 || event.keyCode > 105) {event.preventDefault();}
             } 
          }  
     },
     mensaje : function(texto){
        $("#mensaje").empty().append(texto);
        $("#mensaje").dialog('open');
     },
     get_date : function (input_name){
        var t = new Date;
        var dia = fn_solotrucking.pad((parseInt(t.getDate())+0)+"",2,'0',STR_PAD_LEFT);
        var mes = fn_solotrucking.pad((parseInt(t.getMonth()) + 1)+"",2,'0',STR_PAD_LEFT);
        $(input_name).val(mes+"/"+dia+"/"+t.getFullYear());
      },
     pad : function(str, len, pad, dir){
        if (typeof(len) == "undefined") { var len = 0; }
        if (typeof(pad) == "undefined") { var pad = ' '; }
        if (typeof(dir) == "undefined") { var dir = STR_PAD_RIGHT; }

        if (len + 1 >= str.length) {
          switch (dir){
            case STR_PAD_LEFT:
              str = Array(len + 1 - str.length).join(pad) + str;
              break;
            case STR_PAD_BOTH:
              var right = Math.ceil((padlen = len - str.length) / 2);
              var left = padlen - right;
              str = Array(left+1).join(pad) + str + Array(right+1).join(pad);
              break;
            default:
              str = str + Array(len + 1 - str.length).join(pad);
              break;
          } // switch
        }
        return str;
      },
     daysInMonth : function( month, year) {
            
            var m = [ 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31]; 
            
            if (month != 2) 
                return m[month - 1]; 
            
            if (year%4 != 0) 
                return m[1]; 
            
            if (year%100 == 0 && year%400 != 0) 
                return m[1]; 
            
            return m[1] + 1; 
      },
     obtener_fechas : function() {
            
            var today = new Date();

            var d = today.getDate();
            var m = today.getMonth();
            var m = m + 1;

            var y = today.getFullYear();

            var m2 = m;

            if( m < 10 ) {m = '0' + m;}
            
            if( d < 10 ) {d = '0' + d;}
            
            var fecha_mes_inicio = m + '/' + '01' + '/' + y;
            var fecha_hoy = m + '/' + d + '/' + y;

            d = fn_solotrucking.daysInMonth(m2, y);

            var fecha_mes_fin = m + '/' + d + '/' + y;
            
            fechas = new Array(fecha_hoy, fecha_mes_inicio, fecha_mes_fin);
            return fechas;
        },
     calcular_decimales : function(value,decimals){
         var num = parseFloat(value).toFixed(decimals);
         return value;
     }  
}
var struct_data_post = {
    action         : "",
    domroot        : "",
    domroot_2      : "",
    edit_mode : "",
    parse         : function(){
        var obj_dom = $(this.domroot +" :input:not(:button)");
        var id_len = obj_dom.length;
        var datakey = {};
        datakey["accion"] = this.action;
        datakey["tupla_existente"] = this.tupla_existente;
        datakey["edit_mode"] = this.edit_mode; 
        for(i=0; i<id_len; i++){
            if(obj_dom[i].type=="checkbox"){
                check=$(this.domroot+" :checkbox[id="+obj_dom[i].id+"]").is(":checked");
                if(check){datakey[obj_dom[i].id] ="1";}else{datakey[obj_dom[i].id] ="0";}
            }else{
                datakey[obj_dom[i].id] = $(this.domroot+" :input[id="+obj_dom[i].id+"]").val();
            }
        }
        //Domroot2:
        if(struct_data_post.domroot_2 != ''){
            var obj_dom = $(struct_data_post.domroot_2 +" :input:not(:button)"); 
            var id_len = obj_dom.length;
            for(i=0; i<id_len; i++){
                if(obj_dom[i].type=="checkbox"){
                    check = $(this.domroot_2+" :checkbox[id="+obj_dom[i].id+"]").is(":checked");
                    if(check){datakey[obj_dom[i].id] ="1";}else{datakey[obj_dom[i].id] ="0";}
                }else{
                    datakey[obj_dom[i].id] = $(this.domroot_2+" :input[id="+obj_dom[i].id+"]").val();
                }
            }    
        }
        
        
        return datakey;
    }
}