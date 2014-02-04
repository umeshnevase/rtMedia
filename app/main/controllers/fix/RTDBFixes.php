<?php
/**
 * This file is all about databases fixed that are changed or invalid
 * Will update neccesary records as per requirments
 * @package rtMedia
 * @subpackage Fixes
 */
/**
 * RTDBFixes class is for fix all database releated the issues.
 * Databases fixes that required to run on Plugin Update.
 *
 * @author faishal
 * 
 */
class RTDBFixes {
        /**
         * RTDBUpdate hook fires after database upgrade and every version change
         * @var string
         */
        public
                $db_updarade_hook='rt_db_upgrade' ;
        
        /**
         * Setup All hooks required
         */
        function set_hooks(){
                add_action ( $this->db_updarade_hook , array ( $this , 'fix_parent_id' ) ) ;
                add_action ( $this->db_updarade_hook , array ( $this , 'fix_privacy' ) ) ;
                add_action ( $this->db_updarade_hook , array ( $this , 'fix_group_media_privacy' ) ) ;
                add_action ( $this->db_updarade_hook , array ( $this , 'fix_db_collation' ) ) ;
        }
        
        /**
         * This will fix parent id issue
         * @global type $wpdb
         */
        function fix_parent_id () {
                $site_global = rtmedia_get_site_option ( 'rtmedia-global-albums' ) ;
                if ( $site_global && is_array ( $site_global ) && isset ( $site_global[ 0 ] ) ) {
                        return false ;
                }
                $model     = new RTMediaModel() ;
                $album_row = $model->get_by_id ( $site_global[ 0 ] ) ;
                if ( isset ( $album_row[ "result" ] ) && count ( $album_row[ "result" ] ) > 0 ) {
                        global $wpdb ;
                        $row = $album_row[ "result" ][ 0 ] ;
                        if ( isset ( $row[ "media_id" ] ) ) {
                                $sql = "update {$wpdb->posts} p
                                                left join
                                                        {$model->table_name} r ON ( p.ID = r.media_id and blog_id = '" . get_current_blog_id () . "' )
                                                set
                                                    post_parent = {$row[ "media_id" ]}
                                                where
                                                    p.guid like '%/rtMedia/%'
                                                        and (p.post_parent = 0 or p.post_parent is NULL)
                                                        and not r.id is NULL
                                                        and r.media_type <> 'album'" ;
                                $wpdb->query ( $sql ) ;
                        }
                }
        }
        /**
         * Fix privacy issue for moderarion
         * @global type $wpdb
         */
        function fix_privacy () {
                global $wpdb ;
                $model     =new RTMediaModel() ;
                $update_sql="UPDATE {$model->table_name} SET privacy = '80' where privacy = '-1' " ;
                $wpdb->query ( $update_sql ) ;
        }
        /*
         * Update media privacy of the medias having context=group
         * update privacy of groups medias according to the privacy of the group 0->public, 20-> private/hidden
         */
        function fix_group_media_privacy () {
                //if buddypress is active and groups are enabled
                global $wpdb ;
                $model    =new RTMediaModel() ;
                $sql_group=" UPDATE {$model->table_name} m join {$wpdb->prefix}bp_groups bp on m.context_id = bp.id SET m.privacy = 0 where m.context = 'group' and bp.status = 'public' and m.privacy <> 80 " ;
                $wpdb->query ( $sql_group ) ;
                $sql_group=" UPDATE {$model->table_name} m join {$wpdb->prefix}bp_groups bp on m.context_id = bp.id SET m.privacy = 20 where m.context = 'group' and ( bp.status = 'private' OR bp.status = 'hidden' ) and m.privacy <> 80 " ;
                $wpdb->query ( $sql_group ) ;
        }
        /**
         * Fix db collaction issue 
         * @global type $wpdb
         */
        function fix_db_collation () {
                global $wpdb ;
                $model                       =new RTMediaModel() ;
                $interaction_model           =new RTMediaInteractionModel() ;
                $update_media_sql            ="ALTER TABLE " . $model->table_name . " CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci" ;
                $wpdb->query ( $update_media_sql ) ;
                $update_media_meta_sql       ="ALTER TABLE " . $wpdb->base_prefix . $model->meta_table_name . " CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci" ;
                $wpdb->query ( $update_media_meta_sql ) ;
                $update_media_interaction_sql="ALTER TABLE " . $interaction_model->table_name . " CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci" ;
                $wpdb->query ( $update_media_interaction_sql ) ;
        }
}