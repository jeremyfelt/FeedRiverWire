<?php

/*
 * Feed River Wire
 * @author Jeremy Felt <jeremy.felt@gmail.com>
 * @license MIT License - see license.txt
 */

function db_connect(){
    $database_connection = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=UTF-8' , DB_USER,
        DB_PASS , array( PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8" ) );
    $database_connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    return $database_connection;
}

function get_recent_river_items( $river_source_ids = '2,3,4', $last_item_id = 1, $limit = 20 ){

    $db = db_connect();
    $get_recent_items = $db->prepare( "SELECT item_id, river_source_id, item_url, item_title, item_excerpt,
        body, publish_date, feed_section, feed_title
        FROM river_items
        WHERE river_source_id IN ( $river_source_ids ) AND item_id > :item_id
        ORDER BY item_id DESC
        LIMIT $limit" );
    $get_recent_items->bindParam( ':item_id', $last_item_id );
    $get_recent_items->execute();
    $recent_items = $get_recent_items->fetchAll( PDO::FETCH_CLASS, 'RiverItem' );
    $get_recent_items = NULL;
    $db = NULL;
    return $recent_items;
    
}

function get_display_class( $river_source_id ){
    /*  TODO: Should be a DB based config at some point. */
    if ( 1 == $river_source_id ){
        $extra_class = 'ows';
    }elseif ( 2 == $river_source_id ){
        $extra_class = 'hacker_news';
    }elseif ( 3 == $river_source_id ){
        $extra_class = 'nyt';
    }elseif ( 4 == $river_source_id ){
        $extra_class = 'guardian';
    }
    return $extra_class;
}

function add_hn_items( $hn_data ){

    $db = db_connect();

    $decoded_data = json_decode( $hn_data );
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
        $this_item_id = 'hn_' . $item->id;

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

}

function add_nyt_items( $nyt_data ){

    $db = db_connect();

    $decoded_data = json_decode( $nyt_data );
    $capture_date = date( 'Y-m-d H:i:s' );

    foreach ( $decoded_data->results as $item ){
        $this_item_id = 'nyt_' . md5( $item->url );
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

}