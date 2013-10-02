<?php

namespace hzphp\DBI\mysqli;


class Connection extends \mysqli implements \hzphp\DBI\Connection {


    public function __construct(
        Array $info
    ) {

        $port = 3306;
        if( isset( $info[ 'port' ] ) == true ) {
            $port = $info[ 'port' ];
        }

        parent::__construct(
            $info[ 'host' ],
            $info[ 'user' ],
            $info[ 'pass' ],
            trim( $info[ 'path' ], '/' ),
            $port
        );

    }


}


?>