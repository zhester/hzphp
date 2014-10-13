<?php
/*****************************************************************************

MDLite.php

Markdown document conversion (lite edition).

TODO:
    - Add lines and columns to set_position() calls.
    - Clean up how expressions are kept per element sub-type.

*****************************************************************************/

/*----------------------------------------------------------------------------
Dependencies
----------------------------------------------------------------------------*/

/*----------------------------------------------------------------------------
Default Namespace
----------------------------------------------------------------------------*/

namespace hzphp\Markdown;

/*----------------------------------------------------------------------------
Classes
----------------------------------------------------------------------------*/


/**
 *  Exception to report problems when using the Markdown module.
 */
class MarkdownException extends \Exception {

    /**
     *  Exception constructor.
     *
     *  @param message
     *  @param code
     *  @param previous
     */
    public function __construct(
        $message,
        $code = 0,
        Exception $previous = null
    ) {
        parent::__construct( $message, $code, $previous );
    }

}


/**
 *  Does minimal document translation with very strict inputs.
 */
class Document {

    /*------------------------------------------------------------------------
    Protected Properties
    ------------------------------------------------------------------------*/

    protected $source;      //The original Markdown string


    /*------------------------------------------------------------------------
    Static Public Methods
    ------------------------------------------------------------------------*/

    /**
     *  Parses a Markdown string into an array of Block objects.
     *
     *  @param string The Markdown string to parse
     *  @return       An array of Block objects representing the string
     */
    static public function parse_blocks( $string ) {

        //Sanitize line endings.
        $subject = str_replace( "\r", '', $string );

        //Break string into block-like sections.
        $parts = explode( "\n\n", $subject );

        //Create an array of Block objects for every block string.
        $blocks = array_map(
            function( $part ) { return new Block( $part ); },
            $parts
        );

        //Return the array.
        return $blocks;
    }


    /**
     *  Parses a Markdown sub-string (it should not have block boundaries)
     *  into an array of Text and Inline objects.
     *
     *  @param string The Markdown string to parse
     *  @return       An array of Text and Inline objects representing the
     *                string
     */
    static public function parse_inlines( $string ) {

        //Perform basic cleanup (assume newline conversion has happened).
        $subject = trim( $string );

        //Do not process empty text.
        if( strlen( $subject ) == 0 ) {
            return [];
        }

        //Emulation for \b matching => "$start_boundary|$end_boundary"
        $start_boundary = '(?<=\\W)(?=\\w)';
        $end_boundary   = '(?<=\\w)(?=\\W)';
        //special boundary matching for inline Markdown elements
        //ZIH - don't like the generic boundaries.  these should be built per
        //      type of inline element so it can scale a little easier
        //ZIH - consider storing each pattern in a static table
        //ZIH - the table can contain the HTML tag, a whole-element capturing
        //      pattern, an internal detection + capturing pattern
        $se = '(?<=\\W)(?=[*_`\\[])';
        $ee = '(?<=[*_`\\)])(?=\\W)';
        //inline element patterns
        $patterns = [
            '(_[^_]+?_)',
            '(\\*[^*]+?\\*)',
            '(`[^`]+?`)',
            '(\\[[^\\]]+\\]\\([^\\)]+\\))'
        ];

        //Construct a pattern to capture all inline elements.
        $pattern = "/$se" . implode( "$ee|$se", $patterns ) . "$ee/";

        //Use set order so we get the elements in the order in which they
        //occur in the string.  We also need offsets to track their positions.
        $flags   = ( PREG_SET_ORDER | PREG_OFFSET_CAPTURE );

        //Parse the string for inline elements.
        $result  = preg_match_all( $pattern, $subject, $matches, $flags );

        //See if there are any non-text elements.
        if( ( $result !== false ) && ( $result > 0 ) ) {

            //Create an array to store the text and inline elements.
            $inlines = [];

            //Keep track of our position in the subject string.
            $last_offset = 0;

            //Loop through each matched sub-expression.
            foreach( $matches as $match ) {

                //Count entries in the match array.
                $count = count( $match );

                //Scan for the first matched sub-expression in OR'ed set.
                for( $i = 1; $i < $count; ++$i ) {

                    //Shortcut to the offset for this match.
                    $offset  = $match[ $i ][ 1 ];

                    //When offset is -1, this sub-expression was not matched.
                    if( $offset == -1 ) {
                        continue;
                    }

                    //Shortcuts to the captured string and its length.
                    $capture = $match[ $i ][ 0 ];
                    $length  = strlen( $capture );

                    //See if the offset of this sub-string comes after
                    //our last known position in the subject.
                    if( $offset > $last_offset ) {

                        //Capture all previous text in a text element.
                        $previous_length = $offset - $last_offset;
                        $text = new Text(
                            substr( $subject, $last_offset, $previous_length )
                        );
                        $text->set_position( $last_offset );
                        $inlines[] = $text;
                    }

                    //Create a new inline element for this sub-string.
                    $inline = new Inline( $capture );
                    $inline->set_position( $offset );
                    $inlines[] = $inline;

                    //Update the tracked position in the subject string.
                    $last_offset = $offset + $length;
                }
            }

            //Check to see if there is anything following the last inline.
            $length = strlen( $subject );
            if( $last_offset < $length ) {

                //Capture all the trailing text in a new text element.
                $append_length = $length - $last_offset;
                $text = new Text(
                    substr( $subject, $last_offset, $append_length )
                );
                $text->set_position( $last_offset );
                $inlines[] = $text;
            }

            //Return the array of Inline and Text objects.
            return $inlines;
        }

        //No inline elements found, put everything in a text element.
        return [ new Text( $subject ) ];
    }


    /*------------------------------------------------------------------------
    Public Methods
    ------------------------------------------------------------------------*/

    /**
     *  Constructor.
     *
     *  @param string Optional Markdown string to use immediately
     */
    public function __construct( $string = false ) {
        if( $string !== false ) {
            $this->load_from_string( $string );
        }
    }


    /**
     *  Loads a Markdown string from a file.
     *
     *  @param filename The file from which to load a Markdown document
     */
    public function load_from_file( $filename ) {
        $this->load_from_string( file_get_contents( $filename ) );
    }


    /**
     *  Loads a Markdown string.
     *
     *  @param string The string from which to parse a Markdown document
     */
    public function load_from_string( $string ) {
        $this->source = $string;
    }


    /**
     *  Returns a list of all block elements in the document.
     *
     *  @return An array containing all block elements in the document.
     */
    public function get_blocks() {
        return self::parse_blocks( $this->source );
    }


    /**
     *  Retrieves an HTML translation of the Markdown string.
     *
     *  @return A string of HTML representing the Markdown document
     */
    public function get_html() {
        $html   = '';
        $blocks = self::parse_blocks( $this->source );
        foreach( $blocks as $block ) {
            $html .= $block->get_html() . "\n";
        }
        return $html;
    }

}


/**
 *  Models any element that needs our attention in the document.
 */
abstract class Element {

    /*------------------------------------------------------------------------
    Protected Properties
    ------------------------------------------------------------------------*/
    protected $attributes = [];         //Assoc array of element attributes
    protected $contents   = null;       //Bare-text contents of the element
    protected $object     = 'Element';  //The local name of the object
    protected $position   = [           //Source document position
        'offset' => -1,
        'line'   => -1,
        'column' => -1
    ];
    protected $readonly   = [];         //Array of read-only properties
    protected $source     = null;       //The original Markdown string
    protected $type       = null;       //The type of document element


    /*------------------------------------------------------------------------
    Abstract Public Methods
    ------------------------------------------------------------------------*/

    /**
     *  Retrieves an HTML translation of the Markdown string.
     *
     *  @return A string of HTML representing the Markdown document
     */
    abstract public function get_html();


    /*------------------------------------------------------------------------
    Public Methods
    ------------------------------------------------------------------------*/

    /**
     *  Constructor.
     *
     *  @param source The element's Markdown source string
     */
    public function __construct( $source ) {

        //Initialize object state.
        $this->source   = $source;
        $this->readonly = [
            'contents', 'object', 'position', 'source', 'type'
        ];

        //Set the name of this object's instance for future checking.
        $parts = explode( '\\', get_class( $this ) );
        $this->object_type = array_pop( $parts );

        //Invoke customizeable object initialization.
        $this->initialize_element();
    }


    /**
     *  Provides read-only access to Element object properties.
     *
     *  @return The value of one of the available properties
     */
    public function __get( $key ) {

        //Ensure the user can read this property.
        if( in_array( $key, $this->readonly ) == true ) {
            return $this->$key;
        }

        //Allow read-only attribute access.
        else if( in_array( $key, array_keys( $this->attributes ) ) == true ) {
            return $this->attributes[ $key ];
        }

        //This property can't be read.
        throw new MarkdownException(
            "Element property \"$key\" is not available."
        );
    }


    /**
     *  Get the basic string representation of this element.
     *  Note: Specific types should override this to their liking.
     *
     *  @return The string version of this element
     */
    public function __toString() {
        return $this->contents;
    }


    /**
     *  Allows the parsers to record where they got the source string for this
     *  element.
     *
     *  @param offset The absolute offset into the original source string
     *  @param line   The line number in the source string
     *  @param column The column number in the line
     */
    public function set_position( $offset, $line = -1, $column = -1 ) {
        $this->position[ 'offset' ] = $offset;
        $this->position[ 'line' ]   = $line;
        $this->position[ 'column' ] = $column;
    }


    /*------------------------------------------------------------------------
    Protected Methods
    ------------------------------------------------------------------------*/

    /**
     *  Provides a common facility for building HTML attribute strings.
     *
     *  @return A string of HTML attributes for the current element.
     */
    protected function get_html_attribute_string() {

        //Build an array of key="value" pairs.
        $pairs = [];
        foreach( $this->attributes as $k => $v ) {
            $pairs[] = $k . '="' . htmlentities( $v ) . '"';
        }

        //Prefix a space for direct insertion into tags.
        if( count( $pairs ) > 0 ) {
            return ' ' . implode( ' ', $pairs );
        }

        //No attributes, return something usable.
        return '';
    }


    /**
     *  Provides a common facility for building complete HTML tag strings.
     *
     *  @return A string of HTML representing the current element.
     */
    protected function get_html_tag_string() {

        //Fetch the attributes.
        $attributes = $this->get_html_attribute_string();

        //Construct the tag as best as we can.
        return "<{$this->type}$attributes>{$this->contents}</{$this->type}>";
    }


    /**
     *  Perform element-specific initialization.  This can be overridden by
     *  specific element types to obviate the need for them to maintain their
     *  own constructors and still get a cleanly-initialize Element object.
     */
    protected function initialize_element() {
        $this->contents = null;
        $this->type     = null;
    }

}


/**
 *  Models a single block in the document.  These are typically things like
 *  paragraphs, code listings, etc.
 */
class Block extends Element {


    /**
     *  Retrieves an HTML translation of the Markdown string.
     *
     *  @return A string of HTML representing the Markdown document
     */
    public function get_html() {

        //Check for inline elements.
        $inlines = Document::parse_inlines( $this->contents );

        //Fetch any attributes.
        $attributes = $this->get_html_attribute_string();

        //Set the block tag.
        $html = "<{$this->type}$attributes>";

        //Assemble the block element as HTML.
        foreach( $inlines as $inline ) {
            $html .= $inline->get_html();
        }

        //Return the HTML to render this block.
        return $html . "</{$this->type}>";
    }


    /**
     *  Override the automatic element initializer to perform internal
     *  element type detection and content capturing.
     *
     */
    protected function initialize_element() {

        //Patterns used to detect/capture content from the block string.
        $line_pattern = '/([^\\n]+)\\n([=-]+)/';
        $hash_pattern = '/^(#+) *(.+)$/';

        //Check for === and --- headings.
        if( preg_match( $line_pattern, $this->source, $matches ) == 1 ) {
            $this->contents = $matches[ 1 ];
            $this->type     = $matches[ 2 ][ 0 ] == '=' ? 'h1' : 'h2';
        }

        //Check for ### headings.
        else if( preg_match( $hash_pattern, $this->source, $matches ) == 1 ) {
            $this->contents = $matches[ 2 ];
            $this->type     = 'h' . strlen( $matches[ 1 ] );
        }

        //Default to paragraphs.
        else {
            $this->contents = $this->source;
            $this->type     = 'p';
        }
    }

}


/**
 *  Models a single inline element in the document.  These are typically
 *  things like bold and italic text spans.
 */
class Inline extends Element {


    static protected $map = [   //Directly-mappable inline elements
        '*' => 'strong',
        '_' => 'em',
        '`' => 'code'
    ];


    /**
     *  Retrieves an HTML translation of the Markdown string.
     *
     *  @return A string of HTML representing the Markdown document
     */
    public function get_html() {

        //Use the built-in implementation to build the HTML.
        return $this->get_html_tag_string();
    }


    /**
     *  Override automatic element initialization.
     *
     */
    protected function initialize_element() {

        //Default to spans.
        $this->type     = 'span';
        $this->contents = $this->source;

        //Extract parts of the string to help determine type and contents.
        $delim = substr( $this->source, 0, 1 );
        $text  = substr( $this->source, 1, ( strlen( $this->source ) - 2 ) );

        //Check for an easily mapped inline element.
        if( in_array( $delim, array_keys( self::$map ) ) == true ) {
            $this->type = self::$map[ $delim ];
            if( $this->type == 'code' ) {
                $this->contents = htmlspecialchars( $text );
            }
            else {
                $this->contents = $text;
            }
        }

        //Check for links.
        else if( $delim == '[' ) {

            //Pattern to extract link text and reference.
            $pattern = '/\\[([^\\]]+)\\]\\(([^)]+)\\)/';

            //Extract the link text and reference.
            if( preg_match( $pattern, $this->source, $matches ) == 1 ) {
                $this->type                 = 'a';
                $this->contents             = $matches[ 1 ];
                $this->attributes[ 'href' ] = htmlentities( $matches[ 2 ] );
            }
        }
    }

}


/**
 *  Models a piece of contiguous text that doesn't require special handling.
 */
class Text extends Element {


    /**
     *  Retrieves an HTML translation of the Markdown string.
     *
     *  @return A string of HTML representing the Markdown document
     */
    public function get_html() {
        return $this->source;
    }

}


/*----------------------------------------------------------------------------
Functions
----------------------------------------------------------------------------*/


/*----------------------------------------------------------------------------
Execution
----------------------------------------------------------------------------*/

if( $_SERVER[ 'SCRIPT_FILENAME' ] == __FILE__ ) {
    $test = <<<EOD
Testing Top-level Heading
=========================

Second-level Heading
--------------------

A paragraph of text.  With some words that probably go on and on.  Look, I
need to fill this up enough to get a couple newlines, and possibly some other
junk.

### Third-level Heading

This is a one-line paragraph that _contains_ a couple *inline* elements.

The `code` ticks should work.  They should also `<escape>` HTML stuff.

MDLite allows simple links.  For example, the Markdown "standard" comes from
[Daring Fireball](http://daringfireball.net/projects/markdown/syntax).

This paragraph contains *degenerate_ inline elements.
Don't use [anchors][like this] and you'll be fine.
EOD;
    header( 'Content-Type: text/plain' );
    $doc = new Document( $test );
    echo $doc->get_html();
}
?>
