<?php

namespace hzphp\Template;


/**
 *  Base template class for object-oriented template users
 */
class Template {


    protected           $m_data;    //template substitution data
    protected           $m_engine;  //template engine instance


    /**
     *  Constructs a Template instance
     *
     *  @param template The template format string
     */
    public function __construct(
        $template = null
    ) {

        //the user may have other ideas for their template
        if( $template === null ) {

            //ZIH - not sure I like this idea, but we'll see how it goes
            $class_name = get_class( $this );
            if( property_exists( $class_name, 'template' ) == true ) {
                $template = static::$template;
            }
            else if( method_exists( $class_name, 'getTemplate' ) == true ) {
                $template = static::getTemplate();
            }
        }

        //create a template engine to render the template
        $this->engine = new Engine( $template );

        //assign some default data references
        $this->m_data = [
            [
//ZIH - debugging, need these to be disabled for now
//                '_REQUEST' => &$_REQUEST,
//                '_SERVER'  => &$_SERVER,
//                '_SESSION' => &$_SESSION,
//                'GLOBALS'  => &$GLOBALS
            ]
        ];

    }


    /**
     *  Unknown method name handler
     *
     *  This method only gets invoked when the inheriting class did not
     *  implement this particular method.  Here, we try to resolve the data
     *  needed by the template using the internal data.
     *
     *  @param name     The method name
     *  @param arguments
     *                  The array of arguments passed to the method
     *  @return         The data referenced by name, or null if none found
     */
    public function __call(
        $name,
        $arguments
    ) {

        //ZIH - would like to support arguments that can further dereference
        //      nested elements in the arrays, or automatically pass the
        //      remaining arguments to a method

        return $this->__get( $name );
    }



    /**
     *  Unknown property name handler
     *
     *  This method only gets invoked when the inheriting class did not
     *  implement this particular property.  Here, we try to resolve the data
     *  needed by the template using the internal data.
     *
     *  @param key      The property name
     *  @return         The data referenced by key, or null if none found
     */
    public function __get(
        $key
    ) {

        //find the number of data sets in the list
        $num_data = count( $this->m_data );

        //scan the list for a matching entry from newest to oldest
        for( $i = ( $num_data - 1 ); $i >= 0; --$i ) {

            //see if this array contains the property name as a key
            if( isset( $this->m_data[ $i ][ $key ] ) == true ) {
                return $this->m_data[ $i ][ $key ];
            }
        }

        //key not found in any data source
        return null;
    }


    /**
     *  Render the template when type casting to a string
     *
     *  @return         The rendered template as a string
     */
    public function __toString() {

        //call the render method to construct the output
        return $this->render();

    }


    /**
     *  Render the template
     *
     *  @param data     An associative array of data for substitution values
     *  @return         A string constructed from a template and data
     */
    public function render(
        Array $data = null
    ) {

        //see if there is some last-minute data needed for rendering
        if( $data !== null ) {
            array_push( $this->m_data, $data );
        }

        //use our own properties to handle template references
        $string = $this->engine->render( $this );

        //clean up the immediate data
        if( $data !== null ) {
            array_pop( $this->m_data );
        }

        //return the rendered string
        return $string;

    }


    /**
     *  Adds a data entry to the internal storage
     *
     *  @param data     An associative array of data for substitution values
     */
    public function addDataEntry(
        Array $data
    ) {

        //push this entry onto our internal list
        array_push( $this->m_data, $data );

    }


}

?>