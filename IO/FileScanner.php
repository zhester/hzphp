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

This system makes the assumption that we are working with files that contain
sequential units of structured data.  The structure of these units is
irrelevent.  Each unit of structured data is called a "record".

To clarify, a _record_ in a given format is the entire contents of a storage
unit within a file.  This includes any framing, headers, footers, and/or
content/payload.  Thus, when speaking of offsets into a record, the reference
is always from the begging of the very first part of the record (usually the
first byte in a record's header).  This allows all other offsets to work
without any knowledge of the internal boundaries between parts of the record.

Record Stepping
---------------

The foundation of this system is built on the notion of _record stepping_.  In
order to scan through a file of unknown format, this system must understand
how to locate the boundaries between records, and _step_ from one record to
the next.  As an example, a text or CSV file can be considered a format whose
records are delimited by new-line (`\n`) characters.  Stepping between records
in those formats is a simple matter of finding the next new-line character.

In an unknown format, there is often internal information that either delimits
or explicitly defines the boundaries from one record to the next.  In most
cases, the length of a record is encoded in a field in a record's header.
Occasionally, this field is a symbolic reference to a size given in an
external specification.  Most of the time, however, this is the literal number
of bytes that are used to represent either the entire record, or some
(possibly variable) portion of the record.

Currently, this system allows a simple way to define record traversal in terms
of two pieces of information:

1. The offset into the record from which we will count bytes
2. The number of bytes from the offset until the end of the record

Thus, the _step_ parameter is made of an _offset_ and a _length_.  If a format
uses fixed-size records, these can simply be literal values.  If a format uses
variable-size records, either or both of these can represent a way to read the
proper values from the record data itself.  This representation is referred to
here as an "extraction specifier" (see below).

Extraction Specifiers
---------------------

Any piece of information can be extracted from a record given a three-part
specifier.  The result of this extraction is typically a string of bytes, but
the more powerful specifier (`var`) is also able to convert the data it finds
into a numeric value, and use that for data analysis.

### Self-terminating Records

The simplest formats will typically use a self-terminating record format.  To
specify how a record terminates itself, two pieces of information can be
specified:

1. The value of a byte that signals the end of a record
2. The value of a byte that escapes the end-of-record byte

### Fixed-length Records

Occasionally, a format will contain records of monotonic length.  Again, two
pieces of information allow extraction of these records:

1. The offset into the record from which the record length is counted (number
   of bytes)
2. The length of the record (number of bytes)

### Variable-length Records

Non-trivial formats will usually encode the size of each record (or its
contents) in a header field of a known position and type.  Two pieces of
information are needed to traverse each record:

1. The offset into the record where we will find the record's length value
2. The format of the numeric value that encodes the record's length value

The numeric formats are specified as a single ASCII character that represents
a primitive numeric data type.  The following should illustrate the typical
conversions to common C data types.

    c : sint8
    C : uint8
    s : sint16 (host byte order)
    S : uint16 (host byte order)
    v : uint16 (little endian)
    l : sint32 (host byte order)
    L : uint32 (host byte order)
    V : uint32 (little endian)
    q : sint64 (host byte order)
    Q : uint64 (host byte order)
    P : uint64 (little endian)
    f : float  (host representation)
    d : double (host representation)

### Specifiers

Specifiers are given as a triplet with the following fields:

- `class`: one of `fix`, `trm`, or `var`
- `arg1`:
    - for `fix` extractors, this is the offset into the record to extract
    - for `trm` extractors, this is the terminating character
    - for `var` extractors, this is the offset into the record to extract
- `arg2`:
    - for `fix` extractors, this is the number of bytes to extract
    - for `trm` extractors, this is the escape character
    - for `var` extractors, this is one of the following type specifiers

In a programming language, the triplet is usually an array or list where the
first item is a string, and the other two items are numbers or characters.
See below for information about how specifiers are given in a query string.

Record Matching
---------------

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

Subset Extraction in Output
---------------------------

If only a subset of the matching records is required, this can be specified
using a slice-style vector.

    (start,stop,step)

- `start` is the index of the first record to return
- `stop` is the index of the last record to return (negative indexes are
  relative to the end of the set)
- `step` is how many records to step over between returned records

Specifying `null` for any of the items in the vector indicates the default
value.

The default slice (all records) would look like this:

    (0,null,1)

To get all even records:

    (0,null,2)

To get the first 100 records:

    (0,100)

In Query Parameter Strings
--------------------------

This system uses the notion of structured query parameter values.  The intent
is to allow a compact, string-based notation to represent the entirety of a
request to scan, extract, combine, and slice data from a file.  To do this,
four parameter strings that can be used:

- `step`: how to navigate between records (default: records are 1 byte long)
- `ident`: record identification scheme (default: match all records)
- `part`: what to extract from each record (default: entire record)
- `slice`: reduce the output to a subset of the final record list (default:
  send all matching records)

None of the parameters are required to scan through a file.  However, the
default behavior is to merely scan the file byte-for-byte and return the whole
thing.

Each parameter uses its own structure.  The following notation shows how each
parameter would be given in a query parameter value string.

### `step`

Record steps are given with with a vector containing two parts.

    (<offset>,<length>)

Each part may be a number or extraction specifier.  The offset is relative to
start of the record (usually the header) to where the reference point for the
specified length of the record.  This allows the record length to be both
dynamically extracted from the stream, and also indicate how this length
should be used to step over the record.  Not all formats will base their
length from the beginning of the record (header), and we don't require knowing
details of the format (such as the length of the header itself).  The offset
can be dynically determined if we know where to extract it relative to the
start of the record.

### `ident`

Record identifiers are given with a vector containing two parts.

    (<extracter>,<match>)
    ((fix,0,4),abcd)
    ((var,4,L),(s,128,256))

### `part`

Record payloads are given with a vector containing two parts.  Each part may
be either a number (indicating a fixed offset or length in the record) or an
extraction specifier (indicating where in the record to find the offset or
length).

    (<offset>,<length>)
    (0,(var,8,L))
    ((var,2,s),48)
    ((var,8,c),(var,9,c))

### `slice`

Slices are given as a slice vector.  See the section above regarding "Subset
Extraction".

### Parameters in a URL

The following shows a few examples of parameters given in a URL GET query.

#### Example 1

Records are variable-length (extracted from a field that counts relative to 8
bytes into the record).  Only report records where the first 4 bytes encodes a
32-bit unsigned integer that contains the value 42.  Extract 4 bytes from each
record starting 8 bytes into the record.  Only report the first 100 matching
records.

    step=(8,(var,4,L))&ident=((var,0,L),42)&part=(8,4)&slice=(0,100)

#### Example 2

Records are fixed-length, 16 bytes each.  Extract the last 4 bytes of each
record.

    step=(0,16)&part=(12,4)

#### Example 3

Records are variable-length (extracted from a field that counts relative to 12
bytes into the record).  Report every third record.

    step=(12,(var,10,S)&slice=(0,null,3)

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

    //artificially limit how much of the file is scanned for all instances
    public static $record_limit = 0;
    public static $scan_limit   = 0;

    //upper limit for record lengths in the stream (16kB)
    public $max_record_length = 16384;


    /*------------------------------------------------------------------------
    Protected Properties
    ------------------------------------------------------------------------*/

    //these are the initial query values that are overridden
    protected static $initial = [

        //match all records
        'ident' => [ [ 'fix', 0, 1 ], null ],

        //each record is one byte long
        'step'  => [ 0, 1 ],

        //extract the entire record
        'part'  => [ 0, null ],

        //return parts of all matching records
        'slice' => [ 0, null, 1 ]
    ];

    //rough validation of incoming query values [ required, optional ]
    protected static $validate = [
        'ident' => [ 1, 1 ],
        'step'  => [ 2, 0 ],
        'part'  => [ 2, 0 ],
        'slice' => [ 2, 1 ]
    ];

    /*======================================================================*/

    //the development event log
    protected $log = null;

    //the StructuredQuery instance that specifies the scan query
    protected $query = null;

    //the Slicer instances that assists with reporting subsets
    protected $slicer = null;

    //the scan specification array
    protected $spec = [];

    //file scanning statistics
    protected $stats = [
        'bytes'    => 0,
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
     * FileScanner constructor.
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
                $spec = $this->query->$k;
                $val  = self::$validate[ $k ];
                $num_spec = count( $spec );
                if(
                    ( $num_spec < $val[ 0 ] )
                    ||
                    ( $num_spec > ( $val[ 0 ] + $val[ 1 ] ) )
                ) {
                    throw new \RuntimeException(
                        "Invalid scanning specification for $k."
                    );
                }
                //ZIH - add more validation (using StructuredQuery)
                $this->spec[ $k ] = $spec;
            }
        }

        //create a new slice object (allow under-specified slice lists)
        $slicer_class = new \ReflectionClass( '\hzphp\Util\Slicer' );
        $this->slicer = $slicer_class->newInstanceArgs(
            $this->spec[ 'slice' ]
        );

        //create an event log for development purposes
        $this->log = \hzphp\Util\EventLog::create_log(
            'FileScanner.php', true, true
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
            $value = \hzphp\Util\Struct::unpack(
                $spec[ 2 ],
                substr( $string, $spec[ 1 ] ),
                true
            );
            return $value;
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
     * Retrieves internal state information as an associative array.
     *
     * @return An associative array of internal state information
     */
    public function getInfo() {
        return [
            'query' => $this->query,
            'spec'  => $this->spec,
            'stats' => $this->stats
        ];
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

        //step specifiers
        list( $offset, $length ) = $this->spec[ 'step' ];

        //step through each record until we find a match
        while( ( $data = $this->step( $offset, $length ) ) !== false ) {

            //dump raw data to log
            //$this->log->put( \hzphp\Util\Struct::gethexshort( $data ) );

            //check for artificial limit on fetching records
            if( self::$record_limit > 0 ) {
                if( $this->stats[ 'scanned' ] >= self::$record_limit ) {
                    return false;
                }
            }

            //increment records scanned counter
            $this->stats[ 'scanned' ] += 1;

            //test for a matching record
            if( $this->matchRecord( $data ) == true ) {

                //increment the match counter
                $this->stats[ 'matched' ] += 1;

                //see if the record is not reportable for the requested subset
                if( $this->slicer->index() == false ) {

                    //see if the slicer is causing issues
                    $this->log->put( "slicer miss {$this->slicer->current}" );

                    //fetch next matching record
                    continue;
                }

                //check in on the slicer
                $this->log->put( "slicer hit {$this->slicer->current}" );

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

        //log non-zero record IDs
        //if( $value != 0 ) {
        //    $this->log->put( "matchRecord: $value == $match" );
        //}

        //return the result of direct comparison matching
        return $value == $match;
    }


    /*------------------------------------------------------------------------
    Protected Methods
    ------------------------------------------------------------------------*/

    /**
     * Steps to the next record in the stream, returning the traversed record
     * data.
     *
     * @param offset The fixed offset or extraction spec for the offset
     * @param length The fixed length or extraction spec for the length
     * @return       The record's data as a string
     */
    protected function step( $offset, $length ) {

        //check for extraction specifier to find offset
        if( is_array( $offset ) ) {

            //only var offsets are allowed
            if( $offset[ 0 ] != 'var' ) {
                return false;
            }

            //extract offset
            $offset = $this->sneak( $offset );

            //check for proper read of offset
            if( $offset === false ) {
                return false;
            }
        }

        //check for extraction specifier to find length
        if( is_array( $length ) ) {

            //only var lengths are allowed
            if( $length[ 0 ] != 'var' ) {
                return false;
            }

            //extract length
            $length = $this->sneak( $length );

            //check for proper read of length
            if( $length === false ) {
                return false;
            }

            //sanity check the extracted length
            if( $length > $this->max_record_length ) {
                return false;
            }
        }

        //calculate the total read length for the record
        $read_length = $offset + $length;

        //see if reading this will put us past our limit
        if( self::$scan_limit > 0 ) {
            $next_read = $this->stats[ 'bytes' ] + $read_length;
            if( $next_read > self::$scan_limit ) {
                return false;
            }
        }

        //gather some information and format a log message
        if( $this->log->is_enabled() ) {
            $position = $this->streamio->tell();
            $message = "reading $read_length at $position: ";
        }

        //fetch the record data
        $data = $this->streamio->read( $read_length );

        //update the amount of data read
        $this->stats[ 'bytes' ] += strlen( $data );

        //log diagnostic information
        if( $this->log->is_enabled() ) {
            $message .= \hzphp\Util\Struct::gethexshort(
                substr( $data, 0, 8 )
            );
            $this->log->put( $message );
        }

        //return the data
        return $data;
    }


    /*------------------------------------------------------------------------
    Private Methods
    ------------------------------------------------------------------------*/

    /**
     * Sneaks data out of the stream such that no one will notice.
     *
     * @param spec   The extraction specifier
     * @param unpack If the specifier was fixed-length, optionally unpack it
     *               using the given specifier (default: no unpacking done)
     * @return       The data as a string or unpacked value (depending on spec)
     */
    private function sneak( $spec, $unpack = false ) {

        //check for variable specifier
        if( $spec[ 0 ] == 'var' ) {

            //determine the length of the field
            $length = \hzphp\Util\Struct::calcsize( $spec[ 2 ] );

            //fetch the data for this field, reset file position
            $data = $this->streamio->extract( $spec[ 1 ], $length, true );

            //check for error/EOF
            if( $data === false ) {
                return false;
            }

            //unpack the extracted field
            $value = \hzphp\Util\Struct::unpack( $spec[ 2 ], $data, true );
        }

        //assume fixed-length specifier
        else {

            //fetch the data, reset file position
            $value = $this->streamio->extract( $spec[ 1 ], $spec[ 2 ], true );

            //see if the user wants automatic unpacking
            if( $unpack != false ) {

                //see if the unpack string is singular
                if( strlen( $unpack ) == 1 ) {

                    //unpack a single value
                    $value = \hzphp\Util\Struct::unpack(
                        $unpack,
                        $data,
                        true
                    );
                }

                //non-trivial unpack string
                else {

                    //unpack it all
                    $value = \hzphp\Util\Struct::unpack( $unpack, $data );
                }
            }
        }

        //return what was extracted
        return $value;
    }


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
            $data .= pack( 'LLLL', 48, 8, $i, $i + 1024 );
        }
        $data .= pack( 'LLL', 64, 4, $i );
    }

    //write test data to temp file, and rewind file position
    fwrite( $file, $data );
    rewind( $file );

    //define some scanning parameters
    $params = [
        'ident' => '((var,0,L),64)',    //match all records starting with 64
        'step'  => '(8,(var,4,L))',     //record lengths are 4 bytes in
                                        //the extracted length indicates the
                                        //size of record less 8 header bytes
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

