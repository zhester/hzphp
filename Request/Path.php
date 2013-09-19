<?php

namespace hzphp\Request;


class Path {


    public static       $separator = '/';


    protected           $is_regexp;
    protected           $specifier;


    public function __construct(
        $specifier,
        $is_regexp = false
    ) {
        if( is_array( $specifier ) ) {
            $this->specifier = implode( self::$separator, $specifier );
        }
        else {
            $this->specifier = $specifier;
        }
        $this->is_regexp = $is_regexp;
    }


    public function match(
        $argument
    ) {
        if( is_array( $argument ) ) {
            return $this->matchArray( $argument );
        }
        else if( is_string( $argument ) ) {
            return $this->matchString( $argument );
        }
        return $this->matchString( strval( $argument ) );
    }


    public function matchArray(
        Array $array
    ) {
        return $this->matchString( implode( self::$separator, $array ); );
    }


    public function matchString(
        $string
    ) {

        if( $this->is_regexp == true ) {
            $result = preg_match( $this->specifier, $string, $matches );
            if( $result == 1 ) {
                return true;
            }
        }

        $spec_len = strlen( $this->specifier );
        $str_len  = strlen( $string );

        if( $spec_len <= $strlen ) {
            return $this->specifier == substr( $string, 0, $spec_len );
        }

        return false;
    }


}

?>