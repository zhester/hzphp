<?php
/****************************************************************************

Unit Test for IO/StreamIO Module
================================

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
class StreamIO extends hzphp\Test\UnitTest {

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

        $report->step( 'Open a file handle to memory.' );
        $file = fopen( 'php://memory', 'w+' );

        $report->step( 'Write some packed binary data to the file.' );
        $values = [ 42, 26, 12345, 8192, 16, 255, 32767 ];
        $data = call_user_func_array(
            'pack',
            array_merge( [ 'LLLLCCS' ], $values )
        );
        fwrite( $file, $data );
        fseek( $file, 0, SEEK_SET );

        $report->step( 'Create a `StreamIO` instance.' );
        $si = new hzphp\IO\StreamIO( $file );

        $report->step(
            '`extract()` a single field (advances the file position).'
        );
        $data = $si->extract( 4, 4 );
        $verify->int( 26, unpack( 'Lvalue', $data )[ 'value' ] );

        $report->step(
            'Rewind the file position to the previously-read field.'
        );
        $si->seek( -4, SEEK_CUR );
        $report->step(
            '`load()` a new value into the same position in the file.'
        );
        $si->load( 'L', [ 53 ] );

        $report->step( 'Read the modified field back.' );
        $si->seek( -4, SEEK_CUR );
        $data = $si->extract( 0, 4 );
        $verify->int( 53, unpack( 'Lvalue', $data )[ 'value' ] );

        $report->step(
            '`unload()` a number of fields, and convert to native types.'
        );
        $actual   = $si->unload( 'LLCCS' );
        $expected = array_slice( $values, 2 );
        for( $index = 0; $index < count( $expected ); ++$index ) {
            $verify->int( $expected[ $index ], $actual[ $index ] );
        }

        $report->step(
            'Replace the file\'s contents with a few lines of text.'
        );
        $content = "line 1\nline \\\\ 2\nline \\\n 3\nlast \\\t line\n";
        fseek( $file, 0, SEEK_SET );
        fwrite( $file, $content );
        fseek( $file, 0, SEEK_SET );

        $report->step( 'Extract lines using `readterm()`.' );
        //replace escaped newlines and escapes with their natural character
        $expected = preg_replace(
            '/\\\\(\\\\|\\n)/',
            '\\1',
            //split the sample subject across un-escaped newlines
            preg_split( '/(?<!\\\\)\\n/', $content )
        );
        $actual = [];
        $index  = 0;
        while( ( $line = $si->readterm() ) !== false ) {
            $verify->string( $expected[ $index ], $line );
            $index += 1;
        }

        fclose( $file );

        $report->heading( 'Evaluate PHP\'s `fseek()` for 64-bit Support' );

        $report->step( 'Check PHP\'s integer width.' );
        $verify->int( 8, PHP_INT_SIZE );

        $report->step( 'Open the `/dev/zero` file.' );
        $zero = fopen( '/dev/zero', 'rb' );

        $report->step( 'Verify file is at position 0.' );
        $verify->int( 0, ftell( $zero ) );

        $report->step( 'Seek to the 2GB boundary.' );
        $offset = ( 1024 * 1024 * 1024 * 2 ) - 1;
        fseek( $zero, $offset, SEEK_SET );

        $report->step( 'Verify we have reached 2G - 1.' );
        $verify->int( $offset, ftell( $zero ) );

        $report->step( 'Seek ahead one byte.' );
        fseek( $zero, 1, SEEK_CUR );

        $report->step( 'Verify we have reached 2G.' );
        $verify->int( $offset + 1, ftell( $zero ) );

        $report->step( 'Seek ahead one byte.' );
        fseek( $zero, 1, SEEK_CUR );

        $report->step( 'Verify we have reached 2G + 1.' );
        $verify->int( $offset + 2, ftell( $zero ) );

        fclose( $zero );

    }

}


/*---------------------------------------------------------------------------
Functions
---------------------------------------------------------------------------*/

