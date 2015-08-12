<?php

	class RTMediaBPAttachment extends BP_Attachment {

		/**
		 *
		 */
		public function __construct(){
			parent::__construct( array(
				'action'             => 'rtmedia_bp_attachment_upload',
				'file_input'         => 'rtmedia_bp_files',
				'base_dir'           => apply_filters( 'rtmedia_upload_folder_name', 'rtMedia-test' ),
				'required_wp_files'  => array( 'file', 'image' ),
			) );
		}

		/**
		 * Set upload directory for rtMedia attachments
		 *
		 * @return array
		 */
		public function upload_dir_filter() {

			// identify whether it's group or user profile and set upload directory accordingly
			$dir_name = 'users';
			$id = bp_displayed_user_id();
			if ( bp_is_group() ) {
				$dir_name = 'groups';
				$id = bp_get_group_id();
			}
			$sub_dir = $dir_name . '/' . $id;

			// Add /year/month/ into upload directory
			$wp_dir = wp_upload_dir();
			if( !empty( $wp_dir ) && is_array( $wp_dir ) && isset( $wp_dir['subdir'] ) ){
				$wp_base_dir = $wp_dir['subdir'];
				$sub_sub_dir = $wp_base_dir;
			} else {
				$time = current_time( 'mysql' );
				$year = substr( $time, 0, 4 );
				$month = substr( $time, 5, 2 );
				$sub_sub_dir = "/$year/$month";
			}

			$sub_dir .= $sub_sub_dir;

			$rtmedia_upload_dir = array(
				'path'    => $this->upload_path . $sub_dir,
				'url'     => $this->url . $sub_dir,
				'subdir'  => $sub_dir,
				'basedir' => $this->upload_path,
				'baseurl' => $this->url,
				'error'   => false
			);

			$rtmedia_upload_dir = apply_filters( "rtmedia_bp_attachments_upload_dir", $rtmedia_upload_dir );

			return $rtmedia_upload_dir;
		}

	}