<?php

namespace hzphp\DBI;


/**
 *  Defines a common interface for any records-based source of data.
 *
 */
interface Source {


    /**
     *  Retrieves the current record as an associative array, and advances the
     *  internal pointer to the next record.
     *
     *  @return         Contents of the current record as an associative array
     */
    public function fetch_assoc();


    /**
     *  Retrieves an array of objects describing each field in the record set.
     *
     *  See: http://www.php.net/manual/en/mysqli-result.fetch-fields.php
     *
     *  @return         Contents of the current record as an associative array
     */
    public function fetch_fields();


    /**
     *  Retrieves the current record as a numeric array, and advances the
     *  internal pointer to the next record.
     *
     *  @return         Contents of current record as a numeric array
     */
    public function fetch_row();


    /**
     *  Returns the number of fields in the current record set.
     *
     *  @return         The number of fields in the set
     */
    public function get_num_fields();


    /**
     *  Returns the number of records in the current record set.
     *
     *  @return         The number of records in the set
     */
    public function get_num_records();


    /**
     *  Seeks to the specified record by 0-based offset.
     *
     *  @param offset   The offset into the current record set
     *  @return         True on success, false on failure
     */
    public function data_seek(
        $offset
    );


}


?>