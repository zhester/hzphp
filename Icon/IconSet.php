<?php

namespace hzphp\Icon;


/**
 *  Icon Set Delivery System
 */
class IconSet implements \ArrayAccess {


    public              $config = [
        'color'      => '#4E4E50',
        'css_prefix' => '.button',
        'pad'        => 4,
        'size'       => 32
    ];


    protected           $database;


    protected static    $document = '<?xml version="1.0" encoding="utf-8"?>
<svg version="1.1" xmlns="http://www.w3.org/2000/svg"
  width="%d" height="%d" viewBox="0 0 %d %d">
<style type="text/css">.base { fill: %s; }</style>
%s
</svg>';


    public function __construct(
        Database $database
    ) {
        $this->database = $database;
    }


    public function __toString() {
        return $this->getSVG();
    }


    public function getConfig(
        $key
    ) {
        if( isset( $this->config[ $key ] ) == true ) {
            return $this->config[ $key ];
        }
        return null;
    }


    public function getCSS() {
        $this->updateLayout();
        extract( $this->config );
        $icons = [];
        $col   = 0;
        $row   = 0;
        foreach( $this->database->ids as $id ) {
            $icons[] = sprintf(
                '%s.%s { background-position: -%dpx -%dpx; }',
                $css_prefix,
                $id,
                ( ( $col * $size ) + ( $col * $pad ) ),
                ( ( $row * $size ) + ( $row * $pad ) )
            );
            $col += 1;
            if( $col == $cols ) {
                $col = 0;
                $row += 1;
            }
        }
        return "    " . implode( "\n    ", $icons );
    }


    public function getSVG(
        $id = null
    ) {

        $this->updateLayout();
        extract( $this->config );

        if( is_null( $id ) == false ) {
            return $this->getDocument(
                $size,
                $size,
                ( $this->database->getSize() / $size ),
                $color,
                $this->database[ $id ]->getGroup()
            );
        }

        $orig   = $this->database->getSize();
        $zoom   = $orig / $size;
        $zpad   = $zoom * $pad;
        $width  = ( $cols * $size ) + ( ( $cols + 1 ) * $pad );
        $height = ( $rows * $size ) + ( ( $rows + 1 ) * $pad );
        $icons  = [];
        $col    = 0;
        $row    = 0;
        foreach( $this->database->ids as $id ) {
            $icons[] = $this->database[ $id ]->getGroup(
                [
                    ( ( $col * $orig ) + ( ( $col + 1 ) * $zpad ) ),
                    ( ( $row * $orig ) + ( ( $row + 1 ) * $zpad ) )
                ]
            );
            $col += 1;
            if( $col == $cols ) {
                $col = 0;
                $row += 1;
            }
        }

        return $this->getDocument(
            $width,
            $height,
            $zoom,
            $color,
            implode( "\n", $icons )
        );
    }


    public function offsetExists(
        $id
    ) {
        return isset( $this->database[ $id ] );
    }


    public function offsetGet(
        $id
    ) {
        $this->getSVG( $id );
    }


    public function offsetSet(
        $id,
        $value
    ) {
        //meh
    }


    public function offsetUnset(
        $id
    ) {
        //meh
    }


    public function setConfig(
        $key,
        $value
    ) {
        if( isset( $this->config[ $key ] ) ) {
            $type = gettype( $this->config[ $key ] );
            if( $type == 'integer' ) {
                $this->config[ $key ] = intval( $value );
            }
            else if( $type == 'double' ) {
                $this->config[ $key ] = floatval( $value );
            }
            else {
                $this->config[ $key ] = $value;
            }
        }
    }


    public function setConfigs(
        Array $array
    ) {
        foreach( $array as $key => $value ) {
            $this->setConfig( $key, $value );
        }
    }


    protected function getDocument(
        $width,
        $height,
        $zoom,
        $color,
        $body
    ) {
        return sprintf(
            static::$document,
            $width,
            $height,
            ( $width * $zoom ),
            ( $height * $zoom ),
            $color,
            $body
        );
    }


    protected function updateLayout() {
        $num = $this->database->count();
        $this->config[ 'rows' ] = ceil( sqrt( $num ) );
        $this->config[ 'cols' ] = ceil( $num / $this->config[ 'rows' ] );
    }


}

?>