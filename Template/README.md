Minimal Templating Module
=========================

This provides an extremely limited templating mechanism.  This only be used
with small strings that don't require streamed parsing (everything is parsed
at once using regular expressions).

Allows a string to map simple sub-strings to program data from either
associative arrays or objects.  The format uses double curly braces to
specify a binding string.  The binding string may be used to dereference
descendant array elements, object properties, or object methods.

Simple Example
--------------

    $t = '<h1>{{title}}</h1>';

    $d = array( 'title' => 'Document Title' );

    echo hzphp\Template\Engine::srender( $t, $d );

Better Example
--------------

    
