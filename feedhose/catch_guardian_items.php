<?php

/*  This script pulls the latest available data from the Guardian's Open Platform,
    which is a really great service - http://www.guardian.co.uk/open-platform

    There aren't too many restrictions on what can be acccessed, but you do need an
    API key. This is (a) free and (b) configured in includes/config.php

    Currently we track a seed in a separate table called guard_seeds, but this will
    probably change to be stored in the sources table. A seed is required for maximum
    efficiency when using the API though. */

/*  Boot it up. Or something. */
require_once( dirname( dirname( __FILE__ ) ) . '/includes/config.php' );
require_once( dirname( dirname( __FILE__ ) ) . '/includes/RiverItem.php' );
require_once( dirname( dirname( __FILE__ ) ) . '/includes/functions.php' );
$feed_site = 'http://content.guardianapis.com/search';
$feed_unique_prefix = "guard"; // TODO: store in sources table
$start_seconds = time(); // This will feed into the loop below and help determine when to quit.

while ( $script_max_run_time > ( time() - $start_seconds ) ){

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

    $thedate = date( 'Y-m-d', strtotime( "+1 hours" ) );

    if ( $thedate > $old_seed_date ){
        $old_count = 0;
        $start_page = 1;
    }

    /*  Assume at least one page exists, set total pages to start pages. */
    $total_pages = $start_page;
    $goround = 1;

    while ( $start_page <= $total_pages ){

        $feed_url = sprintf( '%1$s?from-date=%2$s&to-date=%2$s&page=%3$d&order-by=oldest&show-fields=all&format=json&api-key=%4$s',
                             $feed_site, $thedate, $start_page, $guardian_api_key );

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