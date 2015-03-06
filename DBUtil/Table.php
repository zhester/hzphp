<?php

/*----------------------------------------------------------------------------
Namespace
----------------------------------------------------------------------------*/

namespace hzphp\DBUtil;


/**
 *
 */
class Table {

    /*------------------------------------------------------------------------
    Class Constants
    ------------------------------------------------------------------------*/


    /*------------------------------------------------------------------------
    Public Properties
    ------------------------------------------------------------------------*/


    /*------------------------------------------------------------------------
    Protected Properties
    ------------------------------------------------------------------------*/

    protected           $db;        //the initialized mysqli instance
    protected           $id_col_i = 0;
                                    //the index of the ID column
    protected           $name;      //the name of the table


    /*------------------------------------------------------------------------
    Private Properties
    ------------------------------------------------------------------------*/

    private             $_columns = [];
                                    //the list of columns in the table


    /*------------------------------------------------------------------------
    Public Static Methods
    ------------------------------------------------------------------------*/

    /**
     *  Tests any string to ensure it can safely be used as a table name.
     *
     *  @param name The potential name of a table
     *  @return     True if the name appears valid, otherwise false
     */
    public static function isValidName( $name ) {
        return preg_match( '/[^a-zA-Z_]/', $name ) === 0;
    }


    /*------------------------------------------------------------------------
    Public Methods
    ------------------------------------------------------------------------*/

    /**
     *  Constructor
     *
     *  @param db   An initialized mysqli instance
     *  @param name The name of the table in the database
     */
    public function __construct( $db, $name ) {

        //sanity check table name
        if( self::isValidName( $name ) == false ) {
            throw new UsageException( "Invalid table name requested: $name" );
        }

        //initialize object state
        $this->db   = $db;
        $this->name = $name;
    }


    /**
     *  Getter to make the table's name read-only.
     *
     *  @param name The name of the property that was requested
     */
    public function __get( $name ) {
        if( $name == 'name' ) {
            return $this->name;
        }
        throw new UsageException( "Invalid property name requested: $name" );
    }


    /**
     * Represents the instance as the name of the table.
     *
     * @return The name of the table as a string
     */
    public function __toString() {
        return $this->name;
    }


    /**
     *  Returns an array of table Column objects.
     *
     *  @return An array of Column objects for the columns in this table
     */
    public function getColumns() {
        if( count( $this->_columns ) == 0 ) {
            $this->loadColumns();
        }
        return $this->_columns;
    }


    /**
     *  Get the name of the ID column.
     *
     *  @return The name of the ID column
     */
    public function getIDField() {
        if( count( $this->_columns ) == 0 ) {
            $this->loadColumns();
        }
        return $this->_columns[ $this->id_col_i ]->name;
    }


    /**
     *  Loads the list of columns into the object's state.
     *
     */
    public function loadColumns() {

        //fetch the column list for this table
        $result = $this->db->query( "show columns from {$this->name}" );

        //check the query for problems
        if( $this->db->errno != 0 ) {
            throw new DatabaseException(
                "Unable to load columns for table ({$this->name}): "
                . $this->db->error
            );
        }

        //load each column into object state
        $i = 0;
        while( ( $cdef = $result->fetch_assoc() ) !== null ) {
            $c = new Column( $cdef );
            $this->_columns[ $i ] = $c;

            //set the index to the ID column
            if( $c->key == 'PRI' ) {
                $this->id_col_i = $i;
            }
            $i += 1;
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
    //$t = new Table();
}

