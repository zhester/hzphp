<?php

namespace hzphp\DBI;


/**
 *  The interface exposed to the input and output part of the application.
 */
interface DataSet {



    /**
     *  Creates a new, in-memory Element instance that can interact with the
     *  parent Set instance.  This does not affect persistent storage.
     *
     *  @param arguments
     *                  An associative array of initial field values
     *  @return         A new Element instance in the Set
     */
    public function createElement(
        Array $arguments
    );


    /**
     *  Deletes an Element in the Set by its unique ID.
     *
     *  @param id       The ID of the record to delete
     *  @return         True if successfully deleted, false on error
     */
    public function deleteById(
        $id
    );


    /**
     *  Retrieves an Element in the Set by its unique ID.
     *
     *  @param id       The ID of the record to fetch
     *  @return         Element instance of record, or false on error
     */
    public function fetchById(
        $id
    );


    /**
     *  Inserts a new Element object into the Set.
     *
     *  @param element  The Element instance to insert
     *  @return         The unique ID of the element, or false on failure
     */
    public function insert(
        Element $element
    );


}


?>