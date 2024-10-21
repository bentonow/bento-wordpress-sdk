<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

add_filter('tve_filter_available_connection', 'add_bento_connection');

function add_bento_connection($available_connections) {
	require_once plugin_dir_path(__FILE__) . 'thrive/bento-connection.php';
	$available_connections['bento'] = 'Thrive_Dash_List_Connection_Bento';
    
    return $available_connections;
}

add_filter('tvd_api_available_connections', 'add_bento_connection_to_thrive', 10, 3);

function add_bento_connection_to_thrive($lists, $only_connected, $api_filter) {
    
	// Create an instance of the Bento connection
    $bento_connection = new Thrive_Dash_List_Connection_Bento('bento');

    // Check if it should be included based on the filter criteria
    if (Thrive_Dash_List_Manager::should_include_api($bento_connection, $api_filter)) {
        // Check if we should only include connected APIs
        if (!$only_connected || $bento_connection->is_connected()) {
            // Add Bento to the list
            $lists['bento'] = $api_filter['only_names'] ? $bento_connection->get_title() : $bento_connection;
        }
    }

    return $lists;
}
