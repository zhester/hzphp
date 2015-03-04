<?php
/*****************************************************************************

Structured Binary Data Utilities
================================

Implements utilities for handling binary data stored in strings.

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
 * Hosts static methods for handling structured binary data in strings.
 */
class Struct {

    /*------------------------------------------------------------------------
    Public Properties
    ------------------------------------------------------------------------*/

    /*------------------------------------------------------------------------
    Protected Properties
    ------------------------------------------------------------------------*/

    //a character class of all pack string formats
    protected static $cclass = '[aAcCdfhHiIJlLnNPqQsSvVxXZ]';

    //pack string format sizes (in bytes)
    protected static $sizes = [
        'c' => 1, 'C' => 1, 's' => 2, 'S' => 2, 'l' => 4, 'L' => 4,
        'q' => 8, 'Q' => 8, 'f' => 4, 'd' => 8,
        'a' => 1, 'A' => 1, 'h' => 1, 'H' => 1, 'x' => 1, 'Z' => 1,
        'n' => 2, 'v' => 2, 'i' => 2, 'I' => 2,
        'N' => 4, 'V' => 4, 'J' => 8, 'P' => 8, 'X' => -1
        //@: absolute byte offset
        //*: repeat to end of data
    ];


    /*------------------------------------------------------------------------
    Private Properties
    ------------------------------------------------------------------------*/

    /*------------------------------------------------------------------------
    Public Methods
    ------------------------------------------------------------------------*/

    /**
    * Calculates the amount of data packed or unpacked using
    * `pack()`/`unpack()` format strings (similar to Python's
    * `struct.calcsize()`).
    *
    * @param format The pack/unpack format string to analyze
    * @param unpack If this is an ambiguous unpack string, force it to be
    *               analyzed as a PHP-specific unpack string.  For example:
    *               `Cval` can either be a four-part pack string, or a single
    *               unpack specifier with the name "val".  If this is an
    *               unpack string with multiple specifiers separated by
    *               forward-slashes, this argument is not necessary.
    * @return       The total size (in bytes) of the data represented by the
    *               pack/unpack string.  If the size can not be determined
    *               (due to an invalid or unsupported format specifier),
    *               boolean false is returned.
    */
    public static function calcsize( $format, $unpack = false ) {

        //no way to calculate size with "remaining" repetition
        if( strpos( $format, '*' ) !== false ) {
            return false;
        }

        //absolute position specifier not yet supported
        if( strpos( $format, '@' ) !== false ) {
            return false;
        }

        //detect if this is a PHP-specific unpack string
        if( ( $unpack == true ) || ( strpos( $format, '/' ) !== false ) ) {

            //break apart each format specifier, and strip unpack names
            $specs = array_map(
                function( $part ) {
                    $result = preg_match( '/(\\w)(\\d*).+/', $part, $m );
                    if( $result == 1 ) {
                        $rep = $m[ 2 ] == '' ? 1 : intval( $m[ 2 ] );
                        return [ $m[ 1 ], $rep ];
                    }
                    return [ $part, 1 ];
                },
                explode( '/', $format )
            );
        }

        //this is a normal pack/unpack string
        else {

            //generate the normal packing list
            $specs = self::packlist( $format );
        }

        //iterate through each format specifier, and add up the total size
        $length    = 0;
        $num_specs = count( $specs );
        for( $i = 0; $i < $num_specs; ++$i ) {

            //the format specifier character
            $char = $specs[ $i ][ 0 ];

            //make sure we have a size for this character
            if( isset( self::$sizes[ $char ] ) ) {

                //size of format specifier multiplied by repetition
                $length += self::$sizes[ $char ] * $specs[ $i ][ 1 ];
            }

            //unknown specifier character
            else {
                return false;
            }
        }

        //return total length
        return $length;
    }


    /**
    * Retrieves a formatted hex dump for diagnostic purposes.
    *
    * @param string The string containing the data to inspect
    * @param ncols  The number of columns in the formatted table
    * @param csep   The column separator for the formatted table
    * @param rsep   The row separator for the formatted table
    * @return       A table of byte values using plain text formatting
    */
    public static function gethexdump(
        $string,
        $ncols = 16,
        $csep  = ' ',
        $rsep  = "\n"
    ) {

        //number of bytes to format
        $num_bytes = strlen( $string );

        //number of rows to render
        $num_rows = ceil( $num_bytes / $ncols );

        //formatted table rows
        $rows = [];

        //iterate through each row
        for( $i = 0; $i < $num_rows; ++$i ) {

            //formatted hexadecimal strings
            $hex = [];

            //iterate through each byte value
            for( $j = 0; $j < $ncols; ++$j ) {

                //determine the index into the subject string for this value
                $index = ( $i * $ncols ) + $j;

                //ensure this index is valid
                if( $index < $num_bytes ) {

                    //note: array indexing (e.g. $string[ $index ]) doesn't
                    //work if the first character in the string is a null byte
                    $values = unpack( 'Cbyte', substr( $string, $index, 1 ) );
                    $hex[] = sprintf( '%02X', $values[ 'byte' ] );
                }

                //index has exceeded the string
                else {

                    //done adding formatted strings to table
                    break;
                }
            }

            //collapse the formatted strings into a single row
            $rows[] = implode( $csep, $hex );
        }

        //collapse the formatted rows into the final table, and return
        return implode( $rsep, $rows );
    }


    /**
     * Retrieves a (supposedly) very short hex dump for quick display inside
     * strings.
     *
     * @param string The data to convert to hexadecimal as a string
     * @param chars  Optional output surrounding/delimiting characters
     * @return       A string of hexadecimal strings
     */
    public static function gethexshort( $string, $chars = '{ }' ) {
        $num_chars = strlen( $chars );
        if( $num_chars == 0 ) {
            $chars = [ '', '', '' ];
        }
        else if( $num_chars == 1 ) {
            $chars = [ '', $chars, '' ];
        }
        else if( $num_chars == 2 ) {
            $chars = [ $chars[ 0 ], ' ', $chars[ 1 ] ];
        }
        $num_bytes = strlen( $string );
        $hex = [];
        for( $i = 0; $i < $num_bytes; ++$i ) {
            $values = unpack( 'Cbyte', $string[ $i ] );
            $hex[] = sprintf( '%02X', $values[ 'byte' ] );
        }
        return $chars[ 0 ] . implode( $chars[ 1 ], $hex ) . $chars[ 2 ];
    }


    /**
     * Converts a normal pack string into a list of packing specifiers as an
     * array of two-element arrays.  Each nested array contains the packing
     * format specifier and the repetition of that specifier.
     *
     * @param format The pack formatting string to parse
     * @return       An array of two-element arrays with discrete pack/unpack
     *               specifiers
     */
    public static function packlist( $format ) {

        //a regular expression fragment for matching pack fields
        $refrag = '(' . self::$cclass . ')(\\*|\\d+)?';

        //list of packing specifiers
        $specs = [];

        //see if this is clearly a PHP-style unpack string
        if( strpos( $format, '/' ) !== false ) {

            //break apart each unpack field
            $fields = explode( '/', $format );

            //strip out the field name, and construct specifiers
            $specs = array_map(
                function( $field ) {
                    if( preg_match( "/$refrag.*/", $field, $m ) == 1 ) {
                        return self::groups2spec( $m );
                    }
                    return false;
                },
                $fields
            );
        }

        //this is a normal pack string
        else {

            //offset into the format string to begin matching
            $off = 0;

            //scan format string for each packing specifier
            while( preg_match( "/$refrag/", $format, $m, 0, $off ) == 1 ) {

                //add to the list of specifiers
                $specs[] = self::groups2spec( $m );

                //advance the scanning offset
                $off += strlen( $m[ 0 ] );
            }
        }

        //return the list of specifiers
        return $specs;
    }


    /**
     * Provides an alternative to PHP's built-in `unpack()` that relies on
     * mapping things to associative arrays.  Using this function removes the
     * need to specify names for each specifier.  Additionally, a single value
     * may be retrieved by specifying the index into the final unpack array.
     *
     * @param format The pack/unpack format string to use for unpacking data
     * @param data   The string from which data will be unpacked
     * @param select Optionally select one of the unpacked fields to retrieve
     *               at the exclusion of all other unpacked fields.  This is
     *               a numeric index into the list of unpacked fields.  If it
     *               is set to boolean `true`, the first field is returned.
     * @return       A numerically-indexed array of the unpacked data.  If
     *               the `select` parameter is given (and refers to a valid
     *               index), a single unpacked value is returned.
     */
    public static function unpack( $format, $data, $select = false ) {

        //see if the user has passed a PHP-style unpack string
        if( strpos( $format, '/' ) !== false ) {

            //use it
            $unpack_string = $format;
        }

        //the user has passed a normal pack string
        else {

            //parse the list of unpacking specifiers
            $specs = self::packlist( $format );

            //convert the packing specifiers into a PHP-style unpack string
            $num_specs = count( $specs );
            $unpack_specs = [];
            for( $i = 0; $i < $num_specs; ++$i ) {
                $unpack_specs[] = implode( '', $specs[ $i ] ) . "k$i";
            }
            $unpack_string = implode( '/', $unpack_specs );
        }

        //bounds check the data against how much we need
        if( strlen( $data ) < self::calcsize( $format ) ) {
            return false;
        }

        //unpack the data using "normal" unpacking
        $parts = unpack( $unpack_string, $data );

        //see if the user just wants the first item
        if( $select === true ) {
            $select = 0;
        }

        //see if the user has selected a single item to report
        if( $select !== false ) {
            $key = "k$select";
            if( isset( $parts[ $key ] ) ) {
                return $parts[ $key ];
            }
        }

        //report the list of unpacked values
        return array_values( $parts );
    }


    /*------------------------------------------------------------------------
    Protected Methods
    ------------------------------------------------------------------------*/

    /*------------------------------------------------------------------------
    Private Methods
    ------------------------------------------------------------------------*/

    /**
     * Converts expression matching groups list to a field specifier array.
     *
     * @param groups The list of matching groups (from `preg_match()`)
     * @return       A two-element array specifying a pack field.  The
     *               specifier represents the packing type in the first
     *               offset, and the repetition in second offset.
     */
    private static function groups2spec( $groups ) {

        //assume no repeition is specified
        $rep = 1;

        //check for specified repetition
        if( count( $groups ) == 3 ) {

            //check for "remainder" repetition
            $rep = $groups[ 2 ] == '*' ? null : intval( $groups[ 2 ] );
        }

        //construct the specifier
        return [ $groups[ 1 ], $rep ];
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
    $data = pack(
        'LLLLLLLLcs', 0xDEADBEAF, 1, 2, 3, 4, 5, 6, 0x80000001, 42, 7
    );
    echo "Struct::gethexdump()\n";
    echo Struct::gethexdump( $data );
    echo "\n\n";
    echo "Struct::calcsize()\n";
    $cases = [
        'LL'                      => 8,
        'L3'                      => 12,
        'c20'                     => 20,
        'C2Q10l4'                 => 98,
        'Ladam/l2baker/c3charlie' => 15
    ];
    foreach( $cases as $format => $length ) {
        $size = Struct::calcsize( $format );
        echo "$format / $length --> $size\n";
    }
    echo "\n\n";
    echo "Struct::unpack()\n";
    print_r( Struct::unpack( 'LLLLLLLLcs', $data ) );
}

