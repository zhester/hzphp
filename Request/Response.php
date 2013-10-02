<?php

namespace hzphp\Request;


/**
 *  Response output model.  Provides an abstraction to the method of response
 *  output, and normalizes when/how headers are sent to the client.
 */
class Response {


    protected           $handler;


    /**
     *  Initializes the Response object.
     *
     *  @param handler
     */
    public function __construct(
        Handler $handler
    ) {
        $this->handler = $handler;
    }


    /**
     *  Puts HTTP headers on the specified handle.
     *
     *  @param handle
     */
    public function putHeaders(
        $handle
    ) {
        fwrite( $handle, Status::getHeader( $this->handler->getStatus() ) );
        $headers = $this->handler->headers();
        foreach( $headers as $key => $value ) {
            fwrite( $handle, ( "\n" . $key . ': ' . $value ) );
        }
        fwrite( $handle, "\n\n" );
    }


    /**
     *  Puts the response output on the specified handle.
     *
     *  @param handle
     *  @return
     *  @throws Exception
     */
    public function put(
        $handle
    ) {
        $total = 0;
        while( $buffer = $this->handler->read() ) {
            $length   = strlen( $buffer );
            $attempts = 0;
            $written  = 0;
            while( $written < $length ) {
                $result = fwrite( $handle, substr( $buffer, $written ) );
                if( $result === false ) {
                    throw new \Exception( 'Unable to write output.' );
                }
                else if( $result === 0 ) {
                    $attempts += 1;
                    if( $attempts > 1000 ) {
                        throw new \Exception( 'Unable to write output.' );
                    }
                }
                $written += $result;
            }
            $total += $written;
        }
        return $total;
    }


    /**
     *  Sends the complete response to the client over HTTP.
     *
     *  @return
     *  @throws Exception
     */
    public function send() {
        $this->sendHeaders();
        if(
            ( isset( $_SERVER[ 'REQUEST_METHOD' ] ) == true )
            &&
            ( $_SERVER[ 'REQUEST_METHOD' ] == 'HEAD' )
        ) {
            return 0;
        }
        else if(
            array_key_exists( 'Location', $this->handler->headers() ) == true
        ) {
            return 0;
        }
        $handle = fopen( 'php://output', 'wb' );
        if( $handle == false ) {
            throw new Exception( 'Unable to open output.' );
        }
        $sent = $this->put( $handle );
        fclose( $handle );
        return $sent;
    }


    /**
     *  Sends the HTTP headers to the client.
     *
     */
    public function sendHeaders() {
        $status = $this->handler->getStatus();
        header( Status::getHeader( $status ), true, $status );
        $headers = $this->handler->headers();
        foreach( $headers as $key => $value ) {
            header( $key . ': ' . $value );
        }
    }


}

?>