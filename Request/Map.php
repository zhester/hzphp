<?php

namespace hzphp\Request;


/**
 *  Provides centralized, consistent request mapping features.
 */
class Map {


    protected           $default;
    protected           $map;


    /**
     *
     */
    public function __construct(
        Array $map = []
    ) {
        $this->default = null;
        $this->map     = [];
        $this->load( $map );
    }


    /**
     *
     */
    public function append(
        $source,
        $target
    ) {
        $this->map[] = [ $source, $target ];
        $this->default = $target;
    }


    /**
     *
     */
    public function prepend(
        $source,
        $target
    ) {
        array_unshift( $this->map, [ $source, $target ] );
    }


    /**
     *
     */
    public function findTarget(
        $string
    ) {
        for( $i = 0; $i < count( $this->map ); ++$i ) {
            if( $this->map[ $i ][ 0 ]->match( $string ) == true ) {
                return $this->map[ $i ][ 1 ];
            }
        }
        return $this->default;
    }


    /**
     *
     */
    protected function load(
        Array $map
    ) {

        for( $i = 0; $i < count( $map ); ++$i ) {
            $sname = __NAMESPACE__ . '\\';
            if( count( $map[ $i ] ) == 3 ) {
                $sname .= 'Source' . $this->camelCase( $map[ $i ][ 2 ] );
            }
            else {
                $sname .= 'SourcePrefix';
            }
            $this->append(
                new $sname( $map[ $i ][ 0 ] ),
                new Target( $map[ $i ][ 1 ] )
            );
        }

    }


    /**
     *
     */
    protected function camelCase(
        $string
    ) {
        return str_replace(
            ' ',
            '',
            ucfirst( str_replace( '-', ' ', $string ) )
        );
    }
}

?>