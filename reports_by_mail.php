<?php

/*
Plugin Name: Reports by Mail
Plugin URI: https://github.com/jcwrightson/reports-by-mail
Description: Get daily/weekly/monthly reports of published posts directly to your inbox.
Version:     1.3a
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

// On Plugin Activation
register_activation_hook( __FILE__, 'wpemail_activate' );

function wpemail_activate(){


    if (!wp_next_scheduled('wpemail_daily_report')) {

        $today_date = date("Y-m-d");
        $ten_to_midnight = strtotime($today_date . " " . date("H:i:s", strtotime("23:50:00")));
        $ten_to_midnight = get_gmt_from_date( date( 'Y-m-d H:i:s', $ten_to_midnight ), 'U' );


        wp_schedule_event($ten_to_midnight, 'daily', 'wpemail_daily_report', [false]);

        //Debug
        //wp_schedule_event( time(), 'hourly', 'wpemail_daily_report', [false] );
    }
}

add_action( 'wpemail_daily_report', 'wpemail_daily_task', 10, 1);



// On Plugin Deactivation
register_deactivation_hook( __FILE__, 'wpemail_deactivate' );

function wpemail_deactivate() {

    //Deactivate Daily Task
    $timestamp = wp_next_scheduled( 'wpemail_daily_report' );
    wp_unschedule_event( $timestamp, 'wpemail_daily_report' );

    //Deactivate Email Task
    $timestamp = wp_next_scheduled( 'wpemail_send_report' );
    wp_unschedule_event( $timestamp, 'wpemail_send_report' );
}