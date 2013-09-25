Database Interface
==================

Objective
---------

Provide a very thin interface between the presentation layer and the source of
the data being presented.  It should be noted that this should not become a
complete ORM solution.  I'm also not looking to "hide" the database API.

It is my intention to provide a common mechanism to abstract most/all queries
behind parameterized interfaces.  The basic tenants of the DBI module are:

### Retrieval ###

* Simple objects may be hard-loaded from the database with minimal code.
* Complex objects may be lazy-loaded from the database.
* Collections of objects are always accessed through iterative retrieval (not
    large arrays/strings returned from methods).

### Mutation ###

* All SQL queries for a particular object (table) are "near" each other in the
    code.
* All sources of information use to construct a query are automatically
    type/bounds checked, and are passed to the database API through the
    appropriate value injection (not just string concatenation).

### Abstraction ###

* The source of the data should not be the concern of the consumers (the
   consumer doesn't need to know how to connect to the DB, or that there's
   even a connection happening).
* Future implementations may include the ability to prefix all table names.
* Future implementations may include the ability to adapt to changes in the
    schema.

Desired Coding Conventions
--------------------------

### Boilerplate ###

Most applications use a common bit of boilerplate to get off the ground in a
known state.  In this case, the module will need the basics of how to connect
to the databse.  A set of static helper methods may be provided to initialize
the module's internal state.

    require 'hzphp/tools/loader.php';
    hzphp\DBI\DBI::init( 'mysqli://user:pass@hostname/database' );

Multiple simultaneous connections should be supported.  These should be done
with a "tagging" system to allow a common name space to be shared between all
connections, regardless of the internal details:

    $db = hzphp\DBI\DBI::init( 'mysqli://user:pass@hostname/database' );
    $db->alias( 'mysql' );
    $db = hzphp\DBI\DBI::init( 'sqlite:///path/to/file.sqlite' );
    $db->alias( 'file' );
    $db = hzphp\DBI\DBI::init( 'sqlite:///path/to/file2.sqlite' );
    $db->alias( 'file2' );

### Single Object Retrieval ###

The user should extend the ObjectMeta class.  This will require the user to
define two methods:

    class MyObject extends hzphp\DBI\ObjectMeta {
        public static   $dbi_parameters = [
            //ZIH - not sure what/how to specify parameters yet
            'id' => [ ???? ]
        ];
        public function dbi_getParameters() {
            return self::$dbi_parameters;
        }
        public function dbi_getSelect( Array $parameters ) {
            return hzphp\DBI\Query::buildSelectOne(
                'my_object',
                $parameters
            );
        }
    }

Once the module can query the user's code to find out how to query for the
object, it can be instantiated and used as a record directly:

    $my_object = new MyObject( [ 'id' => 42 ] );
    echo $my_object[ 'description' ];

Note: We're relying on ObjectMeta's constructor to do a lot of heavy lifting
here.  If it gets overridden, the user is on the hook for making sure it's
invoked correctly.


