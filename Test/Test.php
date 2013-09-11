<?php

namespace hzphp\Test;


/**
 *  Test framework base class for all types of test jigs.
 */
abstract class Test {


    abstract public function reset(
        Environment $env
    );


    abstract public function run(
        Environment $env,
        Verifier $v
    );

}

?>