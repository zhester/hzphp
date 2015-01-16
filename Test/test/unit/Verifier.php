<?php

/*============================================================================
    Test Stubs
============================================================================*/


class StubReport {
    public              $calls = [];
    public function __call( $name, $args ) {
        $this->calls[] = [ $name, $args ];
    }
    public function _reset() {
        $this->calls = [];
    }
}


class StubExecutor extends hzphp\Test\Executor {
    public              $report;
    public function __construct() {
        $this->report = new StubReport();
    }
    public function signalResult( $result ) {
    }
}


/*============================================================================
    Test Script
============================================================================*/


/**
 *  Test script container class
 */
class Verifier extends hzphp\Test\UnitTest {


    /**
     *  Run the test script
     *
     *  @param report   The test report generator
     *  @param verify   The test verification system
     */
    public function run(
        hzphp\Test\Report $report,
        hzphp\Test\Verifier $verify
    ) {

        $report->heading( 'Test Setup' );

        $report->step( 'Create a stub Executor object.' );
        $exec = new StubExecutor();

        $report->step( 'Create a Verifier object to test.' );
        $verifier = new hzphp\Test\Verifier( $exec );

        $report->heading( 'Check boolean verification.' );

        $data = [
            [ 'bool',     'Value', 'Type' ],
            [ 'Expected', '',      ''     ],
            [ 'Actual',   '',      ''     ]
        ];

        $verifier->bool( true, true );
        $data[ 1 ][ 1 ] = 'true';
        $data[ 1 ][ 2 ] = 'boolean';
        $data[ 2 ][ 1 ] = 'true';
        $data[ 2 ][ 2 ] = 'boolean';
        $verify->varray(
            [ 'table', array( $data ) ],
            $exec->report->calls[ 0 ]
        );
        //ZIH a $verify->call() method could be handy to short-hand this


        $verifier->bool( true,  false  );
        $verifier->bool( false, true   );
        $verifier->bool( false, false  );
        $verifier->bool( true,  1      );
        $verifier->bool( false, 0      );
        $verifier->bool( true,  1.1    );
        $verifier->bool( false, 0.0    );
        $verifier->bool( true,  'text' );
        $verifier->bool( false, 'text' );
        $verifier->bool( true,  null   );
        $verifier->bool( false, null   );

        //ZIH - finish test script

    }


}

