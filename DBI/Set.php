<?php

namespace hzphp\DBI;


/**
 *  Data Set Model
 *
 *  Represents a collection of data elements.
 */
abstract class Set implements Countable, Iterator {


    protected           $m_number = 0;
                                    //the number of records in the set
    protected           $m_index  = 0;
                                    //current record index
    protected           $m_record;  //current record object
    protected           $m_source;  //Source instance


    /**
     *  Constructor
     *
     *  @param source   Source instance to use for records
     */
    public function __construct(
        Source $source
    ) {
        $this->m_source = $source;
        $this->m_number = $this->m_source->get_num_records();
    }


    /**
     *  Creates a new element object in the set.
     *
     *  @param source   The originating data source object
     *  @param record   The element record's associative array
     *  @return         A new Element instance
     */
    abstract public function createElement(
        Source $source,
        Array $record
    );


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


?>