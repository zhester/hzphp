<?php
/*****************************************************************************

A Simple Event Log Abstraction
==============================

Makes it easy to add and enable/disable development or tracking events to user
code.  This is helpful when output to a browser is not possible.

*****************************************************************************/

/*----------------------------------------------------------------------------
Default Namespace
----------------------------------------------------------------------------*/

namespace hzphp\Util;

/*----------------------------------------------------------------------------
Dependencies
----------------------------------------------------------------------------*/

/*----------------------------------------------------------------------------
Classes
----------------------------------------------------------------------------*/

/**
 * A generic interface for event logs.
 */
interface IEventLog {

    public function __toString();
    public function assrt( $assertion, $message );
    public function commit();
    public function enable( $enable );
    public function is_enabled();
    public function put( $message );
    public function trace( $message );
    public function trace_from( $message, $trace );

}


/**
 * Allows a low-resource version of the EventLog interface to be safely
 * substituted in production code.
 */
class DummyLog implements IEventLog {

    /*------------------------------------------------------------------------
    Public Methods
    ------------------------------------------------------------------------*/

    /**
     * Represents the object state as a string.
     *
     * @return A string representing the object
     */
    public function __toString() { return ''; }


    /**
     * Trap requests for undefined public properties.
     */
    public function __isset( $key ) { return false; }
    public function __get( $key ) { return null; }
    public function __set( $key, $value ) {}


    /**
     * Support the interface with dummy methods.
     */
    public function assrt( $assertion, $message ) {}
    public function commit() {}
    public function enable( $enable ) {}
    public function is_enabled() { return false; }
    public function put( $message ) {}
    public function trace( $message ) {}
    public function trace_from( $message, $trace ) {}

}


/**
 * Provides a simple interface to logging events from user code.
 *
 */
class EventLog implements IEventLog {

    /*------------------------------------------------------------------------
    Public Properties
    ------------------------------------------------------------------------*/

    //global enable/disable setting for all event logs
    //  Set to false to only create DummyLog instances.
    //  Set to true to allow normal EventLog instances (each can be
    //  independently enabled/disabled).
    public static $create = true;

    //use this directory for relative file paths (when using file names)
    public static $logdir = '__TEMP__';

    //amount of path (number of directories) to log before file name paths
    public $dirnames = 2;


    /*------------------------------------------------------------------------
    Protected Properties
    ------------------------------------------------------------------------*/

    //current enable state
    protected $enable = true;

    //target log file resource, name, StreamIO instance, etc.
    protected $file = null;

    //whether or not to skip in-memory logging
    protected $flush = false;

    //temporary logging file handle
    protected $stream = null;


    /*------------------------------------------------------------------------
    Private Properties
    ------------------------------------------------------------------------*/

    /*------------------------------------------------------------------------
    Public Methods
    ------------------------------------------------------------------------*/

    /**
     * Creates an EventLog (or DummyLog) based on the value of
     *
     * Factory static method for creating event log instances.  See the
     * constructor's documentation for more information.
     *
     * @param log_file (see `__construct()`)
     * @param enable   (see `__construct()`)
     * @param flush    (see `__construct()`)
     * @return         A new object implementing the IEventLog interface
     */
    public static function create_log(
        $log_file,
        $enable = true,
        $flush  = false
    ) {
        if( self::$create == true ) {
            return new EventLog( $log_file, $enable, $flush );
        }
        return new DummyLog();
    }


    /**
     * Represents the object state as a string.
     *
     * @return A string representing the object
     */
    public function __toString() {
        $this->stream->seek( 0, SEEK_SET );
        return $this->stream->read();
    }


    /**
     * Adds a trace entry to the log only if the assertion fails.
     *
     * @param assertion The result of the user's assertion.  If the assertion
     *                  fails (false), the message is logged.  Otherwise, the
     *                  message is ignored.
     * @param message   The message to log if the assertion fails
     */
    public function assrt( $assertion, $message ) {
        if( $assertion == false ) {
            $this->trace_from( $message, debug_backtrace() );
        }
    }


    /**
     * Commits the contents of the in-memory log to the user's target file.
     *
     */
    public function commit() {

        //do not commit unless enabled, and not using auto-flush
        if( ( $this->enable == true ) && ( $this->flush == false ) ) {

            //create a target stream to the user's file
            $target = \hzphp\IO\StreamIO::createStream( $this->file, 'w' );

            //rewind the in-memory log, and write to the user's file
            $size = $this->stream->tell();
            $this->stream->seek( 0, SEEK_SET );
            $target->write( $this->stream->read( $size ) );
        }
    }


    /**
     * Enables/disables logging from user code.  Provided to allow user code
     * to maintain use of the logging class/instance within code and globally
     * toggle actual log output.
     *
     * @param enable Set to false to disable logging
     */
    public function enable( $enable = true ) {
        $this->enable = $enable;
    }


    /**
     * Indicates if the log is currently enabled.
     *
     * @return True if the log is enabled, otherwise false.
     */
    public function is_enabled() {
        return $this->enable;
    }


    /**
     * Puts a new entry in the log.
     *
     * @param message A message to add to the end of the log.  This should not
     *                include a new-line character at the end.  This will
     *                attempt to serialize non-string data and objects.
     */
    public function put( $message ) {

        //check enable state
        if( $this->enable == false ) {
            return;
        }

        //log the message to the output stream
        $this->stream->write( $this->format_message( $message ) . "\n" );
        $this->stream->flush();
    }


    /**
     * Logs a message in the log with backtrace information.
     *
     * @param message The message to log
     */
    public function trace( $message ) {
        $this->trace_from( $message, debug_backtrace() );
    }


    /**
     * Logs a trace entry from a given backtrace.
     *
     * @param message
     * @param trace
     */
    public function trace_from( $message, $trace ) {

        //get information about the caller
        $index = count( $trace ) - 1;
        $caller = $trace[ $index ];

        //set the defaults we want to log
        $file = $caller[ 'file' ];
        $line = $caller[ 'line' ];
        $func = $caller[ 'function' ];

        //called from global context
        if( $index == 0 ) {
            $func = '__MAIN__';
        }

        //see if we were called from a class/object
        else if( isset( $caller[ 'class' ] ) ) {
            $line = $trace[ $index - 1 ][ 'line' ];
            $func = $caller[ 'class' ] . $caller[ 'type' ]
                . $caller[ 'function' ];
        }

        //see if were called from inside a global function
        else if( $index > 0 ) {
            $line = $trace[ $index - 1 ][ 'line' ];
        }

        //check for file path adjustment
        if( $this->dirnames >= 0 ) {
            $file = implode(
                '/',
                array_slice(
                    explode( '/', $file ),
                    ( -1 * ( $this->dirnames + 1 ) )
                )
            );
        }

        //format a log entry with code context information
        $output = "$file:$line,$func> " . $this->format_message( $message );

        //add the log entry to the log
        $this->put( $output );
    }



    /*------------------------------------------------------------------------
    Protected Methods
    ------------------------------------------------------------------------*/

    /**
     * EventLog constructor.
     *
     * Since this abstraction is intended to allow a global enable flag, there
     * are a few use-cases to consider when constructing an EventLog instance.
     * If the log is to be enabled the entire time, it's probably best to set
     * the `flush` value in the constructor.  This skips memory logging, and
     * writes directly to the file.  If the log may be disabled in the future
     * (e.g. to leave user calls in place), do NOT set the `flush` value.
     * Instead, make all user calls (toggling enable/disable as needed), then
     * use the `commit()` method to dump the in-memory log to the file.
     *
     * @param log_file The target file resource or name for logging events
     * @param enable   The initial log enable state
     * @param flush    Set to enable automatic flushing to the file
     */
    protected function __construct( $log_file, $enable = true, $flush = false ) {

        //set the user's logging target file (resource, name, etc.)
        $this->file = $log_file;

        //set the initial enable state
        $this->enable = $enable;

        //set the flush setting
        $this->flush = $flush;

        //see if the class's logdir property needs to be set up
        if( self::$logdir == '__TEMP__' ) {
            $tmpdir = sys_get_temp_dir();
            self::$logdir = $tmpdir . '/hzphp';
            if( is_dir( self::$logdir ) == false ) {
                mkdir( self::$logdir );
            }
        }

        //see if the user's logging target appears to be a file name with a
        //  relative path
        if( is_string( $this->file ) && ( $this->file[ 0 ] != '/' ) ) {

            //prepend the logging directory to it
            $this->file = self::$logdir . '/' . $this->file;
        }

        //see if the user wants to avoid in-memory logging
        if( $this->flush == true ) {
            $this->stream = \hzphp\IO\StreamIO::createStream(
                $this->file, 'w+'
            );
        }

        //the user might mess with the enable setting, set up a memory file
        else {

            //open a temporary file stream for immediate logging (auto-closes
            //  when the StreamIO object is garbage-collected)
            $this->stream = new \hzphp\IO\StreamIO( 'php://temp', 'w+' );
        }
    }


    /**
     * Formats message arguments into strings for insertion into the log.
     *
     * @param message The message string, object, array, value, to format
     * @return        A string representation of the message
     */
    protected function format_message( $message ) {

        //see if the message is not a string
        if( is_string( $message ) == false ) {

            //message is an object
            if( is_object( $message ) ) {

                //message can be turned into JSON
                if( $message instanceof \JsonSerializable ) {
                    $message = json_encode( $message );
                }

                //messsage can be converted into a string
                else if( method_exists( $message, '__toString' ) ) {
                    $message = strval( $message );
                }

                //message can be serialized into a string
                else if( $message instanceof \Serializable ) {
                    $message = serialize( $message );
                }

                //try default string conversion
                else {
                    $message = strval( $message );
                }
            }

            //message is an array
            else if( is_array( $message ) ) {

                //convert message into a JSON string
                $message = json_encode( $message );
            }

            //message is something else
            else {

                //try default string conversion
                $message = strval( $message );
            }
        }

        //return the formatted message string
        return $message;
    }


    /*------------------------------------------------------------------------
    Private Methods
    ------------------------------------------------------------------------*/

}



/*----------------------------------------------------------------------------
Functions
----------------------------------------------------------------------------*/

/*----------------------------------------------------------------------------
Execution
----------------------------------------------------------------------------*/

if( $_SERVER[ 'SCRIPT_FILENAME' ] == __FILE__ ) {

    header( 'Content-Type: text/plain; charset=utf-8' );

    require __DIR__ . '/../tools/loader.php';

    //some stack containers for testing tracing
    function user_fun( $log ) {
        $log->trace( 'Inside a function.' );
    }
    class UserClass {
        public function user_meth( $log ) {
            $log->trace( 'Inside a method.' );
        }
        public static function user_static( $log ) {
            $log->trace( 'Inside a static method.' );
        }
    }

    //temporary file for directing log output
    $tmp = tmpfile();

    //create an event log for basic testing
    $log = EventLog::create_log( $tmp );

    //log messages various ways
    $log->put( 'Message A' );
    $log->put( 'Message B' );
    $log->trace( 'Message Trace' );
    $log->assrt( true, 'Message Assert (true)' );
    $log->assrt( false, 'Message Assert (false)' );
    user_fun( $log );
    $user = new UserClass();
    $user->user_meth( $log );
    UserClass::user_static( $log );

    //by default, we need to request a commit at the end
    $log->commit();

    //dump the temporary file to the output
    $size = ftell( $tmp );
    if( $size > 0 ) {
        rewind( $tmp );
        echo fread( $tmp, $size );
    }
    else {
        echo "(nothing logged)\n";
    }

    //reset the temporary file
    rewind( $tmp );
    ftruncate( $tmp, 0 );

    //create an event log for non-caching testing
    $log = EventLog::create_log( $tmp, true, true );

    //add some entries to the log
    $log->put( 'Message C' );
    $log->put( 'Message D' );

    //dump the temporary file to the output
    $size = ftell( $tmp );
    if( $size > 0 ) {
        rewind( $tmp );
        echo fread( $tmp, $size );
    }
    else {
        echo "(nothing logged)\n";
    }

    //test the global disable feature
    EventLog::$create = false;
    rewind( $tmp );
    ftruncate( $tmp, 0 );
    $log = EventLog::create_log( $tmp, true, true );
    $log->put( 'Message E' );
    $size = ftell( $tmp );
    if( $size == 0 ) {
        echo "Successfully disabled the event log.\n";
    }
    else {
        echo "Did not disable the event log.\n";
    }

}

