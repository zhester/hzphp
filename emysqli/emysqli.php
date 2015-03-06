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
     * Instantiates a database interface object.
     *
     * @param host     Host name of the DBMS host
     * @param username User name for the DBMS credentials
     * @param passwd   Password for the DBMS credentials
     * @param dbname   Name of the database to use
     * @param port     TCP port number of the DBMS host service
     * @param socket   Socket or named pipe to use
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
     * Performs a structured, safe insertion into a table in the database.
     *
     * @param table  The name or Table object of the table in which to insert
     * @param values An associative array of values to store in the table.
     *               The keys of which must match the table's field names.
     * @param ignore A list of fields that should be ignored, if given
     * @return       The insertion ID of the new record in the table
     * @throws       DatabaseException
     *                   1. if the query fails mysqli::prepare()
     *                   2. if the parameters can be bound
     *                   3. if the query fails execution
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
        $statement = $this->create_statement( $query, $types, $vlist );

        //fetch the ID of the inserted record
        $id = $statement->insert_id;

        //release the statement's resources
        $statement->close();

        //return the ID of the inserted record
        return $id;
    }


    /**
     * Performs a structured, safe update to a record in a table.
     *
     * @param table  The name or Table object of the table in which to insert
     * @param id     The target record's ID value
     * @param values An associative array of values to update in the record.
     *               The keys of which must match the table's field names.
     * @param ignore A list of fields that should be ignored, if given
     * @return       True if the record needed to be updated, false if the
     *               request worked, but there were no changes in content
     * @throws       DatabaseException
     */
    public function auto_update( $table, $id, $values, $ignore = null ) {

        //check for having a table object passed
        if( ( $table instanceof \hzphp\DBUtil\Table ) == false ) {

            //create the table object assuming we were given a name
            $table = $this->get_table( $table );
        }

        //get the data for the current record
        $record = $this->fetch_one_assoc(
            "select * from $table where id = ?",
            'i',
            [ $id ]
        );

        //make sure the target record exists
        if( $record == false ) {
            throw new DatabaseException(
                "Unable to update non-existent record with an `id` of '$id'."
            );
        }

        //check for specified ignore column list
        $ignore = is_array( $ignore ) ? $ignore : [];

        //check passed values against stored values
        foreach( $values as $key => $value ) {

            //skip ignored keys
            if( in_array( $key, $ignore ) ) {
                continue;
            }

            //if the record doesn't have the column, the query is messed up
            if( isset( $record[ $key ] ) == false ) {
                throw new DatabaseException(
                    "Unable to update record with unknown column `$key`."
                );
            }

            //if the requested update value is the same, remove it
            if( $value == $record[ $key ] ) {
                unset( $values[ $key ] );
            }
        }

        //see if there's anything left to update
        if( count( $values ) == 0 ) {

            //return no update needed to be performed
            return false;
        }

        //set up an update query
        $mq = new \hzphp\DBUtil\MutateQuery( $table, $values );
        $mq->setIgnoredFields( $ignore );
        list( $query, $types, $vlist ) = $mq->getUpdate( $id );

        //prepare a query statement
        $statement = $this->create_statement( $query, $types, $vlist );

        //release the statement's resources
        $statement->close();

        //return that we performed the update
        return true;
    }


    /**
     * Properly creates a multi-query object for executing multiple queries
     * with one request to the DBMS host.
     *
     * @return A multi-query object
     */
    public function create_multiquery() {
        return new multiquery( $this );
    }


    /**
     * Performs a prepared query that expects to retrieve one record, and
     * returns the result as an associative array.
     *
     * @param query The query to perform
     * @param types The prepared type string /[idsb]+/
     * @param vlist The list of values to substitute in the query
     * @return      An associative array containing the record, false if an
     *              an error occurred, or null if the record was not found
     * @throws      DatabaseException
     */
    public function fetch_one_assoc( $query, $types = null, $vlist = null ) {

        //create the statement for this query
        $statement = $this->create_statement( $query, $types, $vlist );

        //get the result object for the statement
        $result = $statement->get_result();

        //check result retrieval
        if( $result === false ) {
            throw new DatabaseException(
                'Get result from statement failed: ' . $this->error
            );
        }

        //unload the first record as an associative array
        $assoc = $result->fetch_assoc();

        //release the statement resources
        $statement->close();

        //return the record as an associative array
        return $assoc;
    }


    /**
     * Performs a prepared query that expects to retrieve one or more records,
     * and returns the result as a emysqli::result instance.
     *
     * @param query The query to perform
     * @param types The prepared type string /[idsb]+/
     * @param vlist The list of values to substitute in the query
     * @return      A result object for retrieving the query results
     * @throws      DatabaseException
     */
    public function fetch_result( $query, $types = null, $vlist = null ) {

        //create the statement for this query
        $statement = $this->create_statement( $query, $types, $vlist );

        //create a new result instance for this statement
        $result = new result( $statement );

        //free the statement resources
        $statement->close();

        //return a new result instance for this query
        return $result;
    }


    /**
     * Fetches a table object representing a table in the current database.
     *
     * @param name The name of the table to fetch
     * @return     A hzphp\DBUtil\Table instance
     */
    public function get_table( $name ) {
        return new \hzphp\DBUtil\Table( $this, $name );
    }


    /**
     * Performs a query on the database without expecting any result object
     * or record.  This provides the same interface as the `fetch_*`
     * convenience methods.
     *
     * @param query The query to perform
     * @param types The prepared type string /[idsb]+/
     * @param vlist The list of values to substitute in the query
     * @throws      DatabaseException
     */
    public function send_query( $query, $types = null, $vlist = null ) {

        //create the statement
        $statement = $this->create_statement( $query, $types, $vlist );

        //free the statement
        $statement->close();
    }


    /*------------------------------------------------------------------------
    Protected Methods
    ------------------------------------------------------------------------*/

    /**
     * Creates a prepared statement for a query with a normalized interface.
     * The prepared statement is executed, and ready for error checking and/or
     * results retrieval.
     *
     * @param query The query to perform
     * @param types The prepared type string /[idsb]+/
     * @param vlist The list of values to substitute in the query
     * @return      A result object for retrieving the query results
     * @throws      DatabaseException
     */
    protected function create_statement(
        $query,
        $types = null,
        $vlist = null
    ) {

        //attempt to create the statement instance
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
                throw new DatabaseException(
                    'Bind parameters failed: ' . $this->error
                );
            }
        }

        //execute the statement
        $statement->execute();
        if( $this->errno != 0 ) {
            throw new DatabaseException( 'Execute failed: ' . $this->error );
        }

        //return the statement instance
        return $statement;
    }


    /**
     * Example of unloading the next record as an associative array from a
     * prepared and executed statement object.
     *
     * Note: This is for example/reference only.  Do not call this in a loop.
     *
     * @param statement The statement from which to unload a record
     * @return          An associative array of the next record in the results
     */
    public function unload_statement( $statement ) {

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
        $result = call_user_func_array( [ $statement, 'bind_result' ], $output );
        if( $result === false ) {
            throw new DatabaseException(
                'Bind result failed: ' . $this->error
            );
        }

        //fetch the result of the query into the bound array
        $result = $statement->fetch();

        //check for errors retrieving the record
        if( $result === false ) {
            throw new DatabaseException(
                'Fetch from statement failed: ' . $this->error
            );
        }

        //check for empty results
        else if( $result === null ) {
            return null;
        }

        //return the associative array representing the record's data
        return $assoc;
    }


    /*------------------------------------------------------------------------
    Private Methods
    ------------------------------------------------------------------------*/


}


/*----------------------------------------------------------------------------
Testing
----------------------------------------------------------------------------*/

if( realpath( $_SERVER[ 'SCRIPT_FILENAME' ] ) == __FILE__ ) {

    header( 'Content-Type: text/plain; charset=utf-8' );

    require __DIR__ . '/test_setup.php';

    $table = 'hzphp_test';

    $db = new emysqli();

    $result = check_test_database( $db );
    if( $result === false ) {
        echo "Error checking test database: {$db->error}";
        exit();
    }

    //test fetch_one_assoc()
    echo "emysqli::fetch_one_assoc() (simple query)\n";
    $record = $db->fetch_one_assoc(
        "select * from $table order by id limit 1"
    );
    echo json_encode( $record, JSON_PRETTY_PRINT );
    echo "\n";
    echo "emysqli::fetch_one_assoc() (placed value query)\n";
    $record = $db->fetch_one_assoc(
        "select * from $table where id = ?", 'i', [ 2 ]
    );
    echo json_encode( $record, JSON_PRETTY_PRINT );
    echo "\n\n";

    //test fetch_result()
    echo "emysqli::fetch_result()\n";
    $result = $db->fetch_result(
        "select * from $table where id > ?", 'i', [ 0 ]
    );
    echo json_encode( $result, JSON_PRETTY_PRINT );
    echo "\n\n";

    //test auto_insert()
    echo "emysqli::auto_insert()\n";
    $values = [
        'parent_id' => 1,
        'name'      => 'Insert Test',
        'notes'     => 'Hello!',
        'fake'      => 'not a column in the table'
    ];
    $insert_id = $db->auto_insert( $table, $values, [ 'fake' ] );
    $record = $db->fetch_one_assoc(
        "select * from $table where id = ?", 'i', [ $insert_id ]
    );
    unset( $values[ 'fake' ] );
    foreach( $values as $k => $v ) {
        if( $v != $record[ $k ] ) {
            echo "emysqli::auto_insert() FAILED: $v != {$record[$k]}\n";
        }
    }
    echo json_encode( $record, JSON_PRETTY_PRINT );
    echo "\n\n";

    //test auto_update()
    echo "emysqli::auto_update() (no changes)\n";
    $updated = $db->auto_update( $table, $insert_id, $values );
    if( $updated == true ) {
        echo "emysqli::auto_update() FAILED: changed same values\n";
    }
    else {
        echo "emysqli::auto_update() PASSED: no changes needed\n";
    }
    echo "\n";

    echo "emysqli::auto_update() (changes)\n";
    $values[ 'name' ] = 'Update Test';
    $values[ 'notes' ] = 'Updated!';
    $updated = $db->auto_update( $table, $insert_id, $values );
    if( $updated == false ) {
        echo "emysqli::auto_update() FAILED: update not sent\n";
    }
    $record = $db->fetch_one_assoc(
        "select * from $table where id = ?", 'i', [ $insert_id ]
    );
    foreach( $values as $k => $v ) {
        if( $v != $record[ $k ] ) {
            echo "emysqli::auto_update() FAILED: $v != {$record[$k]}\n";
        }
    }
    echo json_encode( $record, JSON_PRETTY_PRINT );
    echo "\n\n";

    //test send_query()
    echo "emysqli::send_query()\n";
    $db->send_query(
        "delete from $table where id = ? limit 1",
        'i',
        [ $insert_id ]
    );
    $record = $db->fetch_one_assoc(
        "select * from $table where id = ?", 'i', [ $insert_id ]
    );
    if( $record != false ) {
        echo "emysqli::send_query() FAILED: record not deleted\n";
    }
    else {
        echo "emysqli::send_query() PASSED\n";
    }
    echo "\n\n";

}

