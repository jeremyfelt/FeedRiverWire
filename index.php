<?php

require_once( dirname( __FILE__ ) . '/includes/config.php' );
require_once( dirname( __FILE__ ) . '/includes/RiverItem.php' );
require_once( dirname( __FILE__ ) . '/includes/functions.php' );

session_start();
$_SESSION[ 'last_item_id' ] = NULL;

/*  This is a hacky way to determine what sources we're looking at. */
if ( ! $_SESSION[ 'river_sources' ] ) {
    $_SESSION[ 'river_sources' ] = '1,2,3';
}

$river_source_ids = $_SESSION[ 'river_sources' ];

$recent_river_items = get_recent_river_items( $river_source_ids, 1, 20 );

$last_item_id = 1;
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

    if ( $river_item->item_title ) {
        $build_item_display .= $river_item->item_title;
    }else{
        $build_item_display .= $river_item->item_url;
    }

    $build_item_display .= '</a></div>';
    $build_item_display .= '<div class="water_published">' . $item_publish_date . '</div>';
    $build_item_display .= '<div class="water_description">';
    if ( $river_item->item_excerpt ) {
        $build_item_display .= '<p>' . $river_item->item_excerpt . '</p>';
    }elseif ( $river_item->body ) {
        $build_item_display .= '<p>' . $river_item->body . '</p>';
    }
    $build_item_display .= '</div><div class="water_sub_title"><strong>Source:</strong> ';
    $build_item_display .= $river_item->feed_title . ' | ' . $river_item->feed_section . '</div>';
    $build_item_display .= '</div><div style="clear:left;margin-top:12px;height:10px;"><br></div>
    ';

}

$_SESSION['last_item_id'] = $last_item_id;
session_write_close();


?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Feed River Wire</title>
	<script src="http://code.jquery.com/jquery-latest.min.js"></script>
	<link href='http://fonts.googleapis.com/css?family=Neuton&subset=latin' rel='stylesheet' type='text/css'>
	<link rel="stylesheet" href="style.css">
</head>
<body>
<h1>Feed River Wire</h1>
<div class="infobox">An expirement by <a href="http://www.jeremyfelt.com">Jeremy Felt</a>.
Each column will update with any new headlines every 3 seconds. Stick around for a bit to see the river flow. Built on
    the <a href="http://developer.nytimes.com/docs">New York
    Times API</a>, the <a href="http://www.guardian.co.uk/open-platform">Guardian's Open Platform</a>, and
    <a href="http://ronnieroller.com/">Ronnie Roller's</a> <a href="http://api.ihackernews.com/">Hacker News
    API</a>.</div>
<div class="infobox" style="max-width:840px;"><b>Sources:</b> <a href="set_source.php?option=3">All</a> 
| NY Times <a href="set_source.php?option=1&add_source_id=3">replace</a> or <a href="set_source.php?option=2&add_source_id=3">add</a>
| Guardian <a href="set_source.php?option=1&add_source_id=4">replace</a> or <a href="set_source.php?option=2&add_source_id=4">add</a>
| Hacker News <a href="set_source.php?option=1&add_source_id=2">replace</a> or <a href="set_source.php?option=2&add_source_id=2">add</a>
</div>
<div class="rivers">
    <div class="big_rivers">
        <div class="big_main_river" id="river_flow">
            <?php echo $build_item_display; ?>
        </div>
    </div>
</div>
<script>
function loadwires(){
    $.get("script_wire_data.php", function(data){$("#river_flow").prepend(data);});
}
setInterval(loadwires,10000);
</script>
<?php if ( $google_analytics_id ) include dirname( __FILE__ ) . '/extras/google_analytics.php'; ?>
<?php if ( $github_fork_display ) include dirname( __FILE__ ) . '/extras/forkme_code.php'; ?>
</body>
</html>
