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


    /**
     *  Creates an appropriate Handler object for the specified target.
     *
     *  To resolve a specifier, the following rules are checked, in turn:
     *      1. Does the specifier appear to be a static method invocation?
     *         e.g. Namespace\Class::method
     *      2. Does the specifier appear to name a Handler-derived class?
     *         e.g. Namespace\Class
     *      3. Does the specifier appear to name a global function?
     *         e.g. my_function
     *
     *  Once the type is determined, one of two actions are taken:
     *      Option 1: If the specifier names a Handler-derived class, the
     *          Handler is instantiated and returned.
     *      Option 2: A CallbackHandler is instantiated, and configured to
     *          use the static method or global function.
     *
     *  @param request
     *  @return
     *  @throws
     */
    public function getHandler(
        Request $request
    ) {

        //alias the member
        $spec = $this->specifier;

        //the partial expression to match a class identifier
        $part = '[A-Za-z\\_][A-Za-z0-9\\_]+';

        //check for a static method
        $result = preg_match( '/(' . $part . ')::(\w+)/', $spec, $matches );
        if( $result == 1 ) {
            if( ( class_exists( $matches[ 1 ] ) == true )
             && ( method_exists( $matches[ 1 ], $matches[ 2 ] ) == true ) ) {
                $handler = new CallbackHandler();
                $handler->setCallback( $spec );
                $handler->setRequest( $request );
                return $handler;
            }
        }

        //check for a class
        $result = preg_match( '/^(' . $part . ')$/', $spec, $matches );
        if( $result == 1 ) {
            $hclass = __NAMESPACE__ . '\\Handler';
            if( ( class_exists( $matches[ 1 ] ) == true )
             && ( is_subclass_of( $matches[ 1 ], $hclass ) == true ) ) {
                $handler = new $spec();
                $handler->setRequest( $request );
                return $handler;
            }
        }

        //pull a list of all the functions
        $functions = get_defined_functions();

        //ensure that the string is in the list of user-defined functions
        if( in_array( $spec, $functions[ 'user' ] ) == true ) {
            $handler = new CallbackHandler();
            $handler->setCallback( $spec );
            $handler->setRequest( $request );
            return $handler;
        }

        //unable to match a usable request handler
        throw new \Exception( 'Invalid request handler: ' . $spec );
    }

}

?>