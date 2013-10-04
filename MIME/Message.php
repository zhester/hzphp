<?php

namespace hzphp\MIME;


/****************************************************************************
    Original Module Comments

    MimeMessage
    Zac Hester
    2009-01-22
    Version 1.0.0

    This class provides a well-abstracted MIME format message parser.
    I found several MIME parser classes with terrible interfaces.  This
    provides a powerful and more simplified interface that actually
    uses OOP methodology (not just a pile of associative arrays).

    I've read through RFC2822 (http://www.faqs.org/rfcs/rfc2822.html)
    and this class should decode a modern, compliant message.  With
    the possible variations in message generators, I can't be sure this
    is a one-size-fits-all solution, and it, honestly, isn't my intention
    to make something that universal.  I will continue to tweak and adjust
    the implementation as I see more message formats that break the current
    (reasonably robust) parsing methods.

    Known non-conformance with RFC2822:
        -- Header data fields:
        - Nested '(' or ')' inside comments will break comment stripping
        - Allows nonstandard line endings (for compatibility)
        - Obsolete forms may impact parsing (untested)

    Usage:
    $email = file_get_contents('my_message.eml');
    $mm = new MimeMessage($email);
    $from = $mm->getHeader('From')->data;
    if($mm->hasParts()) {
        $num_parts = $mm->getPartCount();
        for($i = 0; $i < $num_parts; ++$i) {
            $p = $mm->getPart($i);
            $part_type = $p->getHeader('Content-Type')->getMeta('type');
        }
    }
    //Dump parsed info:
    print_r($mm->dumpArray());
****************************************************************************/


/**
 * The root user interface for MIME formatted messages.
 */
class Message extends Part {


    /**
     * Constructor.
     *
     * @param message The entire RFC2822 formatted message.
     */
    public function __construct($message) {
        parent::__construct($message);
    }


    /**
     * __toString
     * Makes some assumptions about what the user would want when asking
     * for just a string representation of the message.  It pretty much
     * always returns the plain text message body (which can depend on
     * the original construction of the message).
     *
     * @return A string representation of the message
     */
    public function __toString() {

        //Look for multipart message.
        if($this->hasParts()) {

            //Grab all plain text parts.
            $parts = $this->getPartsByHeader('Content-Type', 'text/plain');

            //Make sure the message has a plain text part.
            if(is_array($parts) && count($parts)) {

                //Send back the first plain text part.
                return($parts[0]->getContent());
            }

            //No plain text parts, send back the first part.
            return($this->getPart(0)->getContent());
        }

        //Simple message, send back the body.
        return($this->getContent());
    }
}

?>