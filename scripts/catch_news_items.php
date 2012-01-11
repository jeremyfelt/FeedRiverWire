<?php

/*
 * Feed River Wire
 * @author Jeremy Felt <jeremy.felt@gmail.com>
 * @license MIT License - see license.txt
 */

require_once( dirname( dirname( __FILE__ ) ) . '/includes/config.php' );
require_once( dirname( dirname( __FILE__ ) ) . '/includes/RiverItem.php' );
require_once( dirname( dirname( __FILE__ ) ) . '/includes/functions.php' );

$hn_feed_url = "http://api.ihackernews.com/new";
$hn_feed_unique_prefix = 'hn';

$nyt_feed_url = "http://api.nytimes.com/svc/news/v3/content/all/all/.json?api-key=$nyt_api_key";
$nyt_feed_unique_prefix = 'nyt';

$start_seconds = time();

while ( $script_max_run_time > ( time() - $start_seconds ) ){

    $db = db_connect();

    $ch_hn = curl_init();
    curl_setopt ( $ch_hn, CURLOPT_URL, $hn_feed_url );
    curl_setopt ( $ch_hn, CURLOPT_HEADER, 0 ); /// Header control
    curl_setopt ( $ch_hn, CURLOPT_POST, false );  /// tell it to make a POST, not a GET
    curl_setopt ( $ch_hn, CURLOPT_TIMEOUT, 60 );
    curl_setopt ( $ch_hn, CURLOPT_RETURNTRANSFER, 1 );

    $ch_nyt = curl_init();
    curl_setopt ( $ch_nyt, CURLOPT_URL, $nyt_feed_url );
    curl_setopt ( $ch_nyt, CURLOPT_HEADER, 0 ); /// Header control
    curl_setopt ( $ch_nyt, CURLOPT_POST, false );  /// tell it to make a POST, not a GET
    curl_setopt ( $ch_nyt, CURLOPT_TIMEOUT, 60 );
    curl_setopt ( $ch_nyt, CURLOPT_RETURNTRANSFER, 1 );

    $feeds_handler = curl_multi_init();

    curl_multi_add_handle( $feeds_handler, $ch_hn );
    curl_multi_add_handle( $feeds_handler, $ch_nyt );

    $active_request = NULL;

    do {
        curl_multi_exec( $feeds_handler, $active_request );
    }
    while ( $active_request );

    $hn_data = curl_multi_getcontent( $ch_hn );
    $nyt_data = curl_multi_getcontent( $ch_nyt );

    curl_multi_remove_handle( $feeds_handler, $ch_hn );
    curl_multi_remove_handle( $feeds_handler, $ch_nyt );

    curl_multi_close( $feeds_handler );

    curl_close( $ch_hn );
    curl_close( $ch_nyt );

    add_hn_items( $hn_data );
    add_nyt_items( $nyt_data );

    sleep( 120 );
}