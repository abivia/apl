<?php
/**
 * Abivia PHP5 Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: ImageFill.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Image area fills
 *
 * This is a helper class for AP5L_Gfx_Image that provides a variety of area
 * fill methods.
 * 
 * @package AP5L
 * @subpackage Gfx
 */
class AP5L_Gfx_ImageFill extends AP5L_Php_InflexibleObject {

    /**
     * Apply a fill map to an image.
     *
     * @param AP5L_Gfx_Image The image to modify.
     * @param AP5L_Math_Vector2d The area in the image to fill.
     * @param AP5L_Gfx_FillMap The fill mapper.
     * @param array AP5L_Gfx_ColorSpace The colors used in the fill.
     */
    static function fill(&$im, $area, $map, $colors = array()) {
        $aa = $im -> setAntialias(false);
        $ih = $im -> getImageHandle();
        $ix = $map -> incrX;
        $iy = $map -> incrY;
        $bx = $area -> org -> x;
        $by = $area -> org -> y;
        if ($map -> iterateMode == 'xy') {
            /*
             * Set the color at each point in the area
             */
            for ($x = $map -> startX; $x != $map -> endX; $x += $ix) {
                for ($y = $map -> startY; $y != $map -> endY; $y += $iy) {
                    try {
                        imagesetpixel(
                            $ih, $bx + $x, $by + $y,
                            $map -> getColorInt($x, $y, $colors)
                        );
                    } catch (AP5L_Gfx_Exception $trap) {
                        if ($trap -> getCode() != AP5L_Gfx_Exception::NOTHING_TO_DO) {
                            throw $trap;
                        }
                    }
                }
            }
        } elseif ($map -> iterateMode == 'r') {
            /*
             * Set a mosaic of rectangles
             */
            for ($x = $map -> startX; $x != $map -> endX; $x += $ix) {
                for ($y = $map -> startY; $y != $map -> endY; $y += $iy) {
                    try {
                        imagefilledrectangle(
                            $ih,
                            $bx + $x, $by + $y,
                            $bx + $x + $ix - 1, $by + $y + $iy - 1,
                            $map -> getColorInt($x, $y, $colors)
                        );
                    } catch (AP5L_Gfx_Exception $trap) {
                        if ($trap -> getCode() != AP5L_Gfx_Exception::NOTHING_TO_DO) {
                            throw $trap;
                        }
                    }
                }
            }
        } elseif ($map -> iterateMode == 'y') {
            // simplified case: parallel to the y axis
            $sx = $area -> org -> x + $map -> startX;
            $ex = $area -> org -> x + $map -> endX;
            for ($y = $map -> startY; $y < $map -> endY; $y += $iy) {
                try {
                    imagefilledrectangle(
                        $ih, $sx, $by + $y, $ex - 1, $by + $y,
                        $map -> getColorInt(0, $y, $colors)
                    );
                } catch (AP5L_Gfx_Exception $trap) {
                    if ($trap -> getCode() != AP5L_Gfx_Exception::NOTHING_TO_DO) {
                        throw $trap;
                    }
                }
            }
        } elseif ($map -> iterateMode == 'x') {
            // simplified case: parallel to the x axis
            $sy = $area -> org -> y + $map -> startY;
            $ey = $area -> org -> y + $map -> endY;
            for ($x = $map -> startX; $x < $map -> endX; $x += $ix) {
                try {
                    imagefilledrectangle(
                        $ih, $bx + $x, $sy, $bx + $x, $ey - 1,
                        $map -> getColorInt($x, 0, $colors)
                    );
                } catch (AP5L_Gfx_Exception $trap) {
                    if ($trap -> getCode() != AP5L_Gfx_Exception::NOTHING_TO_DO) {
                        throw $trap;
                    }
                }
            }
        }
        $im -> setAntialias($aa);
    }

    /**
     * Single color flat fill.
     *
     * This creates a single color flat fill in the requested area of the image.
     *
     * @param AP5L_Gfx_Image The image to modify.
     * @param AP5L_Math_Vector2d The area in the image to fill.
     * @param AP5L_Gfx_ColorSpace The fill color.
     * @param array Options. Option is "debug" true/false (default false).
     */
    static function flat(&$im, $area, $fill, $options = array()) {
        $map = new AP5L_Gfx_FillMap_Flat();
        $map -> setup($area);
        if (isset($options['debug'])) {
            $map -> debug = $options['debug'];
        }
        self::fill($im, $area, $map, array($fill));
    }

    /**
     * Use an image to generate a border.
     *
     * This takes a "template" image that contains a compressed definition of
     * a border and applies it to the image.
     *
     * @param AP5L_Gfx_Image The image to modify.
     * @param AP5L_Math_Vector2d The area in the image to fill.
     * @param int The border depth in pixels.
     * @param AP5L_Gfx_Image|string The template image or image file name.
     * @param array Options. Options are: "debug" true/false (default false).
     */
    static function imageBorder(&$im, $area, $depth, $template, $options = array()) {
        if (is_string($template)) {
            $template = AP5L_Gfx_Image::factory($template);
        }
        $map = new AP5L_Gfx_FillMap_ImageBorder();
        $map -> setup($area, $depth, $template);
        if (isset($options['debug'])) {
            $map -> debug = $options['debug'];
        }
        self::fill($im, $area, $map);
    }

    /**
     * Linear fill along a direction vector.
     *
     * This causes a linear transition fill in the requested area of the image.
     * The direction of fill determines the points in the area that have the
     * start and end colours.
     *
     * @param AP5L_Gfx_Image The image to modify.
     * @param AP5L_Math_Vector2d The area in the image to fill.
     * @param float The direction of fill, clockwise from left to right
     * horizontal.
     * @param AP5L_Gfx_ColorSpace The starting color.
     * @param AP5L_Gfx_ColorSpace The ending color.
     * @param array Options. Options are: "bands" (default 0); "bandmidpoint":
     * true/false (default true); "debug" true/false (default false).
     */
    static function linear(&$im, $area, $angle, $from, $to, $options = array()) {
        $map = new AP5L_Gfx_FillMap_Linear();
        $map -> setup($area, $angle);
        if (isset($options['bands'])) {
            $map -> bands = $options['bands'];
        }
        if (isset($options['bandmidpoint'])) {
            $map -> bandMidpoint = $options['bandmidpoint'];
        }
        if (isset($options['debug'])) {
            $map -> debug = $options['debug'];
        }
        self::fill($im, $area, $map, array($from, $to));
    }

    /**
     * Rectagular fill from an arbitrary internal point
     *
     * This causes a fill from a specified point to the edges of an area.
     * Various transition modes are supported.
     *
     * @param AP5L_Gfx_Image The image to modify.
     * @param string Transition function; one of (linear, sin, ...)
     * @param AP5L_Math_Vector2d The area in the image to fill.
     * @param AP5L_Math_Point2d The anchor point containing the starting color.
     * @param AP5L_Gfx_ColorSpace The starting color.
     * @param AP5L_Gfx_ColorSpace The ending color.
     * @param array Options. Options are: "bands" (default 0); "bandmidpoint":
     * true/false (default true); "debug" true/false (default false).
     */
    static function rectangular(&$im, $subType, $area, $anchor, $from, $to, $options = array()) {
        $map = new AP5L_Gfx_FillMap_Rectangular();
        $map -> setup($subType, $area, $anchor);
        if (isset($options['bands'])) {
            $map -> bands = $options['bands'];
        }
        if (isset($options['bandmidpoint'])) {
            $map -> bandMidpoint = $options['bandmidpoint'];
        }
        if (isset($options['debug'])) {
            $map -> debug = $options['debug'];
        }
        self::fill($im, $area, $map, array($from, $to));
    }

}

