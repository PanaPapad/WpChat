<?php
/*
Plugin Name: WpChat
Description: A plugin that allows you to chat with other users.
Version: 1.0a
Author: Panagiotis Papadopoulos
*/

//Exit if accessed directly
if(!defined('ABSPATH')) {
    exit;
}

//GLOBAL CONSTANTS
define('WPCHAT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPCHAT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPCHAT_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('WPCHAT_TABLES',array(
    'MESSAGES' => 'wpchat_messages',
    'GROUPS' => 'wpchat_groups'
));
define('DEFAULT_GROUP_ID',500);

//Wordpress Hooks
add_action('rest_api_init', 'wpchat_register_routes');
add_action('wp_enqueue_scripts', 'wpchat_enqueue_scripts');
add_filter('script_loader_tag', 'wpchat_add_defer_attribute', 10, 2);
register_activation_hook(__FILE__, 'wpchat_activate');
register_deactivation_hook(__FILE__, 'wpchat_deactivate');

//Settings Page
require_once WPCHAT_PLUGIN_DIR . 'pages/settings.php';

/**
 * Call activate function. If it fails, call wp_die function.
 */
function wpchat_activate(){
    require_once WPCHAT_PLUGIN_DIR . 'install.php';
    $success = wpchat_check_install();
    if(!$success){
        wp_die('WpChat plugin failed to activate.');
    }
    wpchat_create_demo_page();
}
/**
 * Call deactivate function. If it fails, call wp_die function.
 */
function wpchat_deactivate(){
    require_once WPCHAT_PLUGIN_DIR . 'install.php';
    $success = wpchat_uninstall();
    if(!$success){
        wp_die('WpChat plugin failed to deactivate.');
    }
}
/**
 * Register REST routes for plugin.
 */
function wpchat_register_routes() {
    require_once WPCHAT_PLUGIN_DIR . 'API/API_lib.php';
}
/**
 * Enqueue scripts for plugin.
 */
function wpchat_enqueue_scripts(){
    //Bootstrap
    wp_enqueue_script('bootstrapJS', WPCHAT_PLUGIN_URL . 'JS/bootstrap.bundle.min.js', array(), '5.3.1', true);
    wp_enqueue_style('bootstrapCSS', WPCHAT_PLUGIN_URL . 'Styles/bootstrap.min.css', array(), '5.3.1', 'all');

    wp_enqueue_script('wpchatJS', WPCHAT_PLUGIN_URL . 'JS/wpchat.js', array('jquery'), '0.1', true);
    wp_enqueue_style('wpchatGlobalCSS', WPCHAT_PLUGIN_URL . 'Styles/global.css', array(), '0.1', 'all');
    wp_localize_script('wpchatJS', 'WPCHAT', array(
        'baseUrl' => esc_url_raw(rest_url('wpchat/v1')),
        'nonce' => wp_create_nonce('wp_rest'),
        'current_user_id' => get_current_user_id()
    ));
}
/**
 * Add defer attribute to wpchatJS script.
 * @param string $tag The script tag.
 * @param string $handle The script handle.
 */
function wpchat_add_defer_attribute(string $tag, string $handle) {
    if('wpchatJS' === $handle) {
        $tag = str_replace(' src', ' defer="defer" src', $tag);
    }
    else if('bootstrapJS' === $handle){
        $tag = str_replace(' src', ' defer="defer" src', $tag);
    }
    return $tag;
}
/**
 * Create Demo page for plugin.
 */
function wpchat_create_demo_page(){
    //delete demo page if exists
    $page = get_page_by_path('WpChat-Demo');
    if($page){
        wp_delete_post($page->ID, true);
    }
    $page = new WP_Query(array(
        'post_type' => 'page',
        'name' => 'WpChat-Demo'
    ));
    if(!$page->have_posts()){
        //read demo html file
        $demo_page_content = file_get_contents(WPCHAT_PLUGIN_DIR . 'pages/demo.php');
        $page = array(
            'post_title' => 'WpChat Demo',
            'post_content' => $demo_page_content,
            'post_status' => 'publish',
            'post_type' => 'page'
        );
        wp_insert_post($page);
    }
}
?>