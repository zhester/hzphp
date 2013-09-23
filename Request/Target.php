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
     *  Arguments may be automatically passed to any Handler or function call.
     *  Arguments come from the path that was mapped to the handler.  Parts of
     *  the path, additional query arguments, query parameters, and the entire
     *  path can be routed into the handler.
     *
     *  The pseudo-variables to capture arguments are:
     *      Path branch names: #1 to #N
     *      Entire path: #*
     *      Suffix path: #+
     *      A query parameter: #{key}
     *      A query argument: #{1} to #{N}
     *
     *  The request formalizes these concepts.  For example:
     *      - Assume a highly specified path is requested:
     *          http://domain/app/object/category:id,subid?a=b&c=d
     *      - Assume "app" is the above the known root of the request map.
     *      - Assume the request source is specified as:
     *          'object'
     *      - Therefore:
     *          #1      object
     *          #2      category
     *          #*      object/category
     *          #+      category
     *          #{1}    id
     *          #{2}    subid
     *          #{a}    b
     *          #{c}    d
     *
     *  A few example target specifiers:
     *      MyObjectList::getSubCategory(#{1},#{2})
     *
     *  ZIH - well, this turned into a mini-language far too quickly...
     *      gotta rethink this whole design... possibly just expose the
     *      user to regexps if they need fancy parameters/arguments
     *      note: the handler should be able to fetch the parameters/arguments
     *      from its member Request instance...
     *      this is just for the CallbackHandler to deal with static methods
     *      and global functions... maybe these types of specifiers belong in
     *      that part of the implementation
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
        $result = preg_match(
            '/(' . $part . ')::(\w+)/',
            $spec,
            $matches
        );
        if( $result == 1 ) {
            //ZIH - set this up in a CallbackHandler, and return it
            // obj->setRequest( $request )
            return null;
        }

        //check for a class
        $result = preg_match(
            '/(' . $part . ')/',
            $spec,
            $matches
        );
        if( $result == 1 ) {
            //ZIH - create one of these, and return it
            // obj->setRequest( $request )
            return null;
        }

        //pull a list of all the functions
        $functions = get_defined_functions();

        //ensure that the string is on the list of user-defined functions
        if( in_array( $spec, $functions ) == true ) {
            //ZIH - set this up in a CallbackHandler, and return it
            // obj->setRequest( $request )
            return null;
        }

        //unable to match a usable request handler
        throw new Exception( 'Invalid request handler: ' . $spec );
    }

}

?>