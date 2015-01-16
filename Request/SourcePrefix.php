<?php

namespace hzphp\Request;


class SourcePrefix extends Source {


    public function match(
        $string
    ) {
        $spec_len = strlen( $this->specifier );
        $str_len  = strlen( $string );

        if( $spec_len <= $str_len ) {
            return $this->specifier == substr( $string, 0, $spec_len );
        }

        return false;
    }


}

