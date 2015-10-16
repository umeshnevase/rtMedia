<?php

class RTMediaNav {

	function __construct( $action = true ) {

		if ( $action === false ) {
			return;
		}
	}

	/**
	 *  Legacy functions.
	 *  Hanging here so that it won't break anything if any other plugin or theme calls it directly.
	 */
	function media_screen() {
		return;
	}

	function custom_media_nav_tab() {
		return;
	}

	function admin_nav() {
		return;
	}

	public function sub_nav() {
		return;
	}

	// E.O. legacy functions.


	/**
	 * Refresh user/group media count.
	 *
	 * @param $user_id
	 * @param $where
	 *
	 * @return array
	 */
	function refresh_counts( $user_id, $where ) {
		$model       = new RTMediaModel();
		$counts      = $model->get_counts( $user_id, $where );
		$media_count = array();
		foreach ( $counts as $count ) {
			if ( ! isset ( $count->privacy ) ) {
				$count->privacy = 0;
			}
			if ( isset ( $media_count[ strval( $count->privacy ) ] ) ) {
				foreach ( $media_count[ strval( $count->privacy ) ] as $key => $val ) {
					$media_count[ strval( $count->privacy ) ]->{$key} = intval( $count->{$key} ) + intval( $val );
				}
			} else {
				$media_count[ strval( $count->privacy ) ] = $count;
			}
			unset ( $media_count[ strval( $count->privacy ) ]->privacy );
		}

		if ( isset ( $where["context"] ) ) {
			if ( $where["context"] == "profile" ) {
				update_user_meta( $user_id, 'rtmedia_counts_' . get_current_blog_id(), $media_count );
			} else if ( $where["context"] == "group" && function_exists( "groups_update_groupmeta" ) ) {
				groups_update_groupmeta( $user_id, 'rtmedia_counts_' . get_current_blog_id(), $media_count );
			}
		}

		return $media_count;
	}

	/**
	 * @param bool|false $profile_id
	 * @param string $context
	 *
	 * @return array|bool|mixed
	 */
	function get_counts( $profile_id = false, $context = "profile" ) {
		if ( $profile_id === false && $context == "profile" ) {
			$profile_id = $this->profile_id();
		} else if ( $profile_id === false && $context == "group" ) {
			$profile_id = $this->group_id();
		}
		if ( ! $profile_id ) {
			return false;
		}
		$counts = array();
		if ( $context == "profile" ) {
			$counts = get_user_meta( $profile_id, 'rtmedia_counts_' . get_current_blog_id(), true );
			if ( $counts == false || empty ( $counts ) ) {
				$counts = $this->refresh_counts( $profile_id, array( "context"      => $context,
				                                                     'media_author' => $profile_id
				) );
			}
		} else if ( function_exists( "groups_get_groupmeta" ) && $context = "group" ) {
			$counts = groups_get_groupmeta( $profile_id, 'rtmedia_counts_' . get_current_blog_id() );
			if ( $counts === false || empty ( $counts ) ) {
				$counts = $this->refresh_counts( $profile_id, array( "context"    => $context,
				                                                     'context_id' => $profile_id
				) );
			}
		}

		return $counts;
	}

	/**
	 * Get user profile id
	 *
	 * @return bool|integer profile id
	 */
	function profile_id() {
		global $rtmedia_query;
		if ( isset ( $rtmedia_query->query['context'] ) && ( $rtmedia_query->query['context'] == 'profile' ) ) {
			return $rtmedia_query->query['context_id'];
		}

		return false;
	}

	/**
	 * Get group id
	 *
	 * @return bool|integer group id
	 */
	function group_id() {
		global $rtmedia_query;
		if ( isset ( $rtmedia_query->query['context'] ) && ( $rtmedia_query->query['context'] == 'group' ) ) {
			return $rtmedia_query->query['context_id'];
		}

		return false;
	}

	/**
	 * @param bool|false $profile_id
	 * @param string $context
	 *
	 * @return array|bool
	 */
	function actual_counts( $profile_id = false, $context = "profile" ) {
		if ( $profile_id === false ) {
			if ( ! $this->profile_id() ) {
				return false;
			}
		}

		$media_count = $this->get_counts( $profile_id, $context );
		$privacy     = $this->set_privacy( $profile_id );

		return $this->process_count( $media_count, $privacy );
	}

	/**
	 * @param $media_count
	 * @param $privacy
	 *
	 * @return array
	 */
	function process_count( $media_count, $privacy ) {
		$total              = array( 'all' => 0 );
		$media_count        = ! empty( $media_count ) ? $media_count : array();
		$exclude_type_count = apply_filters( 'rtmedia_media_count_exclude_type', array( 'album' ) );
		foreach ( $media_count as $private => $ind_count ) {
			if ( $private <= $privacy ) {
				foreach ( $ind_count as $type => $ind_ind_count ) {
					if ( in_array( $type, $exclude_type_count ) ) {
						// do nothing
					} else {
						$total['all'] += ( int ) $ind_ind_count;
					}
					if ( ! isset ( $total[ $type ] ) ) {
						$total[ $type ] = 0;
					}
					$total[ $type ] += ( int ) $ind_ind_count;
				}
			} else {
				unset ( $media_count[ $private ] );
			}
		}

		$media_count['total'] = $total;

		return $media_count;
	}

	/**
	 * Get current visitor user's id
	 *
	 * @return int
	 */
	function visitor_id() {
		if ( is_user_logged_in() ) {
			$user = get_current_user_id();
		} else {
			$user = 0;
		}

		return $user;
	}

	/**
	 * Set privacy in respect of current user.
	 *
	 * @param $profile
	 *
	 * @return int
	 */
	function set_privacy( $profile ) {
		if ( is_rt_admin() ) {
			return 60;
		}

		$user    = $this->visitor_id();
		$privacy = 0;
		if ( $user ) {
			$privacy = 20;
		}
		if ( $profile === false ) {
			$profile = $this->profile_id();
		}
		if ( class_exists( 'BuddyPress' ) && bp_is_active( 'friends' ) ) {

			if ( friends_check_friendship_status( $user, $profile ) ) {
				$privacy = 40;
			}
		}
		if ( $user === $profile ) {
			$privacy = 60;
		}

		return $privacy;
	}

}