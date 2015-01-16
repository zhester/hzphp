<?php

namespace hzphp\Test;


/**
 *  Test infrastructure logger base class.
 */
abstract class Logger {


    protected           $filename;  //output file name
    protected           $handle = false;
                                    //output file handle


    /**
     *  Send level-qualified text to the log output.
     *
     *  @param level    Message level (int, 0==highest)
     *  @param message  The message to log (string)
     *  @return         True if the message was logged, false on falure
     */
    abstract public function log(
        $level,
        $message
    );


    /**
     *  Send a test result to the log output.
     *
     *  @param result   The result to log (boolean)
     *  @return         True if the message was logged, false on falure
     */
    abstract public function result(
        $result
    );


    /**
     *  Send a table of information to the log output.
     *
     *  @param table    An array of arrays of strings to display as a table
     *  @return         True if the message was logged, false on falure
     */
    abstract public function table(
        $table
    );


    /**
     *  Send unqualified text to the log output.
     *
     *  @param message  The message to log (string)
     *  @return         True if the message was logged, false on falure
     */
    abstract public function text(
        $message
    );


    /**
     *  Construct a logger instance.
     *
     *  @param filename Name of the file to which output is sent
     *  @throws Exception
     *                  On failure to open the log file
     */
    public function __construct(
        $filename = 'php://output'
    ) {

        $this->filename = $filename;
        $this->handle = fopen( $this->filename, 'wb' );
        if( $this->handle === false ) {
            throw new Exception(
                "Unable to open file \"$filename\" for writing."
            );
        }
    }


    /**
     *  Destruct a logger instance.
     */
    public function __destruct() {

        if( $this->handle !== false ) {
            fclose( $this->handle );
        }
    }


    /**
     *  Write log string output.
     *
     *  @param string   The string to write
     *  @return         True if the string was written, false on failure
     */
    protected function out(
        $string
    ) {

        $length  = strlen( $string );
        $written = fwrite( $this->handle, $string );

        return $length == $written;
    }


}

