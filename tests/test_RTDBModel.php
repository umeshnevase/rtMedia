<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Description of test_RTDBModel
 *
 * @author faishal
 */
class test_RTDBModel extends WP_UnitTestCase {
        //put your code here
        var
                $rtdbmodel ;
        /**
         * Setup Class Object and Parent Test Suite
         */
        function setUp () {
                parent::setUp () ;
                $this->rtdbmodel = new RTDBModel ( "test_table" ) ;
        }
        /**
         * Check table name with Default withprefix Paramater which is false And set MultiSite Wordpress Signle Table true
         * 
         * @global type $wpdb Global WordPress DB object to get prefix
         */
        function test_set_table_name_witout_prefix_param_mu_single () {
                global $wpdb ;
                $this->rtdbmodel->mu_single_table = true ;
                $this->rtdbmodel->set_table_name ( "test_table" ) ;
                $this->assertEquals ( $wpdb->base_prefix . "rt_" . 'test_table' , $this->rtdbmodel->table_name ) ;
        }
        /**
         * Check table name with Default withprefix Paramater which is false And set MultiSite Wordpress Signle Table false
         * 
         * @global type $wpdb Global WordPress DB object to get prefix
         */
        function test_set_table_name_witout_prefix_param_mu_single_false () {
                global $wpdb ;
                $this->rtdbmodel->mu_single_table = false ;
                $this->rtdbmodel->set_table_name ( "test_table" ) ;
                $this->assertEquals ( $wpdb->prefix . "rt_" . 'test_table' , $this->rtdbmodel->table_name ) ;
        }
        /**
         * Check table name by set withPrefix Parameter to false And also set MultiSite Wordpress Single Table true
         */
        function test_set_table_name_with_prefix_param_mu_single () {
                $this->rtdbmodel->mu_single_table = true ;
                $this->rtdbmodel->set_table_name ( "test_table" , true ) ;
                $this->assertEquals ( 'test_table' , $this->rtdbmodel->table_name ) ;
        }
        /**
         * Check table name by set withPrefix Parameter to false And also set MultiSite Wordpress Single Table false
         */
        function test_set_table_name_with_prefix_param_mu_single_false () {
                $this->rtdbmodel->mu_single_table = false ;
                $this->rtdbmodel->set_table_name ( "test_table" , true ) ;
                $this->assertEquals ( 'test_table' , $this->rtdbmodel->table_name ) ;
        }
        function test_get () {
                $this->rtdbmodel->set_table_name ( "rtm_media_meta" ) ;
                $this->rtdbmodel->insert ( array ( 'media_id' => 1 , 'meta_key' => 'test_key' , 'meta_value' => 'test_value' ) ) ;
                $result = $this->rtdbmodel->get ( array ( 'meta_key' => 'test_key' ) ) ;
                $this->assertGreaterThan ( 0 , count ( $result ) ) ;
        }
        function test_get_1(){
                $this->rtdbmodel->set_table_name ( "rtm_media_meta" ) ;
                $this->rtdbmodel->insert ( array ( 'media_id' => 1 , 'meta_key' => 'test_key' , 'meta_value' => 'test_value' ) ) ;      
                $result = $this->rtdbmodel->get ( array ( 'meta_key' => 'test_key' ) , 'asdf' ) ;
                $this->assertGreaterThan ( 0 , count ( $result ) ) ;
                $this->rtdbmodel->set_table_name ( "rtm_media_meta" ) ;
                $result = $this->rtdbmodel->get ( array ( 'meta_key' => 'test_key' ) , '1' ) ;
                $this->assertGreaterThan ( 0 , count ( $result ) ) ;
                $this->rtdbmodel->set_table_name ( "rtm_media_meta" ) ;
                $result = $this->rtdbmodel->get ( array ( 'meta_key' => 'test_key' ) , 1 ) ;
                $this->assertGreaterThan ( 0 , count ( $result ) ) ;
                $this->rtdbmodel->set_table_name ( "rtm_media_meta" ) ;
                $result = $this->rtdbmodel->get ( array ( 'meta_key' => 'test_key' ) , '-9999' ) ;
                $this->assertGreaterThan ( 0 , count ( $result ) ) ;
                $this->rtdbmodel->set_table_name ( "rtm_media_meta" ) ;
                $result = $this->rtdbmodel->get ( array ( 'meta_key' => 'test_key' ) , -9999 ) ;
                $this->assertGreaterThan ( 0 , count ( $result ) ) ;
        }
        function test_insert_with_right_input () {
                $this->rtdbmodel->set_table_name ( "rtm_media_meta" ) ;
                $this->assertGreaterThan ( 0 , $this->rtdbmodel->insert ( array ( 'media_id' => 1 , 'meta_key' => 'test_key' , 'meta_value' => 'test_value' ) ) ) ;
        }
}