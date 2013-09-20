<?php

namespace hzphp\Request;


/**
 *  Models the destination of a mapped request.
 *
 *  Destinations provide a compact syntax to describe how to execute code that
 *  is used to fulfill a request.  To keep things simple, the destination
 *  syntax is largely just a checking and resolution of a few different
 *  string formats to determine where in the application we need to look for
 *  an appropriate request handler.
 *
 *  When instantiating an object to handle a request, the class must implement
 *  a Response\Handler interface.
 *
 *  The request handler must always return a Response\Provider-compatable
 *  object.
 */
class Destination {


    protected           $specifier;


    public function __construct(
        $specifier
    ) {
        $this->specifier = $specifier;
    }


    public function getProvider() {
        
    }


}

?>