<?php

namespace hzphp\Request;

class Request {


    protected           $map;


    public function __construct(
        Map $map
    ) {
        $this->map = $map;
    }


    public function handlePath(
        $path
    ) {

        //ZIH - need to do some more sophisticated parsing here to extract
        //      query details and load into $this so the handler object
        //      can use them directly

        //search for first match to path in map
        $target = $this->map->findTarget( $path );

        //use Target to create the Handler object
        $handler = $target->getHandler( $this );

        //create a new Response object, and return to user
        return new Response( $handler );
    }


}

?>