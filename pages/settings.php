<?php
//Exit if accessed directly
if(!defined('ABSPATH')) {
    exit;
}
//Wordpress Hooks
add_action('admin_menu', 'wpchat_add_admin_menu');
add_action('admin_init', 'wpchat_settings_init');
/**
 * Add admin menu page for plugin.
 */
function wpchat_add_admin_menu() {
    add_menu_page(
        'WpChat', 
        'WpChat', 
        'manage_options', 
        'wpchat', 
        'wpchat_options_page'
    );
}
/**
 * Register settings for plugin.
 */
function wpchat_settings_init() {
    //Settings Section
    register_setting('wpchat', 'wpchat_settings');
    add_settings_section(
        'wpchat_wpchat_section', 
        __('WpChat Settings', 'wpchat'), 
        'wpchat_settings_section_callback', 
        'wpchat'
    );
    //TTL-NUMBER
    add_settings_field(
        'wpchat_ttl_interval', 
        __('TTL Interval', 'wpchat'), 
        'wpchat_ttl_interval_render', 
        'wpchat', 
        'wpchat_wpchat_section'
    );
    //DEFAULT GROUP - NUMBER
    add_settings_field(
        'wpchat_default_group', 
        __('Default Group ID', 'wpchat'), 
        'wpchat_default_group_render', 
        'wpchat', 
        'wpchat_wpchat_section'
    );

}
//Render Functions
/**
 * Render default group field for plugin.
 */
function wpchat_default_group_render() {
    $options = get_option('wpchat_settings');
    ?>
    <input type='number' name='wpchat_settings[wpchat_default_group]' value='<?php echo isset($options['wpchat_default_group']) ? esc_attr($options['wpchat_default_group']) : '' ?>'>
    <?php

}
/**
 * Render text field for plugin.
 */
function wpchat_ttl_interval_render() {
    $options = get_option('wpchat_settings');
    ?>
    <input type='number' name='wpchat_settings[wpchat_ttl_interval]' value='<?php echo isset($options['wpchat_ttl_interval']) ? esc_attr($options['wpchat_ttl_interval']) : '' ?>'>
    <?php
}

//Section Callbacks
/**
 * Render settings section for plugin.
 */
function wpchat_settings_section_callback() {
    echo __('This section description', 'wpchat');
}

//Page Callbacks
/**
 * Render options page for plugin.
 */
function wpchat_options_page() {
    ?>
    <form action='options.php' method='post'>
        <h2>WpChat</h2>
        <?php
        settings_fields('wpchat');
        do_settings_sections('wpchat');
        submit_button();
        ?>
    </form>
    <?php
}
?>