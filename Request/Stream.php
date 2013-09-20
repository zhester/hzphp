<?php

namespace hzphp\Request;


/**
 *  The standard response (readable) data stream interface.
 */
interface Stream {


    /**
     *  Close the stream.
     *
     */
    public function stream_close();


    /**
     *  Test the stream for EOF.
     *
     *  @return         True if the stream has reached EOF, otherwise false
     */
    public function stream_eof();


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
    );


    /**
     *  Read data from the stream.
     *
     *  @param count    The number of bytes to read from the current position
     *  @return         The data read from the stream, or false when empty
     */
    public function stream_read(
        $count
    );


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
    );


    /**
     *  Get the current position in the stream.
     *
     *  @return         The position in the stream (number of bytes)
     */
    public function stream_tell();


}

?>