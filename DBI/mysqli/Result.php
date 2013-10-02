<?php

namespace hzphp\DBI\mysqli;


/**
 *  Implements the compatibility layer for mysqli data source objects.
 *
 */
class Result extends \mysqli_result implements \hzphp\DBI\Result {


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