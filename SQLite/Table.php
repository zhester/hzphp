<?php

namespace hzphp\SQLite;


/**
 *  Represents a table in the database
 */
class Table extends User {


    public              $name;      //name of the table


    /**
     *  Constructs a Table instance
     *
     *  @param client   The owner client instance
     *  @param name     The name of the table in the database
     */
    public function __construct(
        Client $client,
        $name
    ) {

        //invoke the parent's constructor
        parent::__construct( $client );

        //store the table name
        $this->name = $name;
    }


    /**
     *  Describes a table by returning an array of arrays of column info
     *
     *  @return         Column information (array of arrays), or false on
     *                  failure
     */
    public function describe() {

        //proprietary table info query (unable to prepare this?)
        $q = "pragma table_info( {$this->name} )";

        //run the query
        $result = $this->client->query( $q );
        if( $result === false ) {
            return false;
        }

        //initialize an array of column information
        $info = array();

        //load each column from the results
        while( ( $row = $result->fetchArray( SQLITE3_ASSOC ) ) !== false ) {
            $info[] = $row;
        }

        //return the table description information
        return $info;
    }

}

?>