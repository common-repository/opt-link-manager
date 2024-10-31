(function($){
	
/*	// for the test of newval
	wp.customize('olm_def_thumbnail_image', function(value){
		value.bind(function(newval){
			alert(newval);
		});
	});
*/

	// background image src
	wp.customize('olm_background_image',function(value){
		value.bind(function(newval){
			console.log('aptl_bgi');
			if(newval==false){
				
				$('.e-content .link-set-ul-li').css('background-image', 'none');
				
			}else{
				
				$('.e-content .link-set-ul-li').css({
					'background-image': 'url('+newval+')'
				});
			}
		});
	});

	// background image size
	wp.customize('olm_background_image_size',function(value){
		value.bind(function(newval){
			console.log('related_links_bgi_size');
			$('.link-set-ul-li').css('background-size', newval);
		});
	});	
	
	// background image position row
	wp.customize('olm_background_image_position_row',function(value){
		value.bind(function(newval){
			console.log('related_links_bgi_size');
			$('.link-set-ul-li').css('background-position-y', newval);
		});
	});	
	
	// background image position column
	wp.customize('olm_background_image_position_column',function(value){
		value.bind(function(newval){
			console.log('related_links_bgi_size');
			$('.link-set-ul-li').css('background-position-x', newval);
		});
	});	
	
	// background image repeat
	wp.customize('olm_background_image_repeat',function(value){
		value.bind(function(newval){
			console.log('related_links_bgi_size');
			if(newval == 'no-repeat'){
				$('.link-set-ul-li').css('background-repeat', 'no-repeat');
			}else if(newval == 'repeat'){
				$('.link-set-ul-li').css('background-repeat', 'repeat');
			}else if(newval == 'repeat-x'){
				$('.link-set-ul-li').css('background-repeat', 'repeat no-repeat');
			}else if(newval == 'repeat-y'){
				$('.link-set-ul-li').css('background-repeat', 'no-repeat repeat');
			}
		});
	});	
	
	// default thumbnail
	wp.customize('olm_def_thumbnail_image',function(value){
		value.bind(function(newval){
			console.log('aptl_thumbnail');
			if(newval==false){
				
				var defImageURL = './wp-content/plugins/Googlist-aptl/images/no-img.png';
				
				$('.link-set-def-img-div').css('background-image', 'url('+defImageURL+')');
				
			}else{
				
				$('.link-set-def-img-div').css('background-image', 'url('+newval+')');
			}
		});
	});

	// border style type
	wp.customize('olm_style_border', function(value){
		value.bind(function(newval){
			console.log('aptl_border');
			if(newval=='no-border'){
			
				$('.link-set-ul-li').css('border', 'none');
				$('.link-set-ul-li').css('box-shadow', 'none');
			
			}else if(newval=='border'){
				
				$('.link-set-ul-li').css('border', 'solid #666 1px');
				$('.link-set-ul-li').css('box-shadow', 'none');
				
			}else if(newval=='box-shadow'){
				
				$('.link-set-ul-li').css('border', 'none');
				$('.link-set-ul-li').css('box-shadow', '0 0 5px');
				
			}
		});
	});

	// border radius px
	wp.customize('olm_style_border_radius_px', function(value){
		value.bind(function(newval){
			console.log('aptl_border_radius');
			$('.link-set-ul-li,.link-set-ul-li-with-background-image,.link-set-def-img-div,.link-set-img-div').css('border-radius', newval+'px');
		});
	});
	
	// background color
	wp.customize('olm_background_color', function(value){
		value.bind(function(newval){
			console.log('aptl_bgc');
			if(newval!=false){
				$('.link-set-ul-li').css('background-color', newval);
			}else{
				$('.link-set-ul-li').css('background-color', 'none');				
			}
		});
	});
	
	// text color
	wp.customize('olm_text_color', function(value){
		value.bind(function(newval){
			console.log('aptl_bgc');
			if(newval!=''){
				$('.link-set-text-description-p').css('color', newval);
			}else{
				$('.link-set-text-description-p').css('color', 'none');
			}
		});
	});

	// text link color
	wp.customize('olm_text_link_color', function(value){
		value.bind(function(newval){
			console.log('aptl_bgc');
			if(newval!=''){
				$('.link-set-text-name-a, .link-set-text-name-a:link, .link-set-text-name-a:visited').css('color', newval);
			}else{
				$('.shortcode-post-link-title-p a, .shortcode-post-link-title-p a:link, .shortcode-post-link-title-p a:visited').css('color', 'none');
			}
		});
	});

})( jQuery );