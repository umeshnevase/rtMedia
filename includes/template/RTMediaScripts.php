<?php

/**
 * Class RTMediaScripts
 *
 * Register scripts and styles
 *
 * @scope global $rtmedia_scripts
 * @author Ritesh Patel <ritesh.patel@rtcamp.com>
 */
class RTMediaScripts {

	public function __construct() {
		//todo register all the scripts and styles in this class
		add_action( 'wp_enqueue_scripts', array( $this, 'register_uploader_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_scripts_styles' ), 999 );
	}

	public function register_uploader_scripts() {
		if ( ! wp_script_is( 'plupload-all' ) ) {
			wp_enqueue_script( 'plupload-all' );
		}
		wp_enqueue_script( 'rtmedia-backbone', RTMEDIA_URL . 'app/assets/js/rtMedia.backbone.js', array(
			'plupload-all',
			'backbone'
		), RTMEDIA_VERSION, true );

		if ( is_rtmedia_album_gallery() ) {
			$template_url = esc_url( add_query_arg( array(
				"action"   => 'rtmedia_get_template',
				"template" => "album-gallery-item"
			), admin_url( "admin-ajax.php" ) ), null, '' );
		} else {
			$template_url = esc_url( add_query_arg( array(
				"action"   => 'rtmedia_get_template',
				"template" => apply_filters( 'rtmedia_backbone_template_filter', "media-gallery-item" )
			), admin_url( "admin-ajax.php" ) ), null, '' );
		}
		wp_localize_script( 'rtmedia-backbone', 'template_url', $template_url );
		$url          = trailingslashit( $_SERVER["REQUEST_URI"] );
		$rtmedia_slug = "/" . RTMEDIA_MEDIA_SLUG;
		// check position of media slug from end of the URL
		if ( strrpos( $url, $rtmedia_slug ) !== false ) {
			// split the url upto the last occurance of media slug
			$url_upload = substr( $url, 0, strrpos( $url, $rtmedia_slug ) );
			$url        = trailingslashit( $url_upload ) . "upload/";
		} else {
			$url = trailingslashit( $url ) . "upload/";
		}

		$params = array(
			'url'                 => $url,
			'runtimes'            => 'html5,flash,html4',
			'browse_button'       => 'rtMedia-upload-button',
			'container'           => 'rtmedia-upload-container',
			'drop_element'        => 'drag-drop-area',
			'filters'             => apply_filters( 'rtmedia_plupload_files_filter', array(
				array(
					'title'      => "Media Files",
					'extensions' => get_rtmedia_allowed_upload_type()
				)
			) ),
			'max_file_size'       => ( wp_max_upload_size() ) / ( 1024 * 1024 ) . 'M',
			'multipart'           => true,
			'urlstream_upload'    => true,
			'flash_swf_url'       => includes_url( 'js/plupload/plupload.flash.swf' ),
			'silverlight_xap_url' => includes_url( 'js/plupload/plupload.silverlight.xap' ),
			'file_data_name'      => 'rtmedia_file', // key passed to $_FILE.
			'multi_selection'     => true,
			'multipart_params'    => apply_filters( 'rtmedia-multi-params', array(
				'redirect'             => 'no',
				'action'               => 'wp_handle_upload',
				'_wp_http_referer'     => $_SERVER['REQUEST_URI'],
				'mode'                 => 'file_upload',
				'rtmedia_upload_nonce' => RTMediaUploadView::upload_nonce_generator( false, true )
			) ),
			'max_file_size_msg'   => apply_filters( "rtmedia_plupload_file_size_msg", min( array(
				ini_get( 'upload_max_filesize' ),
				ini_get( 'post_max_size' )
			) ) )
		);
		if ( wp_is_mobile() ) {
			$params['multi_selection'] = false;
		}

		$params = apply_filters( "rtmedia_modify_upload_params", $params );

		global $rtmedia;
		$rtmedia_extns = array();

		foreach ( $rtmedia->allowed_types as $allowed_types_key => $allowed_types_value ) {
			$rtmedia_extns[ $allowed_types_key ] = $allowed_types_value['extn'];
		}

		wp_localize_script( 'rtmedia-backbone', 'rtmedia_exteansions', $rtmedia_extns );
		wp_localize_script( 'rtmedia-backbone', 'rtMedia_plupload_config', $params );
		wp_localize_script( 'rtmedia-backbone', 'rMedia_loading_file', admin_url( "/images/loading.gif" ) );
	}

	function enqueue_scripts_styles() {
		global $rtmedia;
		if ( wp_script_is( 'wp-mediaelement', 'registered' ) ) {
			wp_enqueue_style( 'wp-mediaelement' );
			wp_enqueue_script( 'wp-mediaelement' );
		} else {
			wp_enqueue_script( 'wp-mediaelement', RTMEDIA_URL . 'lib/media-element/mediaelement-and-player.min.js', '', RTMEDIA_VERSION );
			wp_enqueue_style( 'wp-mediaelement', RTMEDIA_URL . 'lib/media-element/mediaelementplayer.min.css', '', RTMEDIA_VERSION );
			wp_enqueue_script( 'wp-mediaelement-start', RTMEDIA_URL . 'lib/media-element/wp-mediaelement.js', 'wp-mediaelement', RTMEDIA_VERSION, true );
		}


		// Dashicons: Needs if not loaded by WP
		wp_enqueue_style( 'dashicons' );

		// Dont enqueue rtmedia.min.css if default styles is checked false in rtmedia settings
		$suffix = ( function_exists( 'rtm_get_script_style_suffix' ) ) ? rtm_get_script_style_suffix() : '.min';

		if ( ! ( isset( $rtmedia->options ) && isset( $rtmedia->options['styles_enabled'] ) && $rtmedia->options['styles_enabled'] == 0 ) ) {
			wp_enqueue_style( 'rtmedia-main', RTMEDIA_URL . 'app/assets/css/rtmedia' . $suffix . '.css', '', RTMEDIA_VERSION );
		}

		if ( $suffix === '' ) {
			wp_enqueue_script( 'rtmedia-magnific-popup', RTMEDIA_URL . 'app/assets/js/vendors/magnific-popup.js', array(
				'jquery',
				'wp-mediaelement'
			), RTMEDIA_VERSION );
			wp_enqueue_script( 'rtmedia-admin-tabs', RTMEDIA_URL . 'app/assets/admin/js/vendors/tabs.js', array(
				'jquery',
				'wp-mediaelement'
			), RTMEDIA_VERSION );
			wp_enqueue_script( 'rtmedia-main', RTMEDIA_URL . 'app/assets/js/rtMedia.js', array(
				'jquery',
				'wp-mediaelement'
			), RTMEDIA_VERSION );
		} else {
			wp_enqueue_script( 'rtmedia-main', RTMEDIA_URL . 'app/assets/js/rtmedia.min.js', array(
				'jquery',
				'wp-mediaelement'
			), RTMEDIA_VERSION );
		}

		wp_localize_script( 'rtmedia-main', 'rtmedia_ajax_url', admin_url( 'admin-ajax.php' ) );
		wp_localize_script( 'rtmedia-main', 'rtmedia_media_slug', RTMEDIA_MEDIA_SLUG );
		wp_localize_script( 'rtmedia-main', 'rtmedia_lightbox_enabled', strval( $rtmedia->options["general_enableLightbox"] ) );

		$direct_upload = ( isset( $rtmedia->options["general_direct_upload"] ) ? $rtmedia->options["general_direct_upload"] : '0' );

		wp_localize_script( 'rtmedia-main', 'rtmedia_direct_upload_enabled', $direct_upload );
		//gallery reload after media upload, by default true
		wp_localize_script( 'rtmedia-main', 'rtmedia_gallery_reload_on_upload', '1' );

		//javascript messages
		wp_localize_script( 'rtmedia-magnific', 'rtmedia_load_more', __( 'Loading media', 'buddypress-media' ) );
		wp_localize_script( 'rtmedia-main', 'rtmedia_empty_activity_msg', __( 'Please enter some content to post.', 'buddypress-media' ) );
		wp_localize_script( 'rtmedia-main', 'rtmedia_empty_comment_msg', __( 'Empty Comment is not allowed.', 'buddypress-media' ) );
		wp_localize_script( 'rtmedia-main', 'rtmedia_media_delete_confirmation', __( 'Are you sure you want to delete this media?', 'buddypress-media' ) );
		wp_localize_script( 'rtmedia-main', 'rtmedia_media_comment_delete_confirmation', __( 'Are you sure you want to delete this comment?', 'buddypress-media' ) );
		wp_localize_script( 'rtmedia-main', 'rtmedia_album_delete_confirmation', __( 'Are you sure you want to delete this Album?', 'buddypress-media' ) );
		wp_localize_script( 'rtmedia-main', 'rtmedia_drop_media_msg', __( 'Drop files here', 'buddypress-media' ) );
		wp_localize_script( 'rtmedia-main', 'rtmedia_album_created_msg', ' ' . __( 'album created successfully.', 'buddypress-media' ) );
		wp_localize_script( 'rtmedia-main', 'rtmedia_something_wrong_msg', __( 'Something went wrong. Please try again.', 'buddypress-media' ) );
		wp_localize_script( 'rtmedia-main', 'rtmedia_empty_album_name_msg', __( 'Enter an album name.', 'buddypress-media' ) );
		wp_localize_script( 'rtmedia-main', 'rtmedia_max_file_msg', __( 'Max file Size Limit : ', 'buddypress-media' ) );
		wp_localize_script( 'rtmedia-main', 'rtmedia_allowed_file_formats', __( 'Allowed File Formats', 'buddypress-media' ) );
		wp_localize_script( 'rtmedia-main', 'rtmedia_select_all_visible', __( 'Select All Visible', 'buddypress-media' ) );
		wp_localize_script( 'rtmedia-main', 'rtmedia_unselect_all_visible', __( 'Unselect All Visible', 'buddypress-media' ) );
		wp_localize_script( 'rtmedia-main', 'rtmedia_no_media_selected', __( 'Please select some media.', 'buddypress-media' ) );
		wp_localize_script( 'rtmedia-main', 'rtmedia_selected_media_delete_confirmation', __( 'Are you sure you want to delete the selected media?', 'buddypress-media' ) );
		wp_localize_script( 'rtmedia-main', 'rtmedia_selected_media_move_confirmation', __( 'Are you sure you want to move the selected media?', 'buddypress-media' ) );
		wp_localize_script( 'rtmedia-main', 'rtmedia_waiting_msg', __( 'Waiting', 'buddypress-media' ) );
		wp_localize_script( 'rtmedia-main', 'rtmedia_uploaded_msg', __( 'Uploaded', 'buddypress-media' ) );
		wp_localize_script( 'rtmedia-main', 'rtmedia_uploading_msg', __( 'Uploading', 'buddypress-media' ) );
		wp_localize_script( 'rtmedia-main', 'rtmedia_upload_failed_msg', __( 'Failed', 'buddypress-media' ) );
		wp_localize_script( 'rtmedia-main', 'rtmedia_close', __( 'Close', 'buddypress-media' ) );
		wp_localize_script( 'rtmedia-main', 'rtmedia_edit', __( 'Edit', 'buddypress-media' ) );
		wp_localize_script( 'rtmedia-main', 'rtmedia_delete', __( 'Delete', 'buddypress-media' ) );
		wp_localize_script( 'rtmedia-main', 'rtmedia_edit_media', __( 'Edit Media', 'buddypress-media' ) );
		wp_localize_script( 'rtmedia-main', 'rtmedia_remove_from_queue', __( 'Remove from queue', 'buddypress-media' ) );
		wp_localize_script( 'rtmedia-main', 'rtmedia_add_more_files_msg', __( 'Add more files', 'buddypress-media' ) );
		wp_localize_script( 'rtmedia-main', 'rtmedia_file_extension_error_msg', __( 'File not supported', 'buddypress-media' ) );
		wp_localize_script( 'rtmedia-main', 'rtmedia_more', __( 'more', 'buddypress-media' ) );
		wp_localize_script( 'rtmedia-main', 'rtmedia_less', __( 'less', 'buddypress-media' ) );
		wp_localize_script( 'rtmedia-main', 'rtmedia_delete_uploaded_media', __( 'This media is uploaded. Are you sure you want to delete this media?', 'buddypress-media' ) );
		wp_localize_script( 'rtmedia-main', 'rtm_wp_version', get_bloginfo( 'version' ) );
		wp_localize_script( 'rtmedia-backbone', 'rMedia_loading_media', RTMEDIA_URL . "app/assets/admin/img/boxspinner.gif" );
		$rtmedia_media_thumbs = array();
		foreach ( $rtmedia->allowed_types as $key_type => $value_type ) {
			$rtmedia_media_thumbs[ $key_type ] = $value_type['thumbnail'];
		}
		wp_localize_script( 'rtmedia-backbone', 'rtmedia_media_thumbs', $rtmedia_media_thumbs );
		wp_localize_script( 'rtmedia-backbone', 'rtmedia_set_featured_image_msg', __( 'Featured media set successfully.', 'buddypress-media' ) );
		wp_localize_script( 'rtmedia-backbone', 'rtmedia_unset_featured_image_msg', __( 'Featured media removed successfully.', 'buddypress-media' ) );

//      We are not using it anymore and hence commenting
//		global $rtmedia_query;
//		if( class_exists('BuddyPress') ) {
//			$rtmedia_user_domain  = trailingslashit ( bp_displayed_user_domain() . constant('RTMEDIA_MEDIA_SLUG') );
//		} else {
//			$rtmedia_user_domain = trailingslashit( trailingslashit( get_author_posts_url($rtmedia_query->query['context_id'] ) ). constant('RTMEDIA_MEDIA_SLUG') );
//		}
//		wp_localize_script ( 'rtmedia-backbone', 'rtmedia_user_domain', $rtmedia_user_domain );
		// Enqueue touchswipe
		wp_enqueue_script( 'rtmedia-touchswipe', RTMEDIA_URL . 'lib/touchswipe/jquery.touchSwipe.min.js', array( 'jquery' ), RTMEDIA_VERSION, true );

		if ( isset( $rtmedia->options ) && isset( $rtmedia->options['general_masonry_layout'] ) && $rtmedia->options['general_masonry_layout'] == 1 ) {
			if ( wp_script_is( "jquery-masonry", "registered" ) ) {
				wp_enqueue_style( 'jquery-masonry' );
				wp_enqueue_script( 'jquery-masonry' );
				wp_localize_script( 'rtmedia-main', 'rtmedia_masonry_layout', 'true' );
			} else {
				wp_localize_script( 'rtmedia-main', 'rtmedia_masonry_layout', 'false' );
			}
		} else {
			wp_localize_script( 'rtmedia-main', 'rtmedia_masonry_layout', 'false' );
		}

		if ( isset( $rtmedia->options['general_display_media'] ) ) {
			wp_localize_script( 'rtmedia-backbone', 'rtmedia_load_more_or_pagination', ( string ) $rtmedia->options['general_display_media'] );
		} else {
			wp_localize_script( 'rtmedia-backbone', 'rtmedia_load_more_or_pagination', 'load_more' );
		}

		if ( isset( $rtmedia->options['buddypress_enableOnActivity'] ) ) {
			wp_localize_script( 'rtmedia-backbone', 'rtmedia_bp_enable_activity', ( string ) $rtmedia->options['buddypress_enableOnActivity'] );
		} else {
			wp_localize_script( 'rtmedia-backbone', 'rtmedia_bp_enable_activity', '0' );
		}

		wp_localize_script( 'rtmedia-backbone', 'rtmedia_upload_progress_error_message', __( "There are some uploads in progress. Do you want to cancel them?", 'buddypress-media' ) );

		// localise media size config
		$media_size_config = array(
			'photo'    => array(
				'thumb'  => array(
					'width'  => $rtmedia->options['defaultSizes_photo_thumbnail_width'],
					'height' => $rtmedia->options['defaultSizes_photo_thumbnail_height'],
					'crop'   => $rtmedia->options['defaultSizes_photo_thumbnail_crop'],
				),
				'medium' => array(
					'width'  => $rtmedia->options['defaultSizes_photo_medium_width'],
					'height' => $rtmedia->options['defaultSizes_photo_medium_height'],
					'crop'   => $rtmedia->options['defaultSizes_photo_medium_crop'],
				),
				'large'  => array(
					'width'  => $rtmedia->options['defaultSizes_photo_large_width'],
					'height' => $rtmedia->options['defaultSizes_photo_large_height'],
					'crop'   => $rtmedia->options['defaultSizes_photo_large_crop'],
				),
			),
			'video'    => array(
				'activity_media' => array(
					'width'  => $rtmedia->options['defaultSizes_video_activityPlayer_width'],
					'height' => $rtmedia->options['defaultSizes_video_activityPlayer_height'],
				),
				'single_media'   => array(
					'width'  => $rtmedia->options['defaultSizes_video_singlePlayer_width'],
					'height' => $rtmedia->options['defaultSizes_video_singlePlayer_height'],
				),
			),
			'music'    => array(
				'activity_media' => array(
					'width' => $rtmedia->options['defaultSizes_music_activityPlayer_width'],
				),
				'single_media'   => array(
					'width' => $rtmedia->options['defaultSizes_music_singlePlayer_width'],
				),
			),
			'featured' => array(
				'default' => array(
					'width'  => $rtmedia->options['defaultSizes_featured_default_width'],
					'height' => $rtmedia->options['defaultSizes_featured_default_height'],
					'crop'   => $rtmedia->options['defaultSizes_featured_default_crop'],
				)
			),
		);
		wp_localize_script( 'rtmedia-main', 'rtmedia_media_size_config', $media_size_config );

	}

}