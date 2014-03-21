<?php
/**
 * Image manipulation class.
 *
 * @package AP5L
 * @subpackage Gfx
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: Image.php 100 2011-03-21 18:26:21Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * An object wrapper class for the GD image library
 */
class AP5L_Gfx_Image extends AP5L_Php_InflexibleObject {

    /**
     * Alpha blending mode.
     *
     * @var boolean
     */
    protected $_alphaBlendMode = false;

    /**
     * Use anti-aliased drawing functions.
     *
     * @var boolean
     */
    protected $_antiAlias = false;

    /**
     * The current draw colour.
     *
     * @var AP5L_Gfx_ColorSpace
     */
    protected $_drawColor;

    /**
     * The current fill colour.
     *
     * @var AP5L_Gfx_ColorSpace
     */
    protected $_fillColor;

    /**
     * The current image.
     *
     * @var binary_string
     */
    protected $_im;

    /**
     * Size of the image in pixels.
     *
     * @var AP5L_Math_Point2d
     */
    protected $_size;
    /**
     * Result from a bounding text box call.
     *
     * @var array
     */
    protected $_textLocn;
    /**
     * Thickness for line drawing operations.
     *
     * @var int
     */
    protected $_thickness = 1;

    static protected $_writeOptions = array(
        'bmp' => array('threshold' => null),
        'jpg' => array('quality' => 100),
        'png' => array('alpha' => true, 'quality' => 0),
    );

    /**
     * Array of problems encountered during an operation.
     *
     * @var array
     */
    public $trouble = array();

    function __construct($x = null, $y = null) {
        $this -> _size = new AP5L_Math_Point2d(0, 0);
        $this -> _drawColor = AP5L_Gfx_ColorSpace::factory();
        $this -> _fillColor = AP5L_Gfx_ColorSpace::factory();
        if (is_null($x)) {
            $this -> _im = 0;
        } else {
            $this -> create($x, $y);
        }
    }

    function __destruct() {
        AP5L::getDebug() -> writeln('Destroy ' . $this -> _im);
        if ($this -> _im) {
            imagedestroy($this -> _im);
            $this -> _im = 0;
        }
    }

    /**
     * Handle arguments that are either a AP5L_Gfx_ColorSpace or (r,g,b[,a])
     */
    protected function _csArgs($args) {
        switch (count($args)) {
            case 1: {
                if ($args[0] instanceof AP5L_Gfx_ColorSpace) {
                    $cs = $args[0];
                } else {
                    throw new AP5L_Gfx_Exception(
                        'Expected AP5L_Gfx_ColorSpace object for first argument.'
                    );
                }
            } break;

            case 3: {
                $cs = new AP5L_Gfx_ColorSpace($args[0], $args[1], $args[2]);
            } break;

            case 4: {
                $cs = new AP5L_Gfx_ColorSpace($args[0], $args[1], $args[2], $args[3]);
            } break;

            default: {
                throw new AP5L_Gfx_Exception(
                    'Incorrect arguments.'
                );
            } break;
        }
        return $cs;
    }

    /**
     * Handle arguments defining a direction: (vector), (point, angle), (int,
     * int, angle);
     *
     * @param array Array of arguments. Passed by reference; used arguments are
     * removed.
     * @throws AP5L_Gfx_Exception When no parameter match is possible.
     * @return array Array of (AP5L_Math_Point2d, angle).
     */
    protected function _directionArgs(&$args) {
        /*
        echo 'c=' . count($args) . ' n0=' . is_numeric($args[0]) . ' n1=' . is_numeric($args[1])
            . ' n2=' .  is_numeric($args[2]) . ' n3=' .  is_numeric($args[3]) . '<br/>';
        echo '<pre>' . print_r($args, true) . '</pre>';
        */
        if ($args[0] instanceof AP5L_Math_Point2d) {
            if (count($args) < 2) {
                throw new AP5L_Gfx_Exception(
                    'AP5L_Math_Point2d parameter needs angle.'
                );
            }
            $c1 = array_shift($args);
            $angle = array_shift($args);
            if (! is_numeric($angle)) {
                throw new AP5L_Gfx_Exception(
                    'Second parameter must be numeric'
                );
            }
            $result = array($c1, $angle);
        } elseif ($args[0] instanceof AP5L_Math_Vector2d) {
            $vec = array_shift($args);
            $result = array($vec -> org, $veg -> getAngle());
        } elseif (count($args) >= 3 && is_numeric($args[0]) && is_numeric($args[1])
            && is_numeric($args[2])) {
            $result = array(new AP5L_Math_Point2d($args[0], $args[1]), $args[2]);
            array_splice($args, 0, 3);
        } else {
            $msg = 'Invalid parameters (#=' . count($args) . ') ';
            for ($ind = 0; $ind < 3 && $ind < count($args); ++$ind) {
                 $msg .= get_class($args[$ind])
                    ? get_class($args[$ind]) : $args[$ind] . ', ';
            }
            throw new AP5L_Gfx_Exception($msg);
        }
        return $result;
    }

    /**
     * Set image anitaliasing property.
     *
     * The internal version doesn't throw an exception if GD is external.
     *
     * @var boolean Anti-alias flag.
     * @return boolean Previous value of flag,
     */
    protected function _setAntialias($anti) {
        if ($this -> _im && function_exists('imageantialias')) {
            if (! @imageantialias($this -> _im, $anti)) {
                throw new AP5L_Gfx_Exception('Failed in GD.');
            }
        }
        $was = $this -> _antiAlias;
        $this -> _antiAlias = $anti;
        return $was;
    }

    /**
     * Handle arguments defining two points: (box), (point, point), (point,
     * vector), or (int, int, int, int).
     *
     * @param array Array of arguments. Passed by reference; used arguments are
     * removed.
     * @param string Defines what the points refer to. In "line" mode, they
     * are endpoints of a line and a vector is returned. In "box" mode, they are
     * the corners of a box. In "center" mode, the first point defines the
     * center of a box, the second point defines the size of the box.
     * @throws AP5L_Gfx_Exception When no parameter match is possible.
     * @return AP5L_Math_Vector2d|AP5L_Math_Box2d Originating at the first
     * point, terminating at the last. In box, center, and radius modes, te
     * enclosing box is returned.
     */
    protected function _twoPointArgs(&$args, $mode) {
        if (AP5L::getDebug() -> isEnabled()) {
            AP5L::getDebug() -> writeln(
                'c=' . count($args)
                . (count($args) > 0 ? ' n0=' . is_numeric($args[0]) : '')
                . (count($args) > 1 ? ' n1=' . is_numeric($args[1]) : '')
                . (count($args) > 2 ? ' n2=' .  is_numeric($args[2]) : '')
                . (count($args) > 3 ? ' n3=' .  is_numeric($args[3]) : '')
            );
            AP5L::getDebug() -> writeln('<pre>' . print_r($args, true) . '</pre>');
        }
        /*
         * Extract c1 and c2 (corner 1 and 2) from any valid combination of
         * supplied arguments.
         */
        $done = false;
        if (count($args) >= 2 && is_numeric($args[0]) && is_numeric($args[1])) {
            $c1 = AP5L_Math_Point2d::factory($args[0], $args[1]);
            array_splice($args, 0, 2);
        } elseif ($args[0] instanceof AP5L_Math_Point2d) {
            $c1 = array_shift($args);
        } elseif ($args[0] instanceof AP5L_Math_Vector2d) {
            $c1 = $args[0] -> org;
            $c2 = $args[0] -> direction;
            array_shift($args);
            $done = true;
        } else {
            $msg = 'Invalid parameters (#=' . count($args) . ') ';
            for ($ind = 0; $ind < 4 && $ind < count($args); ++$ind) {
                 $msg .= get_class($args[$ind])
                    ? get_class($args[$ind]) : $args[$ind] . ', ';
            }
            throw new AP5L_Gfx_Exception($msg);
        }
        if (! $done) {
            if (count($args) >= 2 && is_numeric($args[0]) && is_numeric($args[1])) {
                $c2 = AP5L_Math_Point2d::factory($args[0], $args[1]);
                array_splice($args, 0, 2);
            } elseif ($args[0] instanceof AP5L_Math_Point2d) {
                $c2 = array_shift($args);
            } elseif ($args[0] instanceof AP5L_Math_Vector2d) {
                $c2 = $args[0] -> org -> add($args[0] -> direction);
                array_shift($args);
            } else {
                throw new AP5L_Gfx_Exception(
                    'Expected (int, int), AP5L_Math_Point2d, or AP5L_Math_Vector2d for second point.'
                );
            }
        }
        //---------------------------
        switch ($mode) {
            case 'line': {
                $result = AP5L_Math_Vector2d::factory($c1, $c2 -> subtract($c1));
            }
            break;

            case 'box': {
                $result = AP5L_Math_Box2d::factory($c1, $c2 -> subtract($c1));
            }
            break;

            case 'center': {
                $result = AP5L_Math_Box2d::factory($c1, $c2);
            }
            break;

            default : {
                throw new AP5L_Gfx_Exception(
                    'Unexpected mode "' . $mode . '".'
                );
            }
            break;

        }
        AP5L::getDebug() -> writeln($result);
        return $result;
    }

    /**
     * Draw an arc.
     *
     * arc(Point2d, Point2d, degStart, degEnd[, border [, fill, [style]]])
     *
     * arc(Vector2, degStart, degEnd[, border [, fill, [style]]])
     *
     * arc(xCenter, yCenter, xSize, ySize, degStart, degEnd[, border [, fill,
     * [style]]])
     *
     * The border and fill parameters are AP5L_Gfx_ColorSpace objects, boolean,
     * or missing. If provided as AP5L_Gfx_ColorSpace objects, they are used. If
     * provided and false, no border and/or fill is drawn. If not provided or if
     * provided and true, then the current line and fill colors are used.
     *
     * @throws AP5L_Gfx_Exception If the arguments are bad.
     * @return AP5L_Gfx_Image The current image object.
     *
     */
    function &arc() {
        $args = func_get_args();
        if (func_num_args() < 3) {
            throw new AP5L_Gfx_Exception(
                'Insufficient arguments.'
            );
        }
        try {
            $vec = $this -> _twoPointArgs($args, 'center');
        } catch (AP5L_Gfx_Exception $e) {
            throw new AP5L_Gfx_Exception('ellipse: ' . $e -> getMessage());
        }
        // Look for start and end angles
        if (count($args) < 2) {
            throw new AP5L_Gfx_Exception(
                'Insufficient arguments.'
            );
        }
        $degStart = array_shift($args);
        $degEnd = array_shift($args);
        // We should now have border / fill specifiers
        if (count($args)) {
            $border = array_shift($args);
        } else {
            $border = $this -> _drawColor;
        }
        if (count($args)) {
            $fill = array_shift($args);
        } else {
            $fill = $this -> _fillColor;
        }
        if (count($args)) {
            $style = array_shift($args);
        } else {
            $style = 0;
        }
        if (! $fill instanceof AP5L_Gfx_ColorSpace) {
            if (is_bool($fill)) {
                $fill = $fill ? $this -> _fillColor : null;
            } else {
                throw new AP5L_Gfx_Exception(
                    'Fill must have type boolean or AP5L_Gfx_ColorSpace'
                );
            }
        }
        $sameAsFill = false;
        if (! $border instanceof AP5L_Gfx_ColorSpace) {
            if (is_bool($border)) {
                $sameAsFill = ! $border;
                $border = $border ? $this -> _drawColor : $fill;
            } else {
                throw new AP5L_Gfx_Exception(
                    'Border must have type boolean or AP5L_Gfx_ColorSpace'
                );
            }
        }
        if (! $border) {
            throw new AP5L_Gfx_Exception(
                'Unable to determine border color.'
            );
        }
        $bc = $border -> imageColorAllocate($this -> _im);
        if ($fill) {
            $fc = $fill -> imageColorAllocate($this -> _im);
            imagefilledarc($this -> _im, $vec -> getLeft(), $vec -> getTop(),
                $vec -> direction -> x, $vec -> direction -> y,
                $degStart, $degEnd, $fc, $style);
        }
        if (! $sameAsFill) {
            // This will be wrong for some settings of $style...
            imagearc($this -> _im, $vec -> getLeft(), $vec -> getTop(),
                $vec -> direction -> x, $vec -> direction -> y,
                $degStart, $degEnd, $bc);
        }
        return $this;
    }

    /**
     * Copy from another image.
     *
     * @param AP5L_Gfx_Image The image to copy from
     * @param AP5L_Math_Vector2d Optional. An area in the source image that will
     * be copied. If missing, the entire image will be used.
     * @param AP5L_Math_Point2d|AP5L_Math_Vector2d Optional. The location where
     * the copy will be placed. If a vector is provided, this defines the
     * destination area, which may invoke source scaling. If not provided,
     * @param array Options include scale="none|resize|resample"
     * @throws AP5L_Gfx_Exception
     * @return AP5L_Gfx_Image The current image object.
     */
    function &copy($src, $from = null, $dest = null, $options = array()) {
        if ($from === null) {
            $from = AP5L_Math_Vector2d::factory($src -> getSize());
        }
        if ($dest === null) {
            $dest = AP5L_Math_Vector2d::factory($this -> _size);
        }
        AP5L::getDebug() -> write('Copy from=' . $from . ' dest=' . $dest);
        $scaleMode = 'none';
        if (! $from instanceof AP5L_Math_Vector2d) {
            throw new AP5L_Gfx_Exception(
                'copy: bad from argument ' . get_class($from) . '.'
            );
        }
        if ($dest instanceof AP5L_Math_Point2d) {
            $start = $dest;
        } elseif ($dest instanceof AP5L_Math_Vector2d) {
            // Look at scaling
            if ($dest -> direction != $from -> direction) {
                $scaleMode = isset($options['scale']) ? $options['scale'] : 'resample';
            }
            $start = $dest -> org;
        } else {
            throw new AP5L_Gfx_Exception(
                'copy: bad dest argument ' . get_class($dest) . '.'
            );
        }
        if (! $this -> _im) {
            throw new AP5L_Gfx_Exception(
                'copy: Target image not created.'
            );
        }
        switch ($scaleMode) {
            case 'resample': {
                $result = imagecopyresampled(
                    $this -> _im, $src -> getImageHandle(),
                    $start -> x, $start -> y,
                    $from -> org -> x, $from -> org -> y,
                    $dest -> getSizeX(), $dest -> getSizeY(),
                    $from -> getSizeX(), $from -> getSizeY()
                );
            }
            break;

            case 'resize': {
                $result = imagecopyresized(
                    $this -> _im, $src -> getImageHandle(),
                    $start -> x, $start -> y,
                    $from -> org -> x, $from -> org -> y,
                    $dest -> getSizeX(), $dest -> getSizeY(),
                    $from -> getSizeX(), $from -> getSizeY()
                );
            }
            break;

            default: {
                $result = imagecopy(
                    $this -> _im, $src -> getImageHandle(), $start -> x, $start -> y,
                    $from -> org -> x, $from -> org -> y,
                    $from -> getSizeX(), $from -> getSizeY()
                );
            }
            break;
        }
        if (! $result) {
            throw new AP5L_Gfx_Exception(
                __METHOD__ . ': Operation failed in GD library.'
            );
        }
        return $this;
    }

    /**
     * Allocate an image for manipulation
     *
     * @param mixed An array of integers, a 2D point, 2D box, or a scalar value
     * representing the x size. If not provided, the internal image size is used.
     * @param int|float The y size, truncated to an integer. If not provided,
     * the internal image size is used.
     * @throws AP5L_Gfx_Exception
     * @return AP5L_Gfx_Image The current image object.
     */
    function &create($x = null, $y = null) {
        if ($this -> _im) {
            throw new AP5L_Gfx_Exception(
                'create: Failed; image exists.'
            );
        }
        if (is_numeric($x)) {
            $this -> _size -> x = (int) $x;
            if (is_numeric($y)) {
                $this -> _size -> y = (int) $y;
            } else {
                $this -> _size -> y = (int) $x;
            }
        } else {
            // Handle arrays, points, boxes, and nothing
            if (is_null($x)) {
                // we use the _size without modification
            } elseif ($x instanceof AP5L_Math_Point2d) {
                $this -> _size = $x;
            } elseif ($x instanceof AP5L_Math_Box2d) {
                $this -> _size = $x -> getSize();
            } elseif (is_array($x)) {
                $this -> _size -> x = isset($x['x']) ? $x['x'] : 0;
                $this -> _size -> y = isset($x['y']) ? $x['y'] : 0;
            }
        }
        AP5L::getDebug() -> writeln(
            'create image: ' . $this -> _size -> x . ' by ' . $this -> _size -> y
        );
        $this -> _im = @imagecreatetruecolor($this -> _size -> x, $this -> _size -> y);
        if (! $this -> _im) {
            throw new AP5L_Gfx_Exception(
                'Image create ('
                    . $this -> _size -> x . ', ' . $this -> _size -> y
                . ') failed.'
            );
        }
        $this -> setAlphaBlending($this -> _alphaBlendMode);
        $this -> _setAntialias($this -> _antiAlias);
        $this -> setThickness($this -> _thickness);
        return $this;
    }

    /**
     * Destroy an allocated image.
     *
     * @return AP5L_Gfx_Image The current image object.
     */
    function &destroy() {
        if ($this -> _im) {
            imagedestroy($this -> _im);
        }
        $this -> _im = 0;
        return $this;
    }

    /**
     * Draw an ellipse.
     *
     * ellipse(Point2d, Point2d[, border [, fill]])
     *
     * ellipse(Vector2d[, border [, fill]])
     *
     * ellipse(xCenter, yCenter, xSize, ySize[, border [, fill]])
     *
     * The border and fill parameters are AP5L_Gfx_ColorSpace objects, boolean,
     * or missing. If provided as AP5L_Gfx_ColorSpace objects, they are used. If
     * provided and false, no border and/or fill is drawn. If not provided or if
     * provided and true, then the current line and fill colors are used.
     *
     * @throws AP5L_Gfx_Exception
     * @return AP5L_Gfx_Image The current image object.
     *
     */
    function ellipse() {
        $args = func_get_args();
        if (! func_num_args()) {
            throw new AP5L_Gfx_Exception(
                'Insufficient arguments.'
            );
        }
        try {
            $box = $this -> _twoPointArgs($args, 'center');
        } catch (AP5L_Gfx_Exception $e) {
            throw new AP5L_Gfx_Exception('ellipse: ' . $e -> getMessage());
        }
        // We should now have border / fill specifiers
        if (count($args)) {
            $border = array_shift($args);
        } else {
            $border = $this -> _drawColor;
        }
        if (count($args)) {
            $fill = array_shift($args);
        } else {
            $fill = $this -> _fillColor;
        }
        if (! $fill instanceof AP5L_Gfx_ColorSpace) {
            if (is_bool($fill)) {
                $fill = $fill ? $this -> _fillColor : null;
            } else {
                throw new AP5L_Gfx_Exception(
                    'Fill must have type boolean or AP5L_Gfx_ColorSpace'
                );
            }
        }
        $sameAsFill = false;
        if (! $border instanceof AP5L_Gfx_ColorSpace) {
            if (is_bool($border)) {
                $sameAsFill = ! $border;
                $border = $border ? $this -> _drawColor : $fill;
            } else {
                throw new AP5L_Gfx_Exception(
                    'Border must have type boolean or AP5L_Gfx_ColorSpace'
                );
            }
        }
        if (! $border) {
            throw new AP5L_Gfx_Exception(
                'Unable to determine border color.'
            );
        }
        $bc = $border -> imageColorAllocate($this -> _im);
        if (AP5L::getDebug() -> isEnabled()) {
            AP5L::getDebug() -> writeln('im:<pre>' . print_r($this -> _im, true) . '</pre>');
            AP5L::getDebug() -> writeln($box);
            AP5L::getDebug() -> writeln(
                'Border=' . $border . ' Fill=' . $fill
                . ' sameasfill=' . $sameAsFill
            );
            AP5L::getDebug() -> writeln('border:<pre>' . print_r($border, true) . '</pre>');
            AP5L::getDebug() -> writeln('fill:<pre>' . print_r($fill, true) . '</pre>');
            AP5L::getDebug() -> writeln(
                'ellipse ' . $box -> org -> x . ', ' . $box -> org -> y . ', ' .
                $box -> direction -> x . ', ' . $box -> direction -> y . ', ' . $bc);
        }
        $result = false;
        if ($fill) {
            $fc = $fill -> imageColorAllocate($this -> _im);
            /*
            echo 'Fillellipse ' . $fc . '<br/>';
            /**/
            $result = imagefilledellipse(
                $this -> _im, $box -> org -> x, $box -> org -> y,
                $box -> direction -> x, $box -> direction -> y, $fc);
        }
        if (! $sameAsFill) {
            /*
            echo 'Ellipse ' . $box -> getLeft() . ', ' . $box -> getTop() . ', ' .
                $box -> getRight() . ', ' . $box -> getBottom() . ', ' . $bc . '<br/>';
            /**/
            $result = imageellipse($this -> _im, $box -> org -> x, $box -> org -> y,
                $box -> direction -> x, $box -> direction -> y, $bc);
        }
        if (! $result) {
            throw new AP5L_Gfx_Exception(
                __METHOD__ . ': Operation failed in GD library.'
            );
        }
        return $this;
    }

    /**
     * Image factory.
     *
     * @param integer|float|string If this argument is a non-numeric string,
     * it is taken as a path to a file name. If it is numeric, it is taken as
     * the X dimension.
     * @param integer|float|string Y dimension, if first argument is X
     * dimension; Optional image type if first argument is a path. Not required
     * if first argument supplies X and Y dimensions.
     * @return AP5L_Gfx_Image The new image object.
     */
    static function &factory($xName, $yType = null) {
        if (is_string($xName) && !is_numeric($xName)) {
            AP5L::getDebug() -> writeln(__METHOD__ . ': read image: ' . $xName);
            $im = new AP5L_Gfx_Image();
            $im -> read($xName, $yType);
        } else {
            AP5L::getDebug() -> writeln(
                __METHOD__ . ': create image: ' . $xName . ' by ' . $yType
            );
            $im = new AP5L_Gfx_Image($xName, $yType);
        }
        return $im;
    }

    /**
     * Apply filter to image.
     *
     * @param int Filter types as defined in GD library.
     * @param mixed Integer when filter requres an argument. AP5L_Gfx_ColorSpace
     * or array of integer (r, g, b) if filter is IMG_FILTER_COLORIZE.
     * @throws AP5L_Gfx_Exception If colorize parameter isn't valid, or if operation
     * failed.
     * @return AP5L_Gfx_Image The current image object.
     */
    function filter($filterType, $param = null) {
        switch ($filterType) {
            case IMG_FILTER_COLORIZE: {
                if ($param instanceof AP5L_Gfx_ColorSpace) {
                    $result = imagefilter(
                        IMG_FILTER_COLORIZE,
                        $param -> getRedInt(),
                        $param -> getBlueInt(),
                        $param -> getGreenInt()
                    );
                } elseif (is_array($param)) {
                    $result = imagefilter(
                        IMG_FILTER_COLORIZE, $param[0], $param[1], $param[2]
                    );
                } else {
                    throw new AP5L_Gfx_Exception(
                        'Colorize filter requires AP5L_Gfx_ColorSpace or RGB array parameter.'
                    );
                }
            }
            break;

            default: {
                $result = imagefilter($this -> _im, $filterType, $param);
            }
        }
        if (! $result) {
            throw new AP5L_Gfx_Exception(
                'Operation failed in GD library'
                . ($this -> _im ? '' : ' (image not created)') . '.'
            );
        }
        return $this;
    }

    /**
     * Get current anti-alias setting.
     *
     * @return boolean Current value of the anti-alias flag.
     */
    function getAntialias() {
        return $this -> _antiAlias;
    }

    /**
     * Return the internal image handle.
     *
     * @return resource Image
     */
    function getImageHandle() {
        return $this -> _im;
    }

    /**
     * Get color of a pixel as a ColorSpace object.
     *
     * @param int|AP5L_Math_Point2d The point to read or the x coordinate of the
     * point.
     * @param int The y coordinate, required if x is an integer.
     * @return AP5L_Gfx_ColorSpace|boolean The image color at the specified
     * coordinates. False if the coordinate is invalid.
     */
    function &getPixel($x, $y = null) {
        if ($x instanceof AP5L_Math_Point2d) {
            $c = imagecolorat($this -> _im, $x -> x, $x -> y);
        } else {
            $c = imagecolorat($this -> _im, $x, $y);
        }
        $cs = new AP5L_Gfx_ColorSpace();
        $cs -> setRgbInt($c);
        return $cs;
    }

    /**
     * Get color of a pixel as a packed integer.
     *
     * @param int|AP5L_Math_Point2d The point to read or the x coordinate of the
     * point.
     * @param int The y coordinate, required if x is an integer.
     * @return int|boolean The image color. False if the coordinate is invalid.
     */
    function getPixelInt($x, $y = null) {
        if ($x instanceof AP5L_Math_Point2d) {
            return imagecolorat($this -> _im, $x -> x, $x -> y);
        }
        return imagecolorat($this -> _im, $x, $y);
    }

    /**
     * Get the image size.
     *
     * @return AP5L_Math_Point2d The image dimensions.
     */
    function getSize() {
        return $this -> _size;
    }

    /**
     * Get the bounding box of the last text operation (see
     * {@see text()}.
     *
     * @return array Four AP5L_Math_Point2d objects that define the box.
     */
    function getTextBox() {
        return $this -> _textLocn;
    }

    /**
     * Get the current line thickness.
     *
     * @return int Line thickness, in pixels.
     */
    function getThickness() {
        return $this -> _thickness;
    }

    /**
     * Draw a line.
     *
     * line(Point2d, Point2d[, color])
     *
     * line(Vector2d[, color])
     *
     * line(x1, y1, x2, y2[, color])
     *
     * The color parameter is a AP5L_Gfx_ColorSpace object. If missing, the
     * current line color is used.
     *
     * @throws AP5L_Gfx_Exception
     * @return AP5L_Gfx_Image The current image object.
     */
    function &line() {
        $args = func_get_args();
        if (! func_num_args()) {
            throw new AP5L_Gfx_Exception(
                'Insufficient arguments.'
            );
        }
        try {
            $vec = $this -> _twoPointArgs($args, 'line');
        } catch (AP5L_Gfx_Exception $e) {
            throw new AP5L_Gfx_Exception('line: ' . $e -> getMessage());
        }
        // We should now have an optional line color
        if (count($args)) {
            $draw = array_shift($args);
        } else {
            $draw = $this -> _drawColor;
        }
        if (! $draw instanceof AP5L_Gfx_ColorSpace) {
            throw new AP5L_Gfx_Exception(
                'Line color must have type AP5L_Gfx_ColorSpace'
            );
        }
        $dc = $draw -> imageColorAllocate($this -> _im);
        $end = $vec -> getEnd();
        /*
        echo $vec -> org -> __toString() . ' ' . $end -> __toString() . '<br/>';
        echo 'draw:<pre>' . print_r($draw, true) . '</pre>';
        echo 'col ' . $dc . '<br/>';
        /**/
        $result = imageline($this -> _im, $vec -> org -> x, $vec -> org -> y,
            $end -> x, $end -> y, $dc);
        if (! $result) {
            throw new AP5L_Gfx_Exception(
                __METHOD__ . ': Operation failed in GD library.'
            );
        }
        return $this;
    }

    /**
     * Apply an image mask.
     *
     * This method takes a mask image, and an overlay image or color. It applies
     * the overlay pixel by pixel, in accordance with the value in the mask.
     *
     * @param AP5L_Math_Point2d The start position.
     * @param AP5L_Gfx_Image A mask. The mask is assumed to be monochrome (only
     * the blue component is used). The color value defines the percentage of
     * the overlay image that will be applied.
     * @param AP5L_Gfx_Image|AP5L_Gfx_ColorSpace|int A mask image or color.
     * @return AP5L_Gfx_Image The current image object.
     */
    function &mask($dest, $mask, $overlay) {
        $im = $this -> _im;
        $ox = $dest -> x;
        $oy = $dest -> y;
        $ms = $mask -> getSize();
        $mi = $mask -> getImageHandle();
        if ($overlay instanceof AP5L_Gfx_Image) {
            $oi = $overlay -> getImageHandle();
            $oc = 0;
        } elseif ($overlay instanceof AP5L_Gfx_ColorSpace) {
            $oi = 0;
            $oc = $overlay -> getRgbaInt();
        } else {
            $oc = $overlay;
        }
        for ($y = 0; $y < $ms -> y; ++$y) {
            for ($x = 0; $x < $ms -> x; ++$x) {
                $pct = (imagecolorat($mi, $x, $y) & 0xFF) / 255;
                if ($oi) {
                    $oc = imagecolorat($oi, $x, $y);
                }
                $color = AP5L_Gfx_ColorSpace::rgbaIntBlend(
                    imagecolorat($im, $ox + $x, $oy + $y),
                    $oc,
                    $pct
                );
                imagesetpixel($im, $x, $y, $color);
            }
        }
        return $this;
    }

    /**
     * Merge another image into this one.
     *
     * @param AP5L_Gfx_Image The image to merge from
     * @param AP5L_Math_Vector2d Optional. An area in the source image that will
     * be copied. If missing, the entire image is assumed.
     * @param AP5L_Math_Point2d|AP5L_Math_Vector2d Optional. The location where
     * the copy will be placed. If a vector is provided, this defines the
     * destination area, which may invoke source scaling. If missing, the entire
     * image area is assumed.
     * @param array Options. Possible options are<ul><li>
     * "percent": The percentage of the source image to be used (0 has no
     * effect, 100 completely overwrites the destination)
     * </li><li>"scale": One of none, resample, or resize. Only used if the
     * destination area differs from the source area. If scaling is required but
     * not specified, resampling is used.
     * </li><li>"gray": If set, the destination area is converted to grayscale
     * before copying, thus preserving the hue of the source image.
     * </li></ul>
     * @throws AP5L_Gfx_Exception On bad arguments or operation failure.
     * @return AP5L_Gfx_Image The current image object.
     */
    function &merge($src, $from = null, $dest = null, $options = array()) {
        $scaleMode = 'none';
        if ($from === null) {
            $from = AP5L_Math_Vector2d::factory($src -> getSize());
        }
        if ($dest === null) {
            $dest = AP5L_Math_Vector2d::factory($this -> _size);
        }
        AP5L::getDebug() -> writeln('Merge from=' . $from . ' dest=' . $dest);
        if (! $from instanceof AP5L_Math_Vector2d) {
            throw new AP5L_Gfx_Exception(
                'Bad from argument ' . get_class($from) . '.'
            );
        }
        if ($dest instanceof AP5L_Math_Point2d) {
            $start = $dest;
        } elseif ($dest instanceof AP5L_Math_Vector2d) {
            // Look at scaling
            if (!$dest -> direction -> equals($from -> direction)) {
                $scaleMode = isset($options['scale']) ? $options['scale'] : 'resample';
            }
            $start = $dest -> org;
        } else {
            throw new AP5L_Gfx_Exception(
                'Bad dest argument ' . get_class($dest) . '.'
            );
        }
        if (! $this -> _im) {
            throw new AP5L_Gfx_Exception(
                'Target image not created.'
            );
        }
        $srcImg = $src -> getImageHandle();
        $srcMixPct = isset($options['percent']) ? $options['percent'] : 100;
        AP5L::getDebug() -> writeln(
            'Merge scalemode=' . $scaleMode
            . ' from=' . $srcImg . ' to=' . $this -> _im
            . ' dx=' . $start -> x . ' dy=' . $start -> y
            . ' sx=' . $from -> org -> x . ' sy=' . $from -> org -> y
            . ' w=' . $from -> direction -> x . ' h=' . $from -> direction -> y
            . ' mix=' . $srcMixPct
        );
        if ($scaleMode != 'none') {
            /*
             * The merge functions don't know anything about rescaling, so we
             * have to do it ourselves in an intermediate image.
             */
            $tempImg = imagecreatetruecolor($dest -> getSizeX(), $dest -> getSizeY());
            if (! $tempImg) {
                throw new AP5L_Gfx_Exception('Unable to create intermediate image.');
            }
            if ($scaleMode == 'resize') {
                $result = imagecopyresized(
                    $tempImg, $srcImg,
                    $start -> x, $start -> y,
                    $from -> org -> x, $from -> org -> y,
                    $dest -> getSizeX(), $dest -> getSizeY(),
                    $from -> getSizeX(), $from -> getSizeY()
                );
            } else {
                $result = imagecopyresampled(
                    $tempImg, $srcImg,
                    $start -> x, $start -> y,
                    $from -> org -> x, $from -> org -> y,
                    $dest -> getSizeX(), $dest -> getSizeY(),
                    $from -> getSizeX(), $from -> getSizeY()
                );
            }
            if (! $result) {
                throw new AP5L_Gfx_Exception('Failed while scaling to intermediate image.');
            }
            $srcImg = $tempImg;
            AP5L::getDebug() -> writeln('Merge: new from=' . $srcImg);
        }
        if (isset($options['gray']) && $options['gray']) {
            $result = imagecopymergegray(
                $this -> _im, $srcImg,
                $start -> x, $start -> y,
                $from -> org -> x, $from -> org -> y,
                $from -> direction -> x, $from -> direction -> y,
                $srcMixPct
            );
        } else {
            $result = imagecopymerge(
                $this -> _im, $srcImg,
                $start -> x, $start -> y,
                $from -> org -> x, $from -> org -> y,
                $from -> direction -> x, $from -> direction -> y,
                $srcMixPct
            );
        }
        if (! $result) {
            throw new AP5L_Gfx_Exception(
                'Operation failed in GD library'
                . ($this -> _im ? '' : ' (image not created)') . '.'
            );
        }
        return $this;
    }

    /**
     * Output the image to the browser.
     *
     * @param string Image type.
     * @throws AP5L_Gfx_Exception
     * @return AP5L_Gfx_Image The current image object.
     */
    function &output($type = 'png') {
        switch (strtolower($type)) {
            case 'png': {
                imagepng($this -> _im);
            }
            break;

            default: {
                throw new AP5L_Gfx_Exception(' type "' . $type . '" not supported.');
            }
            break;
        }
        return $this;
    }

    /**
     * Read an image from file.
     *
     * @param string Path to the file to be read, or image data
     * @param int Optional. One of the IMAGETYPE_ constants defined in the
     * GD library, or a type string. Defined type strings are "string" and
     * "string.base64".
     * @throws AP5L_Gfx_Exception
     * @return AP5L_Gfx_Image The current image object.
     */
    function &read($path, $type = false) {
        if (! $type) {
            $info = @getimagesize($path);
            if (! $info) {
                throw new AP5L_Gfx_Exception(
                    'read: "'. $path . '" is not an image.'
                );
            }
            $this -> _size -> x = $info[1];
            $this -> _size -> y = $info[0];
            $type = $info[2];
        }
        switch ($type) {
            case IMAGETYPE_GIF: {
                $this -> _im = @imagecreatefromgif($path);
            }
            break;
            case IMAGETYPE_JPEG: {
                $this -> _im = @imagecreatefromjpeg($path);
            }
            break;
            case IMAGETYPE_PNG: {
                $this -> _im = @imagecreatefrompng($path);
            }
            break;
            case IMAGETYPE_WBMP: {
                $this -> _im = @imagecreatefromwbmp($path);
            }
            break;
            case 'string': {
                $this -> _im = @imagecreatefromstring($path);
            }
            break;
            case 'string.base64': {
                $this -> _im = @imagecreatefromstring(base64_decode($path));
            }
            break;
            default: {
                throw new AP5L_Gfx_Exception(
                    'read: unsupported image file type ' . $type
                );
            }
        }
        if (! $this -> _im) {
            throw new AP5L_Gfx_Exception(
                'read failed: ' . $path
            );
        }
        if (imageistruecolor($this -> _im)) {
            $this -> _size -> x = imagesx($this -> _im);
            $this -> _size -> y = imagesy($this -> _im);
        } else {
            $pim = $this -> _im;
            $sx = imagesx($pim);
            $sy = imagesy($pim);
            $tcim = imagecreatetruecolor($sx, $sy);
            imagecopy($tcim, $pim, 0, 0, 0, 0, $sx, $sy);
            $this -> _im = $tcim;
            imagedestroy($pim);
            $this -> _size -> x = $sx;
            $this -> _size -> y = $sy;
        }
        $this -> setAlphaBlending($this -> _alphaBlendMode);
        $this -> _setAntialias($this -> _antiAlias);
        $this -> setThickness($this -> _thickness);
        return $this;
    }

    /**
     * Draw a rectangle.
     *
     * rectangle(Point2d, Point2d[, border [, fill]])
     *
     * rectangle(Vector2d[, border [, fill]])
     *
     * rectangle(x1, y1, x2, y2[, border [, fill]])
     *
     * The border and fill parameters are AP5L_Gfx_ColorSpace objects, boolean,
     * or missing. If provided as AP5L_Gfx_ColorSpace objects, they are used. If
     * provided and false, no border and/or fill is drawn. If not provided or if
     * provided and true, then the current line and fill colors are used.
     *
     * @throws AP5L_Gfx_Exception
     * @return AP5L_Gfx_Image The current image object.
     */
    function &rectangle() {
        $args = func_get_args();
        if (! func_num_args()) {
            throw new AP5L_Gfx_Exception(
                'Insufficient arguments.'
            );
        }
        try {
            $box = $this -> _twoPointArgs($args, 'box');
            $end = $box -> getEnd();
        } catch (AP5L_Gfx_Exception $e) {
            throw new AP5L_Gfx_Exception('rectangle: ' . $e -> getMessage());
        }
        // We should now have border / fill specifiers
        if (count($args)) {
            $border = array_shift($args);
        } else {
            $border = $this -> _drawColor;
        }
        if (count($args)) {
            $fill = array_shift($args);
        } else {
            $fill = $this -> _fillColor;
        }
        if (! $fill instanceof AP5L_Gfx_ColorSpace) {
            if (is_bool($fill)) {
                $fill = $fill ? $this -> _fillColor : null;
            } else {
                throw new AP5L_Gfx_Exception(
                    'Fill must have type boolean or AP5L_Gfx_ColorSpace.'
                );
            }
        }
        $sameAsFill = false;
        if (! $border instanceof AP5L_Gfx_ColorSpace) {
            if (is_bool($border)) {
                $sameAsFill = ! $border;
                $border = $border ? $this -> _drawColor : $fill;
            } else {
                throw new AP5L_Gfx_Exception(
                    'Border must have type boolean or AP5L_Gfx_ColorSpace'
                );
            }
        }
        if (! $border) {
            throw new AP5L_Gfx_Exception(
                'Unable to determine border color.'
            );
        }
        $bc = $border -> imageColorAllocate($this -> _im);
        $debug = &AP5L::getDebug();
        if ($debug -> isEnabled()) {
            $dbh = $debug -> getHandle();
            $debug -> writeln('im:' . print_r($this -> _im, true), $dbh);
            $debug -> writeln($box, $dbh);
            $debug -> writeln('Border=' . $border, $dbh);
            $debug -> writeln('Fill=' . $fill
                . ' sameasfill=' . $sameAsFill, $dbh);
            $debug -> writeln('border:<pre>' . print_r($border, true) . '</pre>', $dbh);
            $debug -> writeln('fill:<pre>' . print_r($fill, true) . '</pre>', $dbh);
            $debug -> writeln(
                'rect ' . $box -> org -> x . ', ' . $box -> org -> y . ', ' .
                $end -> x . ', ' . $end -> y . ', ' . $bc, $dbh);
        }
        $result = false;
        if ($fill) {
            $fc = $fill -> imageColorAllocate($this -> _im);
            /*
            echo 'Fillrect ' . $fc . '<br/>';
            /**/
            $result = imagefilledrectangle($this -> _im, $box -> org -> x, $box -> org -> y,
                $end -> x, $end -> y, $fc);
        }
        if (! $sameAsFill) {
            /*
            echo 'Rectangle ' . $box -> getLeft() . ', ' . $box -> getTop() . ', ' .
                $box -> getRight() . ', ' . $box -> getBottom() . ', ' . $bc . '<br/>';
            /**/
            $result = imagerectangle($this -> _im, $box -> org -> x, $box -> org -> y,
                $end -> x, $end -> y, $bc);
        }
        if (! $result) {
            throw new AP5L_Gfx_Exception(
                __METHOD__ . ': Operation failed in GD library.'
            );
        }
        return $this;
    }

    /**
     * Set the alpha blending mode.
     *
     * When set, the alpha value of pixels being applied to an image is used to
     * determine the proportion of the pixel's value to be used, and the
     * resulting pixel is opaque. When false, the resulting pixel contains the
     * combined alpha values.
     *
     * @param boolean Alpha blending mode.
     * @return AP5L_Gfx_Image The current image object.
     */
    function &setAlphaBlending($blend = false) {
        $this -> _alphaBlendMode = $blend;
        if ($this -> _im) {
            AP5L::getDebug() -> writeln(
                'Alpha blend ' . ($blend ? 'T' : 'F') . ' for ' . $this -> _im
            );
            $result = @imagealphablending($this -> _im, $blend);
            if (! $result) {
                throw new AP5L_Gfx_Exception('Unable to set alpha blending.');
            }
        }
        return $this;
    }

    /**
     * Set image anitaliasing property.
     *
     * @param boolean Anti-alias flag.
     * @throws AP5L_Gfx_Exception
     * @return AP5L_Gfx_Image The current image object.
     */
    function &setAntialias($anti) {
        if (! function_exists('imageantialias')) {
            throw new AP5L_Gfx_Exception('Bundled version of GD is required.');
        }
        $this -> _setAntialias($anti);
        return $this;
    }

    /**
     * Set the drawing color.
     *
     * Expects a AP5L_Gfx_ColorSpace object or r[, g, b [, alpha]].
     * See {@see AP5L_Gfx_ColorSpace::factory()} for details.
     *
     * @return AP5L_Gfx_Image The current image object.
     */
    function &setDrawColor() {
        $cs = AP5L_Gfx_ColorSpace::factory(func_get_args());
        if (! $cs) {
            throw new AP5L_Gfx_Exception(__METHOD__ . ': Invalid color.');
        }
        $this -> _drawColor = $cs;
        return $this;
    }

    /**
     * Set the fill color.
     *
     * Expects a AP5L_Gfx_ColorSpace object or r[, g, b [, alpha]].
     * See {@see AP5L_Gfx_ColorSpace::factory()} for details.
     *
     * @return AP5L_Gfx_Image The current image object.
     */
    function &setFillColor() {
        $cs = AP5L_Gfx_ColorSpace::factory(func_get_args());
        if (! $cs) {
            throw new AP5L_Gfx_Exception(__METHOD__ . ': Invalid color.');
        }
        $this -> _fillColor = $cs;
        return $this;
    }

    /**
     * Set the value of a pixel.
     *
     * @param mixed If a point class is provided, the X and Y coordinates
     * of the pixel to be set. Otherwise, this is the X coordinate.
     * @param mixed If the first parameter is a point, this is a
     * AP5L_Gfx_ColorSpace object, if the first parameter is scalar, this
     * is the Y coordinate.
     * @param AP5L_Gfx_ColorSpace Optional. Not required if the first
     * argument is a point.
     * @throws AP5L_Gfx_Exception
     * @return AP5L_Gfx_Image The current image object.
     */
    function &setPixel($x, $yColor, $color = null) {
        if ($x instanceof AP5L_Math_Point2d) {
            $color = $yColor;
            $y = $x -> y;
            $x = $x -> x;
        } else {
            $y = $yColor;
        }
        if ($color === null) {
            $color = $this -> _drawColor;
        }
        if ($color instanceof AP5L_Gfx_ColorSpace) {
            $color = $color -> getRgbaInt();
        }
        $result = imagesetpixel($this -> _im, $x, $y, $color);
        if (! $result) {
            throw new AP5L_Gfx_Exception(
                __METHOD__ . ': Operation failed in GD library.'
            );
        }
        return $this;
    }

    /**
     * Set the current line thickness.
     *
     * @param int Line thickness, in pixels.
     */
    function setThickness($thickness) {
        $this -> _thickness = $thickness;
        if ($this -> _im) {
            imagesetthickness($this -> _im, $thickness);
        }
        return $this -> _thickness;
    }

    /**
     * Set default write options.
     *
     * @param string File type (extension).
     * @param string Option name.
     * @param string New default value.
     */
    static function setWriteOption($type, $option, $value) {
        if (!isset(self::$_writeOptions[strtolower($type)])) {
            throw new AP5L_Gfx_Exception(
                __METHOD__ . ': Unrecognized extension: ' . $type . '.'
            );
        }
        self::$_writeOptions[strtolower($type)][$option] = $value;
    }

    /**
     * Write OpenType text.
     *
     * text(point, angle, text, font, size[, drawcolor])
     *
     * text(vector,
     *
     * text(x, y, angle,
     *
     * @throws AP5L_Gfx_Exception
     * @return AP5L_Gfx_Image The current image object.
     */
    function &text() {
        $args = func_get_args();
        try {
            $direction = $this -> _directionArgs($args);
        } catch (AP5L_Gfx_Exception $e) {
            throw new AP5L_Gfx_Exception('text: ' . $e -> getMessage());
        }
        $text = array_shift($args);
        $font = array_shift($args);
        $size = array_shift($args);
        $draw = count($args) ? $args[0] : $this -> _drawColor;
        $dc = $draw -> imageColorAllocate($this -> _im);
        /*
        echo 'size=', $size, ' ang=', $direction[1], ' x=',
            $direction[0] -> x, ' y=', $direction[0] -> y, ' dc=', $dc,
            ' f=', $font, ' t=', $text, AP5L::NL;
        /**/
        $result = @imagettftext($this -> _im, $size, $direction[1],
            $direction[0] -> x, $direction[0] -> y,
            $dc, $font, $text);
        if (! $result) {
            throw new AP5L_Gfx_Exception(
                __METHOD__ . ': Operation failed in GD library.'
            );
        }
        $this -> _textLocn = array(
            AP5L_Math_Point2d::factory($result[0], $result[1]),
            AP5L_Math_Point2d::factory($result[2], $result[3]),
            AP5L_Math_Point2d::factory($result[4], $result[5]),
            AP5L_Math_Point2d::factory($result[6], $result[7]),
        );
        return $this;
    }

    /**
     * Write the image to file or output stream.
     *
     * @param string The output file path, ot null to use the output stream.
     * @param string Optional file type, defaults to "png".
     * @param array Options. For PNG, Options are: quality (default 0), filter
     * (default PNG_NO_FILTER), and alpha (default true); for WBMP the option is
     * "threshold" (default null)
     * @return AP5L_Gfx_Image The current image object.
     */
    function write($fid = null, $type = 'png', $options = array()) {
        if (empty($type)) {
            $type = array_pop(explode('.', $fid));
        }
        switch (strtolower($type)) {
            case 'bmp': {
                image2wbmp(
                    $this -> _im, $fid,
                    isset($options['threshold']) ? $options['threshold']
                    : self::$_writeOptions['wbmp']['threshold']
                );
            }
            break;

            case 'jpg': {
                if (! isset($options['quality'])) {
                    $options['quality'] = self::$_writeOptions['jpg']['quality'];
                }
                imagejpeg($this -> _im, $fid, $options['quality']);
            }
            break;

            case 'png': {
                if (is_null($fid)) {
                    // We must have non-null values for quality and filters
                    if (! isset($options['quality'])) {
                        $options['quality'] = self::$_writeOptions['png']['quality'];
                    }
                    if (! isset($options['filter'])) {
                        $options['quality'] = PNG_NO_FILTER;
                    }
                } else {
                    $options['quality'] = null;
                    $options['filter'] = null;
                }
                if (! isset($options['alpha'])) {
                    $options['alpha'] = self::$_writeOptions['png']['alpha'];
                }
                imagesavealpha($this -> _im, $options['alpha']);
                imagepng($this -> _im, $fid, $options['quality'], $options['filter']);
            }
            break;

        }
        return $this;
    }

}


