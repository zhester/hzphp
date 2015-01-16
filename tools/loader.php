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

    //set the directory that contains the library
    $dir = dirname( dirname( __DIR__ ) );

    //see if this could be a shared exception (should be a rare event)
    if( substr_compare( $path, 'Exception', -9 ) == 0 ) {
        $modpath = substr( $path, 0, strrpos( $path, '/' ) );
        $file = $dir . '/' . $modpath . '/Exceptions.php';
        if( file_exists( $file ) == true ) {
            require $file;
            return;
        }
    }

    //require the file that should contain the class
    require $dir . '/' . $path . '.php';

}


//register the class loader
spl_autoload_register( 'hzphp_loader' );


