<?php

$feed_url = "http://static.scripting.com/houston/rivers/occupy/River3.js";
$feed_unique_prefix = 'ows';

require_once( dirname( dirname( __FILE__ ) ) . '/includes/config.php' );
require_once( dirname( dirname( __FILE__ ) ) . '/includes/Database.php' );
require_once( dirname( dirname( __FILE__ ) ) . '/includes/RiverItem.php' );
require_once( dirname( dirname( __FILE__ ) ) . '/includes/functions.php' );

/*  We'll run this script for a long time, so set some timing variables. */
$start_seconds = time();
$continue = 1;

while ($continue == 1){

    $current_seconds = time();
    $total_seconds = ($current_seconds - $start_seconds);

    if ( 3420 <= $total_seconds ) {
        /*  This script has now been running for 3 hours and 58 minutes. Kill it for a bit. */
        die();
    }

    $db = db_connect();

    $ch = curl_init();
    curl_setopt ( $ch, CURLOPT_URL, $feed_url );
    curl_setopt ( $ch, CURLOPT_HEADER, 0 ); /// Header control
    curl_setopt ( $ch, CURLOPT_POST, false );  /// tell it to make a POST, not a GET
    curl_setopt ( $ch, CURLOPT_TIMEOUT, 60 );
    curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
    $xml_response = curl_exec ( $ch );
    curl_close ( $ch );

    /*  Only needed with Dave's feed to return valid JSON */
    $xml_response = substr( $xml_response, 18 );
    $xml_response = substr( $xml_response, 0, -1 );

    $decoded_data = json_decode($xml_response);
    $decoded_data = $decoded_data->updatedFeeds;
    $decoded_data = $decoded_data->updatedFeed;

    $capture_date = date( 'Y-m-d H:i:s' );

    foreach ( $decoded_data as $result ) {
        foreach ( $result->item as $item ){
            $item_update_query = $db->prepare( "INSERT INTO river_items ( river_source_id, feed_item_id, item_url,
                item_title, publish_date, permalink, body, feed_url, feed_section, feed_title, website_url, capture_date )
                VALUES ( :river_source_id, :feed_item_id, :item_url, :item_title, :publish_date, :permalink,
                :body, :feed_url, :feed_section, :feed_title, :website_url, :capture_date )
                ON DUPLICATE KEY UPDATE capture_date = :capture_date " );
            $this_item_id = $feed_unique_prefix . '_' . $item->id;
            $this_feed_source = "Occupy Web";
            $item_update_query->bindValue( ':river_source_id', 1 );
            $item_update_query->bindParam( ':feed_item_id', $this_item_id );
            $item_update_query->bindParam( ':item_url', $item->link );
            $item_update_query->bindParam( ':item_title', $item->title );
            $item_update_query->bindParam( ':publish_date', date( 'Y-m-d H:i:s', strtotime( $item->pubDate ) ) );
            $item_update_query->bindParam( ':permalink', $item->permalink );
            $item_update_query->bindParam( ':body', $item->body );
            $item_update_query->bindParam( ':feed_url', $result->feedUrl );
            $item_update_query->bindParam( ':feed_section', $this_feed_soruce );
            $item_update_query->bindParam( ':feed_title', $result->feedTitle );
            $item_update_query->bindParam( ':website_url', $result->websiteUrl );
            $item_update_query->bindParam( ':capture_date', $capture_date );
            $item_update_query->execute();
            $item_update_query = NULL;
        }
    }

    $db = NULL;
    sleep( 600 );

}

?>
