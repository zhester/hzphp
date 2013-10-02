<?php

namespace hzphp\Test;


/**
 *  Unit testing subclass should be inherited by all user-defined unit tests
 */
abstract class UnitTest extends Test {


    /**
     *  Reset the test's internal state
     */
    public function reset() {

        //called whenever it appears the test state needs to be reset
        //override to insert typical state resets

    }

}

?>