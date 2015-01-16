<?php

namespace hzphp\DBI;


/**
 *  Data Set Model
 *
 *  Represents a collection of data elements.
 */
class Set implements Countable, Iterator {


    protected static    $dbi_schema = [];
                                    //specified by descendent classes to
                                    //  help communicate with the DBAL


    protected           $m_connection;
                                    //Connection instance
    protected           $m_number = false;
                                    //the number of records in the set
    protected           $m_index  = 0;
                                    //current record index
    protected           $m_record;  //current record object
    protected           $m_result = null;
                                    //when loading the set, this maintians the
                                    //  result instance


    /**
     *  Constructor
     *
     *  @param connection
     *                  Data connection instance
     */
    public function __construct(
        Connection $connection
    ) {
        $this->m_connection = $connection;
    }


    /**
     *  Creates a new element object in the set.
     *
     *  @param source   The originating data source object
     *  @param record   The element record's associative array
     *  @return         A new Element instance
     */
    public function createElement(
        Result $result,
        Array $record
    ) {
        //ZIH - may be unnecessary, but could be handy for overriding the
        //  default construction strategy
    }


    /**
     *  Returns the number of records in the set.
     *
     *  @return         The number of records in the set
     */
    public function count() {
        return $this->m_number;
    }


    /**
     *  Retrieves the current Element in the set.
     *
     *  @return         The current record's Element instance
     */
    public function current() {
        return $this->m_record;
    }


    /**
     *  Retrieves the current index in the set.
     *
     *  @return         The index in the set
     */
    public function key() {
        return $this->m_index;
    }


    /**
     *  Fetches the next record (as an Element object) in the set.
     *
     */
    public function next() {
        $this->m_index += 1;
        $this->m_record = $this->createElement(
            $this->m_source->fetch_assoc()
        );
    }


    /**
     *  Rewinds the internal record pointer into the set.
     *
     */
    public function rewind() {
        $this->m_index = 0;
        $this->m_source->data_seek( 0 );
    }


    /**
     *  Indicates if the next record in the set is a valid record (for bounds
     *  checking).
     *
     *  @return         True if there is a next record, false if none.
     */
    public function valid() {
        if( $this->m_index < $this->m_number ) {
            return true;
        }
        return false;
    }


}


