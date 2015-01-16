<?php
/*****************************************************************************
    regexp.php

    Regular expression test interface.

    Provides a simple way to check out regular expressions against various
    subject strings.  The checking is done asynchronously as the user types.
    Includes checking against PHP (on the server) and JavaScript (on the
    client).
*****************************************************************************/

if( isset( $_GET[ 'm' ] ) && isset( $_GET[ 's' ] ) ) {

    $m = [];

    $result = @preg_match( $_GET[ 'm' ], $_GET[ 's' ], $m );

    $data = [
        'pattern' => $_GET[ 'm' ],
        'subject' => $_GET[ 's' ],
        'result'  => $result,
        'matches' => $m
    ];

    header( 'Content-Type: application/json' );

    echo json_encode( $data, JSON_PRETTY_PRINT );

    exit();
}

?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Regular Expressions</title>
<style>
body {
    font: 10pt/110% Arial,sans-serif;
}
pre {
    min-height: 1em;
    margin: 0.5em;
    padding: 2px;
    border: solid 1px #CCCCCC;
    font: 12pt/110% Consolas,monospace;
}
table {
    margin: 0.5em;
    padding: 2px;
    border-collapse: collapse;
    font: 12pt/110% Consolas,monospace;
}
    table th {
        color: #999999;
        background-color: transparent;
    }
    table th, table td {
        min-height: 1em;
        margin: 0;
        padding: 2px 5px;
        border: solid 1px #CCCCCC;
        white-space: pre;
    }
#status {
    width: 400px;
    margin: 0.5em 2em;
    padding: 0;
    border: solid 1px #333333;
}
    #status .status_fill {
        margin: 0;
        padding: 2px 2em;
    }
.status {
    opacity: 0.0;
    transition: opacity 0.5s ease;
    border-radius: 5px;
    background-color: #CC6600;
}
.status_fill {
    animation: status_wait 1s linear infinite;
    font-weight: bold;
    color: #FFFFFF;
    background-repeat: repeat-x;
    background-size: 48px 48px;
    background-image: linear-gradient(
        -45deg,
        rgba(255, 255, 255, 0.15) 25%,
        transparent 25%,
        transparent 50%,
        rgba(255, 255, 255, 0.15) 50%,
        rgba(255, 255, 255, 0.15) 75%,
        transparent 75%,
        transparent
    );
}
@keyframes status_wait {
    to { background-position: 48px 0; }
}
</style>
</head>
<body>

<h1>Regular Expressions</h1>

<h2>Pattern</h2>
<pre contenteditable="true" id="pattern"></pre>

<h2>Subject</h2>
<pre contenteditable="true" id="subject"></pre>

<div id="status" class="status"><div
    class="status_fill">Waitaminute...</div></div>

<h2>Results</h2>
<table id="report">
    <tr>
        <th></th>
        <th>PHP</th>
        <th>JavaScript</th>
    </tr>
    <tr>
        <th>Pattern</th>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <th>Result</th>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <th>Matches</th>
        <td></td>
        <td></td>
    </tr>
</table>

<script src="http://hzian.com/projects/source/hzjs/lib/uhttp.js"></script>

<script>

function getTextValue( element ) {
    var value = '';
    if( element.childNodes != null ) {
        for( var i = 0; i < element.childNodes.length; ++i ) {
            if( element.childNodes[ i ].nodeType == 3 ) {
                value += element.childNodes[ i ].nodeValue;
            }
            else {
                value += getTextValue( element.childNodes[ i ] );
            }
        }
    }
    return value;
}

function report( data ) {
    var report = document.getElementById( 'report' );
    var tds = report.getElementsByTagName( 'td' );
    if( tds[ 0 ].firstChild == null ) {
        for( var i = 0; i < tds.length; ++i ) {
            tds[ i ].appendChild( document.createTextNode( '' ) );
        }
    }
    tds[ 0 ].firstChild.nodeValue = data[ 'pattern' ];
    tds[ 2 ].firstChild.nodeValue = data[ 'result' ];
    tds[ 4 ].firstChild.nodeValue = JSON.stringify( data[ 'matches' ] );
    var pattern = data[ 'pattern' ];
    var delim   = pattern.substr( 0, 1 );
    var dindex  = pattern.lastIndexOf( delim );
    var flags   = '';
    if( dindex < pattern.length ) {
        flags = pattern.substring( dindex + 1 );
    }
    pattern = pattern.substring( 1, dindex );
    try {
        var re = new RegExp( pattern, flags );
        var m  = re.exec( data[ 'subject' ] );
        tds[ 1 ].firstChild.nodeValue = delim + pattern + delim + flags;
        if( m != null ) {
            tds[ 3 ].firstChild.nodeValue = '1';
            tds[ 5 ].firstChild.nodeValue = JSON.stringify( m );
        }
        else {
            tds[ 3 ].firstChild.nodeValue = '0';
            tds[ 5 ].firstChild.nodeValue = '[]';
        }
    }
    catch( exception ) {
        tds[ 1 ].firstChild.nodeValue = '(unable to parse)';
        tds[ 3 ].firstChild.nodeValue = '0';
        tds[ 5 ].firstChild.nodeValue = '[]';
    }
}

function start_request() {
    //enable_form( false );
    var status = document.getElementById( 'status' );
    status.style.opacity = '1.0';
}

function stop_request() {
    var status = document.getElementById( 'status' );
    status.style.opacity = '0.0';
    //enable_form( true );
}

function send_regexp( pattern, subject ) {
    start_request();
    var client = new uhttp(
        function( txt ) {
            report( JSON.parse( txt ) );
            stop_request();
        },
        function( txt ) { alert( 'Error: ' + txt ); stop_request(); },
        function() { alert( 'Request timed out.' ); stop_request(); }
    );
    client.request(
        'GET',
        'regexp.php?m=' + encodeURIComponent( pattern )
                + '&s=' + encodeURIComponent( subject )
    );
}

function submit_form() {
    var pat = getTextValue( document.getElementById( 'pattern' ) );
    var sub = getTextValue( document.getElementById( 'subject' ) );
    if( ( pat.length > 0 ) && ( sub.length > 0 ) ) {
        send_regexp( pat, sub );
    }
}

function enable_form( enable ) {
    var pat = document.getElementById( 'pattern' );
    var sub = document.getElementById( 'subject' );
    if( enable == true ) {
        pat.contentEditable = 'true';
        sub.contentEditable = 'true';
    }
    else {
        pat.contentEditable = 'false';
        sub.contentEditable = 'false';
    }
}

function prepare_form() {
    var pat = document.getElementById( 'pattern' );
    var sub = document.getElementById( 'subject' );
    pat.onkeyup = submit_form;
    sub.onkeyup = submit_form;
}

prepare_form();

</script>

</body>
</html>
