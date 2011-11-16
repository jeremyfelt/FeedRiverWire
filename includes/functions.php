<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Jeremy
 * Date: 11/15/11
 * Time: 2:36 PM
 * To change this template use File | Settings | File Templates.
 */

function db_connect(){
    $database_connection = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=UTF-8' , DB_USER,
        DB_PASS , array( PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8" ) );
    $database_connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    return $database_connection;
}

function get_recent_river_items( $river_source_ids = '1,2,3,4', $last_item_id = 1, $limit = 20 ){

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