<?php

namespace hzphp\Response;


class Response {


    protected           $headers;
    protected           $provider;
    protected           $status;


    public function __construct(
        Provider $provider,
        $status  = Status::OK,
        $headers = []
    ) {
        $this->provider = $provider;
        $this->status   = $status;
        $this->headers  = $headers;
    }


    public function putHeaders(
        $handle
    ) {
        fwrite( $handle, Status::getStatus( $this->status ) );
        foreach( $this->headers as $key => $value ) {
            fwrite( $handle, ( "\n" . $key . ': ' . $value ) );
        }
        fwrite( $handle, "\n\n" );
    }


    public function put(
        $handle
    ) {
        $total = 0;
        while( $buffer = $this->provider->getOutput() ) {
            $length = strlen( $buffer );
            $written = 0;
            while( $written < $length ) {
                $result = fwrite( $handle, substr( $buffer, $written ) );
                if( $result === false ) {
                    throw new Exception( 'Unable to write output.' );
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
        foreach( $this->headers as $key => $value ) {
            header( $key . ': ' . $value );
        }
    }


}

?>