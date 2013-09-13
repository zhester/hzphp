<?php

namespace hzphp\Test;


/**
 *  Test case verification interface
 */
class Verifier {


    protected           $executor;


    /**
     *  Constructs a Verifier instance
     *
     *  @param executor Test executor instance
     */
    public function __construct(
        Executor $executor
    ) {

        $this->executor = $executor;

    }


    /**
     *  Verify a boolean value
     *
     *  @param expected The expected value
     *  @param actual   The actual value
     */
    public function bool(
        $expected,
        $actual
    ) {

        if( is_bool( $actual ) == false ) {
            $result = false;
        }
        else {
            $result = $expected === $actual;
        }

        $this->report( 'bool', $expected, $actual, $result );

        return $result;
    }


    /**
     *  Verify a float value
     *
     *  @param expected The expected value
     *  @param actual   The actual value
     */
    public function float(
        $expected,
        $actual,
        $tolerance = 0.001
    ) {

        $upper_limit = $expected + $tolerance;
        $lower_limit = $expected - $tolerance;

        if( is_float( $actual ) == false ) {
            $result = false;
        }
        else {
            if( $actual > $upper_limit ) {
                $result = false;
            }
            else if( $actual < $lower_limit ) {
                $result = false;
            }
            else {
                $result = true;
            }
        }

        $exp = $this->info( $expected    );
        $act = $this->info( $actual      );
        $tol = $this->info( $tolerance   );
        $upr = $this->info( $upper_limit );
        $lwr = $this->info( $lower_limit );

        $this->executor->report->table(
            array(
                array( 'float',     'Value',   'Type'    ),
                array( 'Expected',  $exp[ 0 ], $exp[ 1 ] ),
                array( 'Tolerance', $tol[ 0 ], $tol[ 1 ] ),
                array( 'Upper',     $upr[ 0 ], $upr[ 1 ] ),
                array( 'Lower',     $lwr[ 0 ], $lwr[ 1 ] ),
                array( 'Actual',    $act[ 0 ], $act[ 1 ] )
            )
        );

        $this->executor->report->result( $result );

        $this->executor->signalResult( $result );

        return $result;
    }


    /**
     *  Verify an integer value
     *
     *  @param expected The expected value
     *  @param actual   The actual value
     */
    public function int(
        $expected,
        $actual
    ) {

        if( is_int( $actual ) == false ) {
            $result = false;
        }
        else {
            $result = $expected === $actual;
        }

        $this->report( 'int', $expected, $actual, $result );

        return $result;
    }


    /**
     *  Verify a string value
     *
     *  @param expected The expected value
     *  @param actual   The actual value
     */
    public function string(
        $expected,
        $actual
    ) {

        if( is_string( $actual ) == false ) {
            $result = false;
        }
        else {
            $result = $expected === $actual;
        }

        $this->report( 'string', $expected, $actual, $result );

        return $result;
    }


    /**
     *  Verify arrays
     *
     *  @param expected The expected array
     *  @param actual   The actual array
     */
    public function varray(
        $expected,
        $actual
    ) {

        if( is_array( $actual ) == false ) {
            $result = false;
        }
        else {
            $result = $expected == $actual;
        }

        $this->report( 'array', $expected, $actual, $result );

        return $result;
    }



    /**
     *  Add to the report for a basic evaluation
     *
     *  @param type     The type of evaluation
     *  @param expected The expected value
     *  @param actual   The actual value
     *  @param result   The result of the evaluation
     */
    protected function report(
        $type,
        $expected,
        $actual,
        $result
    ) {

        $exp = $this->info( $expected );
        $act = $this->info( $actual   );

        $this->executor->report->table(
            array(
                array( $type,      'Value',   'Type'    ),
                array( 'Expected', $exp[ 0 ], $exp[ 1 ] ),
                array( 'Actual',   $act[ 0 ], $act[ 1 ] )
            )
        );

        $this->executor->report->result( $result );

        $this->executor->signalResult( $result );

    }


    /**
     *  Get some information about the variable passed
     *
     *  @param value    Value to query
     *  @return         An array of the following contents:
     *                      0: string representation of value
     *                      1: type name as a string
     */
    protected function info(
        $value
    ) {

        $type = gettype( $value );

        switch( $type ) {

            case 'boolean':
                $string = $value == true ? 'true' : 'false';
                break;

            case 'integer':
                $string = strval( $value );
                break;

            case 'double':
            case 'float':
                $string = sprintf( '%0.6f', $value );
                $type   = 'float';
                break;

            case 'string':
                $string = $value;
                break;

            case 'array':
                $string = $this->array2string( $value );
                break;

            default:
                $string = strval( $value );
                break;

        }

        return array( $string, $type );

    }


    /**
     *  Serializes an array into a string
     *
     *  @param array    The array to serialize
     *  @return         A string representation of the array
     */
    protected function array2string( $array ) {
        $strings = array();
        foreach( $array as $item ) {
            if( is_array( $item ) ) {
                $strings[] = '[' . $this->array2string( $item ) . ']';
            }
            else {
                $strings[] = strval( $item );
            }
        }
        return implode( ',', $strings );
    }


}

?>