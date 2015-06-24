<?php
/****************************************************************************

Unit Test for Text/SplitSteram Module
=====================================

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
class SplitStream extends hzphp\Test\UnitTest {

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


        $report->heading( 'Test Simple Subject' );

        $report->step( 'Create a sample input stream in memory.' );
        $sample = <<<EOS
abcdefg
hijklmnopqrs
tu
v
w
x
y
zyxwvutsrqponmlkjihgfedcba
EOS;
        $memory = fopen( 'php://memory', 'w+' );
        fwrite( $memory, $sample );
        fseek( $memory, 0, SEEK_SET );

        $report->step( 'Create a SplitStream instance for testing.' );
        $split_stream = new hzphp\Text\SplitStream( $memory );

        $report->step( 'Set a very small read chunk size for testing.' );
        $split_stream->chunk_size = 4;

        $report->step( 'Perform an `explode()` on the test sample.' );
        $parts = explode( "\n", $sample );


        $report->step( 'Iterate through the stream, extracting text.' );
        $index = 0;
        foreach( $split_stream as $string ) {

            //array index and infinite iterator protection
            if( $index >= count( $parts ) ) {
                $report->step( 'Too many text segments in stream.' );
                //ZIH - need domain verifiers
                $verify->bool( false, ( $index >= count( $parts ) ) );
                break;
            }

            //test extracted text against exploded text
            else {

                $report->step( 'Verify extracted string matches sample.' );
                $verify->string( $parts[ $index ], $string );
                $report->step( 'Verify extracted length matches sample.' );
                $length = strlen( $string );
                $verify->int( strlen( $parts[ $index ] ), $length );
                //print_r( $split_stream->stats() );
            }

            //advance parts array index
            $index += 1;
        }

        fclose( $memory );

        $report->heading( 'Test Subject with Successive Separators' );

        $memory = fopen( 'php://memory', 'w+' );
        $sample = "\na\n\nb\n\n\nc\n";
        $parts  = explode( "\n", $sample );
        $index  = 0;
        fwrite( $memory, $sample );
        fseek( $memory, 0, SEEK_SET );
        $ss = new hzphp\Text\SplitStream( $memory );
        foreach( $ss as $string ) {
            if( $index >= count( $parts ) ) {
                break;
            }
            $verify->string( $parts[ $index ], $string );
            $index += 1;
        }
        fclose( $memory );

        $report->heading( 'Test Subject with No Separators' );

        $memory = fopen( 'php://memory', 'w+' );
        $sample = "abc";
        $parts  = explode( "\n", $sample );
        $index  = 0;
        fwrite( $memory, $sample );
        fseek( $memory, 0, SEEK_SET );
        $ss = new hzphp\Text\SplitStream( $memory );
        foreach( $ss as $string ) {
            if( $index >= count( $parts ) ) {
                break;
            }
            $verify->string( $parts[ $index ], $string );
            $index += 1;
        }
        fclose( $memory );

        $report->heading( 'Test Subject with Long Separators' );

        $memory = fopen( 'php://memory', 'w+' );
        $sample = "abczzzdzzzefgzzzhij";
        $parts  = explode( "zzz", $sample );
        $index  = 0;
        fwrite( $memory, $sample );
        fseek( $memory, 0, SEEK_SET );
        $ss = new hzphp\Text\SplitStream( $memory, 'zzz' );
        foreach( $ss as $string ) {
            if( $index >= count( $parts ) ) {
                break;
            }
            $verify->string( $parts[ $index ], $string );
            $index += 1;
        }
        fclose( $memory );

        $report->heading( 'Test Correct Offset Reporting' );

        $memory  = fopen( 'php://memory', 'w+' );
        $sample  = 'zzzazbzczdefghijkzlmnop';
        $offsets = [ 0, 1, 2, 3, 5, 7, 9, 18 ];
        $parts   = explode( 'z', $sample );
        $index   = 0;
        fwrite( $memory, $sample );
        fseek( $memory, 0, SEEK_SET );
        $ss = new hzphp\Text\SplitStream( $memory, 'z' );
        foreach( $ss as $offset => $string ) {
            if( $index >= count( $parts ) ) {
                break;
            }
            $verify->string( $parts[ $index ], $string );
            $verify->int( $offsets[ $index ], $offset );
            $index += 1;
        }
        fclose( $memory );

    }

}


/*---------------------------------------------------------------------------
Functions
---------------------------------------------------------------------------*/

