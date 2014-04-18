<?php

/*----------------------------------------------------------------------------
Namespace
----------------------------------------------------------------------------*/

namespace hzphp\emysqli;


/*----------------------------------------------------------------------------
Exceptions
----------------------------------------------------------------------------*/

class MultiQueryException extends \RuntimeException {}


/**
 *  Provides a more manageable interface to mysqli::multi_query().  This makes
 *  it much easier to add and remove queries from a list of queries while
 *  still properly checking results and errors.
 *
 *  Note: Typical usage involves allowing the emysqli class to construct these
 *  rather than the user having to instantiate an instance.
 */
class multiquery {

    /*------------------------------------------------------------------------
    Class Constants
    ------------------------------------------------------------------------*/


    /*------------------------------------------------------------------------
    Public Properties
    ------------------------------------------------------------------------*/


    /*------------------------------------------------------------------------
    Protected Properties
    ------------------------------------------------------------------------*/


    /*------------------------------------------------------------------------
    Private Properties
    ------------------------------------------------------------------------*/

    private             $db;        //initialized mysqli instance
    private             $queries;   //array of query objects
    private             $reports;   //array of requested reports


    /*------------------------------------------------------------------------
    Public Methods
    ------------------------------------------------------------------------*/

    /**
     *  Constructor
     *
     *  @param db An initialized mysqli instance
     */
    public function __construct( $db ) {

        //initialize object state
        $this->db      = $db;
        $this->queries = [];
        $this->reports = [];
    }


    /**
     *  Adds a query to the execution list.
     *
     *  @param query  The query to add.  This may either be an instance of the
     *                emysqli\query class or a string containing a query.
     *  @param report If specified, results are reported and identified with
     *                the value (usually a string) given.
     */
    public function add_query( $query, $report = null ) {

        //add a query object to the list
        if( is_object( $query ) && ( $query instanceof query ) ) {
            $this->queries[] = $query;
        }

        //add a query string to the list
        else {
            $q = new Query( $this->db, $query );
            $this->queries[] = $q;
        }

        //push the possible report request to the list of reports
        $this->reports[] = $report;

        //return the query that was added
        return $q;
    }


    /**
     *  Adds a "script" of queries to the execution list.
     *
     *  @param script  One or more queries terminated by default MySQL
     *                 delimiters (;).
     *  @param reports An optional list of report requests.  The positions of
     *                 each item in the list correspond to each query in the
     *                 script of queries.
     */
    public function add_script( $script, $reports = null ) {

        //look for a trailing semicolon, and strip, if necessary
        $script = preg_replace( '/;\s*$/', '', $script );

        //separate the individual queries
        $queries = explode( ';', $script );
        $num     = count( $queries );

        //see if the user did not specify a list of reports
        if( is_array( $reports ) == false ) {

            //start with a list that requests no reporting
            $new_reports = array_fill( 0, $num, null );

            //default argument value, or explicitly set to null
            if( $reports === null ) {

                //the default assumption is the final result is desired
                $new_reports[ $num - 1 ] = 'default';
            }

            //see if the user wants a single report
            else if( is_int( $reports ) == true ) {

                //check for a negative report index (reference end of list)
                if( $reports < 0 ) {
                    $new_reports[ $num + $reports ] = $reports;
                }

                //positive report index
                else {
                    $new_reports[ $reports ] = $reports;
                }
            }

            //set the report list for query appending below
            $reports = $new_reports;
        }

        //the user specified a list of reports, see if we need to pad the list
        else if( count( $reports ) < $num ) {
            $reports = array_pad( $reports, $num, null );
        }

        //add all the queries in the script to the execution list
        for( $i = 0; $i < $num; ++$i ) {
            $this->add_query( $queries[ $i ], $reports[ $i ] );
        }

    }


    /**
     *  Runs all queries loaded into the object at once.
     *
     *  @param callback A callable entity to call for each query as its
     *                  results become available.  The results object is
     *                  passed to the callback.
     *  @throws MultiQueryException
     *                  1. if there are no queries to execute
     *                  2. if a query fails on the DBMS host
     *                  3. if a report is requested, but there are no results
     */
    public function execute( $callback, $abort_on_fail = false ) {

        //determine number of queries that will be executed
        $num = count( $this->queries );
        if( $num == 0 ) {
            throw new MultiQueryException( 'No queries to execute.' );
        }

        //send all queries to the DBMS host for sequential execution
        $this->db->multi_query( implode( ';', $this->queries ) );

        //attempt to retrieve results from each query that was sent
        for( $i = 0; $i < $num; ++$i ) {

            //store the result from the server
            $result = $this->db->store_result();

            //check the particular query for any problems on the host
            //  note: $result == false is okay for insert/update/delete
            if( $this->db->errno != 0 ) {
                if( $abort_on_fail == true ) { return; }
                throw new MultiQueryException(
                    "Database error: \n"
                    . $this->db->error
                    . "\n"
                    . $this->queries[ $i ]
                );
            }

            //does this result need to be reported?
            if( $this->reports[ $i ] !== null ) {
                call_user_func( $callback, $result, $this->reports[ $i ] );
            }

            //see if there are more results
            if( $this->db->more_results() == true ) {

                //advance result pointer
                $this->db->next_result();
            }

            //no more results, see if this is not the last query
            else if( $i != ( $num - 1 ) ) {
                throw new MultiQueryException(
                    'Missing results from multiple query execution.'
                );
            }
        }
    }


    /*------------------------------------------------------------------------
    Protected Methods
    ------------------------------------------------------------------------*/


    /*------------------------------------------------------------------------
    Private Methods
    ------------------------------------------------------------------------*/


}


/*----------------------------------------------------------------------------
Testing
----------------------------------------------------------------------------*/

if( $_SERVER[ 'SCRIPT_FILENAME' ] == __FILE__ ) {
    //$mq = new multiquery();
}

?>