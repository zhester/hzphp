<?php
/****************************************************************************

Unit Test for MODULE/CLASS Module
=================================

****************************************************************************/

/*---------------------------------------------------------------------------
Default Namespace
---------------------------------------------------------------------------*/

/*---------------------------------------------------------------------------
Dependencies
---------------------------------------------------------------------------*/

/*---------------------------------------------------------------------------
Classes
---------------------------------------------------------------------------*/

/**
 * Test Script Container Class
 *
 */
class CLASS extends hzphp\Test\UnitTest {

    /**
     * Runs the test script.
     *
     * @param report The test report generator
     * @param verify The test verification system
     */
    public function run(
        hzphp\Test\Report   $report,
        hzphp\Test\Verifier $verify
    ) {

        $report->heading( 'Test Setup' );

        $report->step( 'This placeholder step will fail.' );
        $verify->bool( true, false );

    }

}


/*---------------------------------------------------------------------------
Functions
---------------------------------------------------------------------------*/
