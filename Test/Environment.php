<?php

namespace hzphp\Test;


/**
 *  Test environment model used to allow communication between user test code
 *  and the execution environment.
 */
class Environment {


    const               HEADING    = 0x00;
    const               SECTION    = 0x01;
    const               SUBSECTION = 0x02;
    const               STEP       = 0x04;


    protected           $executor;
    protected           $resets;
    protected           $test;


    public function __construct(
        Executor $executor,
        Test $test
    ) {
        $this->executor = $executor;
        $this->test     = $test;
        $this->resets   = self::SECTION;
    }


    public function heading(
        $heading
    ) {
        $this->recordHeading( $heading, self::HEADING );
    }


    public function section(
        $heading
    ) {
        $this->recordHeading( $heading, self::SECTION );
    }


    public function subSection(
        $heading
    ) {
        $this->recordHeading( $heading, self::SUBSECTION );
    }


    public function step(
        $heading
    ) {
        $this->recordHeading( $heading, self::STEP );
    }


    public function record(
        $message
    ) {
        //// ZIH
    }



    protected recordHeading(
        $heading,
        $level
    ) {
        //// ZIH
        if( ( $this->resets & $level ) == $level ) {
            $this->test->reset();
        }
    }

}

?>