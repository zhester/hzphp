<?php
/*****************************************************************************

DOM Document Rendering
======================

Provides convenience methods for working with documents that need to be sent
to the client.  The intent is to allow a server-side templating mechanism to
build documents from empty HTML/XML files.  Unlike traditional templating
systems, these files require no special markers to provide templating beyond
any necessary DOM-style hooks (e.g. `id` attributes, regular document
structure, `data-` attributes, etc.).

TODO
----

- Move DTL features to its own class that depends on this class.
    - Make it possible to pass a list of element arrays that then requires the
      user to specify their own root node.  This would prevent unwanted nested
      container nodes since the DTL currently relies on returning a single
      node.
- Write automated tests.
- Use exceptions for error reporting rather than magic return values.
- Use/resolve `source` arguments more consistently on all methods.
- Look into auto-detecting the type of DOM insertion when using assignment.

*****************************************************************************/

/*----------------------------------------------------------------------------
Dependencies
----------------------------------------------------------------------------*/

/*----------------------------------------------------------------------------
Default Namespace
----------------------------------------------------------------------------*/

namespace hzphp\DOM;

/*----------------------------------------------------------------------------
Classes
----------------------------------------------------------------------------*/

/**
 * DOM document rendering implementation.
 */
class DOMRenderer implements \ArrayAccess {

    /*------------------------------------------------------------------------
    Public Properties
    ------------------------------------------------------------------------*/

    //the DOMDocument instance used to render the page
    public $dom;


    /*------------------------------------------------------------------------
    Protected Properties
    ------------------------------------------------------------------------*/

    //the automatic setter method for assignment via object property
    //references and array subscripts
    protected $auto_set = 'append';


    /*------------------------------------------------------------------------
    Private Properties
    ------------------------------------------------------------------------*/

    /*------------------------------------------------------------------------
    Public Methods
    ------------------------------------------------------------------------*/

    /**
     * Initializes a rendering instance.
     *
     * @param template The file from which to read the document template.
     */
    public function __construct( $template, $auto_set = 'append' ) {
        $this->dom = new \DOMDocument();
        $this->dom->formatOutput = true;
        $this->dom->loadHTMLFile( $template );
        $this->setAutoSet( $auto_set );
    }


    /**
     * Retrieves a node from the document by requesting it by ID using an
     * object property reference.
     *
     * @param id The DOM element ID of the element to retrieve
     * @return   The requested DOM element, or null on failure
     */
    public function __get( $id ) {
        return $this->dom->getElementById( $id );
    }


    /**
     * Determines if the document can reference a node given its ID using an
     * object property reference.
     *
     * @param id The DOM element ID of the element to check
     * @return   True if the element exists, false if it does not
     */
    public function __isset( $id ) {
        $node = $this->dom->getElementById( $id );
        return $node !== null;
    }


    /**
     * Loads a node into the document by specifying it by ID using an object
     * property reference.  This method depends on the `auto_setter` property
     * being set to one of the methods that can be used to load a node into
     * the document.  Currently, only `append` is used to add the node to the
     * DOM element having the specified ID.
     *
     * @param id   The DOM element ID of the element to target
     * @param node The DOM element to load into the document
     */
    public function __set( $id, $node ) {

        //Handle special "IDs" differently than real DOM IDs.
        if( in_array( $id, [ 'title', 'body' ] ) == true ) {

            //Target the element with the given name.
            $target = $this->dom->getElementsByTagName( $id )->item( 0 );

            //Target the element with the given ID.
            $this->__set( '_' . $id, $node );
        }

        //Allow subversion of the special "IDs" check.
        else if( $id[ 0 ] == '_' ) {
            $target = $this->dom->getElementById( substr( $id, 1 ) );
        }

        //Attempt to resolve target by ID.
        else {
            $target = $this->dom->getElementById( $id );
        }

        //Make sure there is a target element.
        if( $target != null ) {
            if( method_exists( $this, $this->auto_set ) == true ) {
                call_user_func( [ $this, $this->auto_set ], $target, $node );
            }
        }
    }


    /**
     * Retrieves the rendered document as a string.
     *
     * @return The document as a string
     */
    public function __toString() {
        return $this->dom->saveHTML();
    }


    /**
     * Removes a node from the document using the `unset()` built-in with an
     * object property reference.
     *
     * @param id The DOM ID of the element to remove from the document
     */
    public function __unset( $id ) {
        $node = $this->getElementById( $id );
        if( $node != null ) {
            $node->parentNode->removeChild( $node );
        }
    }


    /**
     * Appends several types of nodes or node specifiers to another node in
     * the document.
     *
     * @param target The target parent of the node(s) to be appended
     * @param source The node or node specifier to append to the target
     * @return       The node that was appended to the target node
     */
    public function append( $target, $source ) {
        $target = $this->resolve( $target );
        if( isset( $source->nodeValue ) ) {
            $append = $source;
        }
        else if( is_array( $source ) ) {
            $append = $this->build( $source );
        }
        else if( is_string( $source ) ) {
            if( preg_match( '/^<([^>]+)>$/', $source, $m ) == 1 ) {
                $append = $this->dom->createElement( $m[ 1 ] );
            }
            else {
                $append = $this->dom->createTextNode( $source );
            }
        }
        else {
            $append = $this->dom->createTextNode( strval( $source ) );
        }
        $target->appendChild( $append );
        return $append;
    }


    /**
     * Builds a DOM document fragment using a DTL specification given by a
     * nested array.  Basic example:
     *
     *      [ 'div', [
     *          [ 'h1', 'Title' ],
     *          [ 'h2', 'Subtitle' ],
     *      ], [ 'class' => 'div-class' ] ]
     *
     * @param dtl The document template literal value
     * @return    The root node of the constructed DOM document fragment
     * @throws    Exception if the DTL contains an invalid/unsupported item
     */
    public function build( $dtl, $root = null ) {

        //Check for DTL as a string.
        if( is_string( $dtl ) ) {

            //Check for "slightly compressed" DTL strings.
            if( preg_match( '/^\[\w+,/', $dtl ) == 1 ) {

                //Expand the DTL string to normal JSON.
                $dtl = preg_replace_callback(
                    '/(\[(\w+),)|({(\w+):)/',
                    function( $groups ) {
                        if( $groups[ 0 ][ 0 ] == '{' ) {
                            return '{"' . $groups[ 4 ] . '":';
                        }
                        return '["' . $groups[ 2 ] . '",';
                    },
                    $dtl
                );
            }

            //Use the JSON parser to convert the DTL string.
            $dtl = json_decode( $dtl, true );
        }

        //Create the container node for this part of the document.
        $node = $this->dom->createElement( $dtl[ 0 ] );

        //See if this node needs to be the root node for an entire fragment.
        if( $root === null ) {
            $root = $node;
        }

        //Scan through the template to build each element.
        $num_args = count( $dtl );
        for( $i = 1; $i < $num_args; ++$i ) {

            //A null value is ignored.
            if( $dtl[ $i ] === null ) {
                continue;
            }

            //Shortcut to the current "argument" in the specifier.
            $arg = $dtl[ $i ];

            //Strings are used for text node content.
            if( is_string( $arg ) ) {
                $text_node = $this->dom->createTextNode( $arg );
                $node->appendChild( $text_node );
            }

            //Numbers.
            else if( is_numeric( $arg ) ) {
                $text_node = $this->dom->createTextNode( strval( $arg ) );
                $node->appendChild( $text_node );
            }

            //Arrays specify child nodes or attributes on the current node.
            else if( is_array( $arg ) ) {

                //Numeric arrays specify a list of child nodes.
                if( isset( $arg[ 0 ] ) ) {
                    $num_subargs = count( $arg );
                    for( $j = 0; $j < $num_subargs; ++$j ) {
                        $subarg = $arg[ $j ];
                        if( isset( $subarg->nodeType ) ) {
                            $node->appendChild( $subarg );
                        }
                        else {
                            $node->appendChild( $this->build( $subarg, $root ) );
                        }
                    }
                }

                //Associative arrays specify an attribute list.
                else {

                    //Set each attribute on the current node.
                    foreach( $arg as $k => $v ) {

                        //Nodes with an ID attribute get assigned to the root
                        //node as a property for easier access.
                        if(
                            ( $k == 'id' )
                            &&
                            ( isset( $root->$k ) == false )
                        ) {
                            $root->$v = $node;
                        }

                        //Set the attribute value.
                        $node->setAttribute( $k, $v );
                    }
                }
            }

            //Objects may contain DOM elements.
            else if( is_object( $arg ) ) {
                if( isset( $arg->nodeType ) ) {
                    $node->appendChild( $arg );
                }
                else {
                    throw new \Exception( 'Invalid DTL object type.' );
                }
            }

            //DTL argument is not null, a string, or an array.
            else {
                throw new \Exception( 'Unknown DTL argument type.' );
            }
        }

        //Return the constructed node.
        return $node;
    }


    /**
     * Removes all child nodes from a given node.
     *
     * @param target The target element (or ID) to empty
     */
    public function clear( $target ) {
        $target = $this->resolve( $target );
        if( $target->hasChildNodes() ) {
            $num_child_nodes = $target->childNodes->length;
            for( $i = ( $num_child_nodes - 1 ); $i >= 0; --$i ) {
                $target->removeChild( $target->childNodes->item( $i ) );
            }
        }
    }


    /**
     * Converts tabular data into a DTL specification of a table.
     *
     * @param data The data to display in the table
     * @return     The table's DTL structure
     */
    public function convertData( $data, $headings = null ) {
        $rows = [];
        if( is_array( $headings ) ) {
            $heads = [];
            for( $i = 0; $i < count( $headings ); ++$i ) {
                $heads[] = [ 'th', $headings[ $i ] ];
            }
            $rows[] = [ 'tr', $heads ];
        }
        $num_rows = count( $data );
        for( $i = 0; $i < $num_rows; ++$i ) {
            $cells = [];
            $num_cells = count( $data[ $i ] );
            for( $j = 0; $j < $num_cells; ++$j ) {
                $cells[] = [ 'td', $data[ $i ][ $j ] ];
            }
            $rows[] = [ 'tr', $cells ];
        }
        return [ 'table', [ [ 'tbody', $rows ] ] ];
    }


    /**
     * Converts a simplified array of link specifiers into a DTL structure.
     *
     * Each link is specified by its own array.  The first element is the
     * anchor element's text.  The second element is the value string of the
     * `href` attribute.  The third element may be an associative array of
     * query parameters.
     *
     *     Example:
     *       [
     *         [ 'Text', '/path' ],
     *         [ 'Text 2', '/path2' ],
     *         [ 'T3', '/p3', [ 'x' => '1', 'y' => '2' ] ]
     *       ]
     *
     * @param links The array of link values
     * @param list  The name of the list's container element
     * @param item  The name of the anchor wrapping element
     * @return      The DTL structure for the list of links
     */
    public function convertLinks( $links, $list = 'ul', $item = 'li' ) {
        $items = [];
        $num_links = count( $links );
        for( $i = 0; $i < $num_links; ++$i ) {
            $link = $links[ $i ];
            $href = $link[ 1 ];
            if( isset( $link[ 2 ] ) ) {
                $params = [];
                foreach( $link[ 2 ] as $k => $v ) {
                    $params[] = $k . '=' . urlencode( $v );
                }
                $href .= '?' . implode( '&', $params );
            }
            $anchor = [ 'a', $link[ 0 ], [ 'href' => $href ] ];
            $items[] = [ $item, [ $anchor ] ];
        }
        return [ $list, $items ];
    }


    /**
     * Uses the given HTML to replace the contents of the target node.
     *
     * @param target The target node (or ID) of the element to update
     * @param html   The HTML as a string to place in the target element
     * @return       The target node, or false on failure
     */
    public function html( $target, $html ) {
        $target = $this->resolve( $target );
        $frag = $this->dom->createDocumentFragment();
        $frag->appendXML( $html );
        $this->clear( $target );
        $target->appendChild( $frag );
        return $target;
    }


    /**
     * Determines if a given array index exists for this object.  Array
     * indexes can be used to look up nodes in the document by ID.
     *
     * @param id The DOM element ID of the element to check
     * @return   True if the element exists, false if it does not
     */
    public function offsetExists( $id ) {
        return $this->__isset( $id );
    }


    /**
     * Retrieves a node from the document using array subscript notation to
     * specify the ID of the node.
     *
     * @param id The DOM element ID of the element to retrieve
     * @return   The requested DOM element, or null on failure
     */
    public function offsetGet( $id ) {
        return $this->__get( $id );
    }


    /**
     * Loads a node into the document using array subscript notation to
     * specifty the target ID of the load operation.
     *
     * @param id   The DOM element ID of the element to target
     * @param node The DOM element to load into the document
     */
    public function offsetSet( $id, $node ) {
        $this->__set( $id, $node );
    }


    /**
     * Removes a node from the document using array subscript notation to
     * specify the ID of the node to remove.
     *
     * @param id The DOM ID of the element to remove from the document
     */
    public function offsetUnset( $id ) {
        $this->__unset( $id );
    }


    /**
     * Replaces a node in the document with another node.
     * Note: This method is intended to be an alternative to `append` when
     * using property references and array subscripts for setting nodes in the
     * document.
     *
     * @param target The target node (or ID of a node) to be replaced
     * @param source The source node to insert into the document
     * @return       The node that was replaced, or false on failure
     */
    public function replace( $target, $source ) {
        $target = $this->resolve( $target );
        $parent = $target->parentNode;
        return $parent->replaceChild( $source, $target );
    }


    /**
     * Checks and attempts to resolve many different ways of specifying a
     * target node in the document.
     *
     * @param target The target node object, array, string, whatever
     * @return       If found, the node, otherwise false
     */
    public function resolve( $target ) {
        if( is_string( $target ) ) {
            $node = $this->dom->getElementById( $target );
            if( $node == null ) {
                return false;
            }
            return $node;
        }
        return $target;
    }


    /**
     * Sets the automatic setter method for use in object property reference
     * and array subscript assignment.
     *
     * @param auto_set The name of the method to use for setting new nodes in
     *                 the document.  This should be one of the follwing
     *                 method names: `append`, `html`, `replace`, `text`
     */
    public function setAutoSet( $auto_set ) {
        if( in_array( $auto_set, [ 'append', 'html', 'replace', 'text' ] ) ) {
            $this->auto_set = $auto_set;
        }
    }


    /**
     * Sets the text for the target node in the document.
     *
     * @param target The target node (or ID) for which to set the text
     * @param text   The text to set in the node
     * @return       The text node of the target node, or false on failure
     */
    public function text( $target, $text ) {
        $target = $this->resolve( $target );
        $this->clear( $target );
        $text_node = $this->dom->createTextNode( $text );
        $target->appendChild( $text_node );
        return $text_node;
    }


    /*------------------------------------------------------------------------
    Protected Methods
    ------------------------------------------------------------------------*/

    /*------------------------------------------------------------------------
    Private Methods
    ------------------------------------------------------------------------*/

}


/*----------------------------------------------------------------------------
Functions
----------------------------------------------------------------------------*/

/*----------------------------------------------------------------------------
Execution
----------------------------------------------------------------------------*/

if( $_SERVER[ 'SCRIPT_FILENAME' ] == __FILE__ ) {

    //see what the output looks like
    header( 'Content-Type: text/plain' );

    //load an empty document template into a new renderer
    $doc = new DOMRenderer( 'example.html' );

    //set some plain text using the following assignment operations
    $doc->setAutoSet( 'text' );

    //set the title using a property reference
    $doc->title = 'Dynamic Title';

    //set the subtitle using array subscripting
    $doc[ 'subtitle' ] = 'Dynamic Subtitle';

    //use append method to load navigation (next)
    $doc->setAutoSet( 'append' );

    //demonstrate shorthand navigation generation
    $doc->navigation = $doc->convertLinks( [
        [ 'Home',   '.'                           ],
        [ 'Page 1', 'example.php', [ 'p' => '1' ] ],
        [ 'Page 2', null,          [ 'p' => '2' ] ]
    ] );

    //we can still get stuff using the `dom` property
    $divs = $doc->dom->getElementsByTagName( 'div' );
    $divs->item( 1 )->nodeValue = 'This works, too!';

    //preserve markup in an element using the `html` method
    $doc->html( $divs->item( 2 ), 'Some <strong>marked up</strong> content.' );

    //demonstrate complex tree construction using DTL
    $part = $doc->build(
        [ 'div', [ 'class' => 'section' ], [
            [ 'h3', 'Section Heading' ],
            [ 'p', 'A paragraph in this section.' ],
            [ 'p', 'Before ', [ [ 'a', 'Link Text', [ 'href' => '.' ] ], ], ' After' ],
            [ 'p', 'A final paragraph.' ]
        ] ]
    );
    $divs->item( 3 )->appendChild( $part );

    //maybe the DTL specification is coming from outside PHP
    $part = $doc->build( '[h3,"Hello World",{id:"h3id"}]' );
    $divs->item( 5 )->appendChild( $part );

    //oops, I forgot something
    $part->h3id->nodeValue = 'Hello World!';

    //make a table
    $heads = [ 'A', 'B', 'C', 'D' ];
    $data = [
        [ 1, 2, 3, 4 ],
        [ 2, 3, 4, 5 ],
        [ 3, 4, 5, 6 ]
    ];
    $doc->append( $divs->item( 5 ), $doc->convertData( $data, $heads ) );

    //send the rendered document to the client
    echo $doc;
}

