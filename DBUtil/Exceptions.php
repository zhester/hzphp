<?php

/*----------------------------------------------------------------------------
Namespace
----------------------------------------------------------------------------*/

namespace hzphp\DBUtil;


/*----------------------------------------------------------------------------
Exceptions
----------------------------------------------------------------------------*/

/**
 *  Base exception emitted by this module
 *
 */
class Exception extends \RuntimeException {}


/**
 *  Exception emitted when this module encounters database errors
 *
 */
class DatabaseException extends Exception {}


/**
 *  Exception emitted when this module feels it's being used improperly
 */
class UsageException extends Exception {}

