<?php
namespace Smackcoders\Aioml;


add_action('wp_ajax_fetch_data_from_wp', 'Smackcoders\Aioml\fetchdata');
add_action('wp_ajax_nopriv_fetch_data_from_wp', 'Smackcoders\Aioml\fetchdata');
function fetchdata(){

    global $wpdb;

    $sql = "
        SELECT 
            t.term_id,
            t.name AS category_name,
            t.slug,
            tt.parent AS parent_id,
            parent_t.name AS parent_name,
            tt.count AS total_items
        FROM {$wpdb->terms} AS t
        INNER JOIN {$wpdb->term_taxonomy} AS tt 
            ON t.term_id = tt.term_id
        LEFT JOIN {$wpdb->terms} AS parent_t
            ON tt.parent = parent_t.term_id
        WHERE tt.taxonomy = 'attachment_category'
        ORDER BY t.name ASC
    ";

    $result = $wpdb->get_results($sql);

    if( empty($result) ){
        wp_send_json_error( array('message' => 'No results found') );
    }

    // wp_send_json_success auto encodes result
    wp_send_json_success( $result );
}


