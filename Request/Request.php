<?php

namespace hzphp\Request;


/**
 *  Manages the top level of an application request.
 */
class Request {


    public              $query;     //access to the request's Query instance


    protected           $map;


    /**
     *  Initializes a Request object.
     *
     *  @param map      A request Map object
     */
    public function __construct(
        Map $map
    ) {
        $this->map   = $map;
        $this->query = null;
    }


    /**
     *  Convenience method that just sends the response output for the most
     *  likely request.
     *
     */
    public function handleAndSend() {

        //handle a typical request
        $response = $this->handleRequest();

        //send the response output
        $response->send();
    }


    /**
     *  Creates a Response object capable of handling the requested path.
     *
     *  @param path     The path string to handle
     *  @return         A Response object that can handle the request
     */
    public function handlePath(
        $path
    ) {

        //search for first match to path in map
        $target = $this->map->findTarget( $path );

        //use Target to create the Handler object
        $handler = $target->getHandler( $this );

        //create a new Response object, and return to user
        return new Response( $handler );
    }


    /**
     *  Handles a typical request (based on the request URI).
     *
     *  @return         A Response object that can handle the request
     */
    public function handleRequest() {

        //create a query object (parses the URI)
        $this->query = new Query( $_SERVER[ 'REQUEST_URI' ] );

        //determine the correct Response based on the path
        return $this->handlePath( $this->query->path );
    }


}

