<?php

namespace hzphp\Test;


/**
 *  HTML output logger.
 */
class HTMLLogger extends Logger {


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

        //formatted output buffer
        $out = '';

        if( $level <= Report::SUBSECTION ) {
            $h = strval( $level + 1 );
            $out .= '<h' . $h . '>'
                . htmlspecialchars( $message )
                . '</h' . $h . ">\n";
        }

        else if( $level == Report::STEP ) {
            $out .= '<p class="step">'
                . htmlspecialchars( $message )
                . "</p>\n";
        }

        else {
            $out .= '<p>'
                . htmlspecialchars( $message )
                . "</p>\n";
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
            $out = "<h4 class=\"result success\">PASSED</h4>\n";
        }
        else {
            $out = "<h4 class=\"result failure\">FAILED</h4>\n";
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

        $rows = [];
        foreach( $table as $row ) {
            $cells = array_map(
                function( $item ) {
                    return htmlspecialchars( $item );
                },
                $row
            );
            $rows[] = '<td>' . implode( '</td><td>', $cells ) . '</td>';
        }

        return $this->out(
            "<table>\n"
            . "  <tr>"
            . implode( "</tr>\n  <tr>", $rows )
            . "</tr>\n"
            . "</table>\n"
        );
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

        return $this->out(
            '<blockquote>' . htmlspecialchars( $message ) . "</blockquote>\n"
        );
    }


}

