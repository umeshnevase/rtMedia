<?php
	/**
	 * rtMedia functions for BuddyPress
	 */

	/**
	 * Check whether BuddyPress is active or not
	 *
	 * @return bool
	 */
	function rtm_is_bp_active() {
		return class_exists( 'BuddyPress' );
	}


	/**
	 * Check whether current BuddyPress install has Attachment API or not
	 *
	 * @return bool
	 */
	function rtm_is_bp_have_attachment_api(){
		$return = false;
		if( rtm_is_bp_active() && class_exists( 'BP_Attachment' ) ){
			$return = true;
		}

		return $return;
	}

