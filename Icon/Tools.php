<?php

namespace hzphp\Icon;


/**
 *  Simplified Access Tools
 */
class Tools {


    /**
     *  Retrieve the CSS for sprite-style usage of icons.
     *
     *  @param source   The icon source name (currently only 'Iconic')
     *  @param config   Any configuration overrides (see IconSet)
     *  @return         CSS rules as a string
     */
    public static function getCSS(
        $source       = 'Iconic',
        Array $config = null
    ) {

        if( class_exists( $source ) == true ) {
            $db = new $source();
        }
        else {
            $db = new Iconic();
        }

        $set = new IconSet( $db );

        if( is_null( $config ) == false ) {
            $set->setConfigs( $config );
        }

        return $set->getCSS();
    }


}

?>