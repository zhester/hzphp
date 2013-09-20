<?php

namespace hzphp\Request;


class Response {


    protected           $handler;
    protected           $status;


    public function __construct(
        Handler $handler,
        $status = Status::OK
    ) {
        $this->handler = $handler;
        $this->status  = $status;
    }


    public function putHeaders(
        $handle
    ) {
        fwrite( $handle, Status::getStatus( $this->status ) );
        $headers = $this->handler->headers();
        foreach( $headers as $key => $value ) {
            fwrite( $handle, ( "\n" . $key . ': ' . $value ) );
        }
        fwrite( $handle, "\n\n" );
    }


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
                    throw new Exception( 'Unable to write output.' );
                }
                else if( $result === 0 ) {
                    $attempts += 1;
                    if( $attempts > 1000 ) {
                        throw new Exception( 'Unable to write output.' );
                    }
                }
                $written += $result;
            }
            $total += $written;
        }
        return $total;
    }


    public function send() {
        $this->sendHeaders();
        if(
            ( isset( $_SERVER[ 'REQUEST_METHOD' ] ) == true )
            &&
            ( $_SERVER[ 'REQUEST_METHOD' ] == 'HEAD' )
        ) {
            return 0;
        }
        else if( array_key_exists( 'Location', $this->headers ) == true ) {
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


    public function sendHeaders() {
        header( Status::getStatus( $this->status ), true, $this->status );
        $headers = $this->handler->headers();
        foreach( $headers as $key => $value ) {
            header( $key . ': ' . $value );
        }
    }


}

?>