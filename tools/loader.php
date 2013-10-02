<?php
/*****************************************************************************

    loader.php

    Generic class loader for the hzphp library.  Include this from anywhere
    to access any hzphp class.

    This script assumes it is in the hzphp/tools directory, and that the
    module directories are kept in the hzphp directory.

*****************************************************************************/


/**
 *  Class loader function for the hzphp library
 *
 *  @param class The full name of the class to load
 */
function hzphp_loader( $class ) {

    //absolute namespaces have a leading namespace separator
    $class = ltrim( $class, '\\' );

    //convert namespace separators to path separators
    $path = str_replace( '\\', '/', $class );

    //require the file that contains the class
    require dirname( dirname( __DIR__ ) ) . '/' . $path . '.php';

}


//register the class loader
spl_autoload_register( 'hzphp_loader' );


?>