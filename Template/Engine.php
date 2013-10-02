<?php

namespace hzphp\Template;


/**
 * Template engine implementation
 */
class Engine {


    protected           $format;    //template format string
    protected           $data;      //source of template substitution data


    /**
     *  Constructs an Engine instance
     *
     *  @param format   The template format as a string
     *  @param data     An associative array of data for substitution values
     */
    public function __construct(
        $format,
        Array $data = []
    ) {

        $this->format = $format;
        $this->data   = $data;

    }


    /**
     *  Render the template when type casting to a string
     *
     *  @return         The rendered template as a string
     */
    public function __toString() {

        return $this->render();

    }


    /**
     *  Render the template
     *
     *  @param data     An associative array or object of data for
     *                  substitution values
     *  @return         A string constructed from a template and data
     */
    public function render(
        $data = null
    ) {

        if( $data !== null ) {
            $this->data = $data;
        }

        return preg_replace_callback(
            '/\\{\\{([^}]+?)\\}\\}/',
            array( $this, 'replace_tag' ),
            $this->format
        );

    }


    /**
     *  Handles callbacks from preg_replace_callback() to return an
     *  appropriate value for the given key.
     *
     *  @param matches  The regular expression match list
     *  @return         A value substitute for the template identifier
     */
    public function replace_tag(
        $matches
    ) {

        return $this->resolve_refs(
            $this->data,
            explode( '.', trim( $matches[ 1 ] ) )
        );

    }


    /**
     *  Render the template from a static context.
     *
     *  @param format   The template format as a string
     *  @param data     An associative array or object of data for
     *                  substitution values
     *  @return         A string constructed from a template and data
     */
    public static function srender(
        $format,
        Array $data
    ) {

        //instantiate the template rendering engine
        $te = new Engine( $format );

        //render the string using the data
        return $te->render( $data );

    }


    /**
     *  Resolves referenced data from a path-like list of keys.
     *
     *  @param ref      The base reference to resolve (object, array, etc)
     *  @param keys     The list of keys needed to dereference the data value
     *  @return         The final data value
     */
    private function resolve_refs(
        $ref,
        Array $keys
    ) {

        //if there are still keys, we have more to dereference
        if( count( $keys ) > 0 ) {

            //pull the next key from the list
            $key = array_shift( $keys );

            //if the reference points to an array, and has this key...
            if( ( is_array( $ref )      == true )
             && ( isset( $ref[ $key ] ) == true ) ) {

                //resolve this element in the array
                return $this->resolve_refs( $ref[ $key ], $keys );
            }

            //if the reference points to an object...
            else if( is_object( $ref ) == true ) {

                //if the object has a method named for the key...
                if( method_exists( $ref, $key ) == true ) {

                    //call the method, and pass the remaining keys as
                    //  arguments to the method
                    return call_user_func_array( array( $ref, $key ), $keys );
                }

                //if the object has a property named for the key...
                else if( ( property_exists( $ref, $key )  == true )
                      || ( method_exists( $ref, '__get' ) == true ) ) {

                    //resolve this property in the object
                    return $this->resolve_refs( $ref->$key, $keys );
                }
            }

            //no matches for the key in the reference we resolved
            return null;
        }

        //no more keys left, this reference is considered terminal
        return $ref;

    }


}

?>
