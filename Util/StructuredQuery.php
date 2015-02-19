<?php
/*****************************************************************************

Structured Query Formatting and Parsing
=======================================

Structured queries attempt to preserve type and internal structure in a
uniform syntax for use in URL query parameters.

### URL Encoding

Most of the rules apply to proper URL encoding.  However, to keep the syntax
reasonably terse, things like quotation marks do not need to be encoded.  Of
course, encoding them won't hurt thing, either.

### Scalar Values in Queries

    param=value

Each scalar value can be an integer, float, `null`, `true`, `false`, or a
string (optionally enclosed in double-quotes).

If the the string is not enclosed in double-quotes, it must not appear to be a
numeric value or one of the reserved words `null`, `true`, or `false`.  If one
of those values must be sent as a string, always enclose them in double-quotes.

### Vector Values in Queries

Vectors are comma-separated lists of values or nested vectors.  These are
typically enclosed in parenthesis, but may also be enclosed in parenthesis.

    param=("value1","value2","value3")
    param=[1,2,3]
    param=((a,b,c),(null,true,false),("hello",42,3.14159)

*****************************************************************************/

/*----------------------------------------------------------------------------
Default Namespace
----------------------------------------------------------------------------*/

namespace hzphp\Util;

/*----------------------------------------------------------------------------
Dependencies
----------------------------------------------------------------------------*/

/*----------------------------------------------------------------------------
Classes
----------------------------------------------------------------------------*/

/**
 * Implements structured query handling.
 */
class StructuredQuery {

    /*------------------------------------------------------------------------
    Public Properties
    ------------------------------------------------------------------------*/

    /*------------------------------------------------------------------------
    Protected Properties
    ------------------------------------------------------------------------*/

    //storage for parsed/imported query data
    protected $data = [];


    /*------------------------------------------------------------------------
    Private Properties
    ------------------------------------------------------------------------*/

    //lexical representation of tokens expected in query parameters
    //  Each item defines a capturing pattern and whether or not it should
    //  be enclosed in quotation marks.
    private static $lexicon = [
        'keyword'  => [ 'null|false|true',                    false ],
        'float'    => [ '[+-]?\\d*\\.\\d*(?:[eE][+-]?\\d+)?', false ],
        'integer'  => [ '[+-]?\\d+(?:[eE][+-]?\\d+)?',        false ],
        'closed'   => [ '"(?:[^"\\\\]|\\\\.)*"',              false ],
        'open'     => [ '(?!")[^",\\[\\]]+(?!")',             true  ]
    ];

    /*======================================================================*/

    //stores a pattern for converting query values into JSON types
    private $pattern;

    //stores a lookup list of symbols that must be enclosed in quotes
    private $quote_syms = [];

    //stores a list of all symbols in the lexicon
    private $symbols = [];


    /*------------------------------------------------------------------------
    Public Methods
    ------------------------------------------------------------------------*/

    /**
     * Constructor.
     */
    public function __construct( $query = false ) {

        //list of all symbols (needs to be in order of definition)
        $this->symbols = array_keys( self::$lexicon );

        //build the value conversion pattern and generate a lookup list of
        //symbols that must be quoted
        $patterns = [];
        foreach( self::$lexicon as $sym => $spec ) {
            //$patterns[] = '(?P<' . $sym . '>' . $spec[ 0 ] . ')';
            $patterns[] = '(' . $spec[ 0 ] . ')';
            if( $spec[ 1 ] == true ) {
                $this->quote_syms[] = $sym;
            }
        }
        $this->pattern = '/' . implode( '|', $patterns ) . '/';

        //parse and store the initial query
        if( $query != false ) {
            foreach( $query as $k => $v ) {
                $this->data[ $k ] = $this->decodeString( $query[ $k ] );
            }
        }

    }


    /**
     * Retrieves one of the parsed query values.
     *
     * @param key
     * @return
     */
    public function __get( $key ) {
        if( isset( $this->data[ $key ] ) ) {
            return $this->data[ $key ];
        }
        return null;
    }

    /**
     * Allows users to determine if a parsed query value is present.
     *
     */
    public function __isset( $key ) {
        return isset( $this->data[ $key ] );
    }


    /**
     * Represent the query structure as a string.
     *
     * @return A JSON-formatted string serializing the specifier
     */
    public function __toString() {
        return json_encode( $this->data );
    }


    /**
     * Converts a query value into native values.
     *
     * @param string The parameter string to decode
     * @return       An array or scalar value representing the query value
     */
    public function decodeString( $string ) {

        //manipulate the string to conform to JSON syntax
        $json = preg_replace_callback(
            $this->pattern,

            //use our converter to properly quote opened strings
            [ $this, 'conversionCallback' ],

            //convert parenthesis to square brackets
            str_replace( [ '(', ')' ], [ '[', ']' ], $string )
        );

        //return the native data representation as an array or scalar
        return json_decode( $json, true );
    }


    /*------------------------------------------------------------------------
    Protected Methods
    ------------------------------------------------------------------------*/

    /*------------------------------------------------------------------------
    Private Methods
    ------------------------------------------------------------------------*/

    /**
     * Used by `preg_replace_callback()` to convert matched symbols to their
     * JSON equivalent in query values.
     *
     * @param matches The list of regular expression matches
     * @return        The string to substitute for the current match
     */
    private function conversionCallback( $matches ) {
        $match = array_shift( $matches );
        $index = count( $matches ) - 1;
        if( in_array( $this->symbols[ $index ], $this->quote_syms ) ) {
            return '"' . $matches[ $index ] . '"';
        }
        return $match;
    }

}


/*----------------------------------------------------------------------------
Functions
----------------------------------------------------------------------------*/

/*----------------------------------------------------------------------------
Execution
----------------------------------------------------------------------------*/

if( $_SERVER[ 'SCRIPT_FILENAME' ] == __FILE__ ) {
    header( 'Content-Type: text/plain' );
    $query = new StructuredQuery( [
        'int'           => '42',
        'dec'           => '3.14159',
        'open_string'   => 'open string',
        'closed_string' => '"closed string"',
        'vector_ints'   => '(1,8,42)',
        'vector_decs'   => '(1.1,8.8,3.14159)',
        'vector_mixed'  => '(1,3.14159,open string,true,"closed string")',
        'vector_nested' => '((null,true,false),(1,2,3),(a,b,c))',
        'open_double'   => '(open,open)',
        'closed_double' => '("closed","closed")',
        'closed_escape' => '"closed \\" escaped"'
    ] );
    echo $query;
    echo "\n";
    echo json_encode( $query->vector_nested, JSON_PRETTY_PRINT );
}

