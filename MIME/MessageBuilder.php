<?php
/*****************************************************************************

Multipart MIME Message Builder
==============================

Creates multipart MIME strings for use in email messages.

*****************************************************************************/

/*----------------------------------------------------------------------------
Default Namespace
----------------------------------------------------------------------------*/

namespace hzphp\MIME;

/*----------------------------------------------------------------------------
Dependencies
----------------------------------------------------------------------------*/

/*----------------------------------------------------------------------------
Classes
----------------------------------------------------------------------------*/

/**
 * Contains a MIME message part used by the MessageBuilder
 */
class MessagePart {

    /*------------------------------------------------------------------------
    Class Constants
    ------------------------------------------------------------------------*/

    //default values for new message parts
    const DEFAULT_TYPE     = 'text/plain; charset=utf-8';
    const DEFAULT_ENCODING = '8bit';


    /*------------------------------------------------------------------------
    Public Properties
    ------------------------------------------------------------------------*/

    //the line-break sequence used for most parts of all messages
    public static $break = "\r\n";


    /*------------------------------------------------------------------------
    Protected Properties
    ------------------------------------------------------------------------*/

    //the message part's content (string)
    protected $content = '';

    //the message part's headers (array of strings)
    protected $headers = [];

    //a flag to indicate if this message part is plain text
    protected $is_plain = true;


    /*------------------------------------------------------------------------
    Private Properties
    ------------------------------------------------------------------------*/

    /*------------------------------------------------------------------------
    Public Methods
    ------------------------------------------------------------------------*/

    /**
     * MessagePart Constructor
     *
     * @param content  The content of this message part
     * @param type     The MIME content type identifier
     * @param encoding The MIME encoding identifier (7bit, 8bit, base64, etc.)
     * @param extra    If an array, adds additional headers to the list of
     *                 headers.  If a string, adds it as one additional header
     *                 to the list of headers.
     */
    public function __construct(
        $content,
        $type     = self::DEFAULT_TYPE,
        $encoding = self::DEFAULT_ENCODING,
        $extra    = null
    ) {

        //set initial object state
        $this->content = $content;
        $this->headers = [];

        //allow users to delay setting these headers
        if( $type != false ) {
            $this->setHeader( "Content-Type: $type" );
        }
        if( $encoding != false ) {
            $this->setHeader( "Content-Transfer-Encoding: $encoding" );
        }

        //check for extra headers
        if( is_array( $extra ) ) {
            foreach( $extra as $header ) {
                $this->setHeader( $header );
            }
        }
        else if( is_string( $extra ) ) {
            $this->setHeader( $extra );
        }

        //detect plain text for special handling later
        $this->is_plain = strpos( $type, '/plain' ) !== false;
    }


    /**
     * Represents the object as a string.
     *
     * @return A valid MIME message part that contains the part headers and
     *         content
     */
    public function __toString() {
        return $this->getHeaders()
            . self::$break
            . self::$break
            . $this->getBody();
    }


    /**
     * Retrieves the contents of the message part with any necessary
     * processing for typical MIME message usage.
     *
     * @return A string containing the MIME message contents
     */
    public function getBody() {
        if( $this->is_plain == true ) {
            $break = strpos( $this->content, "\r" ) === false ? "\n" : "\r\n";
            $content = wordwrap( $this->content, 78, $break );
        }
        else {
            $content = $this->content;
        }
        return $content;
    }


    /**
     * Retrieves a possible Content-ID identifer.  This is largely a
     * convenience method to make it easy to get identifiers from inline
     * attachments that need to be referenced in other message parts.
     *
     * @return The CID string (without enclosing angle brackets), or boolean
     *         false if this message part does not have a MIME CID.
     */
    public function getCID() {
        $cid = $this->getHeader( 'Content-ID' );
        if( $cid != false ) {
            return trim( $cid, '<>' );
        }
        return false;
    }


    /**
     * Retrieves the value of a header by its name.
     *
     * @param key The name of the header to retrieve
     * @return    The value of the header as a string, or boolean false if the
     *            named header does not exist in this message part
     */
    public function getHeader( $key ) {
        foreach( $this->headers as $header ) {
            list( $k, $d ) = explode( ':', $header, 2 );
            if( $k == $key ) {
                return trim( $d );
            }
        }
        return false;
    }


    /**
     * Retrieves the part's MIME-formatted headers.
     *
     * @return A string containing all part headers separated by line-breaks
     */
    public function getHeaders() {
        return implode( self::$break, $this->headers );
    }


    /**
     * Convenience method for setting a Content-ID header on the message part.
     *
     * @param cid The desired CID string (without the angle brackets).  If the
     *            traditional mailer portion is ommitted (@...), the mailer
     *            portion is added automatically.
     */
    public function setCID( $cid ) {
        if( strpos( $cid, '@' ) === false ) {
            $cid .= '@' . MessageBuilder::$mailer;
        }
        $this->setHeader( "Content-ID: <$cid>" );
    }


    /**
     * Sets a header for the message.
     *
     * @param header The header string to add or modify
     */
    public function setHeader( $header ) {
        list( $new_key, $new_data ) = explode( ':', $header, 2 );
        $replacing = false;
        for( $i = 0; $i < count( $this->headers ); ++$i ) {
            list( $k, $d ) = explode( ':', $this->headers[ $i ], 2 );
            if( $new_key == $k ) {
                $this->headers[ $i ] = $header;
                $replacing = true;
                break;
            }
        }
        if( $replacing == false ) {
            if( $new_key == 'From' ) {
                array_unshift( $this->headers, $header );
            }
            else {
                $this->headers[] = $header;
            }
        }
    }

}


/**
 * Creates multipart MIME strings for use in email messages.
 */
class MessageBuilder extends MessagePart {

    /*------------------------------------------------------------------------
    Class Constants
    ------------------------------------------------------------------------*/

    //flag to addAttachment() to create an inline attachment
    const INLINE = 0x0001;

    //flag to addAttachment() to load attachment from string
    const USE_STRING = 0x0002;


    /*------------------------------------------------------------------------
    Public Properties
    ------------------------------------------------------------------------*/

    /*------------------------------------------------------------------------
    Protected Properties
    ------------------------------------------------------------------------*/

    //short name of the mailer application used in headers and MIME message
    //  part boundaries
    protected static $mailer = 'hzphp';

    //list of message parts that are attachments
    protected $attachments = [];

    //the hash used in MIME message part boundaries
    protected $hash = '';

    //list of message parts that are alternate versions of the message
    protected $parts = [];


    /*------------------------------------------------------------------------
    Private Properties
    ------------------------------------------------------------------------*/

    //message alternative boundary marker
    private $alt;

    //top-level message part boundary marker
    private $top;


    /*------------------------------------------------------------------------
    Public Methods
    ------------------------------------------------------------------------*/

    /**
     * MessageBuilder Constructor
     *
     */
    public function __construct() {
        $this->hash = md5( date( 'r', time() ) );
        $this->alt  = self::$mailer . '-alt-' . $this->hash;
        $this->top  = self::$mailer . '-top-' . $this->hash;
        parent::__construct(
            '',
            "multipart/mixed; boundary=\"{$this->top}\"",
            false,
            [
                'X-Mailer: ' . self::$mailer,
                'MIME-Version: 1.0'
            ]
        );
    }


    /**
     * Adds a file attachment to the message.
     *
     * @param filename The name of the file to attach
     * @param flags    Attachment behavior flags
     * @param args     Additional arguments depending on flags
     * @return         The MessagePart instance for the attachment that was
     *                 added to the message
     */
    public function addAttachment( $filename, $flags = 0, $args = null ) {

        //set a common name for the attachment
        $name = basename( $filename );

        //determine requested disposition
        $disp = ( $flags & self::INLINE ) != 0 ? 'inline' : 'attachment';

        //see if we are loading a string
        if( ( $flags & self::USE_STRING ) != 0 ) {

            //the "args" parameter should now be an array containing the
            //  same arguments that would be passed to the addPart() method
            //  or the MessagePart constructor
            $num_args = count( $args );
            if( $num_args == 0 ) {
                throw \RuntimeException(
                    'Invalid use of string loading for attachment'
                    . ' (no content given).'
                );
            }

            //set content for message part
            $content  = $args[ 0 ];

            //set defaults
            $type     = MessagePart::DEFAULT_TYPE;
            $encoding = MessagePart::DEFAULT_ENCODING;
            $extra    = [];

            //override defaults, if necessary
            if( $num_args > 1 ) { $type     = $args[ 1 ]; }
            if( $num_args > 2 ) { $encoding = $args[ 2 ]; }
            if( $num_args > 3 ) { $extra    = $args[ 3 ]; }
        }

        //we are loading the attachment from a file
        else {

            //determine the attachment's MIME type
            $finfo = finfo_open( FILEINFO_MIME_TYPE );
            $mtype = finfo_file( $finfo, $filename );
            finfo_close( $finfo );

            //load the file into memory, base-64 encode the data, break the
            //  base-64 string into short lines, trim the trailing newline left by
            //  chunk_split()
            $content = trim(
                chunk_split( base64_encode( file_get_contents( $filename ) ) )
            );

            //the message part's Content-Type declaration string
            $type = "$mtype; name=\"$name\"";

            //the encoding for the attachment
            $encoding = 'base64';

            //no extra headers yet
            $extra = [];
        }

        //append additional headers for the attachment
        $extra = array_merge(
            $extra,
            [
                "Content-Disposition: $disp; filename=\"$name\"",
                "Content-ID: <$name@" . self::$mailer . '>'
            ]
        );

        //create the message part
        $part = new MessagePart( $content, $type, $encoding, $extra );

        //add part to list of attachments
        $this->attachments[] = $part;

        //return the created message part
        return $part;
    }


    /**
     * Adds a part to the message by string data.
     *
     * @param content  The content of this message part
     * @param type     The MIME content type identifier
     * @param encoding The MIME encoding identifier (7bit, 8bit, base64, etc.)
     * @param extra    If an array, adds additional headers to the list of
     *                 headers.  If a string, adds it as one additional header
     *                 to the list of headers.
     * @return         The MessagePart object that was created and added to
     *                 the message
     */
    public function addPart(
        $content,
        $type     = MessagePart::DEFAULT_TYPE,
        $encoding = MessagePart::DEFAULT_ENCODING,
        $extra    = null
    ) {

        //create the message part object
        $part = new MessagePart( $content, $type, $encoding, $extra );

        //add to the list of message alternatives
        $this->parts[] = $part;

        //return the created message part
        return $part;
    }


    /**
     * Retrieves the message body (without headers).
     *
     * @return The string representing the message's body
     * @throws \RuntimeException if the message instance is in an invalid
     *         state for generating the message body
     */
    public function getBody() {

        //ensure there is at least one message part
        $num_parts = count( $this->parts );
        if( $num_parts == 0 ) {
            throw new \RuntimeException(
                'No message parts given for message body.'
            );
        }

        //line break and header-body separator
        $brk = self::$break;
        $sep = self::$break . self::$break;

        //start the message with the top-level boundary marker
        $message = "--{$this->top}$brk";

        //this message has alternative versions of the content
        if( $num_parts > 1 ) {

            //manually set a new Content-Type header and declare an
            //  alternative boundary marker
            $message .= "Content-Type: multipart/alternative;"
                . " boundary=\"{$this->alt}\"$sep";

            //append each message part beginning with the alternative boundary
            //  marker
            foreach( $this->parts as $part ) {
                $message .= "--{$this->alt}$brk" . strval( $part ) . $sep;
            }

            //append the terminating alternative boundary marker
            $message .= "--{$this->alt}--$sep";
        }

        //this message only has one version of the content
        else {

            //append the single message part at the top level
            $message .= strval( $this->parts[ 0 ] ) . $sep;
        }

        //add attachments below the message alternatives
        foreach( $this->attachments as $attachment ) {

            //append the top-level boundary marker followed by the attachment
            $message .= "--{$this->top}$brk" . strval( $attachment ) . $sep;
        }

        //append the terminating top-level boundary marker
        $message .= "--{$this->top}--$sep";

        //return the MIME-formatted message body
        return $message;
    }


    /*------------------------------------------------------------------------
    Protected Methods
    ------------------------------------------------------------------------*/

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

if( realpath( $_SERVER[ 'SCRIPT_FILENAME' ] ) == __FILE__ ) {

    header( 'Content-Type: text/plain; charset=utf-8' );

    //MessageBuilder instance
    $mb = new MessageBuilder();

    //build a PNG image for testing
    $im = imagecreatetruecolor( 320, 64 );
    imagealphablending( $im, false );
    imagesavealpha( $im, true );
    $bg = imagecolorallocatealpha( $im, 255, 255, 255, 127 );
    $fg = imagecolorallocatealpha( $im, 203, 75, 22, 24 );
    $tc = imagecolorallocate( $im, 0, 22, 32 );
    imagefilledrectangle( $im, 0, 0, 320, 64, $bg );
    imagefilledrectangle( $im, 10, 10, 310, 54, $fg );
    imagestring( $im, 5, 20, 20, 'Attached Image in Message', $tc );
    $tname = tempnam( sys_get_temp_dir(), 'hzphp' );
    imagepng( $im, $tname );
    $png = $mb->addAttachment( $tname, MessageBuilder::INLINE );
    unlink( $tname );

    //build a style sheet (note: this does nothing, it's just demonstrating
    //  how to add attachments from strings.  clients won't render it.)
    $styles = <<<EOCSS
body { font: 14pt/130% 'Source Sans Pro', Arial, Helvetica, sans-serif; }
EOCSS;

    //add it as an attachment using a string
    $css = $mb->addAttachment(
        'styles.css',
        ( MessageBuilder::INLINE | MessageBuilder::USE_STRING ),
        [ $styles, 'text/css; charset=utf-8' ]
    );

    //build an HTML message for testing
    $html = <<<EOHTML
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>The Message</title>
    <link rel="stylesheet" href="cid:{$css->getCID()}">
</head>
<body>
<h1>The Message</h1>
<p>HTML message contents.</p>
<p><img src="cid:{$png->getCID()}" alt="Image in Message"></p>
</body>
</html>
EOHTML;

    //build the plain text version of the message for testing
    $plain = <<<EOPLAIN
The Message
===========

Plain message contents.

This is a paragraph in the plain message that should be long enough to require the MessageBuilder to hard-wrap the text to conform to "typical" text-based message readers and clients.  Sorry it had to be so long.  All in the name of science, and whatnot.
EOPLAIN;

    //email addresses
    $to   = 'To Person <to@example.com>';
    $from = 'From Person <from@example.com>';

    //set the usual headers
    //note: to get the From: header to work, ssmtp must have FromLineOverride
    //set to YES in /usr/local/etc/ssmtp/ssmtp.conf
    $mb->setHeader( "From: $from" );
    $mb->setHeader( "Reply-To: $from" );

    //add the alternate content parts of the message
    $mb->addPart( $plain );
    $mb->addPart( $html, 'text/html; charset=utf-8' );

    //add another attachment, just because we can
    $mb->addAttachment( __FILE__ );

    //demonstrate our ability to properly create the message
    echo $mb;

    //don't do this in the test script, but this is how you do it
    if( false ) {
        mail(
            $to,
            'Test Multipart Message',
            $mb->getBody(),
            $mb->getHeaders()
        );
    }

}

