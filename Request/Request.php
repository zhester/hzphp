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
        //search for first match to path in map (default is empty path)
        //use Destination to create the Handler object, call a method,
        //  return its Provider object
    }


}

?>