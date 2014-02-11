jQuery(document).ready(function(){ 
    /* regenerate API key */
    jQuery('body').on('click', '.regen-key', function(e){
        e.preventDefault();
        $this = jQuery(this);
        jQuery.ajax({
            'type'  : 'POST',
            'url'   : ajaxurl,
            'data' : {
                'action'    : 'rtmedia_api_regenerate_key',
                'nonce' : jQuery('#regenerate_api_key').val(),
                'current': jQuery('#rtmedia-login').val(),
                'login' : jQuery('#rtmedia-current').val(),
            },
            'success' : function(res){
                if(res){
                 jQuery('#rtmedia_api_key').val(res);
                }
            } 
        })
    });
});


