<?php

namespace hzphp\Request;


/**
 *  Provides centralized, consistent request mapping features.
 */
class Map {


    protected           $map;


    /**
     *
     */
    public function __construct( Array $map = [] ) {
        $this->map = [];
        for( $i = 0; $i < count( $map ); ++$i ) {
            $this->add( $map[ $i ][ 0 ], $map[ $i ][ 1 ] );
        }
    }


    /**
     *
     */
    public function add( $source, $destination ) {
        $this->map[] = [
            new Source( $source ),
            new Destination( $destination )
        ];
    }


}

?>