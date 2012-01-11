<?php

/*
 * Feed River Wire
 * @author Jeremy Felt <jeremy.felt@gmail.com>
 * @license MIT License - see license.txt
 */

/*  This script pulls the latest data from Ronnie Roller's Hacker
    News API. It's graciously offered to the world without any kind
    of API key or anything. Just a wonderful JSON feed hanging out
    in the sky.

    Right now we pull new articles as they come in. No fancy stuff
    is done with ongoing discussion, popular items, etc... Could be
    though. */

/*  Load it up. Load it up. Let it begin. */
require_once( dirname( dirname( __FILE__ ) ) . '/includes/config.php' );
require_once( dirname( dirname( __FILE__ ) ) . '/includes/RiverItem.php' );
require_once( dirname( dirname( __FILE__ ) ) . '/includes/functions.php' );

$feed_url = "http://api.ihackernews.com/new";
$feed_unique_prefix = 'hn';

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