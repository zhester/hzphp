<?php

namespace hzphp\Icon;


/**
 *  Icon Model
 */
class Icon {


    protected           $id;
    protected           $paths;
    protected           $size;


    /**
     *  Constructor
     *
     *  @param id       This icon's ID
     *  @param paths    SVG paths as an array of strings
     *  @param size     The design size of this icon
     */
    public function __construct(
        $id,
        Array $paths,
        $size = 32
    ) {
        $this->id    = $id;
        $this->paths = $paths;
        $this->size  = $size;
    }


    /**
     *  Builds an SVG group element for this icon.
     *
     *  @param translate
     *                  Group translation as an array of [x,y] (in pixels)
     *  @return         SVG group element as a string
     */
    public function getGroup(
        Array $translate = null
    ) {

        if( $translate != null ) {
            $transform = ' transform="translate('
                . $translate[ 0 ] . ','
                . $translate[ 1 ] . ')"';
        }
        else {
            $transform = '';
        }

        $paths = [];
        foreach( $this->paths as $path ) {
            $paths[] = '<path class="base" d="' . $path . '"/>';
        }

        return sprintf(
            "  <g id=\"%s\"%s>\n    %s\n  </g>",
            $this->id,
            $transform,
            implode( "\n    ", $paths )
        );
    }


}

?>