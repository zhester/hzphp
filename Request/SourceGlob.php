<?php

namespace hzphp\Request;


class SourceGlob extends Source {


    public function match(
        $string
    ) {

        //replace glob-style bits with regexp-style bits
        $pattern = preg_replace_callback(
            '/(?<!\\)([*?])/',
            function( $matches ) {
                return [
                    '?' => '.',
                    '*' => '.*'
                ][ $matches[ 1 ] ];
            }
            $this->specifier
        );

        //surround the pattern in some regexp delimiters
        $pattern = '#' . $pattern . '#';

        //check for a matching string
        $result = preg_match( $pattern, $string );

        if( $result == 1 ) {
            return true;
        }

        return false;
    }


}

?>