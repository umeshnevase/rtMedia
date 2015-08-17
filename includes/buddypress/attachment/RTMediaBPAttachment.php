<?php

	class RTMediaBPAttachment extends BP_Attachment {

		/**
		 * @var $rt_upload_params
		 *
		 * Holds the upload media parameters
		 */
		private $rt_upload_params;

		/**
		 * Initialization
		 */
		public function __construct(){
			parent::__construct( array(
				'action'             => 'rtmedia_bp_attachment_upload',
				'file_input'         => 'rtmedia_file',
				'base_dir'           => apply_filters( 'rtmedia_upload_folder_name', 'rtMedia-test' ),
				'required_wp_files'  => array( 'file', 'image' ),
			) );
		}

		/**
		 * Set upload directory for rtMedia attachments
		 *
		 * @return array
		 */
		public function upload_dir_filter( $up_dir_params = array() ) {

			// identify whether it's group or user profile and set upload directory accordingly
			$dir_name = 'users';
			$id = bp_displayed_user_id();
			if ( bp_is_group() ) {
				$dir_name = 'groups';
				$id = $this->rt_upload_params[ 'context_id' ];
			}
			$sub_dir = $dir_name . '/' . $id;

			// Add /year/month/ into upload directory
			if( empty( $up_dir_params ) ){
				// can't use wp_upload_dir() here as this function it self
				$time = current_time( 'mysql' );
				$year = substr( $time, 0, 4 );
				$month = substr( $time, 5, 2 );
				$sub_sub_dir = "/$year/$month";
			} else {
				$sub_sub_dir = $up_dir_params[ 'subdir' ];
			}
			$sub_dir .= $sub_sub_dir;

			$rtmedia_upload_dir = array(
				'path'    => trailingslashit( $this->upload_path ) . $sub_dir,
				'url'     => $this->url . $sub_dir,
				'subdir'  => $sub_dir,
				'basedir' => $this->upload_path,
				'baseurl' => $this->url,
				'error'   => false
			);


			$rtmedia_upload_dir = apply_filters( "rtmedia_bp_attachments_upload_dir", $rtmedia_upload_dir, $up_dir_params );

			return $rtmedia_upload_dir;
		}

		/**
		 * Insert attachment
		 *
		 * @param $file
		 * @param $upload_params
		 *
		 * @return object media object
		 */
		public function insert_attachment( $file, $upload_params ){

			// Initialize required classes
			$upload_helper = new RTMediaMediaUploadHelper();
			$rtmedia_media = new RTMediaMedia();

			// Set media post object
			$upload_params = $upload_helper->set_media_object( $upload_params );

			/*
			 * Customize upload params for BuddyPress
			 */
			// set context
			// Context might be bp_member for user profile and bp_group for groups
			if( empty( $upload_params[ 'context' ] ) || $upload_params[ 'context' ] == 'bp_member' ){
				$upload_params[ 'context' ] = 'profile';
			}
			if( $upload_params[ 'context' ] == 'profile' ){
				$upload_params[ 'context_id' ] = get_current_user_id();
			}
			if( $upload_params[ 'context' ] == 'bp_group' ){
				$upload_params[ 'context' ] = 'group';
			}

			// set group media privacy
			if ( isset( $upload_params[ 'context' ] ) && isset( $upload_params[ 'context_id' ] )
				&& $upload_params[ 'context' ] == 'group' && function_exists( 'groups_get_group' )
			){

				$group = groups_get_group( array( 'group_id' => $upload_params[ 'context_id' ] ) );
				if ( isset( $group->status ) && $group->status != 'public' ){
					// if group is not public than set media privacy to 20, so only the group members can access media
					$upload_params[ 'privacy' ] = 20;
				} else {
					// if group is public than set media privacy to 0
					$upload_params[ 'privacy' ] = 0;
				}

			}

			$this->rt_upload_params = $upload_params;

			// Upload file
			$file_object = parent::upload( $file );

			// Add media
			$media_id_array = $rtmedia_media->add( $upload_params, array( $file_object ) );

			// Get uploaded media object
			$media_object = $rtmedia_media->model->get( array( 'id' => $media_id_array ) );

			unset( $upload_helper );
			unset( $rtmedia_media );

			$return_object = is_array( $media_object ) ? $media_object[0] : $media_object;

			// pass activity_id which we got from $_POST because it needs to attach in single activity in case of
			// multiple media upload
			$return_object->activity_id = $upload_params[ 'activity_id' ];

			return $return_object;

		}

	}