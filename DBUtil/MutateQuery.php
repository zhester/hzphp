<?php

/*----------------------------------------------------------------------------
Namespace
----------------------------------------------------------------------------*/

namespace hzphp\DBUtil;


/**
 *  Provides a systematic approach to constructing queries intended for
 *  changing data stored in the database via "insert" and "update" queries.
 *  The intent is to use the products of this system as the inputs to a
 *  prepared statement that can safely send data to the DBMS host without
 *  a lot of repetitive (and brittle!) safety-checking code.
 *
 */
class MutateQuery {

    /*------------------------------------------------------------------------
    Class Constants
    ------------------------------------------------------------------------*/


    /*------------------------------------------------------------------------
    Public Properties
    ------------------------------------------------------------------------*/


    /*------------------------------------------------------------------------
    Protected Properties
    ------------------------------------------------------------------------*/

    protected           $ifields = [];
                                    //input field list
    protected           $table;     //target table instance
    protected           $values;    //query input values


    /*------------------------------------------------------------------------
    Private Properties
    ------------------------------------------------------------------------*/

    private             $id_field = 'id';
                                    //the name of the table's ID field

    /*------------------------------------------------------------------------
    Public Methods
    ------------------------------------------------------------------------*/

    /**
     *  Constructor
     *
     *  @param table  A Table object for the target table
     *  @param values An associative array of values for the query.  The keys
     *                must match the names of the fields in the table.
     */
    public function __construct( $table, $values ) {

        //initialize the object state
        $this->id     = $table->getIDField();
        $this->table  = $table;
        $this->values = $values;
    }


    /**
     *  Get the insert query.
     *
     *  @return An array of the following three items (in this order):
     *            0: The query string suitable for a prepared statement
     *            1: The placeholder type declaration string
     *            2: The list of values for placeholder substitution
     */
    public function getInsert() {

        //determine the field names, types, and values
        list( $flist, $types, $vlist ) = $this->getCommon();

        //construct an insert query with placeholders
        $query = 'insert into ' . $this->table->name
            . ' ( ' . implode( ', ', $flist ) . ' ) values ( '
            . implode( ', ', array_fill( 0, count( $flist ), '?' ) ) . ' )';

        //return the query, types, and values
        return [ $query, $types, $vlist ];
    }


    /**
     *  Get the update query.
     *
     *  @param id The value of the target record's ID
     *  @return   An array of the following three items (in this order):
     *              0: The query string suitable for a prepared statement
     *              1: The placeholder type declaration string
     *              2: The list of values for placeholder substitution
     */
    public function getUpdate( $id ) {

        //determine the field names, types, and values
        list( $flist, $types, $vlist ) = $this->getCommon( $this->id_field );

        //construct an update query with placeholders
        $query = 'update ' . $this->table->name . ' set '
            . implode( ' = ?, ', $flist ) . ' = ? where id = ? limit 1';

        //append the ID's type character and value
        $types .= 'i';
        $vlist[] = $id;

        //return the query, types, and values
        return [ $query, $types, $vlist ];
    }


    /**
     *  Set a list of fields that exist in the table, but must be ignored for
     *  this query (even if they are specified in the values array).
     *
     *  @param fields An array of field names (or a single field name)
     */
    public function setIgnoredFields( $fields ) {

        //array of fields passed
        if( is_array( $fields ) == true ) {
            $this->ifields = $fields;
        }

        //single field passed
        else if( is_string( $fields ) == true ) {
            $this->ifields = [ $fields ];
        }
    }


    /*------------------------------------------------------------------------
    Protected Methods
    ------------------------------------------------------------------------*/

    /**
     *  Determine the components of the query common to either mutation
     *  query.
     *
     *  @param ignore Optional field or fields to ignore in addition to the
     *                object's established list of ignored fields
     *  @return       An array containing the following items (in this order):
     *                  0: array of field names
     *                  1: type declaration string
     *                  2: query substitution values
     */
    protected function getCommon( $ignore = null ) {

        //array of immediate ignored fields passed
        if( is_array( $ignore ) == true ) {
            $ignore = array_merge( $ignore, $this->ifields );
        }

        //single immediate ignored field passed
        else if( is_string( $ignore ) == true ) {
            $ignore = array_merge( [ $ignore ], $this->ifields );
        }

        //use only the object's established list
        else {
            $ignore = $this->ifields;
        }

        //initialize some stack space
        $flist = [];
        $types = '';
        $vlist = [];

        //get the list of columns in the table
        $columns = $this->table->getColumns();

        //loop through each column
        for( $i = 0; $i < count( $columns ); ++$i ) {

            //shortcut to the name of this column
            $k = $columns[ $i ]->name;

            /*----------------------------------------------------------------
            Determine if the query should include this column.
            To use a value in a query it:
                1. must be for a field that is not on the ignore list
                2. must be set to some value by the user
                3. must not be set to exactly null (it can be empty)
            ----------------------------------------------------------------*/
            if( ( in_array( $k, $ignore ) == false     )
             && ( isset( $this->values[ $k ] ) == true )
             && ( $this->values[ $k ] !== null         ) ) {
                $flist[] = $k;
                $vlist[] = &$this->values[ $k ];
                $types .= $columns[ $i ]->type_group;
            }
        }

        //return the list of fields, types, and values
        return [ $flist, $types, $vlist ];
    }


    /*------------------------------------------------------------------------
    Private Methods
    ------------------------------------------------------------------------*/


}


/*----------------------------------------------------------------------------
Testing
----------------------------------------------------------------------------*/

if( $_SERVER[ 'SCRIPT_FILENAME' ] == __FILE__ ) {
    //$mq = new MutateQuery();
}

?>