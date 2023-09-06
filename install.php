<?php
//Exit if accessed directly
if(!defined('ABSPATH')) {
    exit;
}

/**
 * Check if plugin is installed.
 * @return bool True if plugin is installed, false otherwise.
 */
function wpchat_check_install(){
    //Check for tables in DB
    return wpchat_create_tables();
}
/**
 * Create tables in DB.
 * @return bool True if tables created successfully, false otherwise.
 */
function wpchat_create_tables(){
    global $wpdb;
    $wpdb->query('START TRANSACTION');
    //Messages table
    $sql = "CREATE TABLE IF NOT EXISTS " . WPCHAT_TABLES['MESSAGES'] . " (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        group_id INT(6) UNSIGNED NOT NULL,
        sender_id BIGINT(20) UNSIGNED NOT NULL,
        message VARCHAR(255) NOT NULL,
        date TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
        INDEX(group_id, date),
        FOREIGN KEY (sender_id) REFERENCES wp_users(ID) ON DELETE CASCADE ON UPDATE CASCADE
    )";
    //Execute query
    $success = $wpdb->query($sql);
    if($success === false){
        $wpdb->query('ROLLBACK');
        return false;
    }

    $wpdb->query('COMMIT');
    return true;
}
/**
 * Uninstall plugin.
 * @return bool True if plugin uninstalled successfully, false otherwise.
 */
function wpchat_uninstall(){
    //Delete tables from DB
    return wpchat_delete_tables();
}
/**
 * Delete tables from DB.
 * @return bool True if tables deleted successfully, false otherwise.
 */
function wpchat_delete_tables(){
    global $wpdb;
    $wpdb->query('START TRANSACTION');
    //Messages table
    $sql = "DROP TABLE IF EXISTS " . WPCHAT_TABLES['MESSAGES'];
    //Execute query
    $success = $wpdb->query($sql);
    if($success === false){
        $wpdb->query('ROLLBACK');
        return false;
    }

    $wpdb->query('COMMIT');
    return true;
}