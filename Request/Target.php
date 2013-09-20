<?php

namespace hzphp\Request;


/**
 *  Models the target of a mapped request.
 *
 *  Targets provide a compact syntax to describe how to execute code that
 *  is used to fulfill a request.  To keep things simple, the syntax is
 *  largely just a checking and resolution of a few different string formats
 *  to determine where in the application we need to look for an appropriate
 *  request handler.
 */
class Target {


    protected           $specifier;


    public function __construct(
        $specifier
    ) {
        $this->specifier = $specifier;
    }


    public function getHandler() {

////////////////////////ZIH - parse specifier here

    }

}

?>