<?php

namespace hzphp\DBI;


/**
 *  Implements the compatibility layer for mysqli data source objects.
 *
 */
class Source_mysqli extends \mysqli_result implements Source {


    /**
     *  Returns the number of fields in the current record set.
     *
     *  @return         The number of fields in the set
     */
    public function get_num_fields() {
        return $this->field_count;
    }


    /**
     *  Returns the number of records in the current record set.
     *
     *  @return         The number of records in the set
     */
    public function get_num_records() {
        return $this->num_rows;
    }


}


?>