<?php

/*
 * Feed River Wire
 * @author Jeremy Felt <jeremy.felt@gmail.com>
 * @license MIT License - see license.txt
 */

/*  This script pulls the latest data from the New York Times API,
    which needs only an API key to get started. It does not provide
    full text of the articles, and in no way side steps the paywall,
    but it's still great for the headlines and excerpts.

    In order for this script to work, you will need to add your
    NYT API to the includes/config.php file. */

/*  All the stuff we need, right. */
require_once( dirname( dirname( __FILE__ ) ) . '/includes/config.php' );
require_once( dirname( dirname( __FILE__ ) ) . '/includes/RiverItem.php' );
require_once( dirname( dirname( __FILE__ ) ) . '/includes/functions.php' );


$feed_url = "http://api.nytimes.com/svc/news/v3/content/all/all/.json?api-key=$nyt_api_key";
$feed_unique_prefix = 'nyt';

$start_seconds = time();

while ( $script_max_run_time > ( time() - $start_seconds ) ){

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