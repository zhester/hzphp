<?php

namespace hzphp\Icon;


/**
 *  Icon Database Manager
 */
class Database implements \ArrayAccess {


    public              $ids = [];  //list of all available IDs


    protected static    $icons   = [];
    protected static    $size    = 32;
    protected static    $unknown = [
        'm 4.8,4.8 0,22.4 22.4,0 0,-22.4 z m 10.959375,3.7361543 c 2.128685,-0.082259 4.460356,1.1712156 5.218744,3.2791867 0.568442,1.441251 -0.06514,3.049214 -1.183044,4.031015 -0.818371,0.945212 -1.867213,1.531119 -2.453296,2.664255 -0.302426,0.602713 -0.162318,1.514389 -0.162318,1.514389 0,0 -2.119817,0 -2.776336,0 -0.137658,-1.391376 0.132554,-2.930105 1.249877,-3.878171 0.798892,-0.874803 1.981932,-1.409999 2.566317,-2.465626 0.581326,-1.28063 -0.576619,-2.731136 -1.917995,-2.735925 -2.326971,-0.116055 -3.25674,1.709686 -3.375384,2.669116 0,0 -1.937067,-0.407018 -2.52594,-0.480019 0.720868,-2.929042 2.992566,-4.5153346 5.359375,-4.5982207 z M 14.403125,21.03125 c 0.955208,0 1.910417,0 2.865625,0 0,0.955208 0,1.910417 0,2.865625 -0.955208,0 -1.910417,0 -2.865625,0 0,-0.955208 0,-1.910417 0,-2.865625 z'
    ];


    /**
     *  Constructor
     *
     */
    public function __construct() {
        $this->ids = array_keys( static::$icons );
    }


    /**
     *  Returns the number of icons in the database.
     *
     *  @return         Number of icons in the database
     */
    public function count() {
        return count( $this->ids );
    }


    /**
     *  Returns the design size of the icons in the database
     *
     *  @return         Icon design size (in pixels)
     */
    public function getSize() {
        return static::$size;
    }


    /**
     *  Implements existence check for ArrayAccess.
     *
     *  @param id       The icon ID to check
     *  @return         True if the ID is in the database, otherwise false
     */
    public function offsetExists(
        $id
    ) {
        return isset( static::$icons[ $id ] );
    }


    /**
     *  Implements retrieval for ArrayAccess.
     *
     *  @param id       The icon ID to retrieve
     *  @return         An instance of an Icon object for the given ID
     */
    public function offsetGet(
        $id
    ) {
        $uid = '_unknown';
        if( in_array( $id, $this->ids ) == true ) {
            return new Icon( $id, static::$icons[ $id ], static::$size );
        }
        else if( in_array( $uid, $this->ids ) == true ) {
            return new Icon( $uid, static::$icons[ $uid ], static::$size );
        }
        return new Icon( $uid, self::$unknown, self::$size );
    }


    /**
     *  Implements modification for ArrayAccess.
     *
     *  @param id       The icon ID to modify
     *  @param value    The new icon path information as an array of strings
     */
    public function offsetSet(
        $id,
        $value
    ) {
        static::$icons[ $id ] = $value;
    }


    /**
     *  Implements destruction for ArrayAccess.
     *
     *  @param id       The icon ID to destroy
     */
    public function offsetUnset(
        $id
    ) {
        unset( static::$icons[ $id ] );
    }


}

?>