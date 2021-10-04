<?php
/*
Plugin Name: Data Tensai for Contact Form 7
Plugin URI: https://calendarscripts.info/data-tensai/
Description: Advaned database management, export, and enhancements for Contact Form 7
Author: Kiboko Labs
Version: 0.5.5
Author URI: http://calendarscripts.info/
License: GPLv2 or later
Text domain: datatensai-cf7
*/

define( 'DATATENSAI_PATH', dirname( __FILE__ ) );
define( 'DATATENSAI_RELATIVE_PATH', dirname( plugin_basename( __FILE__ )));
define( 'DATATENSAI_URL', plugin_dir_url( __FILE__ ));

// require controllers and models
require_once(DATATENSAI_PATH.'/models/basic.php');
require_once(DATATENSAI_PATH.'/models/csv.php');
require_once(DATATENSAI_PATH.'/controllers/actions.php');
require_once(DATATENSAI_PATH.'/controllers/manage.php');
require_once(DATATENSAI_PATH.'/controllers/ajax.php');
require_once(DATATENSAI_PATH.'/helpers/htmlhelper.php');

add_action('init', array("DataTensai", "init"));

register_activation_hook(__FILE__, array("DataTensai", "install"));
//add_action('admin_menu', array("DataTensai", "menu"));
add_action('admin_enqueue_scripts', array("DataTensai", "scripts"));

// show the things on the front-end
add_action( 'wp_enqueue_scripts', array("DataTensai", "scripts"));

// other actions
add_action('wp_ajax_datatensai_ajax', 'datatensai_ajax');
add_action('wp_ajax_nopriv_datatensai_ajax', 'datatensai_ajax');