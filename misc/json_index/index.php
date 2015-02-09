<?php
/*****************************************************************************

JSON File System Index
======================

Generates simple directory indexes for use in client-side applications.

The URL scheme is based on the directory where this file is located.  All
path arguments will be "rooted" to this directory.

Requesting this script (without any query parameters) shows a list of all
files in the current directory.  To request a subdirectory, specify the `p`
parameter with a subpath value.

ZIH TODO:

- abstract a path sanitizer like the one implemented here
- refactor to use a common `get_dirs()` implementation
- support sorting (based on `verbose` fields)
- support filtering in the query (names, sizes, times)
    - still won't override the built-in filtering
- support (index) offset and length in the query

*****************************************************************************/

/*----------------------------------------------------------------------------
Dependencies
----------------------------------------------------------------------------*/

/*----------------------------------------------------------------------------
Default Namespace
----------------------------------------------------------------------------*/

/*----------------------------------------------------------------------------
Classes
----------------------------------------------------------------------------*/

/*----------------------------------------------------------------------------
Functions
----------------------------------------------------------------------------*/

/**
 * Returns a list of all file nodes for a given path.
 *
 * @param path    The path for which to build a list of file node entries.
 * @param pattern Optional filter pattern to test each node name against.
 *                The default pattern prevents returning hidden node entries.
 * @return        A list of node entries in the given path.
 */
function get_dlist( $path, $pattern = null, $ignore = null ) {
    $pattern = $pattern == null ? '/^[^.]/' : $pattern;
    $ignore = is_array( $ignore ) ? $ignore : [];
    $nodes = [];
    $dh = opendir( $path );
    if( $dh ) {
        while( ( $node = readdir( $dh ) ) !== false ) {
            if( preg_match( $pattern, $node ) == 1 ) {
                if( in_array( $node, $ignore ) ) {
                    continue;
                }
                $nodes[] = $node;
            }
        }
        closedir( $dh );
        sort( $nodes );
    }
    return $nodes;
}


/**
 * Returns a _verbose_ list of all file nodes for a given path.
 *
 * @param path    The path for which to build a list of file node entries.
 * @param pattern Optional filter pattern to test each node name against.
 *                The default pattern prevents returning hidden node entries.
 * @return        A list of node entries in the given path.
 */
function get_vdlist( $path, $pattern = null, $ignore = null ) {
    $pattern = $pattern == null ? '/^[^.]/' : $pattern;
    $ignore = is_array( $ignore ) ? $ignore : [];
    $nodes = [];
    $dh = opendir( $path );
    if( $dh ) {
        while( ( $node = readdir( $dh ) ) !== false ) {
            if( preg_match( $pattern, $node ) == 1 ) {
                if( in_array( $node, $ignore ) ) {
                    continue;
                }
                $info = stat( $path . '/' . $node );
                $nodes[] = [
                    'name' => $node,
                    'size'  => $info[ 'size' ],
                    'mtime' => $info[ 'mtime' ],
                    'ctime' => $info[ 'ctime' ]
                ];
            }
        }
        closedir( $dh );
        sort( $nodes );
    }
    return $nodes;
}


/*----------------------------------------------------------------------------
Execution
----------------------------------------------------------------------------*/

if( $_SERVER[ 'SCRIPT_FILENAME' ] == __FILE__ ) {

    //check for a verbose request
    if( isset( $_GET[ 'v' ] ) ) {
        $getter = 'get_vdlist';
    }
    else {
        $getter = 'get_dlist';
    }

    //set the root directory for indexing
    $root = __DIR__;

    //defaults: error status, no subpath, no nodes
    $status = 99;
    $path   = '';
    $nodes  = [];

    //check for subpath request
    if( isset( $_GET[ 'p' ] ) ) {

        //validate the subpath
        $path = $_GET[ 'p' ];
        $path = trim( $path, '/' );
        $test_dir = $root . '/' . $path;
        $real_dir = realpath( $test_dir );
        $pf_length = strlen( $root );
        if( substr( $real_dir, 0, $pf_length ) == $root ) {

            //see if the requested subpath is a directory
            if( is_dir( $real_dir ) ) {

                //retrieve the list of nodes from the directory
                $nodes  = $getter( $real_dir );
                $status = 0;
            }
        }
    }

    //no subpath requested
    else {

        //set name of this script to ignore in the root index
        $me = preg_quote( basename( __FILE__ ) );

        //retrieve the list of nodes from the root directory
        $nodes  = $getter( $root, '/^[^.]/', [ 'index.php' ] );
        $status = 0;
    }

    //construct a response array
    $response = json_encode(
        [
            'status' => $status,
            'prefix' => $path,
            'nodes'  => $nodes
        ]
    );

    //send the response as a JSON document
    header( 'Content-Type: application/json; charset=utf-8' );
    header( 'Content-Length: ' . strlen( $response ) );
    echo $response;
}

