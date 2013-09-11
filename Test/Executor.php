<?php

namespace hzphp\Test;


/**
 *  Test executor allows a test to be executed.
 */
class Executor {


    const               HTML      = 0x00;
    const               PLAIN     = 0x01;
    const               LOG_HTML  = 0x02;
    const               LOG_PLAIN = 0x03;
    const               NONE      = 0x80;


    protected           $tests;
    protected           $output;
    protected           $output_argument;


    public function __construct(
        $tests    = false,
        $output   = false,
        $argument = false
    ) {
        if( $tests === false ) {
            $this->tests = array();
        }
        else {
            $this->tests = $tests;
        }
        $this->setOutput( $display, $argument );
    }


    public function addTest(
        Test $test
    ) {
        //// ZIH
    }


    public function runTests() {
        //// ZIH
    }


    public function setOutput( $output = false, $argument = false ) {
        if( $output === false ) {
            $this->output = self::HTML;
        }
        else {
            $this->output = $display;
        }
        if( $arg === false ) {
            $this->output_argument = '';
        }
        else {
            $this->output_argument = $argument;
        }
    }


}

?>