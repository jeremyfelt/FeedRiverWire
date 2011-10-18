<?php

session_start();
$last_item_id = $_SESSION[ 'last_item_id' ];
$river_source_ids = $_SESSION[ 'river_sources' ];

if ( '' != $last_item_id && $last_item_id ){
    include 'includes/Database.php';
    $db = db_connect();

    $get_most_recent_items = $db->prepare( "SELECT item_id, river_source_id, item_url, item_title, item_excerpt, body, publish_date,
        feed_section, feed_title
        FROM river_items
        WHERE river_source_id IN ( $river_source_ids ) AND item_id > :last_item_id
        ORDER BY item_id DESC" );
    $get_most_recent_items->bindParam( ':last_item_id', $last_item_id );
    $get_most_recent_items->execute();
    $most_recent_items = $get_most_recent_items->fetchAll( PDO::FETCH_ASSOC );
    $get_most_recent_items = NULL;
    $db = NULL;

    $build_item_display = '';

    foreach ( $most_recent_items as $item ){
        /*  Determine best way to display initial time data.
            TODO: Smarter way to handle time zone.
            First, subtract 28800 to make PST from UTC.
            F jS h:iA displays Month 00st 00:00AM type format.
         */
        $item_publish_date = date( 'F jS h:iA \P\S\T', ( strtotime( $item[ 'publish_date' ] ) - 28800 ) );

        if ( $last_item_id < $item[ 'item_id' ] ){
            $last_item_id = $item[ 'item_id' ];
        }

        if ( 1 == $item[ 'river_source_id' ] ){
            $extra_class = 'ows';
        }elseif ( 2 == $item[ 'river_source_id' ] ){
            $extra_class = 'hacker_news';
        }elseif ( 3 == $item[ 'river_source_id' ] ){
            $extra_class = 'nyt';
        }elseif ( 4 == $item[ 'river_source_id' ] ){
            $extra_class = 'guardian';
        }

        $build_item_display .= '<div class="water ' . $extra_class . '">';
        $build_item_display .= '<div class="water_title">';
        $build_item_display .= '<a href="' . $item[ 'item_url' ] . '">';
        if ( $item[ 'item_title' ] ) {
            $build_item_display .= $item[ 'item_title' ];
        }else{
            $build_item_display .= $item[ 'item_url' ];
        }
        $build_item_display .= '</a></div>';
        $build_item_display .= '<div class="water_published">' . $item_publish_date . '</div>';
        $build_item_display .= '<div class="water_description">';
        if ( $item[ 'item_excerpt' ] ) {
            $build_item_display .= '<p>' . $item[ 'item_excerpt' ] . '</p>';
        }elseif ( $item[ 'body' ] ) {
            $build_item_display .= '<p>' . $item[ 'body' ] . '</p>';
        }
        $build_item_display .= '</div><div class="water_sub_title"><strong>Source:</strong> ';
        $build_item_display .= $item[ 'feed_title' ] . ' | ' . $item[ 'feed_section' ] . '</div>';
        $build_item_display .= '</div><div style="clear:left;margin-top:12px;">&nbsp;</div>
        ';

    }

    $_SESSION['last_item_id'] = $last_item_id;

    echo $build_item_display;
}

session_write_close();

?>
