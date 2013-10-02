<?php

namespace hzphp\Request;


/**
 *  Provides the model for any class that wishes to handle a request.
 */
abstract class Handler implements Stream {


    public              $context = null;


    protected           $m_eof        = false;
    protected           $m_bytes_read = 0;
    protected           $m_path       = '';
    protected           $m_request    = null;


    /**
     *  Instantiate a Handler object.
     *
     *  Note: To remain compatible with the streamWrapper convention, this
     *  constructor must not take any parameters.
     */
    public function __construct() {
    }


    /**
     *  Repeatedly called to read data from the handler.
     *
     *  @return         Strings of data, or false when data is exhausted
     */
    abstract public function read();


    /**
     *  Allows user handlers to override and provide a custom HTTP status code
     *
     *  @return         HTTP status code (integer)
     */
    public function getStatus() {
        return Status::OK;
    }


    /**
     *  Allows user handlers to override and provide custom HTTP response
     *  headers.  This will always be called before the first call to read().
     *
     *  @return         An associative array of HTTP response headers
     */
    public function headers() {
        return [];
    }


    /**
     *  Sets the request being handled.
     *
     *  @param request  The request being handled.
     */
    public function setRequest(
        Request $request
    ) {
        $this->m_request = $request;
    }


    /**
     *  Close the stream.
     *
     */
    public function stream_close() {
    }


    /**
     *  Test the stream for EOF.
     *
     *  @return         True if the stream has reached EOF, otherwise false
     */
    public function stream_eof() {
        return $this->m_eof;
    }


    /**
     *  Open the stream.
     *
     *  @param path     The path to open
     *  @param mode     The mode used to open the stream
     *  @param options  Not supported, see: streamwrapper.stream-open
     *  @param opened_path
     *                  Not supported, See: streamwrapper.stream-open
     *  @return         True on success, false on failure
     */
    public function stream_open(
        $path,
        $mode         = 'r',
        $options      = 0,
        &$opened_path = null
    ) {
        if( $mode != 'r' ) {
            return false;
        }
        $this->m_eof        = false;
        $this->m_bytes_read = 0;
        $this->m_path       = $path;
    }


    /**
     *  Read data from the stream.
     *
     *  @param count    The number of bytes to read from the current position
     *  @return         The data read from the stream, or false when empty
     */
    public function stream_read(
        $count
    ) {
        $result = $this->read();
        if( $result === false ) {
            $this->m_eof = true;
        }
        return $result;
    }


    /**
     *  Seek to a new position in the stream.
     *
     *  @param offset   The byte offset where we shall seek
     *  @param whence   From whence we shall seek
     *  @return         True if the seek succeeded, false on failure
     */
    public function stream_seek(
        $offset,
        $whence = SEEK_SET
    ) {
        /*
        //ZIH - not currently implemented because i don't want to add the
        //      burden on all user handlers to implement a reset mechanism
        if( ( $offset == 0 ) && ( $whence == SEEK_SET ) ) {
            $this->m_eof        = false;
            $this->m_bytes_read = 0;
            return true;
        }
        */
        return false;
    }


    /**
     *  Get the current position in the stream.
     *
     *  @return         The position in the stream (number of bytes)
     */
    public function stream_tell() {
        return $this->m_bytes_read;
    }


}

?>