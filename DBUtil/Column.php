<?php

/*----------------------------------------------------------------------------
Namespace
----------------------------------------------------------------------------*/

namespace hzphp\DBUtil;


/**
 *
 */
class Column {

    /*------------------------------------------------------------------------
    Class Constants
    ------------------------------------------------------------------------*/


    /*------------------------------------------------------------------------
    Public Properties
    ------------------------------------------------------------------------*/

    public static       $db_property_map = [
                                    //maps database fields to property names
        'Field'   => 'name',
        'Type'    => 'sql_type',
        'Null'    => 'nullok',
        'Key'     => 'key',
        'Default' => 'default',
        'Extra'   => 'extra'
    ];

    public              $default    = null;     //default value
    public              $extra      = null;     //extra information
    public              $key        = null;     //key declaration
    public              $limits     = [];       //limits on storage
    public              $name;                  //column name
    public              $nullok     = true;     //null allowed flag
    public              $sql_type   = null;     //SQL type string
    public              $type       = 'string'; //php type string
    public              $type_group = 's';      //type group character (idsb)


    /*------------------------------------------------------------------------
    Protected Properties
    ------------------------------------------------------------------------*/


    /*------------------------------------------------------------------------
    Private Properties
    ------------------------------------------------------------------------*/


    /*------------------------------------------------------------------------
    Public Static Methods
    ------------------------------------------------------------------------*/

    /**
     *  Parses the type string from an SQL column declaration into an array
     *  containing two values: type and limits.
     *
     *  @param sql_type The SQL type declaration for a column
     *  @return         An array with two keyed items:
     *                    type: gives the same type string as gettype()
     *                    limits: gives a one- or two-element array defining
     *                      a type-appropriate limit on the values that can
     *                      be stored in this column
     */
    public static function parseSQLType( $sql_type ) {

        //set the default structure values
        $struct = [ 'type' => 'string', 'limits' => [] ];

        //parse the type declaration string
        $result = preg_match(
            '/^(\w+)(\\([^)]+\\))?( \w+)?$/',
            $sql_type,
            $matches
        );

        //make sure it parsed okay
        if( $result != 1 ) {

            //unable to parse, go ahead and return something reasonable
            return $struct;
        }

        //see how much of the expression matched
        $num_matches = count( $matches ) - 1;

//http://dev.mysql.com/doc/refman/5.6/en/data-type-overview.html
//ZIH - set up real limits based on SQL docs later
//ZIH - add support for timestamp, datetime, date, time, year, enum, set

        //detect type information for each popular SQL type
        switch( $matches[ 1 ] ) {

            //integers
            case 'int':
            case 'integer':
            case 'bigint':
            case 'mediumint':
            case 'smallint':
            case 'tinyint':
                $struct[ 'type' ] = gettype( 1 );
                if( ( $num_matches  >= 3          )
                 && ( $matches[ 3 ] == ' unsigned' ) ) {
                    $struct[ 'limits' ] = [ 0 ];
                }
                break;

            //booleans
            case 'bool':
            case 'boolean':
                $struct[ 'type' ] = gettype( true );
                break;

            //doubles/floats
            case 'dec':
            case 'decimal':
            case 'double':
            case 'float':
            case 'fixed':
                $struct[ 'type' ] = gettype( 1.0 );
                if( ( $num_matches  >= 3          )
                 && ( $matches[ 3 ] == ' unsigned' ) ) {
                    $struct[ 'limits' ] = [ 0.0 ];
                }
                break;

            //fixed strings
            case 'char':
            case 'binary':
                if( $num_matches >= 2 ) {
                    $length = intval( $matches[ 2 ] );
                    $struct[ 'limits' ] = [ $length, $length ];
                }
                break;

            //short, variable strings
            case 'varchar':
            case 'varbinary':
                if( $num_matches >= 2 ) {
                    $struct[ 'limits' ] = [ 0, intval( $matches[ 2 ] ) ];
                }
                break;

            //larger strings
            case 'text':
            case 'tinytext':
            case 'smalltext':
            case 'mediumtext':
            case 'longtext':
            default:
                //everything else defaults to 'string'
                break;
        }

        //return the type information
        return $struct;
    }


    /*------------------------------------------------------------------------
    Public Methods
    ------------------------------------------------------------------------*/

    /**
     *  Constructor
     *
     *  @param definition 
     */
    public function __construct( $definition ) {
        $this->setColumnDefinition( $definition );
    }


    /**
     *  Loads a column definition from the result of a database query for the
     *  column information into the object state.
     *
     *  @param definition 
     */
    public function setColumnDefinition( $definition ) {

        //pull all the basic stuff out of the definition
        foreach( $definition as $k => $v ) {
            $pk = self::$db_property_map[ $k ];
            $this->$pk = $v;
        }

        //convert the null flag to a boolean
        $this->nullok = $this->nullok == 'YES';

        //parse the SQL type declaration for further details
        $struct = self::parseSQLType( $this->sql_type );
        $this->type       = $struct[ 'type' ];
        $this->limits     = $struct[ 'limits' ];
        $this->type_group = substr( $struct[ 'type' ], 0, 1 );
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
    //$t = new Column();
}

