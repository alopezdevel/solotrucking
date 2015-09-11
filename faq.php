<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, maximum-scale=1">

<title>SoloTrucking - Commercial Truck Insurance</title>
<link rel="icon" href="images/favicon.png" type="image/png">
<link rel="shortcut icon" href="favicon.ico" type="img/x-icon">
<link href="css/bootstrap.css" rel="stylesheet" type="text/css">
<link href="css/style.css" rel="stylesheet" type="text/css">
<link href="css/font-awesome.css" rel="stylesheet" type="text/css">
<link href="css/responsive.css" rel="stylesheet" type="text/css">
<link href="css/animate.css" rel="stylesheet" type="text/css">

<script type="text/javascript" src="js/jquery.1.8.3.min.js"></script>
<script type="text/javascript" src="js/bootstrap.js"></script>
<script type="text/javascript" src="js/jquery-scrolltofixed.js"></script>
<script type="text/javascript" src="js/jquery.easing.1.3.js"></script>
<script type="text/javascript" src="js/jquery.isotope.js"></script>
<script type="text/javascript" src="js/wow.js"></script>
<script type="text/javascript" src="js/classie.js"></script>

	<!---FAQ--->
	<link rel="stylesheet" href="css/faq.css">
    <script>document.documentElement.className = "js";</script>
    <script src="js/faq/jquery.collapse.js"></script>
    <script src="js/faq/jquery.collapse_storage.js"></script>
    <script src="js/faq/jquery.collapse_cookie_storage.js"></script>

	
</head>
<body>
<div style="overflow:hidden;" id="home">
<nav class="main-nav-outer" id="test"><!--main-nav-start-->
	<div class="container">
		<a href="./" class="img-logo"><img  src="images/nav/img-logo.png" alt="logo"></a>
		<ul class="top-nav">
			<li><a href="system/login" class="login-btn" title="Log In" target="_blank"><span>My Account</span></a></li>
			<li><a href="https://www.facebook.com/solotrucking" class="icon facebook" title="follow us in Facebook!" target="_blank"><span></span></a></li>
			<li style="display:none"><a href="https://www.youtube.com/" class="icon youtube" title="follow us in YouTube!" target="_blank"><span></span></a></li>
			<li><a href="https://twitter.com/" class="icon twitter" title="follow us in Twitter!" target="_blank"><span></span></a></li>
			<li><a href="https://www.google.com/" class="icon google" title="follow us in Google +!" target="_blank"><span></span></a></li>
		</ul>
        <ul class="main-nav">
        	<li><a href="./">Home</a></li>
            <li><a href="aboutus">About Us</a></li>
            <li><a href="products">Products</a></li>
            <li><a href="system/login" target="_blank" style="display: none;">Claims</a></li>
            <li><a href="providers">Providers</a></li>
            <li><a href="getaquote/intro" style="display: none;">Get a Quote</a></li>
            <li><a href="./#contact">Contact Us</a></li>
        </ul>
        <a class="res-nav_click right" href="#"><i class="fa-bars"></i></a>
    </div>
</nav><!--main-nav-end-->
<!--- HEADER----->
<div class="header section-faq">
	<h1 class="wow animated fadeInLeft delay-02s">Frequently asked questions</h1>
	<h3 class="wow animated fadeInRight delay-03s">Guides, tutorials, videos and external links to help you.</h3>
</div>
<!--- TERMINA HEADER----->
<section class="main-section"><!--main-section-start-->
	<div class="container">
   		<!-- BEGIN Custom open and close -->
        <p> If you have a question, you may find the answer you're looking for here. If not, please contact us and we'll be happy to answer it for you.</p>
      <h2>Registration and Login Help</h2>
      <div id="custom-show-hide-example">
        <h3>Why should I register?</h3>
      		<div>
          		<p>The use of our online system allows you to consult and do their transactions from anywhere with internet access, which helps save time.</p>
        	</div>
        <h3>What do I need to register?</h3>
        	<p>To register in our system, you need to contact us so we can otorgorarnos the information needed to create your account.</p>
        <h3>What do I do if I forget my User ID or Password?</h3>
        	<p>If you have forgotten your User ID or Password, please return to the Login page and click on the "Forgot your User ID or Password?" link and follow the steps to retrieve your User ID and reset your Password.</p>
      </div>
      <script>
        new jQueryCollapse($("#custom-show-hide-example"), {
          open: function() {
            this.slideDown(150);
          },
          close: function() {
            this.slideUp(150);
          }
        });
      </script>
      <!-- END Custom open and close -->
	</div>
</section><!--main-section-end-->
<div class="footer-line"></div>
<footer class="footer">
    <div class="container">
    	 <div class="nav_foot">
    	 	<a href="./" class="active">Home</a>    /    
    	 	<a href="aboutus">About Us</a>    /    
    	 	<a href="products">Products</a>     /    
            <a href="system/login" target="_blank" style="display: none;">Claims</a>       
            <a href="providers">Providers</a>     /    
    	 	<a href="getaquote/intro" style="display: none;">Get a Quote</a>       
    	 	<a href="./#contact">Contact Us</a>    
    	 </div>
    	 <div class="nav_foot_2">
    	 <a href="terms_conditions">Terms & Conditions</a>  |   
    	 <a href="privacypolicy">Privacy Policy</a>  |   
    	 <a href="faq">F.A.Q.</a></div>   
    	    
   	</div>
</footer>
<div class="copyright">SoloTrucking 2015 . © All rights reserved.</div>

<script type="text/javascript">
    $(document).ready(function(e) {
        $('#test').scrollToFixed();
        $('.res-nav_click').click(function(){
            $('.main-nav').slideToggle();
            return false    
            
        });
        
    });
</script>

  <script>
    wow = new WOW(
      {
        animateClass: 'animated',
        offset:       100
      }
    );
    wow.init();
    document.getElementById('').onclick = function() {
      var section = document.createElement('section');
      section.className = 'wow fadeInDown';
      this.parentNode.insertBefore(section, this);
    };
  </script>


<script type="text/javascript">
	$(window).load(function(){
		
		$('a').bind('click',function(event){
			var $anchor = $(this);
			
			$('html, body').stop().animate({
				scrollTop: $($anchor.attr('href')).offset().top - 80
			}, 1500,'easeInOutExpo');
			/*
			if you don't want to use the easing effects:
			$('html, body').stop().animate({
				scrollTop: $($anchor.attr('href')).offset().top
			}, 1000);
			*/
			event.preventDefault();
		});
	})
</script>

<script type="text/javascript">

$(window).load(function(){
  
  
  var $container = $('.portfolioContainer'),
      $body = $('body'),
      colW = 375,
      columns = null;

  
  $container.isotope({
    // disable window resizing
    resizable: true,
    masonry: {
      columnWidth: colW
    }
  });
  
  $(window).smartresize(function(){
    // check if columns has changed
    var currentColumns = Math.floor( ( $body.width() -30 ) / colW );
    if ( currentColumns !== columns ) {
      // set new column count
      columns = currentColumns;
      // apply width to container manually, then trigger relayout
      $container.width( columns * colW )
        .isotope('reLayout');
    }
    
  }).smartresize(); // trigger resize to set container width
  $('.portfolioFilter a').click(function(){
        $('.portfolioFilter .current').removeClass('current');
        $(this).addClass('current');
 
        var selector = $(this).attr('data-filter');
        $container.isotope({
			
            filter: selector,
         });
         return false;
    });
  
});

</script>
</body>
</html>