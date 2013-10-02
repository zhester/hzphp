<?php

namespace hzphp\Test;


/**
 *  Null logger (prevents output).
 */
class NullLogger extends Logger {


    /**
     *  Send level-qualified text to the log output.
     *
     *  @param level    Message level (int, 0==highest)
     *  @param message  The message to log (string)
     *  @return         True if the message was logged, false on falure
     */
    public function log(
        $level,
        $message
    ) {

        return true;
    }


    /**
     *  Send a test result to the log output.
     *
     *  @param result   The result to log (boolean)
     *  @return         True if the message was logged, false on falure
     */
    public function result(
        $result
    ) {

        return true;
    }


    /**
     *  Send a table of information to the log output.
     *
     *  @param table    An array of arrays of strings to display as a table
     *  @return         True if the message was logged, false on falure
     */
    public function table(
        $table
    ) {

        return true;
    }


    /**
     *  Send unqualified text to the log output.
     *
     *  @param message  The message to log (string)
     *  @return         True if the message was logged, false on falure
     */
    public function text(
        $message
    ) {

        return true;
    }


}

?>