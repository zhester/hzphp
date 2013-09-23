Request
=======

The Request module is designed to formalize the interfaces and methods of
handling a request by mapping request parameters to the correct piece of code
that can respond to the request.

To use the Request module in an application, the following must be specified
by the user:

* A request map listing where to find code for the different requests
* One or more request handlers to process and respond to requests

Interface Example
-----------------

Here's the basic "Hello World" example:

    require 'hzphp/tools/loader.php';

    class MyHandler extends hzphp\Request\Handler {
        private         $eof = false;
        public function headers() {
            return [ 'Content-Type' => 'text/plain; charset=utf-8' ];
        }
        public function read() {
            if( $this->eof == false ) {
                $this->eof = true;
                return 'Hello World';
            }
            return false;
        }
    }

    $map = new hzphp\Request\Map( [ [ '', 'MyHandler' ] ] );

    $request = new hzphp\Request\Request( $map );

    $response = $request->handlePath( '' );

    $response->send();

The example demonstrates that each step of handling a request is isolated
from the details of the application behind it.  Generally speaking, once the
user has set up their own request parameter/path mapping, the application can
be quickly extended by adding to the request map, and implementing any
necessary request handler objects.

Here's a simpler method for generating content (if, a little less organized):

    require 'hzphp/tools/loader.php';

    function greet() {
        return 'Hello World';
    }

    $map = new hzphp\Request\Map( [ [ '', 'greet()' ] ] );

    $request = new hzphp\Request\Request( $map );

    $response = $request->handlePath( '' );

    $response->send();

This example shows a more brief syntax for using this system, but it is less
flexible in that regular expressions would need to be used to pass arguments
into the function:

    function greet2( $location ) { return "Hello $location"; }
    $map = new hzphp\Request\Map( [ [ '#(\w+)#', 'greet2($1)' ] ] );

For reasons of basic security, you may not map to any built-in function.  The
function name specified in the target must always be a user-defined function
(which, then, may call all the built-in functions you like).
