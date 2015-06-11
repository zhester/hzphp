<?php
/*****************************************************************************

File/Stream I/O Abstraction

This started as a way to more easily add functionality to reading highly-
specified parts of files.  However, it quickly became easy to just wrap a
few of the normal file I/O functions, and provide a solid file handling object.

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
 * Abstracts file/stream I/O.
 */
class StreamIO {

    /*------------------------------------------------------------------------
    Public Properties
    ------------------------------------------------------------------------*/

    /*------------------------------------------------------------------------
    Protected Properties
    ------------------------------------------------------------------------*/

    //user file's name
    protected $filename = null;

    //file stream handle
    protected $stream = null;


    /*------------------------------------------------------------------------
    Private Properties
    ------------------------------------------------------------------------*/

    /*------------------------------------------------------------------------
    Public Methods
    ------------------------------------------------------------------------*/

    /**
     * Safely create a StreamIO instance with a lot of flexibility from the
     * user's persepctive.
     *
     * @param target The target file handle, file name, stream-like object,
     *               or existing StreamIO instance
     * @param mode   If specifying a file name, use this access mode
     * @return       A new or existing StreamIO instance
     * @throws       \RuntimeException if the instance can not be created
     */
    public static function createStream( $target, $mode = 'r' ) {

        //StreamIO instance
        $instance = null;

        //check for open file handles
        if(
            is_resource( $target )
            &&
            ( get_resource_type( $target ) == 'stream' )
        ) {
            $instance = new \hzphp\IO\StreamIO( $target );
        }

        //check for objects
        else if( is_object( $target ) ) {

            //check for existing StreamIO instances
            if( $target instanceof StreamIO ) {
                $instance = $target;
            }

            //check for file-like objects
            else {
                //ZIH - not implemented
                throw new \RuntimeException(
                    'Stream-like object usage not yet implemented.'
                );
            }
        }

        //check for file names
        else if( is_string( $target ) ) {
            $instance = new StreamIO( $target, $mode );
        }

        //nothing left to check
        else {
            throw new \RuntimeException(
                'Unable to use requested target for new StreamIO instance.'
            );
        }

        //return the created instance
        return $instance;
    }


    /**
     * StreamIO instance constructor.
     *
     * @param stream The open file stream from which we will extract data.
     *               This can also be a file name (string) that will be opened
     *               during construction, and closed when the object is
     *               garbage collected.
     * @param mode   If passing a file name, specify the mode in which the
     *               file will be opened (default: 'r')
     * @throws       RuntimeException if the file (specified by name) can not
     *               be opened
     */
    public function __construct( $stream, $mode = 'r' ) {

        //detect file name instead of resource
        if( is_string( $stream ) ) {
            $this->filename = $stream;
            $this->stream   = fopen( $stream, $mode );
            if( $this->stream === false ) {
                throw new \RuntimeException( "Unable to open file $stream." );
            }
        }

        //detect normal resource
        else if( is_resource( $stream ) ) {
            $this->stream = $stream;
        }
    }

    /**
     * StreamIO instance desctructor.
     *
     */
    public function __destruct() {

        //see if we were given a file name instead of a stream resource
        if( $this->filename !== null ) {

            //close the file
            $this->close();
        }
    }



    /**
     * Closes the stream resource.
     *
     */
    public function close() {

        //avoid re-closing files that were already closed
        if( $this->stream !== null ) {

            //close the file, and clear the stream property
            fclose( $this->stream );
            $this->stream = null;
        }
    }


    /**
     * Extracts a part of the current file.
     *
     * @param offset The offset from the current position in the file
     * @param length The number of bytes to extract
     *               If null or not given, extract the remainder of the
     *               stream contents.
     * @param reset  Whether or not to reset the file position
     * @return       The extracted data as a string, or false on failure
     */
    public function extract( $offset = 0, $length = null, $reset = false ) {

        //capture the current position in the stream
        $position = $this->tell();

        //see if we need to change the file position
        if( $offset != 0 ) {
            $this->seek( $offset, SEEK_CUR );
        }

        //see if the read is unbounded
        if( $length === null ) {
            $data = $this->read();
        }

        //the read is bounded
        else {
            $data = $this->read( $length );
        }

        //see if the user wishes to reset the file position
        if( ( $data !== false ) && ( $reset == true ) ) {
            $this->seek( $position, SEEK_SET );
        }

        //return the data (or failure)
        return $data;
    }


    /**
     * Flushes the file I/O buffer.
     *
     */
    public function flush() {
        fflush( $this->stream );
    }



    /**
     * Loads data into the file from platform-native values.
     *
     * @param format The pack() format string to construct the data
     * @param data   An array of data values to load into the file
     * @return       Number of bytes written, or false on failure.
     */
    public function load( $format, $data ) {
        $args = $data;
        array_unshift( $args, $format );
        $data = call_user_func_array( 'pack', $args );
        return $this->write( $data );
    }


    /**
     * Reads data from the file.
     *
     * @param size The number of bytes to read from the stream.  If negative,
     *             this will read the remaining contents of the stream.  If
     *             the size is not specified, the remainder of the file is
     *             read.
     * @return     The data read from the stream.  Once the stream has reached
     *             its end, boolean false is returned.
     * @throws     RuntimeException if there is an error
     */
    public function read( $size = -1 ) {
        if( is_integer( $size ) == false ) {
            throw new \RunimeException(
                "Invalid size \"$size\" in call to read()."
            );
        }
        else if( $size < 0 ) {
            $data = stream_get_contents( $this->stream );
        }
        else {
            $data = fread( $this->stream, $size );
        }
        if( $data === false ) {
            throw new \RuntimeException( 'Error reading from stream.' );
        }
        if( ( $data == '' ) && feof( $this->stream ) ) {
            return false;
        }
        return $data;
    }


    /**
     *  Reads a part of a file up to a terminating character.  If the
     *  terminating character is not encountered, the remainder of the file is
     *  returned.
     *
     * @param trm The record's terminating character
     * @param esc The terminating character's escape character
     * @return    The file contents as a string or false on failure/EOF
     */
    public function readterm( $trm = "\n", $esc = '\\' ) {

        //data buffer for output
        $data = '';

        //read until end of record or end of file
        while( feof( $this->stream ) == false ) {

            //read the next character
            $char = fgetc( $this->stream );

            //check for error
            if( $char === false ) {
                if( $data != '' ) {
                    return $data;
                }
                return false;
            }

            //escape character read
            else if( $char == $esc ) {

                //read the next character
                $next = fgetc( $this->stream );

                //error check
                if( $next === false ) {
                    return false;
                }

                //see if we are escaping a terminator or escape
                if( ( $next == $trm ) || ( $next == $esc ) ) {

                    //retain the escaped character
                    $data .= $next;
                }

                //unrecognized escape
                else {

                    //retain both characters
                    $data .= $char . $next;
                }
            }

            //terminating character read
            else if( $char == $trm ) {

                //stop reading data
                break;
            }

            //any other character read
            else {

                //append character to buffer
                $data .= $char;
            }
        }

        //check for an empty read at the EOF
        if( ( $data === '' ) && ( feof( $this->stream ) == true ) ) {
            return false;
        }

        //return data buffer
        return $data;
    }


    /**
     * Changes the current position within the file.
     *
     * @param bytes  The number of bytes to seek
     * @param whence From whence to seek (SEEK_(SET|CUR))
     * @return       True on success, false on failure/error
     */
    public function seek( $bytes, $whence = SEEK_SET ) {
        $result = fseek64( $this->stream, $bytes, $whence );
        return $result == 0;
    }


    /**
     * Reports the current position in the file.
     *
     * @return The current byte offset into the file
     */
    public function tell() {
        return ftell( $this->stream );
    }


    /**
     * Unloads data from the file into platform-native types.
     *
     * @param format The pack() or unpack() format string.
     * @return       An array containing the unpacked data
     */
    public function unload( $format ) {
        $num_bytes = \hzphp\Util\Struct::calcsize( $format );
        $data = $this->read( $num_bytes );
        if( $data === false ) {
            return false;
        }
        return \hzphp\Util\Struct::unpack( $format, $data );
    }


    /**
     * Writes data to the file.
     *
     * @param data   The data to write as a string
     * @param length The maximum number of bytes to write
     * @return       The number of bytes written, or false on failure.
     */
    public function write( $data, $length = false ) {
        if( $length === false ) {
            $result = fwrite( $this->stream, $data );
        }
        else {
            $result = fwrite( $this->stream, $data, $length );
        }
        return $result;
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

/**
 * Works around fseek()'s inability to handle 64-bit offsets in one call.
 *
 * Note: This does not work on 32-bit systems due to fseek()'s internal
 * file position type.
 *
 * @param fh     The file handle to upon which to seek
 * @param offset The offset into the file as a string (allows offset > 2G)
 * @param whence From whence to seek
 * @return       0 on success, -1 on error
 */
function fseek64( $fh, $offset, $whence = SEEK_SET ) {

    //shortcut for sub-2G seeks
    if( gmp_cmp( PHP_INT_MAX, $offset ) > 0 ) {
        return fseek( $fh, intval( $offset ), $whence );
    }

    //move initial file pointer to a known reference position
    fseek( $fh, 0, $whence );

    //create multiprecision resource for the large offset string
    $remaining_offset = gmp_init( $offset );

    //set the seek jump distance
    $distance = gmp_init( PHP_INT_MAX );

    //seek until the remaining offset is <= the jump distance
    while( gmp_cmp( $remaining_offset, $distance ) > 0 ) {
        $result = fseek( $fh, gmp_intval( $distance ), SEEK_CUR );
        if( $result != 0 ) {
            return $result;
        }
        $remaining_offset = gmp_sub( $remaining_offset, $distance );
    }

    //the remaining offset can now be reached
    return fseek( $fh, gmp_intval( $remaining_offset ), SEEK_CUR );
}


/*----------------------------------------------------------------------------
Execution
----------------------------------------------------------------------------*/

if( $_SERVER[ 'SCRIPT_FILENAME' ] == __FILE__ ) {

    header( 'Content-Type: text/plain; charset=utf-8' );

    require __DIR__ . '/../tools/loader.php';

    //open temporary file for testing
    $file = tmpfile();

    //pack some binary data for testing
    $data = pack( 'LLLLCCS', 42, 26, 12345, 8192, 16, 255, 32767 );

    //write the data, and reset the file position
    fwrite( $file, $data );
    rewind( $file );

    //set up a new `StreamIO` object to extract data from the file
    $si = new StreamIO( $file );

    //extract a single field (advances the file position)
    $data = $si->extract( 4, 4 );
    echo "Testing `extract()`\n";
    print_r( unpack( 'Lvalue', $data ) );

    //change the field we just read
    $si->seek( -4, SEEK_CUR );
    $si->load( 'L', [ 53 ] );

    //read it again
    $si->seek( -4, SEEK_CUR );
    $data = $si->extract( 0, 4 );
    echo "Testing `load()`\n";
    print_r( unpack( 'Lvalue', $data ) );

    //extract a number of fields, and convert to native types
    $data = $si->unload( 'LLCCS' );
    echo "Testing `unload()`\n";
    print_r( $data );

    //replace the file's contents with a few lines of text
    rewind( $file );
    fwrite( $file, "line 1\nline \\\\ 2\nline \\\n 3\nlast \\\t line\n" );
    rewind( $file );

    //use the `readterm()` method to extract each line
    $lines = [];
    while( ( $line = $si->readterm() ) !== false ) {
        $lines[] = str_replace( "\n", '{NL}', $line );
    }
    echo "Testing `readterm()`\n";
    print_r( $lines );

    //close the temporary file (PHP will automatically unlink it)
    fclose( $file );
}

