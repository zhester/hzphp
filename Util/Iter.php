<?php
/****************************************************************************

Reduced Iterator Interface
==========================

The PHP Iterable interface requires quite a bit of code to bring up.  The SPL
variants help, but nothing is quite as clean as I need it.  This module
provides a class to help reduce the amount of implementation needed to support
built-in iteration over an object.

****************************************************************************/

/*---------------------------------------------------------------------------
Default Namespace
---------------------------------------------------------------------------*/

namespace hzphp\Util;

/*---------------------------------------------------------------------------
Dependencies
---------------------------------------------------------------------------*/

//The iteration system operates in one of three modes.
define( '_ITER_MODE_NORMAL'   , 0 );    //Front-ending an iterable object
define( '_ITER_MODE_ARRAY'    , 1 );    //Automatically handling an array
define( '_ITER_MODE_PASSTHRU' , 2 );    //Front-ending an \Iterator object


/*---------------------------------------------------------------------------
Classes
---------------------------------------------------------------------------*/


/**
 * Signals Ends of Iteration
 */
class IteratorStop extends \Exception {}



/**
 * Iterable Object Interface
 */
interface Iterable {

    /**
     * Implemented by the inheriting class when an iterator is needed.
     *
     * @return An object that can successively retrieve data items.
     *         If there is no data to iterate, returns null.
     *         This can also return any \Iterator object or native array.
     */
    public function __iter__();


    /**
     * Implemented by the inheriting class to retrieve the next value.
     *
     * @return The next item in the iterable object.
     * @throws IteratorStop exception when there are no more items.
     */
    public function __next__();

}



/**
 * Reduced Iterator
 */
abstract class Iterator implements \Iterator {


    /**
     * Implemented by the inheriting class when an iterator is needed.
     *
     * @return An object that can successively retrieve data items.
     *         If there is no data to iterate, returns NULL or an empty array.
     */
    abstract public function __iter__();


    /**
     * Retrieves the current value from the object.
     *
     * @return The value of the current item in the object
     */
    public function current() {

        //Iterator is in pass-through mode.
        if( $this->_iter_mode == _ITER_MODE_PASSTHRU ) {
            return $this->_iter->current();
        }

        //Iterator is in array or normal mode.
        return $this->_iter_value;
    }


    /**
     * Retrieves the current key from the object.
     *
     * @return The key of the current item in the object
     */
    public function key() {

        //Iterator is in pass-through mode.
        if( $this->_iter_mode == _ITER_MODE_PASSTHRU ) {
            return $this->_iter->key();
        }

        //Iterator is in array or normal mode.
        return $this->_iter_cursor;
    }


    /**
     * Moves internal iterator cursor to next item in object.
     *
     */
    public function next() {

        //Iterator is in pass-through mode.
        if( $this->_iter_mode == _ITER_MODE_PASSTHRU ) {
            $this->_iter->next();
        }

        //Iterator is in array mode.
        else if( $this->_iter_mode == _ITER_MODE_ARRAY ) {
            $this->_iter_cursor += 1;
            if( $this->_iter_cursor >= count( $this->_iter ) ) {
                $this->_iter_valid = false;
            }
            else {
                $this->_iter_value = $this->_iter[ $this->_iter_cursor ];
            }
        }

        //Iterator is in normal mode.
        else {
            $this->_iter_cursor += 1;
            try {
                $this->_iter_value = $this->_iter->__next__();
            }
            catch ( IteratorStop $is ) {
                $this->_iter_valid = false;
            }
        }
    }


    /**
     * Moves the internal iterator cursor to the first item in the object.
     *
     */
    public function rewind() {

        //Request the user object's iterator instance.
        $this->_iter = $this->__iter__();

        //Check for an empty sequence.
        if( $this->_iter === null ) {
            $this->_iter_valid = false;
        }

        //Check for existing \Iterator support.
        else if( !( $this->_iter instanceof Iterator  )
            and   ( $this->_iter instanceof \Iterator ) ) {
            $this->_iter_mode = _ITER_MODE_PASSTHRU;
            $this->_iter->rewind();
        }

        //Check for array handling.
        else if( is_array( $this->_iter ) ) {
            $this->_iter_mode = _ITER_MODE_ARRAY;
            if( count( $this->_iter ) > 0 ) {
                $this->_iter_valid  = true;
                $this->_iter_cursor = 0;
                $this->_iter_value  = $this->_iter[ 0 ];
            }
            else {
                $this->_iter_valid = false;
            }
        }

        //Assume the user object needs to be iterable.
        else {
            $this->_iter_mode   = _ITER_MODE_NORMAL;
            $this->_iter_valid  = true;
            $this->_iter_cursor = 0;
            try {
                $this->_iter_value = $this->_iter->__next__();
            }
            catch ( IteratorStop $is ) {
                $this->_iter_valid = false;
            }
        }
    }


    /**
     * Checks if the cursor is currently referencing a valid item in the
     * object.
     *
     * @return true if the cursor is at a valid item
     */
    public function valid() {

        //Iterator is in pass-through mode.
        if( $this->_iter_mode == _ITER_MODE_PASSTHRU ) {
            return $this->_iter->valid();
        }

        //Iterator is in array or normal mode.
        return $this->_iter_valid;
    }


}


/*---------------------------------------------------------------------------
Functions
---------------------------------------------------------------------------*/

/*---------------------------------------------------------------------------
Execution
---------------------------------------------------------------------------*/

if( realpath( $_SERVER[ 'SCRIPT_FILENAME' ] ) == __FILE__ ) {

    header( 'Content-Type: text/plain; charset=utf-8' );

    class TestSimpleIterator extends Iterator implements Iterable {
        public function __construct( $array ) {
            $this->array = $array;
        }
        public function __iter__() {
            return $this;
        }
        public function __next__() {
            if( $this->_iter_cursor >= count( $this->array ) ) {
                throw new IteratorStop();
            }
            return $this->array[ $this->_iter_cursor ];
        }
    }

    class TestArrayIterator extends Iterator {
        public function __construct( $array ) {
            $this->array = $array;
        }
        public function __iter__() {
            return $this->array;
        }
    }

    class MyIterator implements \Iterator {
        public function __construct( $array ) {
            $this->array = $array;
        }
        public function current() {
            return $this->array[ $this->index ];
        }
        public function key() {
            return $this->index;
        }
        public function next() {
            $this->index += 1;
        }
        public function rewind() {
            $this->index = 0;
        }
        public function valid() {
            return $this->index < count( $this->array );
        }
    }

    class TestPassIterator extends Iterator {
        public function __construct( $array ) {
            $this->iter = new MyIterator( $array );
        }
        public function __iter__() {
            return $this->iter;
        }
    }

    $test_set = [ 8, 6, 7, 5, 3, 0, 9 ];

    $test_cases = [
        [ 'Simple', new TestSimpleIterator( $test_set ) ],
        [ 'Array',  new TestArrayIterator( $test_set )  ],
        [ 'Pass',   new TestPassIterator( $test_set )   ]
    ];

    foreach( $test_cases as $test_case ) {

        echo "$test_case[0]\n";

        $object = $test_case[ 1 ];

        $limit = 20;

        foreach( $object as $key => $value ) {

            if( $limit < 0 ) {
                break;
            }
            $limit -= 1;

            echo "$key : $value\n";

        }

    }

}

