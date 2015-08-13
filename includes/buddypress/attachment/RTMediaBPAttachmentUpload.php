<?php

	class RTMediaBPAttachmentUpload {

		private $upload;

		public function __construct(){
			add_action( 'wp_ajax_rtmedia_bp_attachment_upload', array( $this, 'handle_bp_attachment_upload' ) );
		}

		function handle_bp_attachment_upload(){
			$nonce = $_REQUEST[ 'rtmedia_upload_nonce' ];
			if ( wp_verify_nonce( $nonce, 'rtmedia_upload_nonce' ) ){
				$attachment = new RTMediaBPAttachment();
				$uploaded = $attachment->insert_attachment( $_FILES, $_POST );

				// Insert activity
				$activity_id = $uploaded->activity_id = $this->insert_activity( $uploaded );

				// Prepare output
				$rtmedia_id = $uploaded->id;
				$permalink = get_rtmedia_permalink( $rtmedia_id );
				$media_type = $uploaded->media_type;
				$cover_art = $uploaded->coverr_art;

				if ( $media_type == "photo" ){
					$thumb_image = rtmedia_image( "rt_media_thumbnail", $rtmedia_id, false );
				} elseif ( $media_type == "music" ) {
					$thumb_image = $cover_art;
				} else {
					$thumb_image = "";
				}

				if ( isset ( $_POST[ "rtmedia_update" ] ) && $_POST[ "rtmedia_update" ] == "true" ){
					if ( preg_match( '/(?i)msie [1-9]/', $_SERVER[ 'HTTP_USER_AGENT' ] ) ){ // if IE(<=9) set content type = text/plain
						header( 'Content-type: text/plain' );
					} else {
						header( 'Content-type: application/json' );
					}
					echo json_encode( $activity_id );
				} else {
					// Media Upload Case - on album/post/profile/group
					if( isset( $media[0] ) ) {
						$data = array( 'media_id' => $rtmedia_id, 'activity_id' => $activity_id, 'redirect_url' => '', 'permalink' => $permalink, 'cover_art' => $thumb_image );
					} else {
						$data = array();
					}
					if ( preg_match( '/(?i)msie [1-9]/', $_SERVER[ 'HTTP_USER_AGENT' ] ) ){ // if IE(<=9) set content type = text/plain
						header( 'Content-type: text/plain' );
					} else {
						header( 'Content-type: application/json' );
					}
					echo json_encode( apply_filters( 'rtmedia_upload_endpoint_response', $data ) );
				}
			}
			die();
		}

		function insert_activity( $uploaded ){

			$rtmedia_media = new RTMediaMedia();

			$allow_single_activity = apply_filters( 'rtmedia_media_single_activity', false );

			$activity_id = $uploaded->activity_id;
			$rtmedia_media_id = $uploaded->media_id;
			$rtmedia_id = $uploaded->id;

			// todo why need to use $_POST[ "rtmedia_update" ] here ?
			if ( ( ( $activity_id == - 1 || $activity_id == false ) && ( ! ( isset ( $_POST[ "rtmedia_update" ] ) && $_POST[ "rtmedia_update" ] == "true" ) ) ) || $allow_single_activity ){
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