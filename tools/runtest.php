<?php
/*****************************************************************************

    runtest.php

    Convenience script that can be used to run tests created by the hzphp\Test
    framework.

    Note: This is currently designed to be called from an HTTP request.  It
    should also be usable through the CLI in the future.

    To execute a particular unit test:

        http://domain/path/runtest.php?unit=ModuleName/ClassName

*****************************************************************************/

$hzphp_path = dirname( __DIR__ );

require $hzphp_path . '/tools/loader.php';

$has_test       = false;
$index          = false;
$script         = $_SERVER[ 'SCRIPT_NAME' ];
$title          = 'hzphp Testing';
$unit           = '**unknown**';
$unit_test_path = "$hzphp_path/tests/unit";


if( isset( $_GET[ 'unit' ] ) == true ) {

    if( preg_match( '/^(\w+)\\/(\w+)$/', $_GET[ 'unit' ], $matches ) == 1 ) {

        $unit   = $_GET[ 'unit' ];
        $mname  = $matches[ 1 ];
        $tname  = $matches[ 2 ];
        $module = "$hzphp_path/$mname";
        $test   = "$unit_test_path/$mname/$tname.php";

        if( ( file_exists( $module ) == true )
         && (      is_dir( $module ) == true )
         && ( file_exists( $test   ) == true ) ) {

            $has_test = true;
            $title    = "$mname/$tname - $title";
            require $test;

        }

    }

}

else {

    $nodes = [];
    $dh = opendir( $unit_test_path );
    if( $dh !== false ) {
        while( ( $node = readdir( $dh ) ) !== false ) {
            $path = "$unit_test_path/$node";
            if( ( $node[ 0 ] != '.' ) && ( is_dir( $path ) == true ) ) {
                $sdh = opendir( $path );
                while( ( $snode = readdir( $sdh ) ) !== false ) {
                    if( ( $snode[ 0 ] != '.'             )
                     && ( substr( $snode, -4 ) == '.php' ) ) {
                        $nodes[] = "$node/$snode";
                    }
                }
                closedir( $sdh );
            }
        }
        closedir( $dh );
        sort( $nodes );
        if( count( $nodes ) > 0 ) {
            $unit_test_links = [];
            foreach( $nodes as $node ) {
                $n = substr( $node, 0, -4 );
                $unit_test_links[] = "<a href=\"$script?unit=$n\">$n</a>";
            }
            $index = "<h2>Unit Tests</h2>\n<ul>\n<li>"
                . implode( "</li>\n<li>", $unit_test_links )
                . "</li>\n</ul>\n";
        }
        else {
            $index = '<p>No unit tests found in'
                . " <code>$unit_test_path</code> directory.</p>\n";
        }
    }
    else {
        $index = "<p>Unable to check for unit tests in"
               . " <code>$unit_test_path</code>.</p>\n";
    }

}

?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?php echo $title; ?></title>
  <style type="text/css">
body {
    margin: 0;
    padding: 0;
    font: 10pt/110% 'Source Sans Pro', Arial, sans-serif;
    color: #333333;
    background-color: #FFFFFF;
}
h1, h2, h3, h4, h5, h6, p, address, blockquote,
pre, ul, ol, li, dl, form, table {
    margin: 0.5em 0 0.25em 0;
    padding: 0;
}
li, blockquote {
    margin-left: 2em;
    margin-right: 2em;
}
h1 { font-size: 150%; }
h2 { font-size: 140%; }
h3 { font-size: 130%; }
h4 { font-size: 120%; }
h5 { font-size: 110%; }
h6 { font-size: 100%; }
blockquote { padding: 0 3em; }
table {
    margin-bottom: 0;
    border-collapse: collapse;
}
    table td {
        margin: 0;
        padding: 0.1em 2em;
        border: solid 1px #CCCCCC;
    }
    table tr:first-child td, table tr td:first-child {
        color: #888888;
        background-color: #F4F4F4;
        font-weight: bold;
    }
    table tr:first-child td:first-child {
        color: #000000;
        background-color: #F4F4F4;
    }
    table tr:nth-child( 2 ) td:nth-child( 2 ),
    table tr:nth-child( 3 ) td:nth-child( 2 ) {
        font-family: 'Input Mono', 'Source Code Pro', Consolas, monospace;
        font-size: 80%;
    }
.result {
    padding: 0.5em;
}
.success {
    color: green;
    font-weight: bold;
}
h4.result {
    margin-top: 0;
}
p.result {
    font-size: 150%;
    background-color: #CCFFCC;
}
.failure {
    color: red;
    background-color: #FFCCCC;
    font-weight: bold;
}
.step {
    font-weight: bold;
}
#header {
    margin: 0 0 0.5em 0;
    padding: 1em 5em;
    border-bottom: solid 1px #000000;
    color: #EEEEEE;
    background-color: #666666;
}
#report {
    margin: 0;
    padding: 0.1em 5em 0.5em 5em;
}
  </style>
</head>
<body>

<div id="header"><h1><?php echo $title; ?></h1></div>

<div id="report">

<?php

if( $has_test == true ) {

    $exec = new hzphp\Test\Executor( [ new $tname() ] );

    $result = $exec->runTests();

    if( $result == true ) {
        echo '<p class="result success">Test Result: All tests PASSED</p>';
    }
    else {
        echo '<h1 class="result failure">'
            . 'Test Result: One or more tests FAILED</h1>';
    }

}

else if( $index != false ) {

    echo $index;

}

else {

    echo "<p>The requested unit test at <code>$test</code> does not exist.</p>";

}

?>

</div>

</body>
</html>

