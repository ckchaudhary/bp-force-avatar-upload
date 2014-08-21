HSFA__.debug = false;
jQuery(document).ready(function($){
    //signup-avatar-wrapper
    $.fancybox({
        href: '#signup-avatar-wrapper', 
        modal: true
    });
    
    $( ".user-dp" ).on( "uploadstarted", function( event ){
	$("#signup-avatar-wrapper #crop_image").hide();
	$("#signup-avatar-wrapper .response").html( "uploading.." );
    });
    $( ".user-dp" ).on( "uploadfinished", function( event, response ) {
	if( response.status===true ){
	    $("#signup-avatar-wrapper .response").html( "" );
	    $("#signup-avatar-wrapper #crop_image").show();
	    $("#signup-avatar-wrapper #crop_image img").attr( 'src', response.message.url );
	    $("#signup-avatar-wrapper #image_src").val(response.message.url);
	    //hack to resize fancybox overlay
	    $(window).trigger('resize');
	    
	    if( $("#signup-avatar-wrapper .signup-avatar-submit").length == 0 ){
		var html = "<p><button class='signup-avatar-submit' onclick='return hsfa_finish_avatar_upload();'>Finish<span class='loading'></span></button></p>";
		$("#signup-avatar-wrapper .response").after(html);
		$("#signup-avatar-wrapper #crop_image").before(html);
	    }
	    
	    HSFA__.lastresponse = response;
	    
	    jQuery('#hsfc_uploaded_avatar').Jcrop({
		    /*onChange: showPreview,
		    onSelect: showPreview,*/
		    onSelect: updateCoords,
		    aspectRatio: response.message.crop.aspect_ratio,
		    setSelect: [ response.message.crop.crop_left, response.message.crop.crop_top, response.message.crop.crop_right, response.message.crop.crop_bottom ]
	    });
	    updateCoords({x: response.message.crop.crop_left, y: response.message.crop.crop_top, w: response.message.crop.crop_right, h: response.message.crop.crop_bottom});
	}
    });
    
    hsfa_ajaxform_options = {
	dataType : 'json',
	success:    function(response) { 
	    if( response.status ){
		$('#frm_hsfa_upload_avatar .response').html('Avatar uploaded successfuly!');
		window.location.href = response.redirect;
	    }
	    else{
		//hmmm.. it shouldn't come here actually
		$('#frm_hsfa_upload_avatar .response').html('Error! Request can not be processed at the moment');
	    }
	},
	error: function( jqXHR, textStatus, errorThrown ){
	    //dont know what else to write!
	    $('#frm_hsfa_upload_avatar .response').html('Error! Request can not be processed at the moment');
	}
    };
    
    $('#frm_hsfa_upload_avatar').ajaxForm(hsfa_ajaxform_options);
});

function updateCoords(c) {
	jQuery('#x').val(c.x);
	jQuery('#y').val(c.y);
	jQuery('#w').val(c.w);
	jQuery('#h').val(c.h);
}

function showPreview(coords) {
	if ( parseInt(coords.w) > 0 ) {
		var fw = HSFA__.message.full_width;
		var fh = HSFA__.message.full_width;
		var rx = fw / coords.w;
		var ry = fh / coords.h;

		jQuery( '#avatar-crop-preview' ).css({
			width: Math.round(rx * HSFA__.message.image0) + 'px',
			height: Math.round(ry * HSFA__.message.image1) + 'px',
			marginLeft: '-' + Math.round(rx * coords.x) + 'px',
			marginTop: '-' + Math.round(ry * coords.y) + 'px'
		});
	}
}

function hsfa_finish_avatar_upload(){
    jQuery( '.signup-avatar-submit' ).addClass('loading');
    jQuery( '#frm_hsfa_upload_avatar' ).submit();
    return false;
}