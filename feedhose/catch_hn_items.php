<?php

$feed_url = "http://api.ihackernews.com/new";
$feed_unique_prefix = 'hn';

require_once( $_SERVER['DOCUMENT_ROOT'] . '/includes/Database.php' );

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

    $decoded_data = json_decode($xml_response);
    $capture_date = date( 'Y-m-d H:i:s' );

    foreach ( $decoded_data->items as $item ) {
        if ( "http" != substr( $item->url, 0, 4 ) ){
            $this_item_url = "http://news.ycombinator.com" . $item->url;
            $this_item_source = "Ask HN";
            $this_item_publication = "Hacker News";
        }else{
            $this_item_url = $item->url;
            $this_item_source = "New";
            $this_item_publication = "Hacker News";
        }
        $this_discussion_url = "http://news.ycombinator.com/item?id=" . $item->id;
        $this_item_id = $feed_unique_prefix . '_' . $item->id;

        $item_update_query = $db->prepare( "INSERT INTO river_items ( river_source_id, feed_item_id, item_url,
            item_title, item_author, permalink, publish_date, feed_section, feed_title, capture_date)
            VALUES
            ( :river_source_id, :feed_item_id, :item_url, :item_title, :item_author, :permalink,
            :publish_date, :feed_section, :feed_title, :capture_date)
            ON DUPLICATE KEY UPDATE capture_date = :capture_date" );
        $item_update_query->bindValue( ':river_source_id', 2 );
        $item_update_query->bindParam( ':feed_item_id', $this_item_id );
        $item_update_query->bindParam( ':item_url', $this_item_url );
        $item_update_query->bindParam( ':item_title', $item->title );
        $item_update_query->bindParam( ':item_author', $item->postedBy );
        $item_update_query->bindParam( ':permalink', $this_discussion_url );
        $item_update_query->bindParam( ':publish_date', date( 'Y-m-d H:i:s', strtotime( $item->postedAgo ) ) );
        $item_update_query->bindParam( ':feed_section', $this_item_source );
        $item_update_query->bindParam( ':feed_title', $this_item_publication );
        $item_update_query->bindParam( ':capture_date', $capture_date );
        $item_update_query->execute();
        $item_update_query = NULL;
    }

    $db = NULL;
    sleep( 120 );

}

?>