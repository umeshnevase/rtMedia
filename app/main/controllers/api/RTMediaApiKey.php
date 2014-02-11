<?php
/**
 * File Description
 * @author Umesh Kumar <umeshsingla05@gmail.com>
 */

class RTMediaApiKey{
    var $user = '';

    function __construct(){
        add_action( 'show_user_profile', array($this, 'rtmedia_api_key' ) );
        add_action( 'edit_user_profile', array( $this, 'rtmedia_api_key') );
     }
    function rtmedia_api_key(){
        global $current_user;
        $this->user = $current_user;
        $user_key = get_user_meta($this->user->ID, 'rtmedia_api_key', TRUE);
        if(empty($user_key)){
            $this->rtmedia_api_update_user_key();
        }
        $this->rtmedia_display_api_key();
    }
    function rtmedia_api_update_user_key(){
        $user_key = $this->rtmedia_api_generate_user_key();
        if(!empty($user_key)){
            update_user_meta($this->user->ID, 'rtmedia_api_key', $user_key);
        }
    }
    function rtmedia_api_generate_user_key(){
        $string = '08~'.$this->user->ID.'~'.$this->user->data->user_login.'~kumar';
        return sha1($string.  current_time('timestamp').rand(1,9));
    }
    function rtmedia_display_api_key(){ ?>
        <h3><?php _e('RTMedia API Key', 'rtmedia'); ?></h3>

	<table class="form-table">

		<tr>
                    <th><label for="api_key"><?php _e('API Key', 'rtmedia'); ?></label></th>

                    <td>
                        <input type="text" name="rtmedia_api_key" id="rtmedia_api_key" value="<?php echo esc_attr( get_the_author_meta( 'rtmedia_api_key', $this->user->ID ) ); ?>" class="regular-text" /><br />
                    </td>
		</tr>

	</table><?php
    }
}