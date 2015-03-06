<?php
/*****************************************************************************

Common Test Setup for `emysqli` Module Testing
==============================================

*****************************************************************************/

/*----------------------------------------------------------------------------
Default Namespace
----------------------------------------------------------------------------*/

namespace hzphp\emysqli;

/*----------------------------------------------------------------------------
Dependencies
----------------------------------------------------------------------------*/

/*----------------------------------------------------------------------------
Classes
----------------------------------------------------------------------------*/

/*----------------------------------------------------------------------------
Functions
----------------------------------------------------------------------------*/

/**
 * Checks for, and possibly initializes, a test-only database.
 *
 * @param db       A mysqli instance with a connection to the test database
 * @param attempts Number of attempts to try to initialize the database
 * @return         True if the database is in a valid state, otherwise false
 */
function check_test_database( $db, $attempts = 5 ) {

    //attempt limit reached
    if( $attempts <= 0 ) {
        return false;
    }

    //look for existence of table
    $result = $db->query( 'select id from hzphp_test limit 1' );

    //see if the query produced data results
    if( $result instanceof \mysqli_result ) {

        //see if the data results represents an empty set
        if( $result->num_rows == 0 ) {
            $result = $db->query(
                "insert into hzphp_test (
                    `id`, `parent_id`, `name`, `notes`
                ) values
                ( 1, 0, 'root',     'the root node in the test table'       ),
                ( 2, 1, 'branch0',  'the first branch node'                 ),
                ( 3, 1, 'branch1',  'the second branch node'                ),
                ( 4, 1, 'branch2',  'the third branch node'                 ),
                ( 5, 2, 'branch0a', 'the first branch of the first branch'  ),
                ( 6, 2, 'branch0b', 'the second branch of the first branch' )
                "
            );
            if( $result == false ) {
                return false;
            }
        }
    }

    //the query produced no data results
    else {

        //check for multiple timestamp column support (server version 5.6.5)
        if( $db->server_version >= 50605 ) {
            $mod_default = "current_timestamp on update current_timestamp";
        }
        else {
            $mod_default = "'0000-00-00 00:00:00'";
        }

        //create the test table
        $result = $db->query(
            "create table hzphp_test (
                `id`        int not null auto_increment,
                `parent_id` int not null default 1,
                `name`      varchar( 31 ) not null default '',
                `notes`     mediumtext not null default '',
                `created`   timestamp not null default current_timestamp,
                `modified`  timestamp not null default $mod_default,
                primary key( `id` ),
                key `parent_id` ( `parent_id` ),
                key `name`      ( `name` ),
                key `created`   ( `created` ),
                key `modified`  ( `modified` ),
                fulltext key `search` ( `name`, `notes` )
            ) engine = MyISAM default charset = utf8"
        );
        if( $result === false ) {
            return false;
        }

        //check again to generate test records
        return check_test_database( $db, ( $attempts - 1 ) );
    }

    //if we made it here, everything is properly initialized
    return true;
}


/*----------------------------------------------------------------------------
Execution
----------------------------------------------------------------------------*/

require dirname( __DIR__ ) . '/tools/loader.php';
require dirname( __DIR__ ) . '/misc/test_db_conf.php';

if( realpath( $_SERVER[ 'SCRIPT_FILENAME' ] ) == __FILE__ ) {

    header( 'Content-Type: text/plain; charset=utf-8' );

}

