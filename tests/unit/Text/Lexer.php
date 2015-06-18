<?php
/****************************************************************************

Unit Test Script for Text/Lexer.php
===================================

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
 * Test script container class
 *
 */
class Lexer extends hzphp\Test\UnitTest {

    /**
     * Run the test script
     *
     * @param report The test report generator
     * @param verify The test verification system
     */
    public function run(
        hzphp\Test\Report   $report,
        hzphp\Test\Verifier $verify
    ) {

        $report->heading( 'Test Setup' );

        $report->step( 'Define a basic lexicon.' );
        $specs = [
            [ 'COMMENT',     '#[^\\n]*'                      ],
            [ 'STRING',      '"(?:[^"\\\\]|\\\\.)*"'         ],
            [ 'DECIMAL',     '\\d*\\.\\d*(?:[eE][+-]?\\d+)?' ],
            [ 'INTEGER',     '\\d+(?:[eE][+-]?\d+)?'         ],
            [ 'IDENTIFIER',  '[a-zA-Z_][a-zA-Z0-9_]*'        ],
            [ 'OPERATOR',    '[~!@#$%^&*=|\\\\:<>,.\\/?+-]+' ],
            [ 'TOKEN_ERROR', '\S+'                           ]
        ];

        $report->step( 'Construct a sample of the known lexicon.' );
        $sample = <<<EOS
# This is a sample string.
ident = "string"
number = 42
bobber = 3.14 + 2.345
EOS;

        //ident, value, offset, line, column
        $expected = [
            [ 'COMMENT',    '# This is a sample string.',  0, 1,  1 ],
            [ 'IDENTIFIER', 'ident',                      27, 2,  1 ],
            [ 'OPERATOR',   '=',                          33, 2,  7 ],
            [ 'STRING',     '"string"',                   35, 2,  9 ],
            [ 'IDENTIFIER', 'number',                     44, 3,  1 ],
            [ 'OPERATOR',   '=',                          51, 3,  8 ],
            [ 'INTEGER',    '42',                         53, 3, 10 ],
            [ 'IDENTIFIER', 'bobber',                     56, 4,  1 ],
            [ 'OPERATOR',   '=',                          63, 4,  8 ],
            [ 'DECIMAL',    '3.14',                       65, 4, 10 ],
            [ 'OPERATOR',   '+',                          70, 4, 15 ],
            [ 'DECIMAL',    '2.345',                      72, 4, 17 ],
        ];

        $report->step( 'Write the sample string to a memory stream.' );
        $stream = fopen( 'php://memory', 'w+' );
        fwrite( $stream, $sample );
        fseek( $stream, 0, SEEK_SET );

        $report->step( 'Create a lexer to parse the string from the stream.' );
        $lexer = new hzphp\Text\Lexer( $stream, $specs );

        $report->step( 'Scan the input stream for tokens.' );

        $test_index   = 0;
        $num_expected = count( $expected );
        foreach( $lexer as $token ) {
            if( $test_index >= $num_expected ) {
                $report->step( 'Lexer produced more tokens than expected.' );
                $verify->int( $num_expected, ( $test_index + 1 ) );
                break;
            }
            $report->step( 'Verify correct token identifier.' );
            $verify->string( $expected[ $test_index ][ 0 ], $token->ident );
            $report->step( 'Verify correct token value.' );
            $verify->string( $expected[ $test_index ][ 1 ], $token->value );
            $report->step( 'Verify correct byte offset in input.' );
            $verify->int(
                $expected[ $test_index ][ 2 ],
                $token->position->offset
            );
            $report->step( 'Verify correct line in input.' );
            $verify->int(
                $expected[ $test_index ][ 3 ],
                $token->position->line
            );
            $report->step( 'Verify correct column in input.' );
            $verify->int(
                $expected[ $test_index ][ 4 ],
                $token->position->column
            );
            $test_index += 1;
        }

        fclose( $stream );

    }

}


/*---------------------------------------------------------------------------
Functions
---------------------------------------------------------------------------*/

/*---------------------------------------------------------------------------
Execution
---------------------------------------------------------------------------*/

if( realpath( $_SERVER[ 'SCRIPT_FILENAME' ] ) == __FILE__ ) {

    header( 'Content-Type: text/plain; charset=utf-8' );

    

}

