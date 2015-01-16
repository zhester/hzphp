<?php

namespace hzphp\Test;


/**
 *  Test report generation interface for test scripts and verification
 */
class Report {


    const               HEADING      = 0;
    const               SECTION      = 1;
    const               SUBSECTION   = 2;
    const               STEP         = 3;
    const               NUM_HEADINGS = 4;


    protected           $auto_resets;
    protected           $executor;
    protected           $logger;


    private             $headings;


    /**
     *  Constructs a Report instance
     *
     *  @param executor The test executor object
     *  @param logger   The logger for this test execution
     */
    public function __construct(
        Executor $executor,
        Logger $logger = null
    ) {

        $this->executor    = $executor;
        $this->logger      = $logger;
        $this->auto_resets = 1 << self::SECTION;
        $this->headings    = array_pad( [], self::NUM_HEADINGS, 0 );

    }


    /**
     *  Writes a top-level heading in the report
     *
     *  @param heading  The heading text
     */
    public function heading(
        $heading
    ) {

        $this->recordHeading( self::HEADING, $heading );

    }


    /**
     *  Writes a result (passed/failed) in the report
     *
     *  @param result   true == passed, false == failed
     */
    public function result(
        $result
    ) {

        $this->logger->result( $result );

    }


    /**
     *  Writes a section heading in the report
     *
     *  @param section  The section heading text
     */
    public function section(
        $section
    ) {

        $this->recordHeading( self::SECTION, $section );

    }


    /**
     *  Sets the logging facility for this report
     *
     *  @param logger   The Logger object to use for formatted output
     */
    public function setLogger(
        Logger $logger
    ) {

        $this->logger = $logger;

    }


    /**
     *  Writes a test step in the report
     *
     *  @param step     The test step description
     */
    public function step(
        $step
    ) {

        $this->recordHeading( self::STEP, $step );

    }


    /**
     *  Writes a sub-section heading in the report
     *
     *  @param subsection
     *                  The sub-section heading text
     */
    public function subSection(
        $subsection
    ) {

        $this->recordHeading( self::SUBSECTION, $subsection );

    }


    /**
     *  Writes a table of information in the report
     *
     *  @param data     An array of arrays of information to display
     */
    public function table(
        $data
    ) {

        $this->logger->table( $data );

    }


    /**
     *  Writes plain, unformatted text in the report
     *
     *  @param text     The text to write
     */
    public function text(
        $text
    ) {

        $this->logger->text( $text );

    }



    /**
     *  Records a qualified heading in the report
     *
     *  @param level    The heading level (int, 0==highest)
     *  @param heading  The heading text to record
     */
    protected function recordHeading(
        $level,
        $heading
    ) {

        //see if this heading level is valid
        if( $level < self::NUM_HEADINGS ) {

            //reset heading counts for all lower heading levels
            for( $i = ( $level + 1 ); $i < self::NUM_HEADINGS; ++$i ) {
                $this->headings[ $i ] = 0;
            }

            //increment this heading count
            $this->headings[ $level ] += 1;

            //set up a "path" index for this heading
            $path = implode(
                '.',
                array_slice( $this->headings, 0, ( $level + 1 ) )
            );

            //prepend the heading count to build the display text
            $heading = $path . '. ' . $heading;

            //log the heading
            $this->logger->log( $level, $heading );

            //set the bit for this heading level
            $bit = 1 << $level;

            //check to see if this heading level auto-resets the test state
            if( ( $this->auto_resets & $bit ) == $bit ) {

                //auto-reset the test state
                $this->executor->resetTest();
            }

        }

    }

}

