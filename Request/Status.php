<?php

namespace hzphp\Request;


class Status {


    const               OK                    = 200;
    const               CREATED               = 201;
    const               FOUND                 = 302;
    const               BAD_REQUEST           = 400;
    const               UNAUTHORIZED          = 401;
    const               FORBIDDEN             = 403;
    const               NOT_FOUND             = 404;
    const               INTERNAL_SERVER_ERROR = 500;
    const               NOT_IMPLEMENTED       = 501;
    const               SERVICE_UNAVAILABLE   = 503;


    public static       $index = [
        self::OK                    =>  0,
        self::CREATED               =>  1,
        self::FOUND                 =>  2,
        self::BAD_REQUEST           =>  3,
        self::UNAUTHORIZED          =>  4,
        self::FROBIDDEN             =>  5,
        self::NOT_FOUND             =>  6,
        self::INTERNAL_SERVER_ERROR =>  7,
        self::NOT_IMPLEMENTED       =>  8,
        self::SERVICE_UNAVAILABLE   =>  9
    ];


    public static       $text = [
        'OK', 'Created',
        'Found',
        'Bad Request', 'Unauthorized', 'Forbidden', 'Not Found',
        'Internal Server Error', 'Not Implemented', 'Service Unavailable'
    ];


    public static function getHeader( $status, $version = '1.1' ) {
        return sprintf(
            'HTTP/%s %d %s',
            $version,
            $status,
            self::getText( $status )
        );
    }


    public static function getText( $status ) {
        return self::$text[ self::$index[ $status ] ];
    }


}


?>