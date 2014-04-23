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
     *  Performs a structured, safe insertion into a table in the databse.
     *
     *  @param table  The name or Table object of the table in which to insert
     *  @param values An associative array of values to store in the table.
     *                The keys of which must match the table's field names.
     *  @param ignore A list of fields that should be ignored, if given
     *  @return       The insertion ID of the new record in the table
     *  @throws DatabaseException
     *                1. if the query fails mysqli::prepare()
     *                2. if the parameters can be bound
     *                3. if the query fails execution
     */
    public function auto_insert( $table, $values, $ignore = null ) {

        //check for having a table object passed
        if( ( $table instanceof \hzphp\DBUtil\Table ) == false ) {

            //create the table object assuming we were given a name
            $table = $this->get_table( $table );
        }

        //set up an insert query
        $mq = new \hzphp\DBUtil\MutateQuery( $table, $values );
        $mq->setIgnoredFields( $ignore );
        list( $query, $types, $vlist ) = $mq->getInsert();

        //prepare a query statement
        $statement = $this->prepare( $query );
        if( $this->errno != 0 ) {
            throw new DatabaseException(
                "Unable to insert (prepare): " . $this->error
            );
        }

        //construct an array creating (or adding) references to the value list
        $bp_args = [ $types ];
        foreach( $vlist as $i => $v ) {
            $bp_args[] = &$vlist[ $i ];
        }

        //bind the parameters to the statement
        call_user_func_array( [ $statement, 'bind_param' ], $bp_args );
        if( $this->errno != 0 ) {
            throw new DatabaseException(
                "Unable to insert (bind): " . $this->error
            );
        }

        //execute the statement
        $result = $statement->execute();
        if( $this->errno != 0 ) {
            throw new DatabaseException(
                "Unable to insert (execute): " . $this->error
            );
        }

        //fetch the ID of the inserted record
        $id = $statement->insert_id;

        //release the statement's resources
        $statement->close();

        //return the ID of the inserted record
        return $id;
    }


    /**
     *  Performs a structured, safe update to a record in a table.
     *
     *  @param table  The name or Table object of the table in which to insert
     *  @param id     The target record's ID value
     *  @param values An associative array of values to update in the record.
     *                The keys of which must match the table's field names.
     *  @param ignore A list of fields that should be ignored, if given
     *  @throws DatabaseException
     *                1. if the query fails mysqli::prepare()
     *                2. if the parameters can be bound
     *                3. if the query fails execution
     */
    public function auto_update( $table, $id, $values, $ignore = null ) {

        //check for having a table object passed
        if( ( $table instanceof \hzphp\DBUtil\Table ) == false ) {

            //create the table object assuming we were given a name
            $table = $this->get_table( $table );
        }

        //set up an update query
        $mq = new \hzphp\DBUtil\MutateQuery( $table, $values );
        $mq->setIgnoredFields( $ignore );
        list( $query, $types, $vlist ) = $mq->getUpdate( $id );

        //prepare a query statement
        $statement = $this->prepare( $query );
        if( $this->errno != 0 ) {
            throw new DatabaseException(
                "Unable to update (prepare): " . $this->error
            );
        }

        //construct an array creating (or adding) references to the value list
        $bp_args = [ $types ];
        foreach( $vlist as $i => $v ) {
            $bp_args[] = &$vlist[ $i ];
        }

        //bind the parameters to the statement
        call_user_func_array( [ $statement, 'bind_param' ], $bp_args );
        if( $this->errno != 0 ) {
            throw new DatabaseException(
                "Unable to update (bind): " . $this->error
            );
        }

        //execute the statement
        $result = $statement->execute();
        if( $this->errno != 0 ) {
            throw new DatabaseException(
                "Unable to update (execute): " . $this->error
            );
        }

        //release the statement's resources
        $statement->close();
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
     *  Performs a prepared query that expects to retrieve one record, and
     *  returns the result as an associative array.
     *
     *  @param query The query to perform
     *  @param types The prepared type string /[idsb]+/
     *  @param vlist The list of values to substitute in the query
     *  @return      An associative array containing the record, false if an
     *               an error occurred, or null if the record was not found
     *  @throws DatabaseException
     *               1. if the query fails mysqli::prepare()
     *               2. if the parameters can't be bound to the statement
     *               3. if the statement fails execution
     */
    public function fetch_one_assoc( $query, $types = null, $vlist = null ) {

        //create the statement
        $statement = $this->prepare( $query );
        if( $this->errno != 0 ) {
            throw new DatabaseException( 'Prepare failed: ' . $this->error );
        }

        //check for query parameters
        if( $types != null ) {

            //make an array creating (or adding) references to the value list
            $bp_args = [ $types ];
            foreach( $vlist as $i => $v ) {
                $bp_args[] = &$vlist[ $i ];
            }

            //bind the parameters to the statement
            call_user_func_array( [ $statement, 'bind_param' ], $bp_args );
            if( $this->errno != 0 ) {
                throw new DatabaseException( 'Bind failed: ' . $this->error );
            }
        }

        //execute the statement
        $statement->execute();
        if( $this->errno != 0 ) {
            throw new DatabaseException( 'Execute failed: ' . $this->error );
        }

        //initialize some stack space
        $assoc  = [];
        $output = [];

        //fetch the list of fields that were returned from the query
        $meta = $statement->result_metadata();
        while( $field = $meta->fetch_field() ) {

            //add an element to the associative array to store the record
            $assoc[ $field->name ] = null;

            //set an reference to the element for query output
            $output[] = &$assoc[ $field->name ];
        }

        //bind the output references to the statement
        call_user_func_array( [ $statement, 'bind_result' ], $output );

        //fetch the result of the query
        $result = $statement->fetch();
        $statement->close();

        //make sure the query succeeded
        if( $result != true ) {

            //mysqli_stmt::fetch() returns false on errors, null on empty
            return $result;
        }

        //return the record as an associative array
        return $assoc;
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