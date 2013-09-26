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

User-level Organization
-----------------------

The most important requirement is that the user's code is well-organized, and
easy to navigate and maintain.  Therefore, this module will be heavily focused
on making it simple to co-locate all the queries needed to deal with a
particular level of data organization (be it table, node, file, etc).

### Concepts ###

The DBI module may not always be aware of database tables, or even the queries
being used to access it.  Instead, the module assumes the user can handle that
level of coordination, and just presents a consistent interface to
establishing relationships between the stored data and the presentation layer.

Therefore, it's necessary to abstract away concepts of _tables_, _records_,
_arrays_, and _objects_.  Instead, the user will interact with the database
via the concepts of _elements_, _tuples_, and _sets_.

* *Elements* are a single record or object.
* *Tuples* are finite collections of homogeneous Elements.
* *Sets* are potentially very large collections of homogeneous Elements.

Breaking with set theory terminology, it is assumed that order is always
preserved (regardless if it's a tuple or set).

### Organization ###

For each presentable object in the database, the user will need to provide the
following implementation details:

* How to insert an element into a set
* How to update an element
* How to delete an element
* How to retrieve a tuple and/or set of elements

These details are broken down into two types: mutation and retrieval.  This
gives the user a way to isolate critical queries from the mundane.

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

### Element Mutation ###

The user should extend the ElementMutator class.  Then, there are three ways
to provide the expected queries to the module's interface.

#### 1. Static Query Templates ####

    class MyElementEditor extends hzphp\DBI\ElementMutator {
        public static   $dbi_queries = [
            'insert' : [ 'insert into my_table (name) values ({{name}})' ],
            'update' : [ 'update my_table set name = {{name}}'
                          . ' where id = {{id}} limit 1' ],
            'delete' : [ 'delete from my_table where id = {{id}}' ]
        ];
    }

#### 2. Dynamic Query Templates ####

    class MyElementEditor extends hzphp\DBI\ElementMutator {
        public function getInsert() {
            return [ 'insert into my_table (name) values ({{name}})' ];
        }
        public function getUpdate() {
            return [ 'update my_table set name = {{name}} where id = {{id}}' ];
        }
        public function getDelete() {
            return [ 'delete from my_table where id = {{id}}' ];
        }
    }

#### 3. Automatic Query Generation ####

This only works when managing trival elements that map 1:1 with records.

    class MyElementEditor extends hzphp\DBI\ElementMutator {
        public static   $dbi_autosql = [
            'table'  : 'my_table',
            'id'     : 'id',
            'insert' : [ 'name' ],
            'update' : [ 'name' ],
            'delete' : true
        ];
    }

Note: Each technique provides access to an array of queries.  When mutating,
the queries are executed in order.  If any of the queries fails, the
transation is automatically rolled back.

Note: Update and delete queries are automatically appended with a "limit 1"
clause if it isn't included in the query.  This may be overridden with a
per-element configuration variable (public static $dbi_config; more later).

### Element Retrieval ###

    class MyElement extends hzphp\DBI\Element {
        public function getSelect() {
            return 'select id,name from my_table where id = {{id}}';
        }
    }

### Set Retrieval ###

    class MySet extends hzphp\DBI\Set {
        public function getSelect() {
            return 'select id,name from my_table order by name';
        }
    }

### Mutation Execution ###

These methods acquire (or build) queries using the user's implementation.
Then, they execute the queries using the appropriate super global array as the
source for arguments into the database's API.  That means, if you need to
perform some re-formatting on the data, just update the (in this case) _POST
super global array before calling the DBI static method.

    $result = hzphp\DBI\DBI::insertFromSuper( 'MyElementEditor' );
    $result = hzphp\DBI\DBI::updateFromSuper( 'MyElementEditor' );
    $result = hzphp\DBI\DBI::deleteFromSuper( 'MyElementEditor' );

### Retrieval Execution ###

These methods do the same thing as the mutation methods.  The difference here
is that they return a thin wrapper object on top of the object that is
retrieved from the database's API.  In the case of an element or tuple, the
data is immediately available (without calling a fetch).

    //fetches an hzphp\DBI\Element instace
    $element = hzphp\DBI\DBI::loadFromSuper( 'MyElement' );

    //fetches an hzphp\DBI\Set instance
    $set = hzphp\DBI\DBI::loadFromSuper( 'MySet' );
