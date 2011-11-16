<?php

session_start();

if ( 1 == abs( $_GET[ 'option' ] ) ){
    /*  We're replacing our source list completely. */
    $_SESSION[ 'river_sources' ] = abs( $_GET[ 'add_source_id' ] );
}elseif ( 2 == abs( $_GET[ 'option' ] ) ){
    /*  We're adding to our current list of source. */
    $current_sources = $_SESSION[ 'river_sources' ];
    $to_match = abs( $_GET[ 'add_source_id' ] );
    $to_match = strval ( $to_match );
    if ( ! preg_match( '[' . $to_match . ']', $current_sources ) ){
        $current_sources .= ",$to_match";
        $_SESSION[ 'river_sources' ] = $current_sources;
    }
}elseif ( 3 == abs( $_GET[ 'option' ] ) ){
    /*  We're using all sources. */
    $_SESSION[ 'river_sources' ] = "1,2,3";
}

session_write_close();
header("Location:index.php");
?>
