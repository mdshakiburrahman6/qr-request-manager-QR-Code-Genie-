<?php
/**
 * Plugin Name: QR Request Manager
 * Description: Users can request Meet & Shop QR codes. Admin uploads QR images and users can download them.
 * Version: 1.0.0
 * Author: Md Shakibnur Rahman
 * Author URI: https://github.com/mdshakiburrahman
 */

if ( ! defined('ABSPATH') ) exit;

// Constants
define('QRM_PATH', plugin_dir_path(__FILE__));
define('QRM_URL', plugin_dir_url(__FILE__));

// Includes
require_once QRM_PATH . 'includes/class-cpt.php';
require_once QRM_PATH . 'includes/class-shortcode.php';
require_once QRM_PATH . 'includes/class-ajax.php';

// Init
add_action('plugins_loaded', function () {
    QRM_CPT::init();
    QRM_Shortcode::init();
    QRM_Ajax::init();
});

