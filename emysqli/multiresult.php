<?php
/*****************************************************************************

Convenience Interface to Results of Multiple Queries
====================================================

When using emysqli\multiquery, the user must supply a relatively sophisticated
callback handler to deal with each requested result.  Each result is labeled
by the user, so this object simply allows by-label retrieval of the results
from each result set of a multi-query.

*****************************************************************************/

/*----------------------------------------------------------------------------
Default Namespace
----------------------------------------------------------------------------*/

namespace hzphp\emysqli;

/*----------------------------------------------------------------------------
Dependencies
----------------------------------------------------------------------------*/

/*----------------------------------------------------------------------------
Classes
----------------------------------------------------------------------------*/

/**
 * Implements a collection of result sets that come from a multi-query.
 */
class multiresult implements
    \ArrayAccess,
    \Countable,
    \IteratorAggregate,
    \JsonSerializable {

    /*------------------------------------------------------------------------
    Public Properties
    ------------------------------------------------------------------------*/

    /*------------------------------------------------------------------------
    Protected Properties
    ------------------------------------------------------------------------*/

    //results of all requested reports
    protected $reports;


    /*------------------------------------------------------------------------
    Private Properties
    ------------------------------------------------------------------------*/

    /*------------------------------------------------------------------------
    Public Methods
    ------------------------------------------------------------------------*/

    /**
     * multiresult Constructor
     *
     */
    public function __construct() {
        $this->reports = [];
    }


    /**
     * Represents the object state as a string.
     *
     * @return A string representing the object
     */
    public function __toString() {
        return json_encode( $this->jsonSerialize() );
    }


    /**
     * Impelements the Countable interface.
     *
     * @return The number of reports in the object
     */
    public function count() {
        return count( $this->reports );
    }


    /**
     * Provides an iterator representation of the object.
     *
     */
    public function getIterator() {
        return new ArrayIterator( $this->reports );
    }


    /**
     * Handles result callbacks from a multi-query execution.
     *
     * @param result The result object for the reported query
     * @param report The user's report label
     */
    public function handle_result( $result, $report ) {
        $this->reports[ $report ] = $result;
    }


    /**
     * Implements the JsonSerializable interface.
     *
     */
    public function jsonSerialize() {
        return $this->reports;
    }


    /**
     * Checks for the existence of a report.
     *
     * @param key The report label string
     * @return    True if the report is present, otherwise false
     */
    public function offsetExists( $key ) {
        return isset( $this->reports[ $key ] );
    }


    /**
     * Retrieves reports by array subscript.
     *
     * @param key The report label string
     * @return    The result object for the report
     * @throws    \OutOfBoundsException if the report does not exist
     */
    public function offsetGet( $key ) {
        if( isset( $this->reports[ $key ] ) ) {
            return $this->reports[ $key ];
        }
        throw new \OutOfBoundsException( "Unknown report \"$key\"." );
    }


    /**
     * Allows users to assign a result set to the object.
     *
     * @param key    The name of the report to set
     * @param result The result object to store
     */
    public function offsetSet( $key, $result ) {
        $this->reports[ $key ] = $result;
    }


    /**
     * Allows users to remove a result set from the object.
     *
     * @param key    The name of the report to remove
     */
    public function offsetUnset( $key ) {
        if( isset( $this->reports[ $key ] ) ) {
            unset( $this->reports[ $key ] );
        }
    }


    /*------------------------------------------------------------------------
    Protected Methods
    ------------------------------------------------------------------------*/

    /*------------------------------------------------------------------------
    Private Methods
    ------------------------------------------------------------------------*/

}


/*----------------------------------------------------------------------------
Functions
----------------------------------------------------------------------------*/

/*----------------------------------------------------------------------------
Execution
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

    //create a multiquery object for testing
    $mq = $db->create_multiquery();

    $mq->add_query( "set @table := '$table';" );
    $qr = new query(
        $db,
        "set @query := concat(
            'select id,name from ',
            @table,
            ' limit ?'
        );",
        'i',
        [ 2 ]
    );
    $mq->add_query( $qr );
    $mq->add_query( "prepare test_statement from @query" );
    $mq->add_query( "execute test_statement", 'report' );
    $mq->add_query( "deallocate prepare test_statement" );
    $mq->add_query( "select 'hello' as greeting", 'greeting' );
    $mq->add_query( "select id,name from $table where parent_id = 1", 'kids' );

    //set up a testing multiresult object
    $mr = new multiresult();

    //explicitly set a multiresult instance as a callback handler
    //  note: user code should use the no-callback version of
    //  multiquery::execute to automatically create and return the multiresult
    //  instance
    $mq->execute( [ $mr, 'handle_result' ] );

    //display the output
    echo "multiresult::handle_result()\n";
    echo json_encode( $mr, JSON_PRETTY_PRINT ) . "\n\n";

    //array access
    echo "multiresult[ 'report' ] (subscript access)\n";
    echo json_encode( $mr[ 'report' ], JSON_PRETTY_PRINT );

}

