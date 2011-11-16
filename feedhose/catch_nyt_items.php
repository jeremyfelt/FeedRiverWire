<?php

require_once( dirname( dirname( __FILE__ ) ) . '/includes/config.php' );
require_once( dirname( dirname( __FILE__ ) ) . '/includes/RiverItem.php' );
require_once( dirname( dirname( __FILE__ ) ) . '/includes/functions.php' );

$feed_url = "http://api.nytimes.com/svc/news/v3/content/all/all/.json?api-key=$nyt_api_key";
$feed_unique_prefix = 'nyt';

/*  We'll run this script for a long time, so set some timing variables. */
$start_seconds = time();
$continue = 1;

while ($continue == 1){

    $current_seconds = time();
    $total_seconds = ( $current_seconds - $start_seconds );

    if ( $script_max_run_time <= $total_seconds ) {
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

    foreach ( $decoded_data->results as $item ){
        $this_item_id = $feed_unique_prefix . '_' . md5( $item->url );
        if ( "by" == strtolower( substr( $item->byline, 0, 2 ) ) ){
            $this_item_author = trim( substr( $item->byline, 2 ) );
        }else{
            $this_item_author = $item->byline;
        }

        if ( "Blog" == $item->item_type ){
            $this_feed_title = "NY Times - " . $item->blog_name;
        }else{
            $this_feed_title = $item->source;
        }

        $item_update_query = $db->prepare( "INSERT INTO river_items ( river_source_id, feed_item_id, item_url,
            item_title, item_author, publish_date, body, feed_section, feed_title, capture_date)
            VALUES
            ( :river_source_id, :feed_item_id, :item_url, :item_title, :item_author, :publish_date,
              :body, :feed_section, :feed_title, :capture_date )
            ON DUPLICATE KEY UPDATE capture_date = :capture_date" );
        $item_update_query->bindValue( ':river_source_id', 3 );
        $item_update_query->bindParam( ':feed_item_id', $this_item_id );
        $item_update_query->bindParam( ':item_url', $item->url );
        $item_update_query->bindParam( ':item_title', $item->title );
        $item_update_query->bindParam( ':item_author', $this_item_author );
        $item_update_query->bindParam( ':publish_date', date( 'Y-m-d H:i:s', strtotime( $item->updated_date ) ) );
        $item_update_query->bindParam( ':body', $item->abstract );
        $item_update_query->bindParam( ':feed_section', $item->section );
        $item_update_query->bindParam( ':feed_title', $this_feed_title );
        $item_update_query->bindParam( ':capture_date', $capture_date );
        $item_update_query->execute();
        $item_update_query = NULL;
    }

    $db = NULL;
    sleep( 120 );

}

?>
