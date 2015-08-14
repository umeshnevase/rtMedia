<?php

	/**
	 * Helper class for media upload
	 */
	class RTMediaMediaUploadHelper {

		/**
		 * Default values for media object
		 *
		 * @var array
		 */

		private $upload = array(
			'context' => false,
			'context_id' => false,
			'privacy' => 0,
			'album_id' => false,
			'files' => false,
			'title' => false,
			'description' => false,
			'media_author' => false,
			'activity_id' => -1,
		);

		/**
		 * Prepare media object
		 *
		 * @param $upload_params
		 *
		 * @return array
		 */
		function set_media_object( $upload_params ) {
			$this->upload = wp_parse_args( $upload_params, $this->upload );
			$this->sanitize_object();

			return $this->upload;
		}

		/**
		 *  Sanitize media object data
		 */
		function sanitize_object() {

			// set media context
			if( ! $this->has_context() ) {
				// set default context to profile
				$this->upload[ 'context' ] = 'profile';
				$this->upload[ 'context_id' ] = get_current_user_id();
			}
			$this->upload[ 'context_id' ] = intval( $this->upload[ 'context_id' ] );
			$this->upload[ 'context' ] = esc_html( $this->upload[ 'context' ] );

			// set media album
			if( ! $this->has_album_id() || ! $this->has_album_permissions() ) {
				$this->set_album_id();
			}
			$this->upload[ 'album_id' ] = intval( $this->upload[ 'album_id' ] );

			// set media author
			if( ! $this->has_author() ) {
				$this->set_author();
			}
			$this->upload[ 'media_author' ] = intval( $this->upload[ 'media_author' ] );

			// set media privacy
			if( ( is_rtmedia_privacy_enable() && ! is_rtmedia_privacy_user_overide() )
			// privacy is enabled but user override is not enabled
				|| ( ( is_rtmedia_privacy_enable() && is_rtmedia_privacy_user_overide() && $this->upload[ 'privacy' ] == 0 ) )
				// privacy and user override is enabled but privacy is not provided
				// ( $this->upload[ 'privacy' ] == 0 ) check because default value is 0.
			) {
				$this->upload[ 'privacy' ] = get_rtmedia_default_privacy();
			}
			$this->upload[ 'privacy' ] = intval( $this->upload[ 'privacy' ] );

			// set activity id
			$this->upload[ 'activity_id' ] = intval( $this->upload[ 'activity_id' ] );

			// set title and description
			$this->upload[ 'title' ] = sanitize_title( $this->upload[ 'title' ] );
			$this->upload[ 'description' ] = esc_html( $this->upload[ 'description' ] );
		}

		/**
		 * Check if context is set or not
		 *
		 * @return bool
		 */
		function has_context() {
			if( isset ( $this->upload[ 'context_id' ] ) && ! empty ( $this->upload[ 'context_id' ] ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Check if author is set or not
		 *
		 * @return mixed
		 */
		function has_author() {
			return $this->upload[ 'media_author' ];
		}

		/**
		 * Set author
		 */
		function set_author() {
			$this->upload[ 'media_author' ] = get_current_user_id();
		}

		/**
		 * Check is album is set or not
		 *
		 * @return bool
		 */
		function has_album_id() {
			if( ! $this->upload[ 'album_id' ] || $this->upload[ 'album_id' ] == "undefined" ) {
				return false;
			}

			return true;
		}

		/**
		 * todo Check if user can create album or not
		 *
		 * @return bool
		 */
		function has_album_permissions() {
			return true;
		}

		/**
		 * todo Check whether album exist or not
		 *
		 * @param $id
		 *
		 * @return bool
		 */
		function album_id_exists( $id ) {
			return true;
		}

		/**
		 * Set album id
		 */
		function set_album_id() {
			$this->upload[ 'album_id' ] = RTMediaAlbum::get_default();
		}

	}