<?php

namespace hzphp\Test;


/**
 *  Plain text logger.
 */
class PlainLogger extends Logger {


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

        switch( $level ) {

            case Report::HEADING:
                $out = sprintf(
                    "\n%s\n%s\n",
                    $message,
                    str_repeat( '=', strlen( $message ) )
                );
                break;

            case Report::SECTION:
                $out = sprintf(
                    "\n%s\n%s\n",
                    $message,
                    str_repeat( '-', strlen( $message ) )
                );
                break;

            case Report::SUBSECTION:
                $out = sprintf( "\n%s\n", $message );
                break;

            case Report::STEP:
                $out = sprintf( "\n  %s\n", $message );
                break;
        }

        return $this->out( $out );
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
        if( $result == true ) {
            $out = "  ---- PASSED ----\n";
        }
        else {
            $out = "  ################\n"
                 . "  #### FAILED ####\n"
                 . "  ################\n";
        }
        return $this->out( $out );
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

        //measure all the cells
        $num_rows = count( $table );
        $num_cols = count( $table[ 0 ] );
        $max_rows = array_pad( [], $num_rows, 0 );
        $max_cols = array_pad( [], $num_cols, 1 );
        for( $i = 0; $i < $num_rows; ++$i ) {
            for( $j = 0; $j < $num_cols; ++$j ) {
                $string = $table[ $i ][ $j ];
                $length = strlen( $string );
                if( $length > $max_cols[ $j ] ) {
                    $max_cols[ $j ] = $length;
                }
                $newlines = substr_count( $string, "\n" );
                if( $newlines > $max_rows[ $i ] ) {
                    $max_rows[ $i ] = $newlines;
                }
            }
        }

        //prepare a table row separator line
        $borders = [];
        for( $j = 0; $j < $num_cols; ++$j ) {
            $borders[] = str_repeat( '-', ( $max_cols[ $j ] + 2 ) );
        }
        $row_sep = '+' . implode( '+', $borders ) . '+';

        //build a list of text lines
        $lines = [];
        for( $i = 0; $i < $num_rows; ++$i ) {
            $lines[] = $row_sep;
            $line = [];
            //ZIH - this won't support multi-line cells like this
            for( $j = 0; $j < $num_cols; ++$j ) {
                $line[] = str_pad( $table[ $i ][ $j ], $max_cols[ $j ], ' ' );
            }
            $lines[] = '| ' . implode( ' | ', $line ) . ' |';
        }
        $lines[] = $row_sep;

        return $this->out( "\n" . implode( "\n", $lines ) . "\n" );
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

        $out = wordwrap( $message, 74, "\n", true );
        $out = str_replace( "\n", "\n    " , $out );

        return $this->out( "\n    " . $out . "\n" );
    }


}

?>