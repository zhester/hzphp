<?php

/*----------------------------------------------------------------------------
Namespace
----------------------------------------------------------------------------*/

namespace hzphp\emysqli;


/*----------------------------------------------------------------------------
Exceptions
----------------------------------------------------------------------------*/

class DatabaseException extends \RuntimeException {}


/**
 *  Extended mysqli Features and Utilities
 *
 *  The intent is not to abstract or supplant the mysqli interface, but to
 *  enhance it with common use-case features throughout an application.  Some
 *  of these features make use of a few unique capabilities of mysqli.
 */
class emysqli extends \mysqli {

    /*------------------------------------------------------------------------
    Class Constants
    ------------------------------------------------------------------------*/


    /*------------------------------------------------------------------------
    Public Properties
    ------------------------------------------------------------------------*/


    /*------------------------------------------------------------------------
    Protected Properties
    ------------------------------------------------------------------------*/


    /*------------------------------------------------------------------------
    Private Properties
    ------------------------------------------------------------------------*/


    /*------------------------------------------------------------------------
    Public Methods
    ------------------------------------------------------------------------*/

    /**
     *  Instantiates a database interface object.
     *
     *  @param host     Host name of the DBMS host
     *  @param username User name for the DBMS credentials
     *  @param passwd   Password for the DBMS credentials
     *  @param dbname   Name of the database to use
     *  @param port     TCP port number of the DBMS host service
     *  @param socket   Socket or named pipe to use
     */
    public function __construct(
        $host     = null,
        $username = null,
        $passwd   = null,
        $dbname   = null,
        $port     = null,
        $socket   = null
    ) {

        //invoke the parent constructor
        parent::__construct();

        //set internal numeric conversion option
        $this->options( MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1 );

        //pull the list of arguments that were passed
        $args = func_get_args();

        //check for an attempt to auto-detect configuration from constants
        if( $host === null ) {

            //auto-detect configuration
            if( defined( 'DB_HOST' ) == true ) { $args[ 0 ] = DB_HOST; }
            if( defined( 'DB_USER' ) == true ) { $args[ 1 ] = DB_USER; }
            if( defined( 'DB_PASS' ) == true ) { $args[ 2 ] = DB_PASS; }
            if( defined( 'DB_NAME' ) == true ) { $args[ 3 ] = DB_NAME; }
        }

        //connect to the DBMS
        call_user_func_array( [ $this, 'real_connect' ], $args );

        //check for issues with the connectioni
        if( $this->connect_errno != 0 ) {
            throw new DatabaseException(
                'Unable to connect to database: ' . $this->connect_error
            );
        }
    }


    /**
     *  Properly creates a multi-query object for executing multiple queries
     *  with one request to the DBMS host.
     *
     *  @return A multi-query object
     */
    public function create_multiquery() {
        return new multiquery( $this );
    }


    /**
     *  Fetches a table object representing a table in the current database.
     *
     *  @param name The name of the table to fetch
     *  @return     A hzphp\DBUtil\Table instance
     */
    public function get_table( $name ) {
        return new \hzphp\DBUtil\Table( $this, $name );
    }


    /*------------------------------------------------------------------------
    Protected Methods
    ------------------------------------------------------------------------*/


    /*------------------------------------------------------------------------
    Private Methods
    ------------------------------------------------------------------------*/


}


/*----------------------------------------------------------------------------
Testing
----------------------------------------------------------------------------*/

if( $_SERVER[ 'SCRIPT_FILENAME' ] == __FILE__ ) {
    //$db = new emysqli();
}

?>