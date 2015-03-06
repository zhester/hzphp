<?php
/*****************************************************************************

mysqli Result Set Abstraction
=============================

PHP's `mysqli` implementation is (correctly) very close to the MySQL driver
interface.  However, PHP provides more expressive interfaces for dealing with
the logistics of returning a complicated result set from a database query.
This class helps bridge the gap between `mysqli` result objects and typical
use cases.  As a result, a DBMS-agnostic interface is introduced.

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
 * Result Set Manager
 */
class result implements \Countable, \Iterator, \JsonSerializable {

    /*------------------------------------------------------------------------
    Public Properties
    ------------------------------------------------------------------------*/

    /*------------------------------------------------------------------------
    Protected Properties
    ------------------------------------------------------------------------*/

    //the list of field names for each record in the result set
    protected $fields = null;

    //the reporting mode for array generation
    protected $mode = MYSQLI_NUM;

    //the mysqli::result instance for the query results
    protected $result = null;


    /*------------------------------------------------------------------------
    Private Properties
    ------------------------------------------------------------------------*/

    //list of properties allowed for read-only access
    private static $read_props = [ 'fields', 'mode', 'is_complete' ];

    //tracked index through result set
    private $index = 0;

    //flags if iteration through the result set has finished
    private $is_complete = false;

    //the current record in the result set
    private $record;


    /*------------------------------------------------------------------------
    Public Methods
    ------------------------------------------------------------------------*/

    /**
     * result Constructor
     *
     * @param source The result set source object.  This can be either a
     *               mysqli::statement or mysqli::result instance.
     */
    public function __construct( $source, $mode = MYSQLI_NUM ) {
        if( method_exists( $source, 'get_result' ) ) {
            $this->result = $source->get_result();
        }
        else if( $source instanceof \mysqli_stmt ) {
            throw new DatabaseException(
                'mysqli_stmt does not support get_result() interface'
            );
        }
        else if( $source === false ) {
            throw new DatabaseException(
                'Unable to use failed (false) source object for result.'
            );
        }
        else {
            $this->result = $source;
        }
        $this->mode        = $mode;
        $this->is_complete = $this->result->num_rows == 0;
        $this->fields      = array_map(
            function( $obj ) { return $obj->name; },
            $this->result->fetch_fields()
        );
    }


    /**
     * Handles calls to undefined methods.  Routes some of them to the
     * mysqli::result instance.
     *
     * @param name      The name of the method that was called
     * @param arguments The array of arguments passed to the method
     * @return          Method-dependent return values
     * @throws          \OutOfBoundsException if the method is not supported
     */
    public function __call( $name, $arguments ) {

        /*--------------------------------------------------------------------
        Check for methods we would like to intercept.
        --------------------------------------------------------------------*/
        switch( $name ) {

            /*----------------------------------------------------------------
            Intercept `fetch_array()` so we can supply our mode setting, and
            do our internal checking.
            ----------------------------------------------------------------*/
            case 'fetch_array':
                $mode = count( $arguments ) > 0 ? $arguments[ 0 ] : $this->mode;
                $record = $this->result->$name( $mode );
                if( $record === null ) {
                    $this->is_complete = true;
                }
                else {
                    $this->index += 1;
                }
                return $record;
                break;

            /*----------------------------------------------------------------
            Intercept single-row retrieval for our checking.
            ----------------------------------------------------------------*/
            case 'fetch_assoc':
            case 'fetch_row':
            case 'fetch_object':
                $record = call_user_func_array(
                    [ $this->result, $name ],
                    $arguments
                );
                if( $record === null ) {
                    $this->is_complete = true;
                }
                else {
                    $this->index += 1;
                }
                return $record;
                break;

            /*----------------------------------------------------------------
            No need to intercept this method.
            ----------------------------------------------------------------*/
            default:

                /*------------------------------------------------------------
                If the result instance supports it, hand it off.
                ------------------------------------------------------------*/
                if( method_exists( $this->result, $name ) ) {
                     return call_user_func_array(
                         [ $this->result, $name ],
                         $arguments
                    );
                }

                /*------------------------------------------------------------
                Method name is no good.
                ------------------------------------------------------------*/
                else {
                    throw new \OutOfBoundsException(
                        "Unsupported method \"$name\" in result instance."
                    );
                }
                break;
        }
    }


    /**
     * Allows read-only access to properties on the result instance.
     *
     * @param name The name of the property whose value to retrieve
     * @return     The value of the property
     * @throws     \OutOfBoundsException if the property is not supported
     */
    public function __get( $name ) {
        if( isset( $this->result->$name ) ) {
            return $this->result->$name;
        }
        else if( in_array( $name, self::$read_props ) ) {
            return $this->$name;
        }
        throw new \OutOfBoundsException(
            "Unsupported method \"$name\" in result instance."
        );
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
     * Implements the Countable interface.
     *
     * @return The number of rows in the result set
     */
    public function count() {
        return $this->result->num_rows;
    }


    /**
     * Returns the current record in the result set.
     *
     * @return The current record as an array (depending on current report
     *         mode setting)
     */
    public function current() {
        return $this->record;
    }


    /**
     * Implements the JsonSerializable interface.
     *
     * @return The object state ready for simple serialization
     */
    public function jsonSerialize() {
        $this->result->data_seek( 0 );
        return [
            'fields'  => $this->fields,
            'records' => $this->result->fetch_all( MYSQLI_NUM )
        ];
    }


    /**
     * Returns the current offset into the result set.
     *
     * @return The current integer offset into the result set
     */
    public function key() {
        return $this->index;
    }


    /**
     * Advances the internal result set to the next record.
     *
     */
    public function next() {
        $this->record = $this->result->fetch_array( $this->mode );
        if( $this->record === null ) {
            $this->is_complete = true;
        }
        else {
            $this->index += 1;
        }
    }


    /**
     * Resets the internal result set to the beginning.
     *
     */
    public function rewind() {
        $this->result->data_seek( 0 );
        $this->index  = 0;
        $this->record = $this->result->fetch_array( $this->mode );
        if( $this->record === null ) {
            $this->is_complete = true;
        }
    }


    /**
     * Indicates if the current position in the result set is valid.
     *
     */
    public function valid() {
        return $this->is_complete == false;
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

    $db_result = $db->query( "select * from $table" );

    $result = new result( $db_result );

    echo "Found {$result->num_rows} records.\n\n";

    echo "Fields: " . json_encode( $result->fields ) . "\n\n";

    echo "Records:\n";
    foreach( $result as $record ) {
        echo json_encode( $record, JSON_PRETTY_PRINT ) . "\n";
    }
    echo "\n";

    echo "result::jsonSerialize()\n";
    echo json_encode( $result, JSON_PRETTY_PRINT );

}

