<?php

namespace hzphp\SQLite;


/**
 *  Adds some generic features to the base SQLite3 class.  Used as a base
 *  class for other database objects in this module.
 */
class Client extends \SQLite3 {


    public              $filename;  //stores the database file name


    /**
     *  Constructs a new Client instance
     *
     *  @param filename Path to the SQLite database, or :memory: to use
     *                  in-memory database.
     *  @param flags    Optional flags used to determine how to open the
     *                  SQLite database.  (see: SQLite3::__construct()).
     *  @param encryption_key
     *                  An optional encryption key used when encrypting and
     *                  decrypting an SQLite database.
     */
    public function __construct(
        $filename,
        $flags          = false,
        $encryption_key = ''
    ) {

        //if necessary, default the constructor flags
        if( $flags === false ) {
            $flags = SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE;
        }

        //invoke the parent's constructor
        parent::__construct( $filename, $flags, $encryption_key );

        //store the database file name for later
        $this->filename = $filename;
    }


    /**
     *  Properly constructs a new Table object
     *
     *  @param name     The name of the table in the database
     *  @return         The new Table object
     */
    protected function makeTable(
        $name
    ) {

        //construct the table with a reference to the owner client
        return new Table( $this, $name );
    }


}

?>