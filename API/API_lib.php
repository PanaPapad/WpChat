<?php
//Exit if accessed directly
if(!defined('ABSPATH')) {
    exit;
}
//Server Sent Events Endpoint
register_rest_route( 'wpchat/v1', '/sse', array(
    'methods' => 'GET',
    'callback' => 'sse_endpoint',
    'permission_callback' => '__return_true',
) );
register_rest_route( 'wpchat/v1', '/heartbeat', array(
    'methods' => 'GET',
    'callback' => 'heartbeat_endpoint',
    'permission_callback' => '__return_true',
) );
register_rest_route( 'wpchat/v1', '/sendMessage', array(
    'methods' => 'POST',
    'callback' => 'sendMessage',
    'permission_callback' => '__return_true',
) );
//Constants
define('WPCHAT_HEARTBEAT_INTERVAL', 30);//IN SECONDS
/**
 * Server Sent Events endpoint callback
 */
function sse_endpoint( WP_REST_Request $request ) {
    header( 'Content-Type: text/event-stream' );
    header( 'Cache-Control: no-cache' );
    $connection_UUID = $request->get_param('uuid');
    if(empty($connection_UUID)){
        echo "event: error\ndata: No UUID provided\n\n";
        ob_flush();
        return new WP_REST_Response(array('error' => 'No UUID provided'), 400);
    }
    //Send connection success message
    echo "event: connectionSuccess\ndata: $connection_UUID\n\n";
    //ob_flush();
    flush();

    $counter = get_option('wpchat_settings')['wpchat_ttl_interval']??
        WPCHAT_TTL_INTERVAL;
    $last_date = $request->get_param('last_date')?? 0;
    $last_id = $request->get_param('last_id')?? 0;
    $group_id = $request->get_param('group_id')?? 
        get_option('wpchat_settings')['wpchat_default_group']?? 
        WPCHAT_DEFAULT_GROUP_ID;
    while($counter-- > 0){
        $messages = wpchat_get_messages($group_id,$last_date,$last_id);
        if(!empty($messages)){
            echo "event: newMessage\ndata: " . json_encode($messages) . "\n\n";
            $last_date = $messages[0]['date'];
            $last_id = $messages[0]['id'];
            ob_flush();
            flush();
        }
        sleep(1);
    }
    //IF loop broke and client is still there connection will repopen by the client
    echo "event: expiredConnection\ndata: expired\n\n";
    ob_flush();
    flush();
}
/**
 * Get the latest messages from a group
 */
function wpchat_get_messages(int $group_id, $last_date = 0, $last_id = 0){
    //delete_transient('wpchat_last_messages_group_' . $group_id); //FOR TESTING
    $expiration_time = 120;//IN SECONDS
    $cached_data = get_transient('wpchat_last_messages_group_' . $group_id);
    if($cached_data !== false && $last_date === 0){
        return $cached_data;
    }
    global $wpdb;
    $sql = "SELECT id,message,date,sender_id FROM " . WPCHAT_TABLES['MESSAGES'] . " WHERE group_id = %d AND ((date > %s) OR (date = %s AND id > %d)) ORDER BY date DESC LIMIT 20";
    $sql = $wpdb->prepare($sql, $group_id, $last_date, $last_date, $last_id);
    $messages = $wpdb->get_results($sql,ARRAY_A);
    if($messages === false){
        return array();
    }
    if($last_date===0){
        //Cache the data only if we are not requesting a specific date
        set_transient('wpchat_last_messages_group_' . $group_id, $messages, $expiration_time);
    }
    return $messages;
}
/**
 * Heartbeat endpoint callback
 * Used to update the transient for the connection.
 * This transient is used to check if the connection is still alive.
 * 
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response
 */
function heartbeat_endpoint( WP_REST_Request $request ) {
    $connection_UUID = $request->get_param('uuid');
    if(empty($connection_UUID)){
        return new WP_REST_Response(array('error' => 'No UUID provided'), 400);
    }
    delete_transient('wpchat_sse_connection_' . $connection_UUID);
    $success = set_transient('wpchat_sse_connection_' . $connection_UUID, true, WPCHAT_HEARTBEAT_INTERVAL);
    if($success === false){
        return new WP_REST_Response(array('error' => 'Failed to update transient'), 500);
    }
    return new WP_REST_Response(array('success' => true), 200);
}
/**
 * Send message endpoint callback
 * 
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response
 */
function sendMessage(WP_REST_Request $request){
    $body = $request->get_body();
    $body = json_decode($body, true);
    if(empty($body)){
        return new WP_REST_Response(array('error' => 'No data provided'), 400);
    }
    if(empty($body['message'])){
        return new WP_REST_Response(array('error' => 'No message provided'), 400);
    }
    if(empty($body['group_id'])){
        return new WP_REST_Response(array('error' => 'No group_id provided'), 400);
    }
    $group_id = $body['group_id'];
    $message = $body['message'];
    $sender_id = get_current_user_id();
    if($sender_id === 0){
        return new WP_REST_Response(array('error' => 'User not logged in'), 400);
    }
    $success = wpchat_insert_message($group_id, $message, $sender_id);
    if($success === false){
        return new WP_REST_Response(array('error' => 'Failed to insert message'), 500);
    }
    return new WP_REST_Response(array('success' => true), 200);
}
/**
 * Insert message into DB
 * 
 * @param int $group_id The group id.
 * @param string $message The message.
 * @param int $sender_id The sender id.
 * @return bool True if message inserted successfully, false otherwise.
 */
function wpchat_insert_message(int $group_id, string $message, int $sender_id){
    global $wpdb;
    $sql = "INSERT INTO " . WPCHAT_TABLES['MESSAGES'] . " (group_id, message, sender_id) VALUES (%d, %s, %d)";
    $sql = $wpdb->prepare($sql, $group_id, $message, $sender_id);
    $success = $wpdb->query($sql);
    if($success === false){
        return false;
    }
    delete_transient('wpchat_last_messages_group_' . $group_id);
    return true;
}
?>