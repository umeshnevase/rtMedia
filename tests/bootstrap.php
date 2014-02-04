<?php
/**
 * Bootstrap the plugin unit testing environment.
 *
 * Edit 'active_plugins' setting below to point to your main plugin file.
 *
 * @package wordpress-plugin-tests
 */

// Activates this plugin in WordPress so it can be tested.
$GLOBALS['wp_tests_options'] = array(
	'active_plugins' => array( 'rtMedia/index.php' ),
);

$_tests_dir = getenv('WP_TESTS_DIR');
if ( ! $_tests_dir ) $_tests_dir = '/Users/faishal/work/wordpress-develop/tests/phpunit';

if(  ! file_exists ( '/Users/faishal/work/wordpress-develop/tests/phpunit' ) )
        $_tests_dir = '/home/gitlab_ci_runner/wordpress-develop/tests/phpunit';

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
	require dirname( __FILE__ ) . '/../index.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';