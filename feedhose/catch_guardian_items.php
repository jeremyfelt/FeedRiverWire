<?php

/*  Guardian UK API Call Information */
include 'api_config.php';

$order_by = "oldest";
$fields = "all";
$format = "json";

$feed_unique_prefix = "guard";
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/Database.php');

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

    $old_seed_query = $db->prepare( "SELECT seed_id, seed FROM guard_seeds ORDER BY seed_id DESC LIMIT 1" );
    $old_seed_query->execute();
    $old_seed_query->bindColumn( 1, $old_seed_id );
    $old_seed_query->bindColumn( 2, $old_seed );
    $old_seed_query->fetch();
    $old_seed_query = NULL;

    $old_seed_date = substr( $old_seed, 0, 10 );
    $remainder_seed = explode( "-", substr( $old_seed, 11) );
    $old_count = $remainder_seed[0];
    $start_page = $remainder_seed[1];

    $thedate = date('Y-m-d',strtotime("+1 hours"));

    echo "Was given seed $old_seed <br> \n";
    echo "Parsed to date $old_seed_date <br> \n";
    echo "Parsed to old count $old_count and start page $start_page <br> \n";

    if ( $thedate > $old_seed_date ){
        $old_count = 0;
        $start_page = 1;
    }

    /*  Assume at least one page exists, set total pages to start pages. */
    $total_pages = $start_page;
    $goround = 1;

    while ( $start_page <= $total_pages ){

        $feed_url = "http://content.guardianapis.com/search?from-date=$thedate&to-date=$thedate&page=$start_page";
        $feed_url .= "&order-by=$order_by&show-fields=$fields&format=$format&api-key=$guardian_api_key";

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

        $total_items = $decoded_data->response->total;
        $current_page = $decoded_data->response->currentPage;
        $total_pages = $decoded_data->response->pages;
        $new_seed = $thedate . "-" . $total_items . "-" . $total_pages;
        $start_page++; // Increase the page count for the next API call

        if ( 1 == $goround ){
            /*  This is our first go-round, if you will. We do something different. */
            if ( $new_seed != $old_seed ){
                /*  We've discovered that the new seed indicates new feed items. Log it. */
                $add_new_seed = $db->prepare( "INSERT INTO guard_seeds (seed) VALUES ( :new_seed )" );
                $add_new_seed->bindParam( ':new_seed', $new_seed );
                $add_new_seed->execute();
                $capture_seed_id = $db->lastInsertId();
                $add_new_seed = NULL;
            }
            /*  We tell the loop to skip this if statement every other time around. */
            $goround++;
        }

        foreach( $decoded_data->response->results as $item ){
            $this_item_id = $feed_unique_prefix . '_' . md5( $item->id );
            $this_publication_date = date( 'Y-m-d H:i:s', strtotime( $item->webPublicationDate ) );
            $item_update_query = $db->prepare( "INSERT INTO river_items ( river_source_id, feed_item_id, item_url,
                item_title, item_author, item_thumbnail, publish_date, item_excerpt, body, feed_section,
                feed_title, capture_date )
                VALUES
                ( :river_source_id, :feed_item_id, :item_url, :item_title, :item_author, :item_thumbnail,
                  :publish_date, :item_excerpt, :body, :feed_section, :feed_title, :capture_date )
                ON DUPLICATE KEY UPDATE capture_date = :capture_date" );

            $item_update_query->bindValue( ':river_source_id', 4 );
            $item_update_query->bindParam( ':feed_item_id', $this_item_id );
            $item_update_query->bindParam( ':item_url', $item->webUrl );
            $item_update_query->bindParam( ':item_title', $item->webTitle );
            $item_update_query->bindParam( ':item_author', $item->fields->byline );
            $item_update_query->bindParam( ':item_thumbnail', $item->fields->thumbnail );
            $item_update_query->bindParam( ':publish_date', $this_publication_date );
            $item_update_query->bindParam( ':item_excerpt', $item->fields->trailText );
            $item_update_query->bindParam( ':body', $item->fields->body );
            $item_update_query->bindParam( ':feed_section', $item->sectionName );
            $item_update_query->bindParam( ':feed_title', $item->fields->publication );
            $item_update_query->bindParam( ':capture_date', $capture_date );
            $item_update_query->execute();
            $item_update_query = NULL;
        }
    }

    /*  After each loop, we'll close our database connection and sleep for 2 minutes
        before making another API call.
    */
    $db = NULL;
    sleep( 120 );
}

?>
