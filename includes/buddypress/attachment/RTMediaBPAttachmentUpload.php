<?php

	/**
	 * rtMedia attachment upload for BuddyPress activity / profile / group
	 *
	 * Class RTMediaBPAttachmentUpload
	 */
	class RTMediaBPAttachmentUpload {

		public function __construct(){

			// Do not proceed if BuddyPress is not active and if active, check whether it has attachment api or not
			if( rtm_is_bp_active() && rtm_is_bp_have_attachment_api() ){
				// filter to modify plupload parameters as per the BP Attachments API
				add_filter( 'rtmedia_modify_upload_params', array( $this, 'modify_plupload_params' ) );

				// handle ajax call to add attachment
				add_action( 'wp_ajax_rtmedia_bp_attachment_upload', array( $this, 'handle_bp_attachment_upload' ) );
			}
		}

		/**
		 * Modify plupload parameters for BP Attachments API
		 *
		 * @param $plupload_params
		 */
		function modify_plupload_params( $plupload_params ){

			// set upload url
			$plupload_params[ 'url' ] = admin_url( 'admin-ajax.php' );

			// add action parameter to handle wp_ajax_
			$plupload_params[ 'multipart_params' ][ 'action' ] = 'rtmedia_bp_attachment_upload';

			// set context and context_id
			// By default it will be profile context unless it is group page.
			$context = 'profile';
			$context_id = get_current_user_id();
			if( bp_is_group() ){
				$current_group = groups_get_current_group();
				$context = 'group';
				$context_id = $current_group->id;
			}
			$plupload_params[ 'multipart_params' ][ 'context' ] = $context;
			$plupload_params[ 'multipart_params' ][ 'context_id' ] = $context_id;

			return $plupload_params;
		}

		/**
		 * Handle uploading media and attach it to activity
		 */
		function handle_bp_attachment_upload(){
			$nonce = $_REQUEST[ 'rtmedia_upload_nonce' ];
			if ( wp_verify_nonce( $nonce, 'rtmedia_upload_nonce' ) ){

				// Insert WordPress attachment
				$attachment = new RTMediaBPAttachment();
				$uploaded = $attachment->insert_attachment( $_FILES, $_POST );

				// Insert activity
				$activity_id = $uploaded->activity_id = $this->insert_activity( $uploaded );

				// Prepare output
				$rtmedia_id = $uploaded->id;
				$permalink = get_rtmedia_permalink( $rtmedia_id );
				$media_type = $uploaded->media_type;
				$cover_art = $uploaded->cover_art;

				if ( $media_type == "photo" ){
					$thumb_image = rtmedia_image( "rt_media_thumbnail", $rtmedia_id, false );
				} elseif ( $media_type == "music" ) {
					$thumb_image = $cover_art;
				} else {
					$thumb_image = "";
				}

				// $_POST[ "rtmedia_update" ] will be set for activity attachments
				if ( isset ( $_POST[ "rtmedia_update" ] ) && $_POST[ "rtmedia_update" ] == "true" ){
					if ( preg_match( '/(?i)msie [1-9]/', $_SERVER[ 'HTTP_USER_AGENT' ] ) ){ // if IE(<=9) set content type = text/plain
						header( 'Content-type: text/plain' );
					} else {
						header( 'Content-type: application/json' );
					}

					// Legacy code says it needs data in array !
					// todo need to remove array
					$res_array = array( $rtmedia_id );
				} else {
					if ( preg_match( '/(?i)msie [1-9]/', $_SERVER[ 'HTTP_USER_AGENT' ] ) ){ // if IE(<=9) set content type = text/plain
						header( 'Content-type: text/plain' );
					} else {
						header( 'Content-type: application/json' );
					}

					// Media Upload Case - on album/post/profile/group
					$res_array = apply_filters( 'rtmedia_upload_endpoint_response', array(
								'media_id' => $rtmedia_id,
								'activity_id' => $activity_id,
								'permalink' => $permalink,
								'cover_art' => $thumb_image, )
					);

				}
				echo json_encode( $res_array );
			}
			die();
		}

		/**
		 * Insert activity for attached media
		 *
		 * @param object $uploaded
		 *
		 * @return bool|int
		 */
		function insert_activity( $uploaded ){

			$rtmedia_media = new RTMediaMedia();

			$allow_single_activity = apply_filters( 'rtmedia_media_single_activity', false );

			$activity_id = $uploaded->activity_id;
			$rtmedia_media_id = $uploaded->media_id;
			$rtmedia_id = $uploaded->id;

			// todo why need to use $_POST[ "rtmedia_update" ] here ?
			if ( (
					( $activity_id == - 1 || $activity_id == false )
					&& ( ! ( isset ( $_POST[ "rtmedia_update" ] ) && $_POST[ "rtmedia_update" ] == "true" ) )
				)
				|| $allow_single_activity ){
				$activity_id = $rtmedia_media->insert_activity( $rtmedia_media_id, $uploaded );
			} else {
				$rtmedia_media->model->update( array( 'activity_id' => $activity_id ), array( 'id' => $rtmedia_id ) );
				//
				$same_medias = $rtmedia_media->model->get( array( 'activity_id' => $activity_id ) );

				$update_activity_media = Array();
				foreach ( $same_medias as $a_media ) {
					$update_activity_media[ ] = $a_media->id;
				}
				$privacy = 0;
				if ( isset ( $_POST[ "privacy" ] ) ){
					$privacy = $_POST[ "privacy" ];
				}
				$objActivity = new RTMediaActivity ( $update_activity_media, $privacy, false );
				global $wpdb, $bp;
				$user     = get_userdata( $same_medias[ 0 ]->media_author );
				$username = '<a href="' . get_rtmedia_user_link( $same_medias[ 0 ]->media_author ) . '">' . $user->user_nicename . '</a>';
				$action   = sprintf( __( '%s added %d %s', 'rtmedia' ), $username, sizeof( $same_medias ), RTMEDIA_MEDIA_SLUG );
				$action   = apply_filters( 'rtmedia_buddypress_action_text_fitler_multiple_media', $action, $username, sizeof( $same_medias ), $user->user_nicename );
				$wpdb->update( $bp->activity->table_name, array( "type" => "rtmedia_update", "content" => $objActivity->create_activity_html(), 'action' => $action ), array( "id" => $activity_id ) );
			}

			return $activity_id;
		}

	}