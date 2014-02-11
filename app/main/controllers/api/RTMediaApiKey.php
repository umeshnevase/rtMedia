<?php
/**
 * File Description
 * @author Umesh Kumar <umeshsingla05@gmail.com>
 */

class RTMediaApiKey{
    var $user = '';

    function __construct(){
        add_action( 'wp_ajax_rtmedia_api_regenerate_key', array($this, 'rtmedia_api_regenerate_key') );
        add_action( 'show_user_profile', array($this, 'rtmedia_api_key' ) );
        add_action( 'edit_user_profile', array( $this, 'rtmedia_api_key') );
     }
    function rtmedia_api_key($current_user){
        if(empty($current_user)){ return; }
        $this->user = $current_user;
        $user_key = get_user_meta($this->user->ID, 'rtmedia_api_key', TRUE);
        if(empty($user_key)){
            $this->rtmedia_api_update_user_key();
        }
        $this->rtmedia_display_api_key();
    }
    function rtmedia_api_update_user_key($user_id = false, $user_login = false){
        if( empty($user_id ) || empty($user_login) ){
            $user_id = $this->user->ID;
            $user_login = $this->user->data->user_login;
        }
        $user_key = $this->rtmedia_api_generate_user_key($user_id, $user_login);
        if(!empty($user_key)){
             update_user_meta($user_id, 'rtmedia_api_key', $user_key);
        }
    }
    function rtmedia_api_generate_user_key($user_id = '', $user_login = ''){
        $string = 'enc~'.$user_id.'~'.$user_login.'~rtmedia';
        return sha1($string.  current_time('timestamp').rand(1,99));
    }
    function rtmedia_display_api_key(){ ?>
        <h3><?php _e('RTMedia API Key', 'rtmedia'); ?></h3>

	<table class="form-table">

		<tr>
                    <th><label for="api_key"><?php _e('API Key', 'rtmedia'); ?></label></th>

                    <td>
                        <input type="text" name="rtmedia_api_key" id="rtmedia_api_key" value="<?php echo esc_attr( get_the_author_meta( 'rtmedia_api_key', $this->user->ID ) ); ?>" class="regular-text" /><br />
                        <a class="button regen-key"><?php _e('regenerate', 'rtmedia'); ?></a>
                        <?php wp_nonce_field('regenerate-key', 'regenerate_api_key'); ?>
                        <input type="hidden" id="rtmedia-login" name="rtmedia-login" value="<?php echo $this->user->data->user_login; ?>" />
                        <input type="hidden" id="rtmedia-current" name="rtmedia-current" value="<?php echo $this->user->ID; ?>" />
                    </td>
		</tr>

	</table><?php
    }
    function rtmedia_api_regenerate_key(){
        if(empty($_POST)) { return false; }
        if(!wp_verify_nonce($_POST['nonce'], 'regenerate-key')) { return false; }
        $user_login = $_POST['current'];
        $user_id = $_POST['login'];
        $this->rtmedia_api_update_user_key($user_id, $user_login);
        $key = get_user_meta($user_id, 'rtmedia_api_key', TRUE);
        if(!empty($key)){
            echo $key;
        }
        return false;
        die(1);
    }
}