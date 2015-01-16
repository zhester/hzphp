<?php

namespace hzphp\Test;


/**
 *  Test framework base class for all types of test jigs.
 */
abstract class Test {


    /**
     *  Reset the test's internal state
     */
    abstract public function reset();


    /**
     *  Run the test script
     *
     *  @param report   The test report generator
     *  @param verify   The test verification system
     */
    abstract public function run(
        Report $report,
        Verifier $verify
    );

}

