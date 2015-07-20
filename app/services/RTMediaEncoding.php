<?php

/**
 * Description of BPMediaEncoding
 *
 * @author Joshua Abenazer <joshua.abenazer@rtcamp.com>
 */
class RTMediaEncoding {

	protected $api_url = 'http://api.rtcamp.com/';
	protected $sandbox_testing = 0;
	protected $merchant_id = 'paypal@rtcamp.com';
	public $uploaded = array();
	public $api_key = false;
	public $stored_api_key = false;

	public function __construct( $no_init = false ) {
		$this->api_key = get_site_option( 'rtmedia-encoding-api-key' );
		$this->stored_api_key = get_site_option( 'rtmedia-encoding-api-key-stored' );

		// Do not add any action
		$no_init = true;

		if ( $no_init ){
			return;
		}
		if ( is_admin() && $this->api_key ) {
			add_action( 'rtmedia_before_default_admin_widgets', array( $this, 'usage_widget' ) );
		}


		if ( $this->api_key ) {
			// store api key as different db key if user disable encoding service
			if( !$this->stored_api_key ){
				$this->stored_api_key = $this->api_key;
				update_site_option( 'rtmedia-encoding-api-key-stored', $this->stored_api_key );
			}

			$usage_info = get_site_option( 'rtmedia-encoding-usage' );

			if ( $usage_info ) {
				if ( isset( $usage_info[ $this->api_key ]->status ) && $usage_info[ $this->api_key ]->status ) {
					if ( isset( $usage_info[ $this->api_key ]->remaining ) && $usage_info[ $this->api_key ]->remaining > 0 ) {
						if ( $usage_info[ $this->api_key ]->remaining < 524288000 && ! get_site_option( 'rtmedia-encoding-usage-limit-mail' ) ){
							$this->nearing_usage_limit( $usage_info );
						}
						elseif ( $usage_info[ $this->api_key ]->remaining > 524288000 && get_site_option( 'rtmedia-encoding-usage-limit-mail' ) ){
							update_site_option( 'rtmedia-encoding-usage-limit-mail', 0 );
						}
					}
				}
			}
		}

		add_action( 'wp_ajax_rtmedia_unsubscribe_encoding_service', array( $this, 'unsubscribe_encoding' ) );
		add_action( 'wp_ajax_rtmedia_hide_encoding_notice', array( $this, 'hide_encoding_notice' ), 1 );
		//add_action('wp_ajax_rtmedia_regenerate_thumbnails', array($this, 'rtmedia_regenerate_thumbnails'), 1);
	}

	/**
	 *
	 * @param type $media_ids
	 * @param type $file_object
	 * @param type $uploaded
	 * @param string $autoformat thumbnails for genrating thumbs only
	 */
	function encoding( $media_ids, $file_object, $uploaded, $autoformat = true ) {
		foreach ( $file_object as $key => $single ) {
			$type_arry = explode( ".", $single[ 'url' ] );
			$type = strtolower( $type_arry[ sizeof( $type_arry ) - 1 ] );
			$not_allowed_type = array( "mp3" );
			if ( preg_match( '/video|audio/i', $single[ 'type' ], $type_array ) && ! in_array( $single[ 'type' ], array( 'audio/mp3' ) ) && ! in_array( $type, $not_allowed_type ) ) {
				$options = rtmedia_get_site_option( 'rtmedia-options' );
				$options_vedio_thumb = $options[ 'general_videothumbs' ];
				if ( $options_vedio_thumb == "" )
					$options_vedio_thumb = 3;

				/**  fORMAT * */
				if ( $single[ 'type' ] == 'video/mp4' || $type == "mp4" )
					$autoformat = "thumbnails";

				$query_args = array( 'url' => urlencode( $single[ 'url' ] ),
					'callbackurl' => urlencode( trailingslashit( home_url() ) . "index.php" ),
					'force' => 0,
					'size' => filesize( $single[ 'file' ] ),
					'formats' => ($autoformat === true) ? (($type_array[ 0 ] == 'video') ? 'mp4' : 'mp3') : $autoformat,
					'thumbs' => $options_vedio_thumb,
					'rt_id' => $media_ids[ $key ] );
				$encoding_url = $this->api_url . 'job/new/';
				$upload_url = add_query_arg( $query_args, $encoding_url . $this->api_key );
				//error_log(var_export($upload_url, true));
				//var_dump($upload_url);
				$upload_page = wp_remote_get( $upload_url, array( 'timeout' => 200 ) );

				//error_log(var_export($upload_page, true));
				if ( ! is_wp_error( $upload_page ) && ( ! isset( $upload_page[ 'headers' ][ 'status' ] ) || (isset( $upload_page[ 'headers' ][ 'status' ] ) && ($upload_page[ 'headers' ][ 'status' ] == 200))) ) {
					$upload_info = json_decode( $upload_page[ 'body' ] );
					if ( isset( $upload_info->status ) && $upload_info->status && isset( $upload_info->job_id ) && $upload_info->job_id ) {
						$job_id = $upload_info->job_id;
						update_rtmedia_meta( $media_ids[ $key ], 'rtmedia-encoding-job-id', $job_id );
						$model = new RTMediaModel();
						$model->update( array( 'cover_art' => '0' ), array( 'id' => $media_ids[ $key ] ) );
					} else {
//                        remove_filter('bp_media_plupload_files_filter', array($bp_media_admin->bp_media_encoding, 'allowed_types'));
//                        return parent::insertmedia($name, $description, $album_id, $group, $is_multiple, $is_activity, $parent_fallback_files, $author_id, $album_name);
					}
				}
				$this->update_usage( $this->api_key );
			}
		}
	}

	public function bypass_video_audio( $flag, $file ) {
		if ( isset( $file[ 'type' ] ) ) {
			$fileinfo = explode( '/', $file[ 'type' ] );
			if ( in_array( $fileinfo[ 0 ], array( 'audio', 'video' ) ) )
				$flag = true;
		}
		return $flag;
	}

	public function is_valid_key( $key ) {
		$validate_url = trailingslashit( $this->api_url ) . 'api/validate/' . $key;
		$validation_page = wp_remote_get( $validate_url, array( 'timeout' => 20 ) );
		if ( ! is_wp_error( $validation_page ) ) {
			$validation_info = json_decode( $validation_page[ 'body' ] );
			$status = $validation_info->status;
		} else {
			$status = false;
		}
		return $status;
	}

	public function update_usage( $key ) {
		$usage_url = trailingslashit( $this->api_url ) . 'api/usage/' . $key;
		$usage_page = wp_remote_get( $usage_url, array( 'timeout' => 20 ) );
		if ( ! is_wp_error( $usage_page ) )
			$usage_info = json_decode( $usage_page[ 'body' ] );
		else
			$usage_info = NULL;
		update_site_option( 'rtmedia-encoding-usage', array( $key => $usage_info ) );
		return $usage_info;
	}

	public function nearing_usage_limit( $usage_details ) {
		$subject = __( 'rtMedia Encoding: Nearing quota limit.', 'rtmedia' );
		$message = __( '<p>You are nearing the quota limit for your rtMedia encoding service.</p><p>Following are the details:</p><p><strong>Used:</strong> %s</p><p><strong>Remaining</strong>: %s</p><p><strong>Total:</strong> %s</p>', 'rtmedia' );
		$users = get_users( array( 'role' => 'administrator' ) );
		if ( $users ) {
			foreach ( $users as $user )
				$admin_email_ids[] = $user->user_email;
			add_filter( 'wp_mail_content_type', create_function( '', 'return "text/html";' ) );
			wp_mail( $admin_email_ids, $subject, sprintf( $message, size_format( $usage_details[ $this->api_key ]->used, 2 ), size_format( $usage_details[ $this->api_key ]->remaining, 2 ), size_format( $usage_details[ $this->api_key ]->total, 2 ) ) );
		}
		update_site_option( 'rtmedia-encoding-usage-limit-mail', 1 );
	}

	public function usage_quota_over() {
		$usage_details = get_site_option( 'rtmedia-encoding-usage' );
		if ( ! $usage_details[ $this->api_key ]->remaining ) {
			$subject = __( 'rtMedia Encoding: Usage quota over.', 'rtmedia' );
			$message = __( '<p>Your usage quota is over. Upgrade your plan</p><p>Following are the details:</p><p><strong>Used:</strong> %s</p><p><strong>Remaining</strong>: %s</p><p><strong>Total:</strong> %s</p>', 'rtmedia' );
			$users = get_users( array( 'role' => 'administrator' ) );
			if ( $users ) {
				foreach ( $users as $user )
					$admin_email_ids[] = $user->user_email;
				add_filter( 'wp_mail_content_type', create_function( '', 'return "text/html";' ) );
				wp_mail( $admin_email_ids, $subject, sprintf( $message, size_format( $usage_details[ $this->api_key ]->used, 2 ), 0, size_format( $usage_details[ $this->api_key ]->total, 2 ) ) );
			}
			update_site_option( 'rtmedia-encoding-usage-limit-mail', 1 );
		}
	}


	public function encoding_subscription_form( $name = 'No Name', $price = '0', $force = false ) {
		if ( $this->api_key )
			$this->update_usage( $this->api_key );
		$action = $this->sandbox_testing ? 'https://sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr';		
		$return_page = esc_url( add_query_arg( array( 'page' => 'rtmedia-addons' ), ( is_multisite() ? network_admin_url( 'admin.php' ) : admin_url( 'admin.php' ) ) ) );

		$usage_details = get_site_option( 'rtmedia-encoding-usage' );
		if ( isset( $usage_details[ $this->api_key ]->plan->name ) && (strtolower( $usage_details[ $this->api_key ]->plan->name ) == strtolower( $name )) && $usage_details[ $this->api_key ]->sub_status && ! $force ) {
			$form = '<button data-plan="' . $name . '" data-price="' . $price . '" type="submit" class="button bpm-unsubscribe">' . __( 'Unsubscribe', 'rtmedia' ) . '</button>';
			$form .= '<div id="bpm-unsubscribe-dialog" title="Unsubscribe">
  <p>' . __( 'Just to improve our service we would like to know the reason for you to leave us.' ) . '</p>
  <p><textarea rows="3" cols="36" id="bpm-unsubscribe-note"></textarea></p>
</div>';
		} else {
			$form = '<form method="post" action="' . $action . '" class="paypal-button" target="_top">
                        <input type="hidden" name="button" value="subscribe">
                        <input type="hidden" name="item_name" value="' . ucfirst( $name ) . '">

                        <input type="hidden" name="currency_code" value="USD">


                        <input type="hidden" name="a3" value="' . $price . '">
                        <input type="hidden" name="p3" value="1">
                        <input type="hidden" name="t3" value="M">

                        <input type="hidden" name="cmd" value="_xclick-subscriptions">

                        <!-- Merchant ID -->
                        <input type="hidden" name="business" value="' . $this->merchant_id . '">


                        <input type="hidden" name="custom" value="' . $return_page . '">

                        <!-- Flag to no shipping -->
                        <input type="hidden" name="no_shipping" value="1">

                        <input type="hidden" name="notify_url" value="' . trailingslashit( $this->api_url ) . 'subscribe/paypal">

                        <!-- Flag to post payment return url -->
                        <input type="hidden" name="return" value="' . trailingslashit( $this->api_url ) . 'payment/process">


                        <!-- Flag to post payment data to given return url -->
                        <input type="hidden" name="rm" value="2">

                        <input type="hidden" name="src" value="1">
                        <input type="hidden" name="sra" value="1">

                        <input type="image" src="http://www.paypal.com/en_US/i/btn/btn_subscribe_SM.gif" name="submit" alt="Make payments with PayPal - it\'s fast, free and secure!">
                    </form>';
		}
		return $form;
	}

	public function usage_widget() {
		$usage_details = get_site_option( 'rtmedia-encoding-usage' );
		$content = '';
		if ( $usage_details && isset( $usage_details[ $this->api_key ]->status ) && $usage_details[ $this->api_key ]->status ) {
			if ( isset( $usage_details[ $this->api_key ]->plan->name ) )
				$content .= '<p><strong>' . __( 'Current Plan', 'rtmedia' ) . ':</strong> ' . $usage_details[ $this->api_key ]->plan->name . ($usage_details[ $this->api_key ]->sub_status ? '' : ' (' . __( 'Unsubscribed', 'rtmedia' ) . ')') . '</p>';
			if ( isset( $usage_details[ $this->api_key ]->used ) )
				$content .= '<p><span class="encoding-used"></span><strong>' . __( 'Used', 'rtmedia' ) . ':</strong> ' . (($used_size = size_format( $usage_details[ $this->api_key ]->used, 2 )) ? $used_size : '0MB') . '</p>';
			if ( isset( $usage_details[ $this->api_key ]->remaining ) )
				$content .= '<p><span class="encoding-remaining"></span><strong>' . __( 'Remaining', 'rtmedia' ) . ':</strong> ' . (($remaining_size = size_format( $usage_details[ $this->api_key ]->remaining, 2 )) ? $remaining_size : '0MB') . '</p>';
			if ( isset( $usage_details[ $this->api_key ]->total ) )
				$content .= '<p><strong>' . __( 'Total', 'rtmedia' ) . ':</strong> ' . size_format( $usage_details[ $this->api_key ]->total, 2 ) . '</p>';
			$usage = new rtProgress();
			$content .= $usage->progress_ui( $usage->progress( $usage_details[ $this->api_key ]->used, $usage_details[ $this->api_key ]->total ), false );
			if ( $usage_details[ $this->api_key ]->remaining <= 0 )
				$content .= '<div class="error below-h2"><p>' . __( 'Your usage limit has been reached. Upgrade your plan.', 'rtmedia' ) . '</p></div>';
		} else {
			$content .= '<div class="error below-h2"><p>' . __( 'Your API key is not valid or is expired.', 'rtmedia' ) . '</p></div>';
		}
		new RTMediaAdminWidget( 'rtmedia-encoding-usage', __( 'Encoding Usage', 'rtmedia' ), $content );
	}

	public function hide_encoding_notice() {
		update_site_option( 'rtmedia-encoding-service-notice', true );
		update_site_option( 'rtmedia-encoding-expansion-notice', true );
		echo true;
		die();
	}

	public function unsubscribe_encoding() {
		$unsubscribe_url = trailingslashit( $this->api_url ) . 'api/cancel/' . $this->api_key;
		$unsubscribe_page = wp_remote_post( $unsubscribe_url, array( 'timeout' => 120, 'body' => array( 'note' => $_GET[ 'note' ] ) ) );
		if ( ! is_wp_error( $unsubscribe_page ) && ( ! isset( $unsubscribe_page[ 'headers' ][ 'status' ] ) || (isset( $unsubscribe_page[ 'headers' ][ 'status' ] ) && ($unsubscribe_page[ 'headers' ][ 'status' ] == 200))) ) {
			$subscription_info = json_decode( $unsubscribe_page[ 'body' ] );
			if ( isset( $subscription_info->status ) && $subscription_info->status ) {
				echo json_encode( array( 'updated' => __( 'Your subscription was cancelled successfully', 'rtmedia' ), 'form' => $this->encoding_subscription_form( $_GET[ 'plan' ], $_GET[ 'price' ] ) ) );
			}
		} else {
			echo json_encode( array( 'error' => __( 'Something went wrong please try again.', 'rtmedia' ) ) );
		}
		die();
	}

	public function reencoding( $attachment, $autoformat = true ) {
		$rtmedia_model = new RTMediaModel();
		$media_array = $rtmedia_model->get( array( "media_id" => $attachment ) );
		$media_id = $media_array[ 0 ]->id;
		$attached_file = get_post_meta( $attachment, '_wp_attached_file' );
		$upload_path = trim( get_option( 'upload_path' ) );
		if ( empty( $upload_path ) || 'wp-content/uploads' == $upload_path ) {
			$dir = WP_CONTENT_DIR . '/uploads';
		} elseif ( 0 !== strpos( $upload_path, ABSPATH ) ) {
			// $dir is absolute, $upload_path is (maybe) relative to ABSPATH
			$dir = path_join( ABSPATH, $upload_path );
		} else {
			$dir = $upload_path;
		}
		$file = trailingslashit( $dir ) . $attached_file[ 0 ];
		$url = wp_get_attachment_url( $attachment );
		$file_name_array = explode( "/", $url );
		$file_name = $file_name_array[ sizeof( $file_name_array ) - 1 ];
		$file_object = array();
		$media_type = get_post_field( 'post_mime_type', $attachment );
		$media_type_array = explode( "/", $media_type );
		if ( $media_type_array[ 0 ] == "video" ) {
			$file_object[] = array(
				"file" => $file,
				"url" => $url,
				"name" => $file_name,
				"type" => $media_type
			);
			$this->encoding( array( $media_id ), $file_object, array(), $autoformat );
		}
	}

	function rtmedia_regenerate_thumbnails() {
		$this->reencoding( intval( $_REQUEST[ 'rtreencoding' ] ) );
		die();
	}

}

if ( isset( $_REQUEST[ 'rtreencoding' ] ) ) {
	$objRTMediaEncoding = new RTMediaEncoding( true );
	$objRTMediaEncoding->reencoding( intval( $_REQUEST[ 'rtreencoding' ] ) );
}