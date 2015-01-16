<?php

/*----------------------------------------------------------------------------
Namespace
----------------------------------------------------------------------------*/

namespace hzphp\emysqli;


/*----------------------------------------------------------------------------
Exceptions
----------------------------------------------------------------------------*/

class QueryException extends \RuntimeException {}


/**
 *  Provides a common model for a database query.  Includes features for
 *  programmatically constructing safe queries without using prepared
 *  statements.  However, this is not intended as a replacement for prepared
 *  statements when a statement is the best way to send a query to the DBMS.
 *  Specifically, this class is most useful when using mysqli::multi_query()
 *  since there is no way to use prepared statements with a multi-query.
 *
 *  Note: Typical usage involves allowing the emysqli class to construct these
 *  rather than the user having to instantiate an instance.
 */
class query {

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

    private             $db;        //initialized mysqli instance
    private             $index;     //off-stack index for values array
    private             $num_phs;   //number of placeholders in the query
    private             $query;     //query string storage
    private             $types;     //values type string
    private             $values;    //query substitution values


    /*------------------------------------------------------------------------
    Public Methods
    ------------------------------------------------------------------------*/

    /**
     *  Constructor
     *
     *  @param db    An initialized mysqli instance
     *  @param query The base query string.  The string may include "?" to
     *               denote placeholders for values that will be safely
     *               substituted into the query string.
     */
    public function __construct( $db, $query ) {

        //initialize object state
        $this->db      = $db;
        $this->num_phs = substr_count( $query, '?' );
        $this->query   = preg_replace( '/;\s*$/', '', $query );
        $this->types   = '';
        $this->values  = [];
    }


    /**
     *  Automatic string conversion
     *
     *  @return The query with placeholders resolved to given values
     */
    public function __toString() {

        //build and return the query
        return $this->get_query();
    }


    /**
     *  Construct the query to send to the DBMS.
     *
     *  @return The query with placeholders resolved to given values
     *  @throws QueryException if substituting values won't work
     */
    public function get_query() {

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

        //build a list of values passed to the method
        $values = array_slice( func_get_args(), 1 );

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

        //advance the index (for the next time this method is called).
        $this->index += 1;

        //perform conversion/escaping based on the declared type
        switch( $type ) {

            //integer declared
            case 'i':
                $value = strval( intval( $value ) );
                break;

            //double/float declared
            case 'd':
                $value = strval( floatval( $value ) );
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

        //return the escaped value inside quotes
        return "'$value'";
    }

}


/*----------------------------------------------------------------------------
Testing
----------------------------------------------------------------------------*/

if( $_SERVER[ 'SCRIPT_FILENAME' ] == __FILE__ ) {
    //$q = new query();
}

