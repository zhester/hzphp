<?php

namespace hzphp\Request;


class Source extends Path {


    public function match(
        $string
    ) {
        return $string == $this->specifier;
    }


}

?>