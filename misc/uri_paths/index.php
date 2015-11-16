<?php
/*****************************************************************************

Path-style URL Handling Example
===============================

The following shows how to get Apache's mod_rewrite to send all requests under
a real directory to a base handler.  This allows requests for non-existant
paths to map to a base path without the URL containing the name of the
handler (e.g. handler.php/path/to/resource).  In practice, the .htaccess file
is placed in the same directory where the handler is (the handler should be
named so Apache can bring it up as the default handler when the bare directory
is requested).  In this case, the handler is named "index.php" so all "fake"
paths under the directory get mapped to our script rather than Apache's error
handler.

The functions contained here are intended to assist a simple application with
automatically detecting and parsing the actual path information that was
requested by the client.  Note: We can not rely on PHP's _GET array since that
is populated by the rewritten URI (in this case, I don't pass anything to it
since I'm going to automatically parse the original URI using the _SERVER
environment).

A common pitfall with this technique is that relative URLs in a browser-type
client will now be relative to the "fake" path information in the request.
This means we will now need to either:
  1. properly re-route those requests using the handler script
  2. write and maintain complicated mod_rewrite conditions
  3. use absolute URL paths (e.g. /styles/base.css) in references
  4. use absolute URLs (e.g. http://example.com/styles/base.css) in references
  5. only use this to deliver content that doesn't send references

Additionally, note that the rewrite rules will prevent the server from
triggering its own Not Found errors.  Implementations must handle URLs/URIs
for missing resources, and issue their own error responses and/or internal
logging.

Example error generation and logging:
-------------------------------------

    if( INVALID_URI ) {
        error_log( "uri '$uri' invalid or unknown resource" );
        header( 'HTTP/1.1 404 Not Found' );
        echo '<!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <title>404 Not Found</title>
    </head>
    <body>
        <h1>Not Found</h1>
        <p>The requested URL ' . $uri . ' was not found.</p>
    </body>
    </html>';
        exit();
    }

Example .htaccess for the directory where the handler (index.php) is located:
-----------------------------------------------------------------------------

    <IfModule rewrite_module>

        # Enable rewriting.
        RewriteEngine On

        # Fix requests with a leader "www" subdomain.
        RewriteCond %{HTTP_HOST} ^www\.(.+)$
        RewriteRule (.*) http://%1/$1 [R=301,L]

        # Match any requests for non-existant files or directories.
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d

        # Rewrite request into index handler.
        RewriteRule ^(.*)$ index.php [L]

    </IfModule>

*****************************************************************************/


/**
 *  Reconstructs the full request to the current resource.  This can be
 *  helpful when using 'Location:' header redirection.
 *
 *  @return A string representing the full request to the current resource.
 */
function get_full_request() {
    return $_SERVER[ 'REQUEST_SCHEME' ]
        . '://'
        . $_SERVER[ 'HTTP_HOST' ]
        . $_SERVER[ 'REQUEST_URI' ];
}


/**
 *  Parses a URI for both path information and query variables.
 *  Note: This also provides some built-in protection from misbehaving
 *  client applications and/or users.  It also does not need to be given
 *  any scheme or host information, and can act on partial URI paths.
 *
 *  @param uri Optional URI to parse (default: the current URI)
 *  @return    An associative array with two root items:
 *               path: An array of path node names
 *               vars: An associative array of query variables
 */
function parse_uri( $uri = null ) {

    //default the URI to the current URI
    if( $uri === null ) {
        $uri = $_SERVER[ 'REQUEST_URI' ];
    }

    //request information storage
    $request = [
        'path' => [],
        'vars' => []
    ];

    //time to decode URL-encoded things
    $uri = urldecode( $uri );

    //check the URI for query variables
    $qpos = strpos( $uri, '?' );

    //strip out the query variables, and clip the path string
    if( $qpos !== false ) {
        $path  = substr( $uri, 0, $qpos );
        $query = substr( $uri, ( $qpos + 1 ) );
        parse_str( $query, $request[ 'vars' ] );
    }

    //the entire URI is just the path string
    else {
        $path = $uri;
    }

    //sanitize the path
    try {
        $path = sanitize_path( $path, 0x0005 );
    }

    //since this is protective, just return something (relatively) safe
    //  instead of bombing out the application, and potentially showing a
    //  stack trace (which would make a bad situation worse)
    catch( RuntimeException $e ) {
        return $request;
    }

    //break the path information into easily-digested parts
    $request[ 'path' ] = explode( '/', trim( $path, '/' ) );

    //return the request information
    return $request;
}


/**
 *  General-purpose path sanitizer: No-mess formula!
 *
 *  @param path  The path string to sanitize
 *  @param flags Optional sanitizer flags:
 *                 0x0001: Collapse multiple slashes (default: set)
 *                 0x0004: Replace spaces with an underscore (default: clear)
 *                 0x0010: Force lower-case path members (default: clear)
 *  @param limit Optional limit on the path input string (default: 127)
 *  @return      A string that is safe to use for further parsing and/or
 *               internal reference strings.
 *  @throws RuntimeException if sanitation was deemed unsafe
 */
function sanitize_path( $path, $flags = 0x0001, $limit = 127 ) {

    //make sure this is a string before proceding
    if( is_string( $path ) !== true ) {
        throw new RuntimeException( 'Unable to sanitize non-string paths.' );
    }

    //bounds check the input path
    if( strlen( $path ) > $limit ) {
        throw new RuntimeException( "Path string exceeds limit ($limit)." );
    }

    //remove all unwanted/invalid characters from the path string
    $path = preg_replace( '#[^\w\d \\./_-]#', '', $path );

    //remove any attempts at an upward, relative path
    $path = preg_replace( '#(\\.\\./)|(/\\.\\.)#', '', $path );

    //collapse any multiple slashes into a single slash
    if( $flags & 0x0001 ) {
        $path = preg_replace( '#/+#', '/', $path );
    }

    //replace spaces with underscores
    if( $flags & 0x0004 ) {
        $path = preg_replace( '/ +/', '_', $path );
    }

    //force lower-case path members
    if( $flags & 0x0010 ) {
        $path = strtolower( $path );
    }

    //return the sanitized path
    return $path;
}


header( 'Content-Type: text/plain' );

//reconstructing the complete URL
echo "Full URL: ", get_full_request(), "\n";

//extracting the path/query information
echo json_encode( parse_uri(), JSON_PRETTY_PRINT );

