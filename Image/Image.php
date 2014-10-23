<?php
/*****************************************************************************

Image.php

Simplified, object-oriented image manipulation.

Requires GD to be available to PHP.

TODO:
- Remove support for indexed transparency.  Only deal with it when loading
  images from existing GD image resources or files.  Then, convert those to
  using alpha channels, and switch them to PNGs.
- Split the Rectangle class off into a generic Math or Geometry module.
- Allow all cases where a Rectangle object can be passed to also accept an
  array containing the same values (in its vector representation).
  - Base the Rectangle class on a Vector base class that other types of
    geometry and configuration data can inherit.
- Refactor into base Image class that parents format/feature-specific classes.
  - Simplifies management and checking of transparency, proper GD functions,
    and makes it easier to add support for new formats.
  - Would require a factory-style resource to detect and instantiate the
    appropraite subclass for the user based on the source or their choice.

*****************************************************************************/

/*----------------------------------------------------------------------------
Dependencies
----------------------------------------------------------------------------*/

/*----------------------------------------------------------------------------
Default Namespace
----------------------------------------------------------------------------*/

namespace hzphp\Image;

/*----------------------------------------------------------------------------
Classes
----------------------------------------------------------------------------*/


/**
 *  Exception to report problems when using the Image class.
 */
class ImageException extends \Exception {

    /**
     *  Exception constructor.
     *
     *  @param message
     *  @param code
     *  @param previous
     */
    public function __construct(
        $message,
        $code = 0,
        Exception $previous = null
    ) {
        parent::__construct( $message, $code, $previous );
    }
}


/**
 *  Provides a more sane way of passing around geometry within this module.
 */
class Rectangle implements \Iterator {


    /*------------------------------------------------------------------------
    Public Properties
    ------------------------------------------------------------------------*/

    public $h;  //The height of the rectangle
    public $w;  //The width or square dimension of the rectangle
    public $x;  //The horizontal position of the rectangle
    public $y;  //The vertical position of the rectangle


    /*------------------------------------------------------------------------
    Static Protected Properties
    ------------------------------------------------------------------------*/

    static protected $vector_keys = [ 'w', 'h', 'x', 'y' ];
                //This list of keys defines how we treat rectangles when they
                //  are treated as a vector.
    static protected $num_vector_keys = 4;  //Set to limit iteration


    /*------------------------------------------------------------------------
    Private Properties
    ------------------------------------------------------------------------*/

    private $vector_position = 0;   //Current read position in vector


    /*------------------------------------------------------------------------
    Public Methods
    ------------------------------------------------------------------------*/

    /**
     *  Constructor.
     *
     *  @param w The width or square dimension of the rectangle
     *  @param h The height of the rectangle (optional)
     *  @param x The horizontal position of the rectangle (optional)
     *  @param y The vertical position of the rectangle (optional)
     */
    public function __construct( $w, $h = -1, $x = 0, $y = 0 ) {
        $this->h = $h <= 0 ? $w : $h;
        $this->w = $w;
        $this->x = $x;
        $this->y = $y;
    }


    /**
     *  Compares the current instance to an outside instance to determine if
     *  these rectangles are geometrically equal to each other.
     *
     *  @param model The outside rectangle to which we compare ourselves
     *  @return      Standard "compare" values (0 is equal, >0 means the
     *               current object is the greater, <0 means the current
     *               object the lesser)
     */
    public function compare( $model ) {
        foreach( $this as $key => $value ) {
            $difference = $value - $model->$key;
            if( $difference != 0 ) {
                return $difference;
            }
        }
        return 0;
    }


    /**
     *  Provides the current element of the vectored representation.
     *
     *  Note: Provides support for Iterator interface.
     *
     *  @return The current element of the vectored representation
     */
    public function current() {
        $property = self::$vector_keys[ $this->vector_position ];
        return $this->$property;
    }


    /**
     *  Provides the key of the current element of the vectored
     *  representation.
     *
     *  Note: Provides support for Iterator interface.
     *
     *  @return The key of the current element of the vectored representation
     */
    public function key() {
        return self::$vector_keys[ $this->vector_position ];
    }


    /**
     *  Advances the position of the vector representation.
     *
     *  Note: Provides support for Iterator interface.
     */
    public function next() {
        $this->vector_position += 1;
    }


    /**
     *  Resets the position of the vector representation.
     *
     *  Note: Provides support for the Iterator interface.
     */
    public function rewind() {
        $this->vector_position = 0;
    }


    /**
     *  Scales the rectangle to based on a new major dimension.
     *
     *  @param dimension The new major dimension
     */
    public function scale_major( $dimension ) {

        //Width is the major dimension.
        if( $this->w > $this->h ) {
            $this->h = floor( ( $this->h * $dimension ) / $this->w );
            $this->w = $dimension;
        }

        //Height is the major dimension.
        else if( $this->w < $this->h ) {
            $this->w = floor( ( $this->w * $dimension ) / $this->h );
            $this->h = $dimension;
        }

        //Both dimensions are equal.
        else {
            $this->h = $dimension;
            $this->w = $dimension;
        }
    }


    /**
     *  Indicates if the current position in the vector representation
     *  is valid.
     *
     *  Note: Provides support for Iterator interface.
     *
     *  @return True if the vector can still be iterated, false otherwise
     */
    public function valid() {
        return $this->vector_position < self::$num_vector_keys;
    }

}


/**
 *  Models, manipulates, and provides utilities for working with an image.
 *  Note: This requires GD to be available to PHP.
 */
class Image {


    /*------------------------------------------------------------------------
    Static Protected Properties
    ------------------------------------------------------------------------*/

    static protected $type_info = [     //image type information
        'types' => [ IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF ],
        'exts'  => [
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG  => 'png',
            IMAGETYPE_GIF  => 'gif'
        ],
        'gd'    => [
            '_' => [
                IMAGETYPE_JPEG => 'imagejpeg',
                IMAGETYPE_PNG  => 'imagepng',
                IMAGETYPE_GIF  => 'imagegif'
            ],
            'createfrom' => [
                IMAGETYPE_JPEG => 'imagecreatefromjpeg',
                IMAGETYPE_PNG  => 'imagecreatefrompng',
                IMAGETYPE_GIF  => 'imagecreatefromgif'
            ]
        ]
    ];


    /*------------------------------------------------------------------------
    Public Properties
    ------------------------------------------------------------------------*/

    public $filename = null;    //current file name of image


    /*------------------------------------------------------------------------
    Protected Properties
    ------------------------------------------------------------------------*/

    protected $image             = null;    //GD image object handle
    protected $readable          = null;    //array of readable properties
    protected $rectangle         = null;    //dimensional rectangle
    protected $type              = -1;      //type of image
    protected $transparency      = -1;      //image transparency color index
    protected $transparent_color = -1;      //transparent color handle


    /*------------------------------------------------------------------------
    Private Properties
    ------------------------------------------------------------------------*/

    private $print_line = 0;    //current line in on-image text


    /*------------------------------------------------------------------------
    Static Public Methods
    ------------------------------------------------------------------------*/

    /**
     *  Determine if a given image type is supported by this implementation.
     *
     *  @param type The image type identifier from GD (IMGETYPE_*)
     *  @return     True if the image type is supported
     */
    static public function is_supported( $type ) {

        //Check the type list.
        return in_array( $type, self::$type_info[ 'types' ] );
    }


    /*------------------------------------------------------------------------
    Static Protected Methods
    ------------------------------------------------------------------------*/

    /**
     *  Creates an image resource that starts out completely transparent.
     *
     *  @param width  The width of the image to create (in pixels)
     *  @param height The height of the image to create (in pixels)
     *  @return       The image resource handle
     */
    static protected function create_transparent_image( $width, $height ) {

        //Create the image resource.
        $image = imagecreatetruecolor( $width, $height );
        if( $image == false ) {
            throw new ImageException(
                'Error result from imagecreatetruecolor().'
            );
        }

        //Disable alpha blending before we initialize the base image data.
        $res = imagealphablending( $image, false );
        if( $res == false ) {
            throw new ImageException(
                'Error result from imagealphablending().'
            );
        }

        //Allocate the initial color of all the pixel data.
        $color = imagecolorallocatealpha( $image, 0xFF, 0xFF, 0xFF, 0x7F );
        if( $color === false ) {
            throw new ImageException(
                'Error result from imagecolorallocatealpha().'
            );
        }

        //Initialize the image data to the transparent color.
        $res = imagefilledrectangle( $image, 0, 0, $width, $height, $color );
        if( $res == false ) {
            throw new ImageException(
                'Error result from imagefilledrectangle().'
            );
        }

        //Enable alpha blending for future manipulation.
        $res = imagealphablending( $image, true );
        if( $res == false ) {
            throw new ImageException(
                'Error result from imagealphablending().'
            );
        }

        //Return the image resource.
        return $image;
    }


    /**
     *  Formats a stack trace (provided by the Exception class) for plain-text
     *  readability.
     *
     *  @param trace  The trace array
     *  @param indent How much to indent each line in the output
     *  @return       A string formatted for easier reading of a trace
     */
    static protected function format_trace( $trace, $indent = '      ' ) {
        $num_entries = count( $trace );
        $strings = [];
        for( $i = 0; $i < $num_entries; ++$i ) {
            $entry = $trace[ $i ];
            if( count( $entry[ 'args' ] ) > 0 ) {
                $args = ' ' . implode( ', ', $entry[ 'args' ] ) . ' ';
            }
            else {
                $args = '';
            }
            if( isset( $trace[ 'class' ] ) == true ) {
                $strings[] = sprintf(
                    "#%2d: %s:%d\n$indent     %s->%s(%s)",
                    $i,
                    $entry[ 'file' ],
                    $entry[ 'line' ],
                    $entry[ 'class' ],
                    $entry[ 'function' ],
                    $args
                );
            }
            else {
                $strings[] = sprintf(
                    "#%2d: %s:%d\n$indent     %s(%s)",
                    $i,
                    $entry[ 'file' ],
                    $entry[ 'line' ],
                    $entry[ 'function' ],
                    $args
                );
            }
        }
        return $indent . implode( "\n$indent", $strings );
    }

    /*------------------------------------------------------------------------
    Public Methods
    ------------------------------------------------------------------------*/

    /**
     *  Image Constructor
     *  Note: See Image::load() for more detail on parameters.
     *
     *  @param source     Optional source image, object, or resource
     *  @param dimensions Optional Rectangle dimensions
     */
    public function __construct( $source = null, $dimensions = null ) {

        //Start with fundamental object initialization.
        $this->readable  = [ 'height', 'image', 'mime_type', 'type', 'width' ];

        //Perform possible immediate image loading.
        if( ( $source !== null ) || ( $dimensions !== null ) ) {
            $this->load( $source, $dimensions );
        }
    }


    /**
     *  Image Destructor
     *
     */
    public function __destruct() {

        //If the an image resource was ever created...
        if( $this->image !== null ) {

            //... destroy the current resource.
            imagedestroy( $this->image );
        }

    }


    /**
     *  Read-only access to a limited set of properties.
     *
     *  @param key
     *  @return
     */
    public function __get( $key ) {

        //Ensure the requested property is readable.
        if( in_array( $key, $this->readable ) == true ) {

            //Check for aliased properties.
            if( $key == 'height' ) {
                return $this->rectangle->h;
            }
            else if( $key == 'width' ) {
                return $this->rectangle->w;
            }
            else if( ( $key == 'x' ) || ( $key == 'y' ) ) {
                return $this->rectangle->$key;
            }

            //Check for convenience property.
            else if( $key == 'mime_type' ) {
                return image_type_to_mime_type( $this->type );
            }

            //Return the value of this property.
            return $this->$key;
        }

        //This property does not exist or is not allowed.
        throw new ImageException(
            "The requested property \"$key\" is not available."
        );
    }


    /**
     *  Convert (readable) object state to a string.
     *
     *  @return
     */
    public function __toString() {

        //Return a serialized representation of readable object state.
        return <<<EOD
{
    "filename" : "{$this->filename}",
    "type"     : "{$this->mime_type}",
    "width"    : {$this->width},
    "height"   : {$this->height}
}
EOD;
    }


    /**
     *  Create a copy of the image at a different size.
     *  Note: Purely a convenience method.
     *
     *  @param dimensions The dimensional Rectangle of the new image
     *  @return           A new Image object with all-new image data
     */
    public function create_resized( $dimensions ) {
        return new Image( $this, $dimensions );
    }


    /**
     *  Enable/disable printing (catchable) PHP errors on the image.
     *
     *  @param enable
     */
    public function enable_errors( $errors = true, $exceptions = true ) {

        //Override the default error handler.
        if( $errors == true ) {
            set_error_handler( [ $this, 'error_handler' ] );
        }

        //Restore the default error handler.
        else {
            set_error_handler( null );
        }

        //Override the default exception handler.
        if( $exceptions == true ) {
            set_exception_handler( [ $this, 'exception_handler' ] );
        }

        //Restore the default exception handler.
        else {
            set_exception_handler( null );
        }
    }


    /**
     *  Allows user scripts to display errors in the image rather than HTML or
     *  plain text errors.
     *
     */
    public function error_handler(
        $errno,
        $errstr,
        $errfile    = '',
        $errline    = 0,
        $errcontext = null
    ) {

        //Format and print the error on the image itself.
        $this->print_text(
            sprintf(
                "%s (%d) at:\n    %s:%d",
                $errstr,
                $errno,
                $errfile,
                $errline
            )
        );

        //Prevent calling the default error handler.
        return true;
    }

    /**
     *  Allows user scripts to display exceptions in the image rather than
     *  HTML or plain text errors.
     *
     */
    public function exception_handler( $exception ) {

        //Format the exception for easy reading.
        $this->print_text(
            sprintf(
                "%s (%d) at:\n    %s:%d\n    Stack Trace:\n%s",
                $exception->getMessage(),
                $exception->getCode(),
                $exception->getFile(),
                $exception->getLine(),
                self::format_trace( $exception->getTrace() )
            )
        );

        //Attempt to send the image here since the user's code has bombed out.
        $this->send();
        exit();
    }


    /**
     *  Retrieves the resource handle of the current image resource.
     *
     *  @return The GD image resource handle
     */
    public function get_resource() {
        return $this->image;
    }


    /**
     *  General-purpose image data loader.
     *  This can be used at any time to load an instance with new image data
     *  as well as resizing/resampling the image while it is being loaded.
     *  The first parameter may be given as null, and the second parameter can
     *  be used to initialize a blank image (possibly useful for procedural
     *  images).
     *
     *  @param source     Optional source image file name/object/resource.
     *                    When passing a string, it is assumed to be a file
     *                    name.
     *                    When passing an object, it is checked to see if it
     *                    is an instance of an Image object, then manually
     *                    cloned into the current instance.
     *                    When passing a GD image resource handle, its image
     *                    data is copied into a new resource in the current
     *                    object.
     *  @param dimensions Optional image dimensions (Rectangle instance)
     *                    If there is a source image, it is resampled into the
     *                    specified dimensions for this instance.
     *                    If there is no source image, it specifies the
     *                    dimensions of a new, blank image.
     *  @param type       Optionally, specify the type to use for the new
     *                    image that is being loaded (in case the old image
     *                    data came from another type, or it's not available).
     */
    public function load(
        $source     = null,
        $dimensions = null,
        $type       = IMAGETYPE_PNG
    ) {

        //Check for a source of image data in the parameters.
        if( $source !== null ) {

            //Assume a string contains a file name to load.
            if( is_string( $source ) == true ) {
                $this->load_from_file( $source, $dimensions );
            }

            //Check objects to see if we can load one of our own instances.
            else if( ( is_object( $source ) == true )
                  && ( $source instanceof Image     ) ) {
                $this->load_from_object( $source, $dimensions );
            }

            //Check resources to see if we can load a GD image.
            else if( ( is_resource( $source ) == true       )
                  && ( get_resource_type( $source ) == 'gd' ) ) {
                $this->load_from_image( $source, $dimensions, $type );
            }

            //Unknown image source.
            else {
                throw new ImageException(
                    'Unable to load unknown image source type.'
                );
            }
        }

        //No source given, see if we should create an empty image.
        else if( $dimensions !== null ) {

            //Create the base image.
            $this->rectangle = clone $dimensions;
            $this->type      = $type;
            $this->image     = self::create_transparent_image(
                $this->rectangle->w,
                $this->rectangle->h
            );
            $this->filename  = $this->generate_filename();
        }

        //Nothing specified to load.
        else {
            throw new ImageException(
                'Nothing specified to load into Image object.'
            );
        }
    }


    /**
     *  Load the object with an image from a file.
     *
     *  @param filename
     *  @param dimensions
     */
    public function load_from_file(
        $filename,
        $dimensions = null
    ) {

        //See if this instance already had an image loaded.
        if( $this->image !== null ) {

            //Destroy previously loaded image object.
            imagedestroy( $this->image );
            $this->image = null;
        }

        //Sanity check file.
        if( file_exists( $filename) == false ) {
            throw new ImageException(
                'The specified file does not exist: ' . $filename
            );
        }

        //Get information about this image.
        $info = getimagesize( $filename );

        //Check return.
        if( $info === false ) {
            throw new ImageException( 'Failed return from getimagesize().' );
        }

        //Set the type from the image on disk.
        $type = $info[ 2 ];

        //Make sure image type is supported.
        if( self::is_supported( $type ) == false ) {
            throw new ImageException(
                'Unsupported image type: ' . $type
            );
        }

        //Create the GD image object.
        $function = self::$type_info[ 'gd' ][ 'createfrom' ][ $type ];
        $this->image = $function( $filename );

        //Check image object creation.
        if( $this->image === false ) {
            throw new ImageException(
                'Failed to create image object from file.'
            );
        }

        //Load required internal state.
        $this->rectangle = new Rectangle( $info[ 0 ], $info[ 1 ] );
        $this->type      = $type;
        $this->filename  = $filename;

        //See if there is a need to resample the image.
        if( $dimensions !== null ) {
            $this->resample( $dimensions );
        }
    }


    /**
     *  Load the object with an image from a GD image resource.
     *
     *  @param image
     *  @param dimensions
     *  @param type
     *  @param filename
     */
    public function load_from_image(
        $image,
        $dimensions = null,
        $type       = IMAGETYPE_PNG,
        $filename   = false
    ) {

        //Check for proper usage.
        if( self::is_supported( $type ) == false ) {
            throw new ImageException(
                'Unsupported image type given: ' . $type
            );
        }

        //See if this instance already had an image loaded.
        if( $this->image !== null ) {
            imagedestroy( $this->image );
        }

        //Set our image resource to the incoming resource.
        $this->image = $image;

        //Set up internal state.
        $this->rectangle = new Rectangle(
            imagesx( $image ),
            imagesy( $image )
        );
        $this->type     = $type;
        $this->filename = $filename == false
            ? $this->generate_filename() : $filename;

        //See if there is a need to resample the image.
        if( $dimensions !== null ) {
            $this->resample( $dimensions );
        }
    }


    /**
     *  Load the object with an image from another Image object.
     *
     *  @param model
     *  @param dimensions
     */
    public function load_from_object( $model, $dimensions = null ) {

        //See if this instance already had an image loaded.
        if( $this->image !== null ) {
            imagedestroy( $this->image );
        }

        //Copy required ineternal state.
        $this->type     = $model->type;
        $this->filename = $model->filename;
        if( $dimensions !== null ) {
            $this->rectangle = clone $dimensions;
        }
        else {
            $this->rectangle = clone $model->rectangle;
        }

        //Create a new image resource.
        $this->image = self::create_transparent_image(
            $this->rectangle->w,
            $this->rectangle->h
        );

        //Check for the need to resample the incoming image.
        if( $this->rectangle->compare( $model->rectangle ) !== 0 ) {
            $result = imagecopyresampled(
                $this->image,
                $model->image,
                0,
                0,
                0,
                0,
                $this->rectangle->w,
                $this->rectangle->h,
                $model->width,
                $model->height
            );
            if( $result === false ) {
                throw new ImageException( 'Unable to resample image.' );
            }
        }

        //No resampling necessary.
        else {
            $result = imagecopy(
                $this->image,
                $model->image,
                0,
                0,
                0,
                0,
                $model->width,
                $model->height
            );
            if( $result === false ) {
                throw new ImageException( 'Unable to copy image.' );
            }
        }
    }



    /**
     *  Print (very simple) text on top of the image pixel data.
     *
     *  @param text
     */
    public function print_text( $text, $line_prefix = '' ) {
        $pad         = 4;
        $size        = 5;
        $height      = imagefontheight( $size ) + ( $pad / 2 );
        $width       = imagefontwidth( $size );
        $color       = imagecolorallocate( $this->image, 0xFF, 0x14, 0x93 );
        $lines       = explode( "\n", $text );
        $box_width   = $this->rectangle->w - ( $pad * 2 );
        $max_columns = floor( $box_width / $width );
        $wrap_prefix = "\x0E";
        $cut_length  = $max_columns - strlen( $wrap_prefix );
        foreach( $lines as $line ) {
            $y = $pad + ( $this->print_line * $height );
            $line = $line_prefix . $line;
            $length = strlen( $line );
            if( $length <= $max_columns ) {
                imagestring( $this->image, $size, $pad, $y, $line, $color );
                $this->print_line += 1;
            }
            else {
                $this->print_text( substr( $line, 0, $max_columns ) );
                $position = $max_columns;
                while( $position < $length ) {
                    $this->print_text(
                        substr( $line, $position, $cut_length ),
                        $wrap_prefix
                    );
                    $position += $cut_length;
                }
            }
        }
    }


    /**
     *  Send the image to the browser.
     *
     *  @param headers Optionally, send the necessary HTTP headers before
     *                 sending image data.  The default is to always send the
     *                 HTTP headers.
     *                 The user may also pass an array of header strings that
     *                 will be sent before the image data.
     */
    public function send( $headers = true ) {

        //Make sure the user wants the appropriate headers.
        if( $headers === true ) {
            $mime_type = image_type_to_mime_type( $this->type );
            header( 'Content-Type: ' . $mime_type );
            header(
                'Content-Disposition: inline; filename='
                . basename( $this->filename )
            );
        }

        //See if the user wants to set headers themselves.
        else if( is_array( $headers ) == true ) {
            foreach( $headers as $header ) {
                header( $header );
            }
        }

        //Prepare to send the image using the type-specific output function.
        $function = self::$type_info[ 'gd' ][ '_' ][ $this->type ];
        $params   = [ $this->image ];

        //Ensure alpha channel is preserved in output.
        if( $this->type == IMAGETYPE_PNG ) {
            imagesavealpha( $this->image, true );
        }

        //Set quality on JPEGs.
        else if( $this->type == IMAGETYPE_JPEG ) {
            $params = [ $this->image, null, 100 ];
        }

        //Send the image on the output stream.
        return call_user_func_array( $function, $params );
    }


    /**
     *  Write the image to a file.
     *
     *  @param filename
     */
    public function write( $filename = null ) {

        //Check for an override to the known file name.
        if( $filename === null ) {
            $filename = $this->filename;
        }

        //Prepare to store the image with the type-specific output function.
        $function = self::$type_info[ 'gd' ][ '_' ][ $this->type ];
        $params   = [ $this->image, $this->filename ];

        //Ensure alpha channel is preserved in output.
        if( $this->type == IMAGETYPE_PNG ) {
            imagesavealpha( $this->image, true );
        }

        //Set quality on JPEGs.
        else if( $this->type == IMAGETYPE_JPEG ) {
            $params = [ $this->image, $this->filename, 100 ];
        }

        //Store the image on the file system.
        return call_user_func_array( $function, $params );
    }


    /*------------------------------------------------------------------------
    Protected Methods
    ------------------------------------------------------------------------*/

    /**
     *  Generates a random, but not unique, file name for use as a default
     *  file name.
     *
     *  @return A string suitable for a file name
     */
    protected function generate_filename() {
        $alphabetic = range( 'a', 'z' );
        $numeric    = range( 0, 9 );
        $symbols    = array_merge( $alphabetic, $numeric );
        $name       = $alphabetic[ array_rand( $alphabetic ) ];
        for( $i = 0; $i < 15; ++$i ) {
            $name .= $symbols[ array_rand( $symbols ) ];
        }
        return $name . '.' . self::$type_info[ 'exts' ][ $this->type ];
    }


    /**
     *  Resamples the current image data given a new rectangle.
     *
     *  @param dimensions Rectangular dimensions at which to resample
     */
    protected function resample( $dimensions ) {

        //Create the target image for resampling.
        $image = self::create_transparent_image(
            $dimensions->w,
            $dimensions-h
        );

        //See if we need true resampling.
        if( ( $this->rectangle->w != $dimensions->w )
         || ( $this->rectangle->h != $dimensions->h ) ) {
            $result = imagecopyresampled(
                $image,
                $this->image,
                $this->rectangle->x,
                $this->rectangle->y,
                $dimensions->x,
                $dimensions->y,
                $this->rectangle->w,
                $this->rectangle->h,
                $dimensions->w,
                $dimensions->h
            );
            if( $result === false ) {
                throw new ImageException( 'Unable to resample image.' );
            }
        }

        //Dimensions appear equal, copy image data directly.
        else {
            $result = imagecopy(
                $image,
                $this->image,
                0,
                0,
                0,
                0,
                $dimensions->w,
                $dimensions->h
            );
            if( $result === false ) {
                throw new ImageException( 'Unable to copy image.' );
            }
        }

        //Assume new image data has been resampled.
        imagedestroy( $this->image );
        $this->image     = $image;
        $this->rectangle = clone $dimensions;
    }


    /*------------------------------------------------------------------------
    Private Methods
    ------------------------------------------------------------------------*/

    /**
     *  Sets the transparency state and initializes/resets the image content
     *  for a transparent image.
     *
     *  @param transparency
     *  @param transparent_color
     */
    private function set_transparency(
        $transparency      = false,
        $transparent_color = false
    ) {

        //set the transparency index
        if( $transparency === false ) {
            $this->transparency = imagecolortransparent( $this->image );
        }
        else {
            $this->transparency = $transparency;
        }

        //see if there is a transparency index for this image
        if( $this->transparency != -1 ) {

            //set color at the transparency index
            if( $transparent_color === false ) {
                $this->transparent_color = imagecolorsforindex(
                    $this->image,
                    $this->transparency
                );
            }
            else {
                $this->transparent_color = $transparent_color;
            }

            //allocate the transparent color in the current image
            $trans_color = imagecolorallocate(
                $this->image,
                $this->transparent_color[ 'red' ],
                $this->transparent_color[ 'green' ],
                $this->transparent_color[ 'blue' ]
            );

            //set the allocated color as the transparent color
            imagecolortransparent( $this->image, $trans_color );

            //fill the entire image with the transparent color
            imagefill( $this->image, 0, 0, $trans_color );
        }

        //there is no indexed transparency, check for PNG images
        else if( $this->type == IMAGETYPE_PNG ) {

            //ensure transparency color index is invalid
            $this->transparent_color = -1;

            //disable alpha blending
            imagealphablending( $this->image, false );

            //allocate a color to use for full transparency
            $trans_color = imagecolorallocatealpha(
                $this->image,
                0,
                0,
                0,
                127
            );

            //fill the entire image with the transparent color
            imagefilledrectangle(
                $this->image,
                0,
                0,
                $this->rectangle->w,
                $this->rectangle->h,
                $trans_color
            );
        }

        //there is no indexed transparecy, and this is not a PNG
        else {

            //ensure transparenct color index is invalid
            $this->transparent_color = -1;
        }
    }


    /**
     *  Checks and updates the current transparency state for this image.
     *
     */
    private function update_transparency_state() {

        //update the transparency index
        $this->transparency = imagecolortransparent( $this->image );

        //see if there is a transparency index for this image
        if( $this->transparency != -1 ) {

            //set color at the transparency index
            $this->transparent_color = imagecolorsforindex(
                $this->image,
                $this->transparency
            );
        }

        //there is no indexed transparecy
        else {

            //ensure transparency index is invalid
            $this->transparent_color = -1;
        }
    }

}


/*----------------------------------------------------------------------------
Functions
----------------------------------------------------------------------------*/

/*----------------------------------------------------------------------------
Execution
----------------------------------------------------------------------------*/

if( $_SERVER[ 'SCRIPT_FILENAME' ] === __FILE__ ) {

    /*------------------------------------------------------------------------
    Some baseline configuration that might change with the environment.
    ------------------------------------------------------------------------*/

    $support = '../../../support';
    $GLOBALS[ 'config' ] = [
        'font_file'   => $support . '/fonts/SourceSansPro-Bold.ttf',
        'test_images' => [
            $support . '/images/freebsd_logo.gif',
            $support . '/images/freebsd_logo.jpg',
            $support . '/images/freebsd_logo.png',
            $support . '/images/freebsd_logo.svg'
        ]
    ];

    /*------------------------------------------------------------------------
    Define a few functions for inline module testing.
    ------------------------------------------------------------------------*/

    /**
     *  Generates a test image without using the Image class.
     *
     */
    function create_test_image(
        $width   = 512,
        $height  = 128,
        $message = 'Hello World'
    ) {

        //Create a new image resource (initially transparent).
        $image = imagecreatetruecolor( $width, $height );
        imagealphablending( $image, false );
        $color = imagecolorallocatealpha( $image, 255, 255, 255, 127 );
        imagefilledrectangle( $image, 0, 0, $width, $height, $color );
        imagealphablending( $image, true );

        //Allocate a color to draw non-transparent foreground geometry.
        $color_fg = imagecolorallocatealpha( $image, 0xFF, 0x66, 0x00, 63 );
        $p = 20;
        $w = $width - $p;
        $h = $height - $p;

        //Draw a box (padded) on top of the background.
        imagefilledrectangle( $image, $p, $p, $w, $h, $color_fg );

        //Allocate a color to render some text.
        $color_txt = imagecolorallocatealpha( $image, 0x00, 0x00, 0x00, 31 );

        //Render a quick message on the image using TrueType rendering.
        imagettftext(
            $image,
            28,
            0,
            30,
            60,
            $color_txt,
            $GLOBALS[ 'config' ][ 'font_file' ],
            $message
        );

        //Save full alpha channel information (when sending/storing PNGs).
        imagesavealpha( $image, true );

        //Return the rendered test image.
        return $image;
    }


    /**
     *  Creates a test pattern image for verifying transparency.
     *
     *  @return An Image object with a rendered test pattern
     */
    function create_test_pattern( $size = 64, $tile = 32 ) {
        $rect = new Rectangle( $size, $size );
        $image = new Image( null, $rect );
        $im = $image->get_resource();
        $num = floor( $size / $tile );
        $c0 = imagecolorallocate( $im, 0xCC, 0xCC, 0xCC );
        $c1 = imagecolorallocate( $im, 0xFF, 0xFF, 0xFF );
        $check = false;
        for( $row = 0; $row < $num; ++$row ) {
            $y = $row * $tile;
            $check = !$check;
            for( $col = 0; $col < $num; ++$col ) {
                $x = $col * $tile;
                $c = $check ? $c0 : $c1;
                $check = !$check;
                imagefilledrectangle( $im, $x, $y, $x+$tile, $y+$tile, $c );
            }
        }
        return $image;
    }


    /**
     *  Tests a string to determine if it's a valid user-defined function.
     *
     *  @return True if this function is safe to use
     */
    function is_handler( $function_name ) {
        if( function_exists( $function_name ) == true ) {
            $functions = get_defined_functions();
            $lc_function_name = strtolower( $function_name );
            return in_array( $lc_function_name, $functions[ 'user' ] );
        }
        return false;
    }


    /**
     *  Prints a font preview in the requested font on a new image.
     *
     *  TODO:
     *  - Add support for TTF fonts to allow printing other code pages.
     *  - Allow other code pages to be requested.
     *  - Add support for multi-byte encodings.
     *
     *  GD built-in fonts support the 8859-2 code page:
     *      http://en.wikipedia.org/wiki/ISO/IEC_8859-2
     */
    function print_code_page( $font = 5 ) {

        //Basic setup.
        $chars   = 256;
        $columns = 16;
        $rows    = $chars / $columns;

        //Build a string to display each character at each code point.
        $str_cp  = '  0123456789ABCDEF';
        for( $i = 0; $i < $chars; ++$i ) {
            if( ( $i % $columns ) == 0 ) {
                $str_cp .= sprintf( "\n%X ", ( $i / $columns ) );
            }
            if( ( $i == 0 ) || ( $i == 10 ) ) {
                $str_cp .= ' ';
            }
            else {
                $str_cp .= pack( 'C', $i );
            }
        }

        //Estimate the best size for the image.
        $fw = imagefontwidth( $font );
        $fh = imagefontheight( $font );
        $iw = $fw * ( $columns + 4 );
        $ih = ( $fh + 4 ) * $rows;

        //Create a new empty image.
        $size = new Rectangle( $iw, $ih );
        $image = new Image( null, $size );
        $image->enable_errors();

        //Print the rendered characters on the image.
        $image->print_text( $str_cp );

        //Send image data to the output.
        $image->send();
    }


    /**
     *  Handles requests for test images for checkerboards.
     *
     */
    function test_handle_check() {
        $image = create_test_pattern();
        $image->send();
    }


    /**
     *  Handles requests for test images for development purposes.
     *
     */
    function test_handle_dev() {
        print_code_page();
    }


    /**
     *  Handles requests for test images that don't use the Image class.
     *
     */
    function test_handle_gd() {
        $gd_image = create_test_image();
        header( 'Content-Type: image/png' );
        imagepng( $gd_image );
        imagedestroy( $gd_image );
    }


    /**
     * Handles requests for a "normal" test image.
     *
     */
    function test_handle_normal() {
        $gd_image = create_test_image();
        $image = new Image( $gd_image );
        $image->send();
    }


    /**
     *  Handles requests for a resized test image.
     *
     */
    function test_handle_resized() {
        //resize a pretty PNG with a full alpha channel
        $image = new Image( $GLOBALS[ 'config' ][ 'test_images' ][ 2 ] );
        $w = $image->width / 2;
        $h = $image->height / 2;
        $resize = new Rectangle( $w, $h );
        $resized = new Image( $image, $resize );
        $resized->send();
    }


    /**
     *  Handles requests for an image displaying errors.
     *
     */
    function test_handle_errors() {
        //prove we know how to load a non-PNG image (JPEG)
        $image = new Image( $GLOBALS[ 'config' ][ 'test_images' ][ 1 ] );
        $image->enable_errors();
        throw new ImageException( 'Testing Error Handling' );
    }


    /*------------------------------------------------------------------------
    Execute a requested test.
    ------------------------------------------------------------------------*/

    //List of all available test image requests.
    $requests = [ 'gd', 'normal', 'resized', 'errors', 'check', 'dev' ];

    //Look for a request to serve a particular test image.
    if( ( empty( $_GET[ 'image' ] ) == false )
     && ( in_array( $_GET[ 'image' ], $requests ) == true ) ) {

        //Shortcut to request handler.
        $handler = __NAMESPACE__ . '\\test_handle_' . $_GET[ 'image' ];

        //Dispatch the appropriate request handler.
        if( is_handler( $handler ) == true ) {
            $handler();
            exit();
        }
    }

    /*------------------------------------------------------------------------
    No Image served, send an HTML document that requests the test images.
    ------------------------------------------------------------------------*/

    $sn = $_SERVER[ 'SCRIPT_NAME' ];
    echo <<<EOD
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Test Image Display</title>
  <style>
  body {
    background-image: url( "$sn?image=check" );
    background-repeat: repeat;
  }
  </style>
</head>
<body>
  <p><img src="$sn?image=normal" alt="Example Test Image"></p>
  <p><img src="$sn?image=resized" alt="Resized Test Image"></p>
  <p><img src="$sn?image=errors" alt="Errors on Test Image"></p>
</body>
</html>
EOD;

}

?>
