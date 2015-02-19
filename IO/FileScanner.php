<?php
/*****************************************************************************

File Scanning Specification and Extraction System
=================================================

Specifying how to scan a file of unknown format requires a fairly expressive
querying interface.  Here's the basics of how this system works.

At the top level, the file to be scanned along with an offset and maximum
scan length are given.  These parts of the query are not addressed by this
system.  This system deals with the details of how to step through records
within the file, identify the desired records, and extract parts of each
record.

Query Components
----------------

The query consists of these items:

- `ident`: record identification scheme (default: matches all records)
- `step`: how to navigate between records (default: fixed, byte 0 to 1)
- `part`: what to extract from each record (default: entire record)
- `slice`: reduce the output to a subset of the final record list (default:
  send extracted parts of all matching records)

### Value Extraction

In order to facilitate records of fixed-length, variable-length, and
self-terminated, a way to define how to extract information from any part of
the file is needed.  The specifier is a triplet containing the following
information:

- `class`: one of `fix`, `trm`, or `var`
- `arg1`:
    - for `fix` extractors, this is the offset into the record to extract
    - for `trm` extractors, this is the terminating character
    - for `var` extractors, this is the offset into the record to extract
- `arg2`:
    - for `fix` extractors, this is the number of bytes to extract/skip
    - for `trm` extractors, this is the escape character
    - for `var` extractors, this is one of the following type specifiers

    c : sint8
    C : uint8
    s : sint16
    S : uint16
    l : sint32
    L : uint32
    q : sint64
    Q : uint64
    f : float
    d : double

When specifying an extractor, this is a three-part vector:

    (fix,0,512)
    (trm,"\n","\\")
    (var,4,L)

### Record Matching

Matching a record is done by extracting some value from the record, and
comparing it to a single value, a range of values, or a set of valid values.

Matching a single value is done with a single scalar.  If you wish to match
records that do not contain the specified value, use either range or set
specifiers (shown below).

Matching a range of values is done with a vector specifying range.  The
minimum and maximum are considered part of the range (inclusive).  To invert
range matching (matching records outside of the range, exclusively), use an
uppercase "R".

    (r,MINIMUM,MAXIMUM)
    (R,LOWER,UPPER)

Matching a set of values is done with a vector.  The value from the record
must be a member of the specified set.  To invert set matching (matching
records that have values that are _not_ members of the set), use an uppercase
"S".

    (s,3,5,7,11,15)
    (S,1,2,7,127)

### Subset Extraction in Output

If only a subset of the matching records is required, this can be specified
using a slice-style vector.

    (start,stop,step)

- `start` is the index of the first record to return
- `stop` is the index of the last record to return (negative indexes are
  relative to the end of the set)
- `step` is how many records to step over between returned records

The default slice (all records) would look like this:

    (0,null,1)

To get all even records:

    (0,null,2)

To get the first 100 records:

    (0,100)

In Query Parameters
-------------------

Record identifiers are given with a vector containing two parts.

    (<extracter>,<match>)
    ((fix,0,4),abcd)
    ((var,4,L),(s,128,256))

Record steps are given with as an extraction specifier (vector).

Record payloads are given with a vector containing two parts.  Each part may
be either a number (indicating a fixed offset or length in the record) or an
extraction specifier (indicating where in the record to find the offset or
length).  Any permutation of constant and dynamic specifiers is allowed.

    (<offset>,<length>)
    (0,(var,8,L))
    ((var,2,s),48)
    ((var,8,c),(var,9,c))

Slices are given as a slice vector.

URL-style Queries
-----------------

    ident=((var,0,L),2147592789)
    &
    step=(var,4,L)
    &
    part=(8,4)
    &
    slice=(0,100,1)

*****************************************************************************/

/*----------------------------------------------------------------------------
Default Namespace
----------------------------------------------------------------------------*/

namespace hzphp\IO;

/*----------------------------------------------------------------------------
Dependencies
----------------------------------------------------------------------------*/

/*----------------------------------------------------------------------------
Classes
----------------------------------------------------------------------------*/


/**
 * Encapsulates functionality for parsing and retrieving selected parts of a
 * file based on a scan specification query.
 */
class FileScanner {


    /*------------------------------------------------------------------------
    Public Properties
    ------------------------------------------------------------------------*/

    /*------------------------------------------------------------------------
    Protected Properties
    ------------------------------------------------------------------------*/

    //these are the initial query values that are overridden
    protected static $initial = [

        //match all records
        'ident' => [ [ 'fix', 0, 1 ], null ],

        //each record is one byte long
        'step'  => [ 'fix', 0, 1 ],

        //extract the entire record
        'part'  => [ 0, null ],

        //return parts of all matching records
        'slice' => [ 0, null, 1 ]
    ];

    /*======================================================================*/

    //the StructuredQuery instance that specifies the scan query
    protected $query = null;

    //the Slicer instances that assists with reporting subsets
    protected $slicer = null;

    //the scan specification array
    protected $spec = [];

    //file scanning statistics
    protected $stats = [
        'scanned'  => 0,
        'matched'  => 0,
        'reported' => 0
    ];

    //the StreamIO instance to scan
    protected $streamio = null;


    /*------------------------------------------------------------------------
    Private Properties
    ------------------------------------------------------------------------*/

    /*------------------------------------------------------------------------
    Public Methods
    ------------------------------------------------------------------------*/

    /**
     * Constructor.
     *
     * @param streamio The StreamIO instance to scan
     * @param query    The scanning query as an associative array
     */
    public function __construct( $streamio, $query ) {

        //store the file stream
        $this->streamio = $streamio;

        //store the scan specification query
        $this->query = $query;

        //create a copy of the initial scanning specification
        $this->spec = self::$initial;

        //use the query to modify the default scanning specification
        foreach( $this->spec as $k => $v ) {
            if( isset( $this->query->$k ) ) {
                $this->spec[ $k ] = $this->query->$k;
            }
        }

        //create a new slice object (allow under-specified slice lists)
        $slicer_class = new \ReflectionClass( '\hzphp\Util\Slicer' );
        $this->slicer = $slicer_class->newInstanceArgs(
            $this->spec[ 'slice' ]
        );
    }


    /**
     * Takes any extraction specifier, and extracts the data from the given
     * string.
     *
     * ZIH: this does not support `trm` specifiers yet
     *
     * @param spec   The extraction specifier
     * @param string The string from which to extract
     * @return       The extracted data as a string, or false on failure
     */
    public function extract( $spec, $string ) {
        if( $spec[ 0 ] == 'var' ) {
            return \hzphp\Util\Struct::unpack(
                $spec[ 2 ],
                substr( $string, $spec[ 1 ] ),
                true
            );
        }
        else if( $spec[ 0 ] == 'fix' ) {
            if( $spec[ 2 ] === null ) {
                return substr( $string, $spec[ 1 ] );
            }
            return substr( $string, $spec[ 1 ], $spec[ 2 ] );
        }
        return false;
    }


    /**
     * Filters the entire stream, and sends it to the specified output stream.
     *
     * @param streamio The StreamIO instance to which output is sent
     * @return         The number of bytes written to the stream
     */
    public function filterStream( $streamio = false ) {
        $written = 0;
        $stream  = false;
        if( $streamio == false ) {
            $stream = fopen( 'php://output', 'w' );
            $streamio = new StreamIO( $stream );
        }
        while( ( $data = $this->getNextRecord() ) !== false ) {
            $streamio->write( $data );
            $written += strlen( $data );
        }
        if( $stream != false ) {
            fclose( $stream );
        }
        return $written;
    }


    /**
     * Retrieves the next record from the file stream.
     *
     * ZIH - does not support `trm` record stepping yet
     *
     * @return The extracted data from the next matching record, or false
     *         when reaching the end of the file
     */
    public function getNextRecord() {

        //step specifier
        $step = $this->spec[ 'step' ];

        //determine the method needed for record stepping
        $step_method = $step[ 0 ] == 'var' ? 'getVarRecord' : 'getFixRecord';

        //step through each record until we find a match
        while(
            ( $data = call_user_func(
                [ $this, $step_method ], $step[ 1 ], $step[ 2 ]
            ) ) !== false
        ) {

            //increment records scanned counter
            $this->stats[ 'scanned' ] += 1;

            //test for a matching record
            if( $this->matchRecord( $data ) == true ) {

                //increment the match counter
                $this->stats[ 'matched' ] += 1;

                //see if the record is not reportable for the requested subset
                if( $this->slicer->index() == false ) {

                    //fetch next matching record
                    continue;
                }

                //reporting record, increment report counter
                $this->stats[ 'reported' ] += 1;

                //report the extracted record data
                return $this->getPart( $data );
            }
        }

        //no matching record found before EOF
        return false;
    }


    /**
     * Retrieves a part of the data according to the `part` specifier.
     *
     * @param data The data from which to extract as a string
     * @return     The extracted data as a string
     */
    public function getPart( $data ) {
        $part = $this->spec[ 'part' ];
        if( is_array( $part[ 0 ] ) ) {
            $offset = $this->extract( $part[ 0 ], $data );
        }
        else {
            $offset = $part[ 0 ];
        }
        if( is_array( $part[ 1 ] ) ) {
            $length = $this->extract( $part[ 1 ], $data );
        }
        else {
            $length = $part[ 1 ];
        }
        if( $length === null ) {
            return substr( $data, $offset );
        }
        return substr( $data, $offset, $length );
    }


    /**
     * Determines if the data for a record matches a match specifier.
     *
     * @param record The entire record as a string
     * @param spec   The extraction specifier
     * @param match  The candidate value against which to match
     * @return       True if the record matches, false if it doesn't
     */
    public function matchRecord( $record, $spec = null, $match = null ) {

        //default arguments assume we know how to match already
        $spec  = $spec  == null ? $this->spec[ 'ident' ][ 0 ] : $spec;
        $match = $match == null ? $this->spec[ 'ident' ][ 1 ] : $match;

        //extract the native value for the target of the match
        $value = $this->extract( $spec, $record );

        //analyize the record identifier against a range or set
        if( is_array( $match ) == true ) {

            //the first item in the array is the type of match [rRsS]
            $match_type = array_shift( $match );

            //assume the record does not match
            $result = false;

            //perform matching based on the type of match
            switch( $match_type ) {

                //match values within range (inclusive)
                case 'r':
                    $result = ( $value >= $match[ 0 ] )
                          && ( $value <= $match[ 1 ] );
                    break;

                //match values outside of range (exclusive)
                case 'R':
                    $result = ( $value < $match[ 0 ] )
                           && ( $value > $match[ 1 ] );
                    break;

                //match values that are members of a set
                case 's':
                    $result = in_array( $value, $match );
                    break;

                //match values that are not members of a set
                case 'S':
                    $result = in_array( $value, $match ) == false;
                    break;
            }

            //return the result of record range|set matching
            return $result;
        }

        //return the result of direct comparison matching
        return $value == $match;
    }


    /*------------------------------------------------------------------------
    Protected Methods
    ------------------------------------------------------------------------*/

    /**
     * Retrieves the next record from the stream using the given `fix`
     * step information.
     *
     * @param offset The offset into the stream to start retrieving
     * @param length The length of the record to retrieve
     * @return       The record's data as a string
     */
    protected function getFixRecord( $offset, $length ) {
        return $this->streamio->extract( $offset, $length );
    }


    /**
     * Retrieves the next variable-length record from the stream.
     *
     * @param offset The offset into the stream that contains the length field
     *               for the record.
     * @param format The pack string format of the length field
     * @return       The record's data as a string
     */
    protected function getVarRecord( $offset, $format ) {

        //determine the length of the record length field
        $length = \hzphp\Util\Struct::calcsize( $format );

        //fetch the length of this record, and reset the file position
        $length_string = $this->streamio->extract( $offset, $length, true );

        //check for error/EOF
        if( $length_string === false ) {
            return false;
        }

        //determine the record length
        $length = \hzphp\Util\Struct::unpack( $format, $length_string, true );

        //fetch the record data, and return it
        return $this->streamio->read( $length );
    }


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

if( $_SERVER[ 'SCRIPT_FILENAME' ] == __FILE__ ) {

    header( 'Content-Type: text/plain; charset=utf-8' );

    require __DIR__ . '/../tools/loader.php';

    //open temporary file for testing
    $file = tmpfile();

    //generate test data
    $data = '';
    for( $i = 0; $i < 20; ++$i ) {
        if( ( $i % 3 ) == 0 ) {
            $data .= pack( 'LLLL', 48, 16, $i, $i + 1024 );
        }
        $data .= pack( 'LLL', 64, 12, $i );
    }

    //write test data to temp file, and rewind file position
    fwrite( $file, $data );
    rewind( $file );

    //define some scanning parameters
    $params = [
        'ident' => '((var,0,L),64)',    //match all records starting with 64
        'step'  => '(var,4,L)',         //record lengths are 4 bytes in
        'part'  => '(0,null)',          //extract entire record contents
        'slice' => '(1,null,3)'         //report every 3rd starting at 2nd
    ];

    //set up the scanning chain and file scanning object
    $query    = new \hzphp\Util\StructuredQuery( $params );
    $streamio = new StreamIO( $file );
    $scanner  = new FileScanner( $streamio, $query );

    //iterate through each matching record in the requested subset
    while( ( $record = $scanner->getNextRecord() ) !== false ) {

        //display basic information about each record
        echo "Raw Record Data\n",
             \hzphp\Util\Struct::gethexdump( $record ),
             "\n";
        $fields = unpack( 'Lid/Llength/Lcontent', $record );
        foreach( $fields as $k => $v ) {
            echo "  $k : $v\n";
        }
        echo "\n";
    }

    //close the temporary file
    fclose( $file );
}

