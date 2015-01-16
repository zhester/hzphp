<?php

namespace hzphp\Request;


abstract class Path {


    public static       $separator = '/';


    protected           $specifier;


    public function __construct(
        $specifier
    ) {
        $this->specifier = $specifier;
    }


    public function __toString() {
        return $this->specifier;
    }


    abstract public function match(
        $string
    );


    protected function split(
        $string = false
    ) {
        if( $string == false ) {
            return explode( self::$separator, $this->specifier );
        }
        return explode( self::$separator, $string );
    }


}

