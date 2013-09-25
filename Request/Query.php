<?php

namespace hzphp\Request;


/**
 *  Parses and stores query information.
 */
class Query {


    protected           $parts;
    protected           $string;


    /**
     *  Initializes the object.
     *
     *  @param string   The string to parse for query details
     */
    public function __construct(
        $string = ''
    ) {
        $this->loadString( $string );
    }


    /**
     *  Retrieves a top-level part of the query.
     *
     *  Valid keys: method,scheme,host,port,user,pass,path,query,forks,mods
     *
     *  @param key
     *  @return
     *  @throws
     */
    public function __get(
        $key
    ) {
        if( isset( $this->parts[ $key ] ) ) {
            return $this->parts[ $key ];
        }
        throw new Exception( 'Invalid component name: ' . $key );
    }


    /**
     *  Represents the Query object as a string (in JSON).
     *
     *  @return         A JSON string of the parsed query
     */
    public function __toString() {
        return json_encode(
            [ 'source' => $this->string, 'parts' => $this->parts ]
        );
    }


    /**
     *  Loads a query into the object from a string.
     *
     *  This uses PHP's internal parse_url() implementation to attempt to
     *  parse any type of query (complete URL, just the URI, etc).
     *  Additionally, we expand any query parameters (using parse_str()) into
     *  an associative array, break apart the path into an array of strings,
     *  and provide a simple path element modification expansion (could be
     *  used to handle parameters specific to a path element, tree overlays,
     *  name spaces, etc).
     *
     *  Example of a big query:
     *
     *      http://domain/path:option=b:enabled/to/target?key=value&a=b
     *
     *  Assuming the handler script is in the root of the site, we get this:
     *
     *  [
     *      'method' => 'GET',
     *      'scheme' => 'http',
     *      'host'   => 'domain',
     *      'port'   => 80,
     *      'user'   => false,
     *      'pass'   => false,
     *      'path'   => '/path:option=b:enabled/to/target',
     *      'query'  => [ 'key' => 'value', 'a' => 'b' ],
     *      'forks'  => [ 'path', 'to', 'target' ],
     *      'mods'   => [ [ [ 'option', 'b' ], 'enabled' ], false, false ]
     *  ]
     *
     *  @param string   The query string to load
     */
    public function loadString(
        $string
    ) {

        $this->parts = [
            'method' => $_SERVER[ 'REQUEST_METHOD' ],
            'scheme' => 'http',
            'host'   => $_SERVER[ 'HTTP_HOST' ],
            'port'   => 80,
            'user'   => false,
            'pass'   => false,
            'path'   => false,
            'query'  => false,
            'forks'  => [],
            'mods'   => []
        ];

        //store the string
        $this->string = $string;

        //parse the string
        $url = parse_url( $string );

        //overwrite the default query parts
        if( $url != false ) {
            foreach( $url as $key => $value ) {
                $this->parts[ $key ] = $value;
            }
        }

        //expand the query parameters
        if( $this->parts[ 'query' ] != false ) {
            $query = [];
            parse_str( $this->parts[ 'query' ], $query );
            $this->parts[ 'query' ] = $query;
        }

        //this only happens when parse_url() couldn't make sense of the string
        if( $this->parts[ 'path' ] == false ) {
            $this->parts[ 'path' ] = $string;
        }

        //set up the path's fork list
        $path = trim( $this->parts[ 'path' ], '/' );
        $prepath = trim( $_SERVER[ 'SCRIPT_NAME' ], '/' );
        $prepath = explode( '/', $prepath );
        $forks = array_slice( explode( '/', $path ), count( $prepath ) );

        //set up each fork's mod list
        $length = count( $forks );
        $mods = array_pad( [], $length, false );
        for( $i = 0; $i < $length; ++$i ) {
            $f = $forks[ $i ];
            if( strpos( $f, ':' ) !== false ) {
                $mods[ $i ] = explode( ':', $f );
                $forks[ $i ] = array_shift( $mods[ $i ] );
                for( $j = 0; $j < count( $mods[ $i ] ); ++$j ) {
                    $mods[ $i ][ $j ] = preg_split(
                        '/[,=|]/',
                        $mods[ $i ][ $j ]
                    );
                }
            }
        }

        //assign the fork and fork mod lists
        $this->parts[ 'forks' ] = $forks;
        $this->parts[ 'mods'  ] = $mods;
    }


}

?>