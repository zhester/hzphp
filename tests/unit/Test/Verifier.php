<?php

/*===========================================================================
    Test Stubs
===========================================================================*/


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
    public function _reset() {
        $this->report->_reset();
    }
}


/*===========================================================================
    Test Script
===========================================================================*/


/**
 * Test script container class
 *
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

        /*=================================================================*/
        $report->heading( 'Check boolean verification.' );

        //the report generates these tables internally.  this is the data that
        //is expected to be populated when the verification system is used.
        $data = [
            [ 'bool',     'Value', 'Type' ],
            [ 'Expected', '',      ''     ],
            [ 'Actual',   '',      ''     ]
        ];

        //the report keeps an overall result of the test
        $result = null;

        //shortcut references to the expected values, and how the report was
        //called when the test is executed
        $expt  = [ 'table',  [ &$data   ] ];
        $expr  = [ 'result', [ &$result ] ];
        $calls = &$exec->report->calls;

        $report->step( 'Reset test stubs\' internal state.' );
        $exec->_reset();

        $report->step( 'Request verification from the verifier under test.' );
        $report->step( 'Compare boolean true with boolean true.' );
        $verifier->bool( true, true );

        //modify the report table according the expected results
        $data[ 1 ][ 1 ] = 'true';
        $data[ 1 ][ 2 ] = 'boolean';
        $data[ 2 ][ 1 ] = 'true';
        $data[ 2 ][ 2 ] = 'boolean';
        $result         = true;

        //verify the verifier under test produced the expected outputs
        $verify->varray( $expt, $calls[ 0 ] );
        $verify->varray( $expr, $calls[ 1 ] );

        //the following repeats the previous procedure with more automation
        //  0: test step description
        //  1: inputs to method under test
        //  2: expected result from stub report
        //  3: expected values in output table
        $test_cases = [
            [
                'Compare boolean true with boolean false.',
                [ true, false ],
                false,
                [ 'true', 'boolean', 'false', 'boolean' ]
            ],
            [
                'Compare boolean true with boolean false.',
                [ false, false ],
                true,
                [ 'false', 'boolean', 'false', 'boolean' ]
            ],
            [
                'Compare boolean true with an integer.',
                [ true, 1 ],
                false,
                [ 'true', 'boolean', '1', 'integer' ]
            ],
            [
                'Compare boolean false with an integer.',
                [ false, 0 ],
                false, [ 'false', 'boolean', '0', 'integer' ]
            ],
            [
                'Compare boolean true with a float.',
                [ true, 1.1 ],
                false, [ 'true', 'boolean', '1.100000', 'float' ]
            ],
            [
                'Compare boolean false with a float.',
                [ false, 0.0 ],
                false, [ 'false', 'boolean', '0.000000', 'float' ]
            ],
            [
                'Compare boolean true with a string.',
                [ true, 'text' ],
                false,
                [ 'true', 'boolean', 'text', 'string' ]
            ],
            [
                'Compare boolean false with a string.',
                [ false, 'text' ],
                false,
                [ 'false', 'boolean', 'text', 'string' ]
            ],
            [
                'Compare boolean true with null.',
                [ true, null ],
                false,
                [ 'true', 'boolean', '', 'NULL' ]
            ],
            [
                'Compare boolean false with null.',
                [ false, null ],
                false,
                [ 'false', 'boolean', '', 'NULL' ]
            ],
        ];
        foreach( $test_cases as $case ) {
            $report->step( 'Reset test stubs\' internal state.' );
            $exec->_reset();
            $report->step( $case[ 0 ] );
            call_user_func_array( [ $verifier, 'bool' ], $case[ 1 ] );
            $result         = $case[ 2 ];
            $data[ 1 ][ 1 ] = $case[ 3 ][ 0 ];
            $data[ 1 ][ 2 ] = $case[ 3 ][ 1 ];
            $data[ 2 ][ 1 ] = $case[ 3 ][ 2 ];
            $data[ 2 ][ 2 ] = $case[ 3 ][ 3 ];
            $verify->varray( $expt, $calls[ 0 ] );
            $verify->varray( $expr, $calls[ 1 ] );
        }

        /*=================================================================*/
        $report->heading( 'Check float verification.' );
        $default_tolerance = 0.001;
        $dtol = sprintf( '%.6f', $default_tolerance );
        $data = [
            [ 'float',     'Value',    'Type'  ],
            [ 'Expected',  '0.000000', 'float' ],
            [ 'Tolerance', $dtol,      'float' ],
            [ 'Upper',     $dtol,      'float' ],
            [ 'Lower',     "-$dtol",   'float' ],
            [ 'Actual',    '',         'float' ]
        ];
        $test_cases = [
            [
                'Compare 0.0 to 0.0.',
                [ 0.0, 0.0, $default_tolerance ],
                true,
                [
                    [ '0.000000', 'float' ],
                    [ $dtol,      'float' ],
                    [ $dtol,      'float' ],
                    [ "-$dtol",   'float' ],
                    [ '0.000000', 'float' ],
                ]
            ],
            [
                'Compare 0.0 to 0.1.',
                [ 0.0, 0.1, $default_tolerance ],
                false,
                [
                    [ '0.000000', 'float' ],
                    [ $dtol,      'float' ],
                    [ $dtol,      'float' ],
                    [ "-$dtol",   'float' ],
                    [ '0.100000', 'float' ],
                ],
            ],
            [
                'Compare 0.0 to 0.0005.',
                [ 0.0, 0.0005, $default_tolerance ],
                true,
                [
                    [ '0.000000', 'float' ],
                    [ $dtol,      'float' ],
                    [ $dtol,      'float' ],
                    [ "-$dtol",   'float' ],
                    [ '0.000500', 'float' ],
                ],
            ],
            [
                'Compare 0.0 to 0.0005 with a tolerance of 0.00001.',
                [ 0.0, 0.0005, 0.00001 ],
                false,
                [
                    [ '0.000000',  'float' ],
                    [ '0.000010',  'float' ],
                    [ '0.000010',  'float' ],
                    [ '-0.000010', 'float' ],
                    [ '0.000500',  'float' ],
                ],
            ],
            [
                'Compare 0.0 to 1.0.',
                [ 0.0, 1.0, $default_tolerance ],
                false,
                [
                    [ '0.000000', 'float' ],
                    [ $dtol,      'float' ],
                    [ $dtol,      'float' ],
                    [ "-$dtol",   'float' ],
                    [ '1.000000', 'float' ],
                ],
            ],
            [
                'Compare 1.0 to 1.0.',
                [ 1.0, 1.0, $default_tolerance ],
                true,
                [
                    [ '1.000000', 'float' ],
                    [ $dtol,      'float' ],
                    [ '1.001000', 'float' ],
                    [ '0.999000', 'float' ],
                    [ '1.000000', 'float' ],
                ],
            ],
            [
                'Compare 0.0 to an integer of 0.',
                [ 0.0, 0, $default_tolerance ],
                false,
                [
                    [ '0.000000',  'float'   ],
                    [ $dtol,       'float'   ],
                    [ '0.001000',  'float'   ],
                    [ '-0.001000', 'float'   ],
                    [ '0',         'integer' ],
                ],
            ],
            [
                'Compare 1.0 to an integer of 1.',
                [ 1.0, 1, $default_tolerance ],
                false,
                [
                    [ '1.000000', 'float'   ],
                    [ $dtol,      'float'   ],
                    [ '1.001000', 'float'   ],
                    [ '0.999000', 'float'   ],
                    [ '1',        'integer' ],
                ],
            ],
            [
                'Compare 0.0 to null.',
                [ 0.0, null, $default_tolerance ],
                false,
                [
                    [ '0.000000',  'float' ],
                    [ $dtol,       'float' ],
                    [ '0.001000',  'float' ],
                    [ '-0.001000', 'float' ],
                    [ '',          'NULL'  ],
                ],
            ],
            [
                'Compare 0.0 to boolean false.',
                [ 0.0, false, $default_tolerance ],
                false,
                [
                    [ '0.000000',  'float'   ],
                    [ $dtol,       'float'   ],
                    [ '0.001000',  'float'   ],
                    [ '-0.001000', 'float'   ],
                    [ 'false',     'boolean' ],
                ],
            ],
        ];
        foreach( $test_cases as $case ) {
            $report->step( 'Reset test stubs\' internal state.' );
            $exec->_reset();
            $report->step( $case[ 0 ] );
            call_user_func_array( [ $verifier, 'float' ], $case[ 1 ] );
            $result = $case[ 2 ];
            $table  = $case[ 3 ];
            $number = count( $table );
            for( $i = 0; $i < $number; ++$i ) {
                $data[ $i + 1 ][ 1 ] = $table[ $i ][ 0 ];
                $data[ $i + 1 ][ 2 ] = $table[ $i ][ 1 ];
            }
            $verify->varray( $expt, $calls[ 0 ] );
            $verify->varray( $expr, $calls[ 1 ] );
        }


        /*=================================================================*/
        $report->heading( 'Check int verification.' );
        $data = [
            [ 'int',      'Value', 'Type'    ],
            [ 'Expected', '0',     'integer' ],
            [ 'Actual',   '0',     'integer' ]
        ];
        $test_cases = [
            [
                'Compare 0 to 0.',
                [ 0, 0 ],
                true,
                [ [ '0', 'integer' ], [ '0', 'integer' ] ]
            ],
            [
                'Compare 0 to 1.',
                [ 0, 1 ],
                false,
                [ [ '0', 'integer' ], [ '1', 'integer' ] ]
            ],
            [
                'Compare 1 to 1.',
                [ 1, 1 ],
                true,
                [ [ '1', 'integer' ], [ '1', 'integer' ] ]
            ],
        ];
        foreach( $test_cases as $case ) {
            $report->step( 'Reset test stubs\' internal state.' );
            $exec->_reset();
            $report->step( $case[ 0 ] );
            call_user_func_array( [ $verifier, 'int' ], $case[ 1 ] );
            $result = $case[ 2 ];
            $table  = $case[ 3 ];
            $number = count( $table );
            for( $i = 0; $i < $number; ++$i ) {
                $data[ $i + 1 ][ 1 ] = $table[ $i ][ 0 ];
                $data[ $i + 1 ][ 2 ] = $table[ $i ][ 1 ];
            }
            $verify->varray( $expt, $calls[ 0 ] );
            $verify->varray( $expr, $calls[ 1 ] );
        }


        /*=================================================================*/
        $report->heading( 'Check string verification.' );
        $data = [
            [ 'string',   'Value', 'Type'   ],
            [ 'Expected', '',      'string' ],
            [ 'Actual',   '',      'string' ]
        ];
        $test_cases = [
            [
                'Compare "" to "".',
                [ "", "" ],
                true,
                [ [ '', 'string' ], [ '', 'string' ] ]
            ],
            [
                'Compare "" to " ".',
                [ "", " " ],
                false,
                [ [ '', 'string' ], [ ' ', 'string' ] ]
            ],
            [
                'Compare " " to " ".',
                [ " ", " " ],
                true,
                [ [ ' ', 'string' ], [ ' ', 'string' ] ]
            ],
            [
                'Compare "hello" to "hello".',
                [ "hello", "hello" ],
                true,
                [ [ 'hello', 'string' ], [ 'hello', 'string' ] ]
            ],
            [
                'Compare "" to boolean false.',
                [ "", false ],
                false,
                [ [ '', 'string' ], [ 'false', 'boolean' ] ]
            ],
            [
                'Compare "" to null.',
                [ "", null ],
                false,
                [ [ '', 'string' ], [ '', 'NULL' ] ]
            ],
        ];
        foreach( $test_cases as $case ) {
            $report->step( 'Reset test stubs\' internal state.' );
            $exec->_reset();
            $report->step( $case[ 0 ] );
            call_user_func_array( [ $verifier, 'string' ], $case[ 1 ] );
            $result = $case[ 2 ];
            $table  = $case[ 3 ];
            $number = count( $table );
            for( $i = 0; $i < $number; ++$i ) {
                $data[ $i + 1 ][ 1 ] = $table[ $i ][ 0 ];
                $data[ $i + 1 ][ 2 ] = $table[ $i ][ 1 ];
            }
            $verify->varray( $expt, $calls[ 0 ] );
            $verify->varray( $expr, $calls[ 1 ] );
        }

        /*=================================================================*/
        $report->heading( 'Check varray verification.' );
        $data = [
            [ 'array',    'Value', 'Type'  ],
            [ 'Expected', '',      'array' ],
            [ 'Actual',   '',      'array' ]
        ];
        $test_cases = [
            [
                'Compare [] to [].',
                [ [], [] ],
                true,
                [ [ '', 'array' ], [ '', 'array' ] ]
            ],
            [
                'Compare [] to [ 0 ].',
                [ [], [ 0 ] ],
                false,
                [ [ '', 'array' ], [ '0', 'array' ] ]
            ],
            [
                'Compare [ 0 ] to [ 1 ].',
                [ [ 0 ], [ 1 ] ],
                false,
                [ [ '0', 'array' ], [ '1', 'array' ] ]
            ],
            [
                'Compare [ 0, 0 ] to [ 0 ].',
                [ [ 0, 0 ], [ 0 ] ],
                false,
                [ [ '0,0', 'array' ], [ '0', 'array' ] ]
            ],
            [
                'Compare [ 0, 1 ] to [ 1, 0 ].',
                [ [ 0, 1 ], [ 1, 0 ] ],
                false,
                [ [ '0,1', 'array' ], [ '1,0', 'array' ] ]
            ],
        ];
        foreach( $test_cases as $case ) {
            $report->step( 'Reset test stubs\' internal state.' );
            $exec->_reset();
            $report->step( $case[ 0 ] );
            call_user_func_array( [ $verifier, 'varray' ], $case[ 1 ] );
            $result = $case[ 2 ];
            $table  = $case[ 3 ];
            $number = count( $table );
            for( $i = 0; $i < $number; ++$i ) {
                $data[ $i + 1 ][ 1 ] = $table[ $i ][ 0 ];
                $data[ $i + 1 ][ 2 ] = $table[ $i ][ 1 ];
            }
            $verify->varray( $expt, $calls[ 0 ] );
            $verify->varray( $expr, $calls[ 1 ] );
        }

    }


}

