<?php

namespace hzphp\Icon;


/**
 *
 */
class Icon {


    protected           $id;
    protected           $paths;
    protected           $size;


    public function __construct(
        $id,
        Array $paths,
        $size = 32
    ) {
        $this->id    = $id;
        $this->paths = $paths;
        $this->size  = $size;
    }


    public function getGroup(
        Array $translate = null
    ) {
        $svg = '  <g id="' . $this->id . '"';
        if( $translate != null ) {
            $svg .= ' transform="translate('
                . $translate[ 0 ] . ','
                . $translate[ 1 ] . ')"';
        }
        $svg .= ">\n";
        foreach( $this->paths as $path ) {
            $svg .= '    <path class="base" d="' . $path . '"/>' . "\n";
        }
        $svg .= '  </g>';
        return $svg;
    }


}

?>