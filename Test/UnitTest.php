<?php

namespace hzphp\Test;


/**
 *  Unit testing subclass should be inherited by all user-defined unit tests
 */
class UnitTest extends Test {


    public function reset(
        Environment $env
    ) {

        //called whenever it appears the test state needs to be reset
        //override to insert typical state resets

    }


}

?>