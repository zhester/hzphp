<?php
/****************************************************************************

Token Modeling
==============

****************************************************************************/

/*---------------------------------------------------------------------------
Default Namespace
---------------------------------------------------------------------------*/

namespace hzphp\Text;

/*---------------------------------------------------------------------------
Dependencies
---------------------------------------------------------------------------*/

/*---------------------------------------------------------------------------
Classes
---------------------------------------------------------------------------*/

/**
 * Models a lexical token.  Usually used for parsing strings.
 */
class Token {

    public $ident    = null;
    public $value    = null;
    public $position = null;

    /**
     * Token Constructor
     */
    public function __construct(
        $ident,
        $value,
        $offset = -1,
        $line   = -1,
        $column = -1
    ) {
        $this->ident    = $ident;
        $this->value    = $value;
        $this->position = new TokenPosition( $offset, $line, $column );
    }

}


/**
 * Field container for Token object's position information.
 */
class TokenPosition {

    public $offset = null;
    public $line   = null;
    public $column = null;

    /**
     * TokenPosition Constructor
     */
    public function __construct( $offset, $line, $column ) {
        $this->offset = $offset;
        $this->line   = $line;
        $this->column = $column;
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

    

}

