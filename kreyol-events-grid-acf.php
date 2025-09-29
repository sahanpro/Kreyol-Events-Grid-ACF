<?php
/**
 * Plugin Name: Kreyol Events Grid (ACF)
 * Description: Filterable, paginated events grid that uses your EXISTING events post type and ACF fields.
 * Version: 2.5.7
 * Author: Kreyol App
 * Text Domain: kreyol-events-grid-acf
 */
if(!defined('ABSPATH')) exit;
define('KEG_VERSION','2.5.7');
define('KEG_PATH', plugin_dir_path(__FILE__));
define('KEG_URL', plugin_dir_url(__FILE__));

require_once KEG_PATH.'includes/helpers.php';
require_once KEG_PATH.'includes/shortcode.php';

add_action('wp_enqueue_scripts', function(){
    wp_register_style('keg-style', KEG_URL.'public/css/style.css',[],KEG_VERSION);
    wp_register_script('keg-search', KEG_URL.'public/js/search.js',['jquery'],KEG_VERSION,true);
});
