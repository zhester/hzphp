<?php

namespace hzphp\MIME;

/**
 * Deals with the subtleties of various message and part headers.
 */
class Header {

    //Header metadata (extra descriptors and refined parsing)
    protected $meta = array();

    //Header name field
    protected $name;

    //Header data field (unparsed)
    protected $data;


    /**
     * Constructor.
     * Creates a new message header object.
     *
     * @param name The value of the header name field
     * @param data The value of the header data field
     */
    public function __construct($name, $data) {
        $this->name = $name;
        $this->data = trim($data);
    }


    /**
     * append
     * Appends more data to this header (from whitespace folding).
     *
     * @param data An extra line of data to append to the data field
     * @return true on success
     */
    public function append($data) {
        $this->data .= preg_replace('/^\s+/', ' ', $data);
        return(true);
    }


    /**
     * finalize
     * Called when header data is complete.  This parses header data
     * fields a little more and needs to be done after all additional
     * data lines (from MimeMessageHeader::append()) have been appended.
     *
     */
    public function finalize() {

        //Strip comments.
        $data = preg_replace('/\([^)]*\)/', '', $this->data);

        //Special fields.
        $address_headers = array('To','Cc','Bcc','Reply-To');

        //Complex headers.
        if(strpos($data, ';') !== false) {

            //Begin tokenized processing.
            $nflags = 0;
            $chunk = trim(strtok($data, ';'));
            if(strpos($chunk, '=') === false) {
                $this->meta['type'] = $chunk;
                if(strpos($chunk, '/') !== false) {
                    list($generic, $specific) = explode('/', $chunk, 2);
                    $this->meta['genus'] = $generic;
                    $this->meta['species'] = $specific;
                }
                $chunk = strtok(';');
            }

            //The first one wasn't special, reset tokenizer.
            else {
                $chunk = strtok($data, ';');
            }

            //Scan all later chunks.
            while($chunk !== false) {
                $chunk = trim($chunk);

                //Might be field pair (key=value).
                if(strpos($chunk, '=') !== false) {
                    list($k, $v) = explode('=', $chunk, 2);
                    if(preg_match('/^".*"$/', $v)) {
                        $this->meta[$k] = trim($v, '"');
                        $this->meta[$k] = preg_replace(
                            '/\\"/', '"', $this->meta[$k]
                        );
                    }
                    else {
                        $this->meta[$k] = $v;
                    }
                }

                //Flag-style chunk.
                else if(strlen($chunk)) {
                    $this->meta[$nflags] = $chunk;
                    ++$nflags;
                }

                //Advance to next token.
                $chunk = strtok(';');
            }
        }

        //Date header.
        else if($this->name == 'Date') {
            $this->meta['timestamp'] = strtotime($data);
        }

        //Some headers may contain multiple addresses separated by ","
        else if(in_array($this->name, $address_headers)) {
            if(strpos($data, ',') !== false) {
                $addresses = explode(',', $data);
                array_walk($addresses, 'trim');
                $this->meta['addresses'] = $addresses;
            }
        }
    }


    /**
     * __toString
     * Returns the header's raw string value.
     *
     * @return The complete header data field (without parsing).
     */
    public function __toString() {
        return($this->data);
    }


    /**
     * __get
     * Accesses several common properties for the header.
     *
     * @param key The key given to us from the magic method invocation
     * @return The value of the specified property
     */
    public function __get($key) {
        switch($key) {
            case 'name': case 'key':
                return($this->name);
            break;
            case 'data': case 'value':
                return($this->data);
            break;
            case 'meta':
                return($this->meta);
            break;
        }
        return(false);
    }


    /**
     * getMeta
     * Returns a metadata field value or the list of metadata.  If no field
     * is specified with the key parameter, the entire list of metadata is
     * returned.
     *
     * @param key An optional metadata field specifier
     * @return A metadata value or the list of metadata
     */
    public function getMeta($key = false) {
        if($key && isset($this->meta[$key])) {
            return($this->meta[$key]);
        }
        else if($key === false) {
            return($this->meta);
        }
        return(false);
    }


    /**
     * dumpArray
     * Dumps an array of info for diagnostics.
     *
     * @return A tree structure representing this header's parsed info
     */
    public function dumpArray() {
        $array = array(
            'name' => $this->name,
            'data' => $this->data
        );
        if(count($this->meta)) {
            $array['meta'] = $this->meta;
        }
        return($array);
    }
}

?>