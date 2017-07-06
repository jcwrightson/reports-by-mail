<?php

/*
Plugin Name: Reports by Mail
Plugin URI: https://github.com/jcwrightson/reports-by-mail
Description: Get daily/weekly/monthly reports of published posts directly to your inbox.
Version:     1.0a
Author:      jcwrightson
Author URI:  mailto:jcwrightson@gmail.com
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: wporg
Domain Path: /languages
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
$here = plugin_dir_path( __FILE__ );

function add_styles(){
    wp_enqueue_style( 'styles', plugins_url( '/css/style.css', __FILE__ ) );
}

add_action('admin_print_styles', 'add_styles');

require_once ($here . '/admin.php');
require_once ($here . '/daily.php');