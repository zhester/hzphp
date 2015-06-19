<?php
/****************************************************************************

Minimal Regular Expression Stream-style Lexical Parser
======================================================

Handy for parsing a potentially very long string containing a defined syntax.

Note: This parser assumes it will never be used on an old Mac-style file
stream where lines are only separated by carriage returns (\r).  DOS-style
line endings should work, but are untested and unsupported.  The only features
affected would be a token's line and column numbers.  The user's syntax
specification is where line-ending compatibility becomes important for actual
parsing.

Example Usage
-------------

    $file  = fopen( 'myfile.syntax', 'r' );
    $lexer = new Lexer(
        $file,
        [
            [ 'COMMENT',     '#[^\\n]*'               ],
            [ 'IDENTIFIER',  '[a-zA-Z_][a-zA-Z0-9_]*' ],
            [ 'ASSIGNMENT',  '='                      ],
            [ 'TOKEN_ERROR', '\S+'                    ]
        ]
    );
    foreach( $lexer as $token ) {
        switch( $token->ident ) {
            case 'COMMENT':
                echo "Found comment: ${token->value}\n";
                break;
            case 'IDENTIFIER':
                echo "Found identifier: ${token->value}\n";
                break;
            case 'ASSIGNMENT':
                echo "Found assignment operator: ${token->value}\n";
                break;
            case 'TOKEN_ERROR':
            default:
                echo "Error parsing file.\n";
                break;
        }
    }
    fclose( $file );

The Token object has the following properties.

    $token->ident                   Token identifier (user-specified)
    $token->value                   String value of parsed token
    $token->position                TokenPosition instance
        $token->position->offset    Byte offset into stream
        $token->position->line      Line number into stream
        $token->position->column    Column number in current line

****************************************************************************/

/*---------------------------------------------------------------------------
Default Namespace
---------------------------------------------------------------------------*/

namespace hzphp\Text;

/*---------------------------------------------------------------------------
Dependencies
---------------------------------------------------------------------------*/

/*---------------------------------------------------------------------------
Classes
---------------------------------------------------------------------------*/


/**
 * Lexical Parser
 */
class Lexer implements \Iterator {

    /*-----------------------------------------------------------------------
    Protected Properties
    -----------------------------------------------------------------------*/

    protected $is_complete = false;   //scan completion flag
    protected $offset      = 0;       //the offset after the last token
    protected $line        = 1;       //current line in the input stream
    protected $pattern     = null;    //the pattern used for token matching
    protected $stream      = null;    //input file stream
    protected $subject     = null;    //subject string being parsed
    protected $token       = null;    //most recently matched token
    protected $tspecs      = null;    //token parsing specifications


    /**
     * Lexer Constructor
     *
     * @param stream Text file input stream
     * @param tspecs An array of two-element arrays that specify token
     *               identifiers and their regular expressions for matching
     *               tokens
     * @param mods   Pattern modifiers for the parsing pattern
     */
    public function __construct( $stream, $tspecs = null, $mods = 'mS' ) {

        //set the input file stream
        $this->stream = $stream;

        //check for specified token specifications
        if( $tspecs != null ) {
            $this->tspecs = $tspecs;
        }

        //default to parsing around whitespace
        else {
            $this->tspecs = [ [ 'TOKEN', '\\S+' ] ];
        }

        //construct the regular expression from the token specifications
        $frags = [];
        foreach( $this->tspecs as list( $key, $frag ) ) {
            $frags[] = "(?P<$key>$frag)";
        }
        $this->pattern = '/' . implode( '|', $frags ) . "/$mods";
    }


    /**
     * Retrieve the current token from the stream.
     *
     * @return The current Token information container object
     */
    public function current() {
        return $this->token;
    }


    /**
     * Retrieve the remaining part of the stream without invoking the parser.
     *
     * @return The end of the stream as a string.
     */
    public function get_tail() {
        $this->is_complete = true;
        return substr( $this->subject, $this->offset );
    }


    /**
     * Retrieve the current token's unique key string.
     *
     * @return A unique identifier for the current token
     */
    public function key() {
        return $this->token->position->offset;
    }


    /**
     * Advance the stream to the next token.
     *
     */
    public function next() {

        //attempt to match one of the token patterns
        $result = preg_match(
            $this->pattern,
            $this->subject,
            $matches,
            PREG_OFFSET_CAPTURE,
            $this->offset
        );

        //one of the token patterns matched after the current offset
        if( $result == 1 ) {

            //scan all match groups
            foreach( $matches as $key => $match ) {

                //ignore numerically-indexed groups
                if( is_string( $key ) == false ) {
                    continue;
                }

                //get the group's offset into the subject string
                $offset = $match[ 1 ];

                //unmatched groups have an offset of -1
                if( $offset >= 0 ) {

                    //the match group's key is the token identifier
                    $ident = $key;

                    //the captured string is the token value
                    $value = $match[ 0 ];

                    //stop scanning the match groups
                    break;
                }
            }

            //find out where we are in the subject string
            $token_length = strlen( $value );
            $end_offset   = $offset + $token_length;
            $traversed    = $offset - $this->offset;
            $column       = 1;

            //check for possible position updates
            if( $traversed > 0 ) {

                //count number of lines traversed
                $lines = substr_count(
                    $this->subject,
                    "\n",
                    $this->offset,
                    $traversed
                );

                //update the line position
                $this->line += $lines;

                //count number of characters between token and last newline
                //  note: strrpos() is useless without a fixed string length
                $position = $offset;
                while( ( $position >= 0                      )
                    && ( $this->subject[ $position ] != "\n" ) ) {
                    $position -= 1;
                }

                //the token starts at this column in the current line
                $column = $offset - $position;
            }

            //update the internal offset for the next pass
            $this->offset = $end_offset;

            //construct the token information container
            $this->token = new Token(
                $ident,
                $value,
                $offset,
                $this->line,
                $column
            );
        }

        //no patterns matched after the current offset
        else {

            //consider the tokenization complete
            $this->is_complete = true;
        }
    }


    /**
     * Rewinds the lexer to the start of the stream.
     *
     */
    public function rewind() {
        $this->is_complete = false;
        $this->offset      = 0;
        $this->line        = 1;
        //ZIH - temp - operate on the entire stream - not for large files
        if( $this->subject === null ) {
            $this->subject = stream_get_contents( $this->stream );
        }
        $this->next();
    }


    /**
     * Determines if the current position in the stream is still valid.
     *
     * @return True if the stream has another valid token, otherwise false
     */
    public function valid() {
        return $this->is_complete == false;
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

