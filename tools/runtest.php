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

$unit     = '**unknown**';
$has_test = false;

if( isset( $_GET[ 'unit' ] ) == true ) {

    if( preg_match( '/^(\w+)\\/(\w+)$/', $_GET[ 'unit' ], $matches ) == 1 ) {

        $unit   = $_GET[ 'unit' ];
        $module = $hzphp_path . '/' . $matches[ 1 ];
        $name   = $matches[ 2 ];
        $test   = $module . '/test/unit/' . $name . '.php';

        if( ( file_exists( $module ) == true )
         && (      is_dir( $module ) == true )
         && ( file_exists( $test   ) == true ) ) {

            $has_test = true;
            require $test;

        }

    }

}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
<title><?php echo $unit; ?> - runtest</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<style type="text/css">
body {
	margin: 0;
	padding: 0;
	font: 10pt/130% Arial,Helvetica,sans-serif;
	color: #000000;
	background-color: #FFFFFF;
}
h1, h2, h3, h4, h5, h6, p, address, blockquote, pre, ul, ol, dl, form, table {
	margin: 0.5em 0;
	padding: 0;
}
h1 {
    font-size: 150%;
    border-bottom: solid 1px #999999;
}
h2 {
    font-size: 140%;
    border-bottom: dashed 1px #999999;
}
h3 {
    font-size: 130%;
    border-bottom: dashed 1px #CCCCCC;
}
h4 {
    font-size: 120%;
}
h5 {
    font-size: 110%;
}
h6 {
    font-size: 100%;
}
blockquote {
    padding: 0 3em;
}
table {
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
.result {
    padding: 0.5em;
}
.success {
    color: green;
    background-color: transparent;
    font-weight: bold;
}
.failure {
    color: red;
    background-color: #FFEEEE;
    font-weight: bold;
}
.step {
    color: #000099;
    background-color: transparent;
    font-weight: bold;
}
#header {
    margin: 0 0 1em 0;
    padding: 1em 5em;
    border-bottom: solid 1px #000000;
    color: #FFFFFF;
    background-color: #666666;
}
    #header h1 {
        border: none;
    }
#report {
    margin: 0;
    padding: 1em 5em;
}
</style>
</head>
<body>

<div id="header"><h1><?php echo $unit; ?> - runtest</h1></div>

<div id="report">

<?php

if( $has_test == true ) {

    $exec = new hzphp\Test\Executor( [ new $name() ] );

    $result = $exec->runTests();

    if( $result == true ) {
        echo '<p class="result success">Test Result: All tests PASSED</p>';
    }
    else {
        echo '<h1 class="result failure">'
            . 'Test Result: One or more tests FAILED</h1>';
    }

}
else {

    echo '<p>The requested unit test does not exist.</p>';

}

?>

</div>

</body>
</html>