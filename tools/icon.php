<?php
/*****************************************************************************
    Icon Delivery Host Script

    Allows the client to request dynamically-generated SVG icons.  Icons can
    be any size or color.  The entire set of icons can be requested for use as
    a sprite set.

    Supported GET Query Parameters (all are optional)
        color           Foreground color of the icon shapes (CSS color value)
        css             [See Below]
        css_prefix      Changes CSS selector prefix (default: .button)
        id              Fetch a single icon by ID
        pad             Inter-icon spacing (for sprite sheets only)
        meta            [See below]
        set             Source of icon data by name (currently only 'Iconic')
        size            Output size of the icons in pixels

    A meta query can be performed to return icon database information in JSON.
    Use meta=* (only value currently supported) to see helpful information
    about how to query the icon set.

    CSS rules for positioning the sprite sheet as individual icons can be
    fetched by setting the css parameter to '1' (or, just about anything).
    All other parameters that might impact the generated rules are taken into
    account.

    In the event that an invalid icon ID is requested, an "unknown" icon is
    sent.  This should make it easier to debug problems than returning HTTP
    response codes (since your client is unlikely to show these for images
    that occur inside other documents).
*****************************************************************************/

require __DIR__ . '/loader.php';

//select the icon database
if( ( isset( $_GET[ 'set' ] ) == true )
 && ( class_exists( 'hzphp\\Icon\\' . $_GET[ 'set' ] ) == true ) ) {
    $class = 'hzphp\\Icon\\' . $_GET[ 'set' ];
    $db = new $class();
}
else {
    $db = new hzphp\Icon\Iconic();
}

//create the icon set manager
$set = new hzphp\Icon\IconSet( $db );

//override any configs specified in the request
$set->setConfigs( $_GET );

//check for a meta query
if( ( isset( $_GET[ 'meta' ] ) ) && ( $_GET[ 'meta' ] == '*' ) ) {
    $meta = [
        'ids' => $db->ids,
        'size' => [
            'design' => $db->getSize(),
            'output' => $set->getConfig( 'size' )
        ],
        'css' => $set->getCSS()
    ];
    $json = json_encode( $meta, JSON_PRETTY_PRINT );
    header( 'Content-Type: application/json' );
    header( 'Content-Length: ' . strlen( $json ) );
    echo $json;
    exit();
}

//check for a style sheet request
if( isset( $_GET[ 'css' ] ) == true ) {
    $css = $set->getCSS();
    header( 'Content-Type: text/css' );
    header( 'Content-Length: ' . strlen( $css ) );
    echo $css;
    exit();
}

//check for an individual icon being requested
if( isset( $_GET[ 'id' ] ) == true ) {
    $svg = $set->getSVG( $_GET[ 'id' ] );
}

//the entire set is being requested
else {
    $svg = $set->getSVG();
}

header( 'Content-Type: image/svg+xml' );
header( 'Content-Length: ' . strlen( $svg ) );
echo $svg;

