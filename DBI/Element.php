<?php

namespace hzphp\DBI;


/**
 *  Data Element Model
 *
 *  Represents a single data element (record) from a data source.
 */
class Element implements ArrayAccess {


    protected           $m_keys;    //list of field keys in the element
    protected           $m_fields;  //list of all known fields in the element
    protected           $m_source;  //the originating data source


    /**
     *  Constructor
     *
     *  @param record   An associative array describing the new element
     */
    public function __construct(
        Source $source,
        Array $record
    ) {
        $this->m_keys   = array_keys( $record );
        $this->m_fields = null;
        $this->m_source = $source;
        foreach( $record as $key => $value ) {
            $this->$key = $value;
        }
    }


    /**
     *  Check if the requested field offset exists in the element.
     *
     *  @param offset   The offset (key) to test
     *  @return         True if the offset exists, false if not
     */
    public function offsetExists(
        $offset
    ) {
        return in_array( $offset, $this->m_keys );
    }


    /**
     *  Get the field value at the specified offset in the element.
     *
     *  @param offset   The offset (key) to retrieve
     *  @return         The value of the specified field
     */
    public function offsetGet(
        $offset
    ) {

        if( $this->m_fields === null ) {
            $this->m_fields = $this->m_source->fetch_fields();
        }

        $index = array_search( $offset, $this->m_keys );
        if( $index === false ) {
            throw new \Exception( 'Invalid offset in element: ' . $offset );
        }

        $type = $this->m_fields[ $index ]->type;

/*
    source: http://php.net/manual/en/mysqli.constants.php

    flags
        MYSQLI_NOT_NULL_FLAG       = 1
        MYSQLI_PRI_KEY_FLAG        = 2
        MYSQLI_UNIQUE_KEY_FLAG     = 4
        MYSQLI_BLOB_FLAG           = 16
        MYSQLI_UNSIGNED_FLAG       = 32
        MYSQLI_ZEROFILL_FLAG       = 64
        MYSQLI_BINARY_FLAG         = 128
        MYSQLI_ENUM_FLAG           = 256
        MYSQLI_AUTO_INCREMENT_FLAG = 512
        MYSQLI_TIMESTAMP_FLAG      = 1024
        MYSQLI_SET_FLAG            = 2048
        MYSQLI_NUM_FLAG            = 32768
        MYSQLI_PART_KEY_FLAG       = 16384
        MYSQLI_GROUP_FLAG          = 32768
        MYSQLI_UNIQUE_FLAG         = 65536

    types
        MYSQLI_TYPE_DECIMAL = 0
            DECIMAL
        MYSQLI_TYPE_NEWDECIMAL = 246
            DECIMAL or NUMERIC
        MYSQLI_TYPE_BIT = 16
            BIT
        MYSQLI_TYPE_TINY = 1
            TINYINT
        MYSQLI_TYPE_SHORT = 2
            SMALLINT
        MYSQLI_TYPE_LONG = 3
            INT
        MYSQLI_TYPE_FLOAT = 4
            FLOAT
        MYSQLI_TYPE_DOUBLE = 5
            DOUBLE
        MYSQLI_TYPE_NULL
            DEFAULT NULL
        MYSQLI_TYPE_TIMESTAMP = 7
            TIMESTAMP
        MYSQLI_TYPE_LONGLONG = 8
            BIGINT
        MYSQLI_TYPE_INT24 = 9
            MEDIUMINT
        MYSQLI_TYPE_DATE = 10
            DATE
        MYSQLI_TYPE_TIME = 11
            TIME
        MYSQLI_TYPE_DATETIME = 12
            DATETIME
        MYSQLI_TYPE_YEAR = 13
            YEAR
        MYSQLI_TYPE_NEWDATE = 14
            DATE
        MYSQLI_TYPE_INTERVAL
            INTERVAL
        MYSQLI_TYPE_ENUM = 247
            ENUM
        MYSQLI_TYPE_SET = 248
            SET
        MYSQLI_TYPE_TINY_BLOB = 249
            TINYBLOB
        MYSQLI_TYPE_MEDIUM_BLOB = 250
            MEDIUMBLOB
        MYSQLI_TYPE_LONG_BLOB = 251
            LONGBLOB
        MYSQLI_TYPE_BLOB = 252
            BLOB or TEXT
        MYSQLI_TYPE_VAR_STRING = 253
            VARCHAR
        MYSQLI_TYPE_STRING = 254
            CHAR or BINARY
        MYSQLI_TYPE_CHAR
            TINYINT. For CHAR, see MYSQLI_TYPE_STRING
        MYSQLI_TYPE_GEOMETRY = 255
            GEOMETRY
*/

        switch( $type ) {
            case MYSQLI_TYPE_TINY:
            case MYSQLI_TYPE_SHORT:
            case MYSQLI_TYPE_LONG:
            case MYSQLI_TYPE_LONGLONG:
            case MYSQLI_TYPE_INT24:
                return intval( $this->$offset );
                break;
            case MYSQLI_TYPE_DECIMAL:
            case MYSQLI_TYPE_NEWDECIMAL:
            case MYSQLI_TYPE_FLOAT:
            case MYSQLI_TYPE_DOUBLE:
                return floatval( $this->$offset );
                break;
            case MYSQLI_TYPE_DATE:
            case MYSQLI_TYPE_TIME:
            case MYSQLI_TYPE_DATETIME:
                //ZIH - convert to integer seconds here
                //break;
            default:
                return $this->$offset;
                break;
        }

    }


    /**
     *  Set the field value at the specified offset in the element.
     *
     *  @param offset   The offset (key) to change
     *  @param value    The new value of the field
     */
    public function offsetSet(
        $offset,
        $value
    ) {

        if( $this->m_fields === null ) {
            $this->m_fields = $this->m_source->fetch_fields();
        }

        //ZIH - check type before casting to string

        $this->$offset = strval( $value );
    }


    /**
     *  Clear a field value at the specified offset in the element.
     *
     *  Note: This method is implemented to support the ArrayAccess interface.
     *  Like the goggles, it does nothing.
     *
     *  @param offset   The offset (key) to clear
     */
    public function offsetUnset(
        $offset
    ) {
        //unset( $this->$offset );
    }


}


?>
