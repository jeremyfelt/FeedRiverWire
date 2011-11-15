<?php

session_start();
$last_item_id = $_SESSION[ 'last_item_id' ];
$river_source_ids = $_SESSION[ 'river_sources' ];

if ( '' != $last_item_id && $last_item_id ){
    require_once( dirname( __FILE__) . '/includes/Database.php' );
    require_once( dirname( __FILE__) . '/includes/RiverItem.php' );
    require_once( dirname( __FILE__) . '/includes/functions.php' );

    $recent_river_items = get_recent_river_items( $river_source_ids, $last_item_id );

    $build_item_display = '';

    foreach ( $recent_river_items as $river_item ){
        /*  Determine best way to display initial time data.
            TODO: Smarter way to handle time zone.
            First, subtract 28800 to make PST from UTC.
            F jS h:iA displays Month 00st 00:00AM type format.
         */
        $item_publish_date = date( 'F jS h:iA \P\S\T', ( strtotime( $river_item->publish_date ) - 28800 ) );

        if ( $last_item_id < $river_item->item_id ){
            $last_item_id = $river_item->item_id;
        }

        $extra_display_class = get_display_class( $river_item->river_source_id );

        $build_item_display .= '<div class="water ' . $extra_display_class . '">';
        $build_item_display .= '<div class="water_title">';
        $build_item_display .= '<a href="' . $river_item->item_url . '">';

        $build_item_display .= $river_item->item_title ? $river_item->item_title : $river_item->item_url;

        $build_item_display .= '</a></div>';
        $build_item_display .= '<div class="water_published">' . $item_publish_date . '</div>';
        $build_item_display .= '<div class="water_description">';
        if ( $river_item->item_excerpt ){
            $build_item_display .= '<p>' . $river_item->item_excerpt . '</p>';
        }elseif ( $river_item->body ) {
            $build_item_display .= '<p>' . $river_item->body . '</p>';
        }
        $build_item_display .= '</div><div class="water_sub_title"><strong>Source:</strong> ';
        $build_item_display .= $river_item->feed_title . ' | ' . $river_item->feed_section . '</div>';
        $build_item_display .= '</div><div style="clear:left;margin-top:12px;">&nbsp;</div>
        ';

    }

    $_SESSION['last_item_id'] = $last_item_id;

    echo $build_item_display;
}

session_write_close();

?>