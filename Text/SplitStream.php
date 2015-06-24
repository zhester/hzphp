<?php
/****************************************************************************

Delimited Stream Scanning Implementation
========================================

Implements `explode()`-like string splitting, but operates on streams.  The
important distinction is that this class does not keep the entire contents of
the stream in memory while it is scanning for new substrings.  Therefore,
this implementation is suitable for very large files.

The class is designed to be used in an iterator context (with a `foreach`
loop).  Substrings can be sequentially extracted by following PHP's Iterator
protocol.

    $obj = new IteratorClass();
    $obj->rewind();
    if( $obj->valid() == true ) {
        $first_item = $obj->current();
    }
    $obj->next();
    if( $obj->valid() == true ) {
        $second_item = $obj->current();
    }

The stream only needs to handle `fseek()` and `fread()` calls.

More sophisticated splitting can be accomplished by extending the `Separator`
class, and using that in place of a simple string separator.

Note: The Iterator interface requires a `key()` method.  This class implements
it by returning the current substring's offset into the string.  It can be
considered unique for all substrings in the stream, but these values are
likely to be confusing if the user is expecting an array-style offset.

Example Usage
-------------

    $filename     = 'file.txt';
    $file         = fopen( $filename, 'r' );
    $split_stream = new SplitStream( $file, "\n\n" );
    $paragraphs   = [];
    foreach( $split_stream as $paragraph ) {
        $paragraphs[] = $paragraph;
    }
    fclose( $file );
    $num_paragraphs = count( $paragraphs );
    echo "$filename contains $num_paragraphs paragraphs.";

****************************************************************************/

/*---------------------------------------------------------------------------
Default Namespace
---------------------------------------------------------------------------*/

namespace hzphp\Text;

/*---------------------------------------------------------------------------
Dependencies
---------------------------------------------------------------------------*/

/*---------------------------------------------------------------------------
Classes
---------------------------------------------------------------------------*/

/**
 * Encapsulates some messy details for extracting delimited sections of text
 * from a stream.
 */
class SplitStream implements \Iterator {

    /*-----------------------------------------------------------------------
    Public Properties
    -----------------------------------------------------------------------*/

    public $chunk_size = 4096; //size of chunks to read from stream


    /*-----------------------------------------------------------------------
    Protected Properties
    -----------------------------------------------------------------------*/

    protected $buffer        = '';    //stream string buffer
    protected $buffer_size   = 0;     //current size of stream buffer
    protected $bytes_read    = 0;     //total bytes read from the stream
    protected $is_complete   = false; //splitting completion status
    protected $length        = null;  //length of current text segment
    protected $separator     = null;  //block separating string/pattern
    protected $stream_empty  = false; //stream has been exhausted
    protected $stream_offset = 0;     //current stream offset


    /*-----------------------------------------------------------------------
    Private Properties
    -----------------------------------------------------------------------*/

    private $self_opened = false; //file was opened in instance


    /*-----------------------------------------------------------------------
    Public Methods
    -----------------------------------------------------------------------*/

    /**
     * SplitStream Constructor
     *
     * @param stream    The open file stream handle to scan for text segments
     * @param separator The string or Separator used to split the stream
     */
    public function __construct( $stream, $separator = null ) {

        //check for open stream
        if( get_resource_type( $stream ) == 'stream' ) {
            $this->stream = $stream;
        }

        //attempt to open a file
        else if( is_string( $stream ) && file_exists( $stream ) ) {
            $this->stream = fopen( $stream, 'rb' );
            if( $this->stream !== false ) {
                $this->self_opened = true;
            }
            else {
                throw new \InvalidArgumentException(
                    'Invalid file name for stream object.'
                );
            }
        }

        //all out of things to try
        else {
            throw new \InvalidArgumentException(
                'Invalid stream handle for stream object.'
            );
        }

        //check for a specified separator
        if( $separator !== null ) {

            //simple string separator
            if( is_string( $separator ) ) {
                $this->separator = new Separator( $separator );
            }

            //abstract separator
            else if( is_object( $separator ) ) {
                $this->separator = $separator;
            }

            //unknown separator
            else {
                throw new \InvalidArgumentException(
                    'Invalid separator specified for stream object.'
                );
            }
        }

        //no separator given
        else {

            //create a default separator
            $this->separator = new Separator();
        }

    }


    /**
     * SplitStream Destructor
     *
     */
    public function __destruct() {

        //if we opened the stream, close it
        if( $this->self_opened == true ) {
            fclose( $this->stream );
        }
    }



    /**
     * Retrieves the current text segment in the stream.
     *
     * @return A string containing the current text segment
     */
    public function current() {

        //return the current text segment
        return substr( $this->buffer, 0, $this->length );
    }


    /**
     * Retrieves a unique key for the current text segment in the stream.
     *
     * @return A unique identifier for the current text segment
     */
    public function key() {

        //return the current segment's offset
        return $this->stream_offset;
    }


    /**
     * Advances the stream to the next text segment.
     *
     */
    public function next() {

        //attempt to reclaim unneeded buffer memory from previous segments
        $this->reclaim_memory();

        //check for empty stream and buffer
        if( ( $this->stream_empty == true ) && ( $this->buffer_size <= 0 ) ) {

            //set completion flag
            $this->is_complete = true;

            //no more scanning required
            return;
        }

        //offset used to search for the separator in the stream
        $offset = false;

        //scan the stream up until we hit the first separator
        while( $offset === false ) {

            //buffer additional data
            $this->buffer_data();

            //attempt to locate the first separator in the buffer
            $offset = $this->separator->locate( $this->buffer );

            //see if we've emptied the stream without locating the separator
            if( ( $this->stream_empty == true ) && ( $offset === false ) ) {
                break;
            }
        }

        //see if we failed to find a separator
        if( $offset === false ) {

            //set the last segment to the end of the buffer
            $this->length = $this->buffer_size;
        }

        //separator was found in buffer
        else {

            //set the offset to the separator to allow extracting this segment
            $this->length = $offset;
        }

    }


    /**
     * Resets the input stream.
     *
     */
    public function rewind() {
        $this->buffer        = '';
        $this->buffer_size   = 0;
        $this->bytes_read    = 0;
        $this->is_complete   = false;
        $this->length        = null;
        $this->stream_offset = 0;
        $this->stream_empty  = false;
        fseek( $this->stream, 0, SEEK_SET );
        $this->next();
    }


    /**
     * Retrieves stream statistics.
     *
     * @return An associative array with the follow keys:
     *             buffer_size : current size of text buffer
     *             bytes_read  : total bytes read from stream
     *             length      : length of current text segment
     *             offset      : current byte offset into stream
     */
    public function stats() {
        $stats = [
            'buffer_size' => $this->buffer_size,
            'bytes_read'  => $this->bytes_read,
            'length'      => $this->length,
            'offset'      => $this->stream_offset
        ];
        if( false ) {
            $stats[ 'buffer' ]   = $this->buffer;
            $stats[ 'complete' ] = $this->is_complete  ? 'true' : 'false';
            $stats[ 'empty' ]    = $this->stream_empty ? 'true' : 'false';
        }
        return $stats;
    }


    /**
     * Indicates if the current position in the stream is still valid.
     *
     * @return True if the current segment in the stream is valid
     */
    public function valid() {

        //completion flag indicates if there is a current segment
        return $this->is_complete == False;
    }


    /*-----------------------------------------------------------------------
    Protected Methods
    -----------------------------------------------------------------------*/

    /**
     * Buffers new data from the stream into the object's state.
     *
     */
    protected function buffer_data() {

        //see if we can acquire more data from the stream
        if( $this->stream_empty == true ) {
            return;
        }

        //read the next chunk from the stream
        $chunk = fread( $this->stream, $this->chunk_size );

        //check for major failure to read from stream
        if( $chunk === false ) {
            $this->stream_empty = true;
        }

        //no real problem encountered during read
        else {

            //append new data to buffer
            $this->buffer      .= $chunk;
            $bytes_read         = strlen( $chunk );
            $this->buffer_size += $bytes_read;
            $this->bytes_read  += $bytes_read;

            //see if this read exhausted the stream
            if( $bytes_read < $this->chunk_size ) {
                $this->stream_empty = true;
            }
        }

    }


    /**
     * Reclaims buffer memory no longer needed.
     *
     */
    protected function reclaim_memory() {

        //check for a previously extracted text segment
        if( ( $this->buffer_size > 0 ) && ( $this->length !== null ) ) {

            //set the amount of the buffer to reclaim
            $reclaim = $this->length + $this->separator->length;

            //remove the previous text segment from the buffer
            $this->buffer       = substr( $this->buffer, $reclaim );
            $this->buffer_size -= $reclaim;

            //advance the stream offset to point to the next segment
            $this->stream_offset += $reclaim;
        }

    }

}


/**
 * Provides an abstraction for specifying more elaborate stream separators.
 */
class Separator {


    /*-----------------------------------------------------------------------
    Protected Properties
    -----------------------------------------------------------------------*/

    protected $spec; //the separator specification (string, pattern, etc)


    /*-----------------------------------------------------------------------
    Public Methods
    -----------------------------------------------------------------------*/

    /**
     * Separator Constructor
     *
     * @param spec The separator specification, usually a string
     */
    public function __construct( $spec = "\n" ) {
        $this->spec = $spec;
    }


    /**
     * Property access for read-only attributes.
     *
     * @param  key The property name of which to retrieve a value
     * @return     The value of the property
     * @throws     OutOfBoundsException if the key is invalid
     */
    public function __get( $key ) {
        if( $key == 'length' ) {
            return $this->get_length();
        }
        throw new \OutOfBoundsException( "Invalid property name: $key" );
    }


    /**
     * Locates the separator in the given string.
     *
     *
     * @param string The subject string to search
     * @return       The offset into the string where the separator begins,
     *               or a boolean false if the separator is not found
     */
    public function locate( $string ) {
        return strpos( $string, $this->spec );
    }


    /*-----------------------------------------------------------------------
    Protected Methods
    -----------------------------------------------------------------------*/

    /**
     * Allows inheriting classes to override the separator length calculation
     *
     * @return The length of the most-recently-matched separator
     */
    protected function get_length() {
        return strlen( $this->spec );
    }

}


/*---------------------------------------------------------------------------
Functions
---------------------------------------------------------------------------*/

