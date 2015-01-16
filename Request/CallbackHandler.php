<?php

namspace hzphp\Request;


class CallbackHandler extends Handler {


    protected           $arguments;
    protected           $callback;


    private             $eof = false;


    public function read() {
        if( $this->eof == false ) {
            $this->eof = true;
            return call_user_func_array( $this->callback, $this->arguments );
        }
        return false;
    }


    public function setCallback(
        $callback,
        $arguments = []
    ) {
        $this->callback  = $callback;
        $this->arguments = $arguments;
    }


}

