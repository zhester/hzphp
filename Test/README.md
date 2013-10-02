hzphp\Test
==========

Unit, integration, and regression test framework for hzphp modules.

Unit Testing
------------

Unit testing requires writing a test script to execute the code under test.
The test script is a user-defined method of a class that extends, at least,
the hzphp\Test\Test class.  It is better, however, to extend the
hzphp\Test\UnitTest class.

You will be required to implement a method named `run()` that contains the
unit test code.

If you have a lot of state to keep around, you should also override a method
named `reset()` to get automatic state resets when you request a test section
heading.

Integration Testing
-------------------

_coming soon_

Regression Testing
------------------

_coming soon_

Execution
---------

Test execution is a matter of instantiating the Executor class, binding it
to one or more unit or regression tests, then running it:

    $exec = new hzphp\Test\Executor();
    $test = new myTestClass(); //must be a descendent of hzphp\Test\Test
    $exec->addTest( $test );
    if( $exec->runTests() == true ) {
        echo 'test passed!'; //redundant since there's all kinds of output
    }

By default, the test report is HTML-formatted and sent to the HTTP output
buffer.  This can be changed to send plain text over HTTP, or log either HTML
or plain text to a file (if file system access is available).

    //before calling $exec->runTests() ...
    $exec->setOutput( hzphp\Test\Executor::PLAIN );
    // -- or --
    $exec->setOutput( hzphp\Test\Executor::HTML );
    // -- or --
    $exec->setOutput( hzphp\Test\Executor::LOG_PLAIN, 'path/to/log.txt' );
    // -- or --
    $exec->setOutput( hzphp\Test\Executor::LOG_HTML, 'path/to/log.html' );
    // -- or --
    $exec->setOutput( hzphp\Test\Executor::NONE );

Test Setup Guide
----------------

_coming soon_
