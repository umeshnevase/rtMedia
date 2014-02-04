<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Description of newPHPClass
 *
 * @author faishal
 */
class WP_TEST_RTDBFixes extends WP_UnitTestCase {
        function setUp () {
                parent::setUp () ;
                $this->RTBDFixes = new RTDBFixes() ;
        }
        function test_fix_parent_id () {
                delete_site_option ( 'rtmedia-global-albums' ) ;
                $this->assertnull ( $this->RTBDFixes->fix_parent_id () ) ;
        }
}