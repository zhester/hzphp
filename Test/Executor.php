<?php

namespace hzphp\Test;


/**
 *  Test executor allows a test to be executed.
 */
class Executor {


    const               HTML      = 0x00;
    const               PLAIN     = 0x01;
    const               LOG       = 0x02;
    const               LOG_HTML  = 0x02;
    const               LOG_PLAIN = 0x03;
    const               NONE      = 0x80;


    public              $abort_on_fail = true;
    public              $report = null;


    protected           $output;
    protected           $output_argument;
    protected           $result = true;
    protected           $tests;


    private             $abort_next = false;
    private             $current_test = null;


    /**
     *  Constructs an Executor object
     *
     *  @param tests    Optional array of Test objects to test
     *  @param output   Optional output configuration flags
     *  @param argument Optional output argument
     */
    public function __construct(
        $tests    = false,
        $output   = false,
        $argument = false
    ) {

        if( $tests === false ) {
            $this->tests = [];
        }
        else {
            $this->tests = $tests;
        }

        $this->setOutput( $output, $argument );
        $this->report = new Report( $this );

    }


    /**
     *  Adds a Test object to the list of tests to test
     *
     *  @param test     The Test object to add
     */
    public function addTest(
        Test $test
    ) {

        $this->tests[] = $test;

    }


    /**
     *  Resets the Executor's internal state for a test re-run
     */
    public function reset() {

        $this->abort_next = false;
        $this->result     = true;

    }


    /**
     *  Resets the current Test object's state
     */
    public function resetTest() {

        $this->current_test->reset();

    }


    /**
     *  Runs all tests in the queue
     *
     *  @return         True if all tests passed, false if one or more failed
     */
    public function runTests() {

        $this->reset();

        $this->report->setLogger( $this->makeLogger() );

        $verifier = new Verifier( $this );

        foreach( $this->tests as $this->current_test ) {
            $this->current_test->run(
                $this->report,
                $verifier
            );
            if( $this->abort_next == true ) {
                break;
            }
        }

        return $this->result;
    }


    /**
     *  Configure the test output
     *
     *  @param output   Output configuration flags
     *  @param argument Optional output argument (usually a file name)
     */
    public function setOutput(
        $output   = false,
        $argument = false
    ) {

        if( $output === false ) {
            $this->output = self::HTML;
        }
        else {
            $this->output = $output;
        }

        if( $argument === false ) {
            $this->output_argument = '';
        }
        else {
            $this->output_argument = $argument;
        }

    }


    /**
     *  Sends a result signal to the executor
     *
     *  @param result   The latest test step/verification result as a boolean
     *                  (true == pass, false == fail)
     */
    public function signalResult(
        $result
    ) {

        if( $result == false ) {
            if( $this->abort_on_fail == true ) {
                $this->abort_next = true;
            }
            $this->result = false;
        }

    }



    /**
     *  Constructs the appropriate logging facility given the execution
     *  environment's current settings
     *
     *  @return         A suitable Logger instance
     */
    protected function makeLogger() {

        if( ( $this->output & self::LOG ) == self::LOG ) {
            $log_file = $this->output_argument;
        }
        else {
            $log_file = 'php://output';
        }

        if( ( $this->output & self::NONE ) == self::NONE ) {
            $logger = new NullLogger( $log_file );
        }
        else if( ( $this->output & self::PLAIN ) == self::PLAIN ) {
            $logger = new PlainLogger( $log_file );
        }
        else {
            $logger = new HTMLLogger( $log_file );
        }

        return $logger;
    }
}

?>