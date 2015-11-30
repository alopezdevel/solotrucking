var fn_popups = {

    centrar_ventana : function(ventana){
        //window.scroll(0,0);
        var windowWidth = document.documentElement.clientWidth;
        var windowHeight = document.documentElement.clientHeight;
        var popupHeight = $("#"+ventana).height();
        var popupWidth = $("#"+ventana).width();
        //if((windowHeight/2-popupHeight/2) <0){ var finalHeight = 80;}else{var finalHeight = windowHeight/2-popupHeight/2;}
        //centrando
        $("#"+ventana).css({
            "position": "absolute",
            "top": 20,
            "left": windowWidth/2-popupWidth/2
        });
    },
    resaltar_ventana : function(ventana){
        //e_conta.centrar_ventana(ventana);
        //if(e_conta.popupStatus==0){
            $(".overlay-background").show();
            $("#"+ventana).show();
            //e_conta.popupStatus = 1;
       // }
    },
    cerrar_ventana : function(ventana){
       // if(e_conta.popupStatus==1){
            $(".overlay-background").hide();
            $("#"+ventana).hide();
            //e_conta.popupStatus = 0;
       // }
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