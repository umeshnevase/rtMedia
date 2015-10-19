<?php
/**
 * Description of BPMediaGroupLoader
 *
 * @author faishal
 */
if ( class_exists( 'BP_Group_Extension' ) ) :// Recommended, to prevent problems during upgrade or when Groups are disabled

	class RTMediaGroupExtension extends BP_Group_Extension {

		function __construct() {

			$args = array(
				'name'                 => RTMEDIA_MEDIA_LABEL,
				'slug'                 => RTMEDIA_MEDIA_SLUG,
				'visibility'           => 'private',
				'create_step_position' => 21,
				'enable_nav_item'      => true,
			);

			parent::init( $args );
		}

		function display( $group_id = null ){
			?>
			<div class="item-list-tabs no-ajax" id="subnav">
				<ul>
					<?php bp_get_options_nav( $this->slug ); ?>
					<?php do_action( 'bp_member_plugin_options_nav' ); ?>
				</ul>
			</div><!-- .item-list-tabs -->

			<?php

			global $rtmedia_template;
			if ( ! $rtmedia_template ) {
				$rtmedia_template = new RTMediaTemplate();
			}

			do_action( 'rtm_bp_before_template' );
			include( $rtmedia_template->set_template() );
			do_action( 'rtm_bp_after_template' );
		}

		public function sub_nav () {
			global $rtmedia, $rtmedia_query;
			$nav = new RTMediaNav();
			global $bp;
			$counts = $nav->actual_counts ( $bp->groups->current_group->id, "group" );

			$default = false;
			$link = get_rtmedia_group_link ( bp_get_group_id () );
			$model = new RTMediaModel();
			$other_count = $model->get_other_album_count ( bp_get_group_id (), "group" );

			$all = '';
			if ( ! isset ( $rtmedia_query->action_query->media_type )) {
				$all = 'class="current selected"';
			}
			echo apply_filters ( 'rtmedia_sub_nav_all', '<li id="rtmedia-nav-item-all-li" ' . $all . '><a id="rtmedia-nav-item-all" href="' . trailingslashit ( $link ) . RTMEDIA_MEDIA_SLUG . '/">' . __ ( "All", 'buddypress-media' ) . '<span>' . ((isset ( $counts[ 'total' ][ 'all' ] )) ? $counts[ 'total' ][ 'all' ] : 0 ) . '</span>' . '</a></li>' );

			if ( ! isset ( $rtmedia_query->action_query->action ) || empty ( $rtmedia_query->action_query->action ) ) {
				$default = true;
			}
			//print_r($rtmedia_query->action_query);

			$global_album = '';
			$albums = '';
			if ( isset ( $rtmedia_query->action_query->media_type ) && $rtmedia_query->action_query->media_type == 'album' ) {
				$albums = 'class="current selected"';
			}

			//$other_count = 0;
			if ( is_rtmedia_album_enable () ) {

				if ( ! isset ( $counts[ 'total' ][ "album" ] ) ) {
					$counts[ 'total' ][ "album" ] = 0;
				}

				$counts[ 'total' ][ "album" ] = $counts[ 'total' ][ "album" ] + $other_count;
				$album_label = __( defined('RTMEDIA_ALBUM_PLURAL_LABEL') ? constant ( 'RTMEDIA_ALBUM_PLURAL_LABEL' ) : 'Albums', 'buddypress-media' );
				echo apply_filters ( 'rtmedia_sub_nav_albums', '<li id="rtmedia-nav-item-albums-li" ' . $albums . '><a id="rtmedia-nav-item-albums" href="' . trailingslashit ( $link ) . RTMEDIA_MEDIA_SLUG . '/album/">' . $album_label . '<span>' . ((isset ( $counts[ 'total' ][ "album" ] )) ? $counts[ 'total' ][ "album" ] : 0 ) . '</span>' . '</a></li>' );
			}

			foreach ( $rtmedia->allowed_types as $type ) {
				//print_r($type);
				if( ! isset( $rtmedia->options[ 'allowedTypes_' . $type[ 'name' ] . '_enabled' ] ) )
					continue;
				if ( ! $rtmedia->options[ 'allowedTypes_' . $type[ 'name' ] . '_enabled' ] )
					continue;

				$selected = '';

				if ( isset ( $rtmedia_query->action_query->media_type ) && $type[ 'name' ] == $rtmedia_query->action_query->media_type ) {
					$selected = ' class="current selected"';
				} else {
					$selected = '';
				}

				$context = isset ( $rtmedia_query->query[ 'context' ] ) ? $rtmedia_query->query[ 'context' ] : 'default';
				$context_id = isset ( $rtmedia_query->query[ 'context_id' ] ) ? $rtmedia_query->query[ 'context_id' ] : 0;
				$name = strtoupper ( $type[ 'name' ] );
				$is_group = true;
				$profile = $context_id;


				$profile_link = trailingslashit ( get_rtmedia_group_link ( $profile ) );

				$type_label = __( defined('RTMEDIA_' . $name . '_PLURAL_LABEL') ? constant ( 'RTMEDIA_' . $name . '_PLURAL_LABEL' ) : $type[ 'plural_label' ], 'buddypress-media' );
				echo apply_filters ( 'rtmedia_sub_nav_' . $type[ 'name' ], '<li id="rtmedia-nav-item-' . $type[ 'name' ]
				                                                           . '-' . $context . '-' . $context_id . '-li" ' . $selected
				                                                           . '><a id="rtmedia-nav-item-' . $type[ 'name' ] . '" href="'
				                                                           . $profile_link . RTMEDIA_MEDIA_SLUG . '/'
				                                                           . constant ( 'RTMEDIA_' . $name . '_SLUG' ) . '/' . '">'
				                                                           . $type_label . '<span>' . ((isset ( $counts[ 'total' ][ $type[ 'name' ] ] )) ? $counts[ 'total' ][ $type[ 'name' ] ] : 0) . '</span>' . '</a></li>', $type[ 'name' ]
				);
			}

			do_action("add_extra_sub_nav");
		}

		function create_screen( $group_id = null ) {

			if ( ! bp_is_group_creation_step( $this->slug ) ) {
				return false;
			}
			// HOOK to add PER GROUP MEDIA enable/diable option in rtMedia PRO
			do_action( 'rtmedia_group_media_control_create' );

			global $rtmedia;
			$options = $rtmedia->options; ?>
			<div class='rtmedia-group-media-settings'>
				<?php if ( isset( $options['general_enableAlbums'] ) && $options['general_enableAlbums'] == 1 ) {  // album is enabled ?>

					<h4><?php _e( 'Album Creation Control', 'buddypress-media' ); ?></h4>
					<p><?php _e( 'Who can create Albums in this group?', 'buddypress-media' ); ?></p>
					<div class="radio">
						<label>
							<input name="rt_album_creation_control" type="radio" id="rt_media_group_level_all"
							       checked="checked" value="all">
							<strong><?php _e( 'All Group Members', 'buddypress-media' ); ?></strong>
						</label>
						<label>
							<input name="rt_album_creation_control" type="radio" id="rt_media_group_level_moderators"
							       value="moderators">
							<strong><?php _e( 'Group Admins and Mods only', 'buddypress-media' ); ?></strong>
						</label>
						<label>
							<input name="rt_album_creation_control" type="radio" id="rt_media_group_level_admin"
							       value="admin">
							<strong><?php _e( 'Group Admin only', 'buddypress-media' ); ?></strong>
						</label>
					</div>

				<?php } ?>

				<?php do_action( 'rtmedia_playlist_creation_settings_create_group' ); ?>
			</div>
			<?php
			wp_nonce_field( 'groups_create_save_' . $this->slug );
		}

		/**
		 *
		 * @global type $bp
		 */
		function create_screen_save( $group_id = null ) {
			global $bp;

			check_admin_referer( 'groups_create_save_' . $this->slug );

			/* Save any details submitted here */
			if ( isset ( $_POST['rt_album_creation_control'] ) && $_POST['rt_album_creation_control'] != '' ) {
				groups_update_groupmeta( $bp->groups->new_group_id, 'rt_media_group_control_level', $_POST['rt_album_creation_control'] );
			}
			do_action( 'rtmedia_create_save_group_media_settings', $_POST );
		}

		/**
		 *
		 * @global type $bp_media
		 * @return boolean
		 */
		function edit_screen( $group_id = null ) {
			if ( ! bp_is_group_admin_screen( $this->slug ) ) {
				return false;
			}
			$current_level = groups_get_groupmeta( bp_get_current_group_id(), 'rt_media_group_control_level' );
			if ( empty ( $current_level ) ) {
				$current_level = "all";
			}

			// HOOK to add PER GROUP MEDIA enable/diable option in rtMedia PRO
			do_action( 'rtmedia_group_media_control_edit' ); ?>

			<div class='rtmedia-group-media-settings'>

				<?php global $rtmedia;
				$options = $rtmedia->options;
				if ( isset( $options['general_enableAlbums'] ) && $options['general_enableAlbums'] == 1 ) { // album is enabled
					?>

					<h4><?php _e( 'Album Creation Control', 'buddypress-media' ); ?></h4>
					<p><?php _e( 'Who can create Albums in this group?', 'buddypress-media' ); ?></p>
					<div class="radio">
						<label>
							<input name="rt_album_creation_control" type="radio" id="rt_media_group_level_moderators"
							       value="all"<?php checked( $current_level, 'all', true ) ?>>
							<strong><?php _e( 'All Group Members', 'buddypress-media' ); ?></strong>
						</label>
						<label>
							<input name="rt_album_creation_control" type="radio" id="rt_media_group_level_moderators"
							       value="moderators" <?php checked( $current_level, 'moderators', true ) ?>>
							<strong><?php _e( 'Group Admins and Mods only', 'buddypress-media' ); ?></strong>
						</label>
						<label>
							<input name="rt_album_creation_control" type="radio" id="rt_media_group_level_admin"
							       value="admin" <?php checked( $current_level, 'admin', true ) ?>>
							<strong><?php _e( 'Group Admin only', 'buddypress-media' ); ?></strong>
						</label>
					</div>
					<hr>
				<?php } ?>

				<?php do_action( 'rtmedia_playlist_creation_settings_groups_edit' ); ?>
			</div>
			<input type="submit" name="save" value="<?php _e( 'Save Changes', 'buddypress-media' ); ?>"/>
			<?php
			wp_nonce_field( 'groups_edit_save_' . $this->slug );
		}

		/**
		 *
		 * @global type $bp
		 * @global type $bp_media
		 * @return boolean
		 */
		function edit_screen_save( $group_id = null ) {
			global $bp;

			if ( ! isset ( $_POST['save'] ) ) {
				return false;
			}

			check_admin_referer( 'groups_edit_save_' . $this->slug );

			if ( isset ( $_POST['rt_album_creation_control'] ) && $_POST['rt_album_creation_control'] != '' ) {
				$success = groups_update_groupmeta( bp_get_current_group_id(), 'rt_media_group_control_level', $_POST['rt_album_creation_control'] );
				do_action( 'rtmedia_edit_save_group_media_settings', $_POST );
				$success = true;
			} else {
				$success = false;
			}

			/* To post an error/success message to the screen, use the following */
			if ( ! $success ) {
				bp_core_add_message( __( 'There was an error saving, please try again', 'buddypress-media' ), 'error' );
			} else {
				bp_core_add_message( __( 'Settings saved successfully', 'buddypress-media' ) );
			}

			bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . '/admin/' . $this->slug );
		}

		/**
		 * The display method for the extension
		 *
		 * @since BuddyPress Media 2.3
		 */

		/**
		 *
		 * @global type $bp_media
		 */
		function widget_display() {
			?>
			<div class="info-group">
				<h4><?php echo esc_attr( $this->name ) ?></h4>

				<p>
					<?php _e( 'You could display a small snippet of information from your group extension here. It will show on the group
	                home screen.', 'buddypress-media' ); ?>
				</p>
			</div>
			<?php
		}

	}

endif; // class_exists( 'BP_Group_Extension' )