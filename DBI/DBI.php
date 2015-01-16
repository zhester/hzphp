<?php

namespace hzphp\DBI;


/**
 *  Database Interface Abstraction Layer
 *
 *  This provides a very high-level layer of abstraction to separate
 *  presentation and mutation of the contents of the database from the types
 *  and queries used to manipulate the persistent storage.
 */
class DBI {


    protected static    $configs     = [ '_default' => null ];
                                    //list of all known data source URLs
    protected static    $connections = [ '_default' => null ];
                                    //list of all connection objects
    protected static    $current     = '_default';
                                    //the current connection name


    /**
     *  Private constructor to prevent instantiation.
     *
     */
    private function __construct() {
    }


    /**
     *  Gets the implementation-specific connection object.
     *
     *  This should only be used for debugging.  The project's Set and Element
     *  objects should communicate with the connection object to fulfill
     *  requests and error checking.
     *
     *  @param name     The optional user-supplied name of the data source
     *  @return         The connection object
     */
    public static function getConnection(
        $name = '_default'
    ) {
        return self::$connections[ $name ];
    }


    /**
     *  Retrieves an instance of the specified element object.
     *
     *  @param name     The name of the element's class
     *  @param id       The element's ID value
     *  @param query_name
     *                  Optional alternate query name for fetching the element
     *  @param arguments
     *                  Arguments to pass to the alternate query
     *  @return         An instance of the element object
     *  @throws Exception
     *                  If the element name is not valid
     */
    public static function getElement(
        $name,
        $id,
        $query_name = null,
        $arguments  = null
    ) {

        $class = __NAMESPACE__ . '\\Element';

        if( ( class_exists( $name )           == true )
         && ( is_subclass_of( $name, $class ) == true ) ) {
            $element = new $name();
            //ZIH - magic setup stuff with $element object goes here
            return $element;
        }

        throw new \Exception( 'Invalid element name: ' . $name );
    }


    /**
     *  Retrieves an instance of the specified set object.
     *
     *  @param name     The name of the set's class
     *  @param query_name
     *                  Optional alternate query name for fetching the set
     *  @param arguments
     *                  Arguments to pass to the alternate query
     *  @return         An instance of the set object
     *  @throws Exception
     *                  If the set name is not valid
     */
    public static function getSet(
        $name,
        $query_name = null,
        $arguments  = null
    ) {

        $class = __NAMESPACE__ . '\\Set';

        if( ( class_exists( $name )           == true )
         && ( is_subclass_of( $name, $class ) == true ) ) {
            $set = new $name();
            //ZIH - magic setup stuff with $set object goes here
            return $set;
        }

        throw new \Exception( 'Invalid set name: ' . $name );
    }


    /**
     *  Initializes a data source for future interactions.
     *
     *  @param url      The data source URL (scheme://user:pass@host/name)
     *  @param name     Optional alias to support multiple sources
     */
    public static function init(
        $url,
        $name = '_default'
    ) {
        self::$configs[ $name ]     = $url;
        self::$connections[ $name ] = null;
    }


    /**
     *  Selects the current data source to use.
     *
     *  @param name     The optional user-supplied name of the data source
     *  @return         True if selected, false on failure
     */
    public static function use(
        $name = '_default'
    ) {
        if( isset( self::$configs[ $name ] ) ) {
            self::$current = $name;
            return true;
        }
        return false;
    }


    /**
     *  Lazily checks for the data source "connection" object.
     *
     *  @return         Current connection instance
     *  @throws Exception
     *                  If an unsupported scheme is specified
     */
    protected static checkConnection() {

        $alias = self::$current;

        if( is_null( self::$connections[ $alias ] ) == true ) {

            $info = parse_url( self::$configs[ $alias ] );

            $scheme = 'mysqli';
            if( isset( $info[ 'scheme' ] ) == true ) {
                $scheme = $info[ 'scheme' ];
            }

            $class = __NAMESPACE__ . '\\' . $scheme . '\\Connection';

            switch( $scheme ) {

                case 'mysqli':
                    self::$connections[ $alias ] = new $class( $info );
                    break;

                default:
                    throw new \Exception(
                        'Unsupported data source scheme: ' . $scheme
                    );
                    break;

            }
        }

        return self::$connections[ $alias ];
    }


}


