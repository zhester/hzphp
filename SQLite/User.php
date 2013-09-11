<?php

namespace hzphp\SQLite;


/**
 *  Provides a common base class for "user" objects of the SQLite extension
 */
class User {


    protected           $client;    //the owner client of this object


    /**
     *  Constructs a new User instance
     *
     *  @param client   The "owner" client of this object
     */
    public function __construct(
        Client $client
    ) {

        //set the client reference
        $this->client = $client;
    }


}

?>