<?php
/*
Plugin Name: WpChat
Description: A plugin that allows you to chat with other users.
Version: 0.1
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

/**
 * Call activate function. If it fails, call wp_die function.
 */
function wpchat_activate(){
    require_once WPCHAT_PLUGIN_DIR . 'install.php';
    $success = wpchat_check_install();
    if(!$success){
        wp_die('WpChat plugin failed to activate.');
    }
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
    wp_enqueue_script('wpchatJS', WPCHAT_PLUGIN_URL . 'js/wpchat.js', array('jquery'), '0.1', true);
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
    if('wpchatJS' !== $handle) {
        return $tag;
    }
    return str_replace(' src', ' defer="defer" src', $tag);
}

?>