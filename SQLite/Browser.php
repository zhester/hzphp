<?php

namespace hzphp\SQLite;


/**
 *  Provides a common set of methods for browsing an SQLite3 database
 */
class Browser extends Client {


    /**
     *  Constructs a Browser instance
     *
     *  @param filename Path to the SQLite database, or :memory: to use
     *                  in-memory database.
     *  @param encryption_key
     *                  An optional encryption key used when encrypting and
     *                  decrypting an SQLite database.
     */
    public function __construct(
        $filename,
        $encryption_key = ''
    ) {

        //invoke the parent's constructor
        parent::__construct(
            $filename,
            SQLITE3_OPEN_READONLY,
            $encryption_key
        );
    }


    /**
     *  Gets an array of Table objects representing tables in the database
     *
     *  @param prefix   Optional table prefix for filtering.  Specify an empty
     *                  string (or false) to not filter.  Default is 'sqlite_'
     *  @param exclude  True excludes matching tables, false includes matching
     *                  tables.  Default is true.
     *  @return         An array of Table objects in the database, or false on
     *                  failure
     */
    public function getTables(
        $prefix  = 'sqlite_',
        $exclude = true
    ) {

        //find string length of table name prefix
        $plen = 0;
        if( $prefix ) {
            $plen = strlen( $prefix );
        }

        //query the list of tables in the database
        $q = "select * from sqlite_master where type = 'table'";
        $result = $this->query( $q );
        if( $result === false ) {
            return false;
        }

        //load up an array of Table objects
        $tables = array();
        while( ( $row = $result->fetchArray( SQLITE3_ASSOC ) ) !== false ) {

            //look up the name of the table from the record
            $name = $row[ 'name' ];

            //check for table name filtering
            if( $plen > 0 ) {

                //extract the number of characters to match against the prefix
                $check = substr( $name, 0, $plen );

                //exclusive filter
                if( $exclude == true ) {

                    //only allow if it does NOT match the prefix
                    if( $check != $prefix ) {
                        $tables[] = $this->makeTable( $name );
                    }
                }

                //inclusive filter, and matches the filter
                else if( $check == $prefix ) {
                    $tables[] = $this->makeTable( $name );
                }
            }

            //no table name filtering
            else {
                $tables[] = $this->makeTable( $name );
            }
        }

        //return the array of Table objects
        return $tables;
    }


}

?>