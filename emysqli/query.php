<?php
/*****************************************************************************

SQL Query Abstraction
=====================

*****************************************************************************/

/*----------------------------------------------------------------------------
Default Namespace
----------------------------------------------------------------------------*/

namespace hzphp\emysqli;

/*----------------------------------------------------------------------------
Dependencies
----------------------------------------------------------------------------*/

/*----------------------------------------------------------------------------
Constants
----------------------------------------------------------------------------*/

//flag to indicate double-escaping substitution values
define( 'EMYSQLI_QUERY_DOUBLE_ESCAPE', 1 );

//flag to indicate if substituted numeric values should be quoted
define( 'EMYSQLI_QUERY_QUOTE_NUMBERS', 2 );


/*----------------------------------------------------------------------------
Classes
----------------------------------------------------------------------------*/

/**
 * Exception thrown by the query class.
 */
class QueryException extends \RuntimeException {}


/**
 * Provides a common model for a database query.  Includes features for
 * programmatically constructing safe queries without using prepared
 * statements.  However, this is not intended as a replacement for prepared
 * statements when a statement is the best way to send a query to the DBMS.
 * Specifically, this class is most useful when using mysqli::multi_query()
 * since there is no way to use prepared statements with a multi-query.
 *
 * Note: Typical usage involves allowing the emysqli class to construct these
 * rather than the user having to instantiate an instance.
 */
class query {

    /*------------------------------------------------------------------------
    Public Properties
    ------------------------------------------------------------------------*/

    //allow insertion of resolved placeholders to be doubly escaped.  this
    //allows queries to be sent as SQL string literals to the DBMS (e.g. the
    //query is being prepared and executed on the host).
    public $double_escape = false;

    //allow users to decide if they'd like to send numeric values within
    //quotation marks (') to the DBMS.  MySQL-style queries will automatically
    //convert numbers in strings to numbers (if the target types is numeric).
    //However, this becomes problematic when using value substitution for
    //things like the values in `limit` clauses since those can not be
    //strings.
    public $quote_numbers = false;


    /*------------------------------------------------------------------------
    Protected Properties
    ------------------------------------------------------------------------*/

    /*------------------------------------------------------------------------
    Private Properties
    ------------------------------------------------------------------------*/

    //list of properties that can be read from the object
    private static $read_props = [ 'query', 'types', 'values' ];

    private $db;        //initialized mysqli instance
    private $index;     //off-stack index for values array
    private $num_phs;   //number of placeholders in the query
    private $query;     //query string storage
    private $types;     //values type string
    private $values;    //query substitution values


    /*------------------------------------------------------------------------
    Public Methods
    ------------------------------------------------------------------------*/

    /**
     * query Constructor
     *
     * @param db     An initialized mysqli instance
     * @param query  The base query string.  The string may include "?" to
     *               denote placeholders for values that will be safely
     *               substituted into the query string.
     * @param types  The string representing placeholder value types
     * @param values An array of values to use for each placeholder
     * @param flags  Query generation flags:
     *                   EMYSQLI_QUERY_DOUBLE_ESCAPE: Double-escape
     *                       substituted values
     */
    public function __construct(
        $db,
        $query,
        $types  = '',
        $values = null,
        $flags  = 0
    ) {

        //initialize object state
        $this->db      = $db;
        $this->num_phs = substr_count( $query, '?' );
        $this->query   = preg_replace( '/;\s*$/', '', $query );
        $this->types   = '';
        $this->values  = [];
        if( strlen( $types ) > 0 ) {
            $this->load_values( $types, $values );
        }
        $this->double_escape = ( EMYSQLI_QUERY_DOUBLE_ESCAPE & $flags ) != 0;
        $this->quote_numbers = ( EMYSQLI_QUERY_QUOTE_NUMBERS & $flags ) != 0;
    }


    /**
     * Provide read-only access to internal object state.
     *
     * @param name The name of the property to retrieve
     * @return     The value of the named property
     * @throws     OutOfBoundsException if the property doesn't exist
     */
    public function __get( $name ) {
        if( in_array( $name, self::$read_props ) ) {
            return $this->$name;
        }
        throw new \OutOfBoundsException(
            "Unknown property \"$name\" in query object."
        );
    }


    /**
     * Provides a way to check if a property exists for the object.
     *
     * @param name The name of the property to check
     * @return     True if the property exists, otherwise false
     */
    public function __isset( $name ) {
        return in_array( $name, self::$read_props );
    }


    /**
     * Automatic string conversion
     *
     * @return The query with placeholders resolved to given values
     */
    public function __toString() {

        //build and return the query
        return $this->get_static_query();
    }


    /**
     * Construct a static query to send to the DBMS.
     *
     * @return The query with placeholders resolved to given values
     * @throws QueryException if substituting values won't work
     */
    public function get_static_query() {

        //make sure we have values for substitution (if needed)
        if( $this->num_phs != count( $this->values ) ) {
            throw new QueryException(
                "Unable to construct query without substitution values."
            );
        }

        //reset the values array index
        $this->index = 0;

        //replace all placeholders with properly-escaped values
        return preg_replace_callback(
            '/\\?/',
            [ $this, 'escape_value' ],
            $this->query,
            count( $this->values )
        );
    }


    /**
     *  Loads user-supplied values into the object for use when substituting
     *  the placeholders.  Values are specified in order of the placeholders.
     *  Values must not be escaped.  String values must not be quoted (unless
     *  the quotes are content within string).
     *  Note: This looks a lot like mysqli_stmt::bind_param().  However, it
     *  does not operate on references (and, therefore doesn't care if you
     *  change the values after loading them into the object).
     *
     *  @param types    A typical type declaration string where each character
     *                  represents a generic type to assume when substituting
     *                  placeholders for values.
     *                    i: integer
     *                    d: double
     *                    s: string
     *                    b: blob
     *  @param arg1 ... Arguments to load in order of the types listed in the
     *                  types parameter and the placeholders in the query.
     *  @throws QueryException
     *                  1. if the type declaration string contains an
     *                     unexpected character
     *                  2. if the number of types don't match the number of
     *                     placeholders in the query string
     *                  3. if the number of arguments don't match the number
     *                     of placeholders in the query string
     */
    public function load_values( $types, $arg1 ) {

        //sanity check the type declarations
        $result = preg_match( '/[^bdis]/', $types, $m );
        if( $result == 1 ) {
            throw new QueryException(
                "Unknown query value type declared: {$m[0]}"
            );
        }

        //count how many types are declared
        $num_types = strlen( $types );

        //make sure enough have been declared
        if( $num_types != $this->num_phs ) {
            throw new QueryException(
                "Incorrect number of types declared."
                . " Expected {$this->num_phs}, found $num_types."
            );
        }

        //see if an array of values was passed
        if( is_array( $arg1 ) ) {
            $values = $arg1;
        }

        //values were passed in individual arguments
        else {

            //build a list of values passed to the method
            $values = array_slice( func_get_args(), 1 );
        }

        //check the number of values
        $num_values = count( $values );
        if( $num_values != $this->num_phs ) {
            throw new QueryException(
                "Incorrect number of values given."
                ." Expected {$this->num_phs}, found $num_values."
            );
        }

        //set the declared types and list of values
        $this->types  = $types;
        $this->values = $values;
    }


    /*------------------------------------------------------------------------
    Protected Methods
    ------------------------------------------------------------------------*/


    /*------------------------------------------------------------------------
    Private Methods
    ------------------------------------------------------------------------*/

    /**
     *  Callback method for preg_replace_callback().  Replaces a query
     *  placeholder with a properly-escaped string of the current value.
     *  Note: This relies on the user initializing the index property before
     *  calling preg_replace_callback().
     *
     *  @param matches The matches list from preg_replace_callback()
     *  @return        The value to substitute for the placeholder
     *  @throws QueryException if the type declaration string contains an
     *                 unexpected character
     */
    private function escape_value( $matches ) {

        //get the current type character
        $type  = substr( $this->types, $this->index, 1 );

        //get the current value
        $value = $this->values[ $this->index ];

        //assume this is not a numeric value
        $is_numeric = false;

        //advance the index (for the next time this method is called).
        $this->index += 1;

        //perform conversion/escaping based on the declared type
        switch( $type ) {

            //integer declared
            case 'i':
                $value      = strval( intval( $value ) );
                $is_numeric = true;
                break;

            //double/float declared
            case 'd':
                $value      = strval( floatval( $value ) );
                $is_numeric = true;
                break;

            //string or blob declared
            case 's':
            case 'b':
                $value = strval( $value );
                break;

            //unknown declared
            default:
                throw new QueryException(
                    "Unknown query value type declared: $type"
                );
                break;
        }

        //escape the value using the DBMS client/driver
        $value = $this->db->real_escape_string( $value );

        //check for (lack of) quoting on numeric values
        if( ( $is_numeric ) == true && ( $this->quote_numbers == false ) ) {

            //return the escaped value without apostrophes
            return $value;
        }

        //check for double-escaping needed for string literals
        if( $this->double_escape == true ) {

            //escaped the escape characters
            $value = str_replace( '\\', '\\\\', $value );

            //return the escaped value inside escaped apostrophes
            return "\\'$value\\'";
        }

        //return the escaped value inside quotes
        return "'$value'";
    }

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

    //simple query
    echo "Basic Query\n";
    $q = new query( $db, "select * from $table limit 2" );
    $r = $db->fetch_result( strval( $q ) );
    echo strval( $q ) . "\n";
    echo json_encode( $r, JSON_PRETTY_PRINT ) . "\n\n";

    //placeholder query
    echo "Query with Placeholders\n";
    $q = new query(
        $db,
        "select id,name from $table where id > ?",
        'i',
        [ 2 ]
    );
    $r = $db->fetch_result( strval( $q ) );
    echo strval( $q ) . "\n";
    echo json_encode( $r, JSON_PRETTY_PRINT ) . "\n\n";

    //double-escaping values
    echo "Query with Placeholders (Double Escaped)\n";
    $q = new query(
        $db,
        "select id,name from $table where id > ?",
        'i',
        [ 2 ],
        EMYSQLI_QUERY_DOUBLE_ESCAPE
    );
    echo strval( $q ) . "\n\n";

}

