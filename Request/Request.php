<?

namespace hzphp\Request;

class Request {


    protected           $map;


    public function __construct(
        Map $map
    ) {
        $this->map = $map;
    }


    public function handlePath(
        $path
    ) {
        //ZIH - todo:

        //search for first match to path in map
        //$target = $this->map->getTargetFromPath( $path );

        //use Target to create the Handler object
        //$handler = $target->getHandler();

        //create a new Response object, and return to user
        //  ZIH - probably should allow Handler to talk directly to
        //        Response for the status
        return new Response( $handler, Status::OK );
    }


}

?>