<?php
/**
 * Support for two dimensional vectors
 *
 * @package AP5L
 * @subpackage Math
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: Vector2d.php 100 2011-03-21 18:26:21Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Two-dimensional vector.
 */
class AP5L_Math_Vector2d {
    /**
     * The origin of the vector.
     *
     * @var AP5L_Math_Point2d
     */
    public $org;

    /**
     * The direction of the vector.
     *
     * @var AP5L_Math_Point2d
     */
    public $direction;

    /**
     * Construct a new vector.
     */
    function __construct() {
        $this -> org = new AP5L_Math_Point2d();
        $this -> direction = new AP5L_Math_Point2d();
    }

    /**
     * Convert vector to string.
     *
     * @return string Representation of this vector as a string.
     */
    function __toString() {
        return get_class($this) . '(' . $this -> org -> __toString()
            . '->' . $this -> direction -> __toString() . ')';
    }

    /**
     * Move the vector origin by adding a point/vector.
     *
     * If a point is passed, the point value is added to the vector origin. If a
     * vector is passed, the vector's direction is added to the current vector.
     *
     * @param AP5L_Math_Point2dAP5L_Math_Vector2d Amount to add.
     * @return AP5L_Math_Vector2d The repositioned vector.
     */
    function &add($pointVec) {
        $result = clone $this;
        if ($pointVec instanceof AP5L_Math_Point2d) {
            $result -> org -> x += $pointVec -> x;
            $result -> org -> y += $pointVec -> y;
        } elseif ($pointVec instanceof AP5L_Math_Vector2d) {
            $result -> org -> x += $pointVec -> direction -> x;
            $result -> org -> y += $pointVec -> direction -> y;
        }
        return $result;
    }

    /**
     * Compare vectors.
     *
     * @param AP5L_Math_Vector2d Vector to compare.
     * @return boolean True if vectors match.
     */
    function equals($vector) {
        return $this -> org -> equals($vector -> org)
            && $this -> direction -> equals($vector -> direction);
    }

    /**
     * Create a vector from another vector or point.
     * 
     * @param AP5L_Math_Vector2d|AP5L_Math_Point2d Vector: vector to be cloned;
     * Point: If direction is provided, this is the vector's start point. If it
     * is not, then the result is a vector from (0,0) to this point.
     * @param AP5L_Math_Point2d End point.
     * @throws Exception on bad parameters
     * @return AP5L_Math_Vector2d
     */
    static function &factory($org, $direction = null) {
        if ($org instanceof AP5L_Math_Vector2d) {
            $result = clone $org;
        } elseif ($org instanceof AP5L_Math_Point2d && $direction === null) {
            $result = new AP5L_Math_Vector2d();
            $result -> org = new AP5L_Math_Point2d();
            $result -> direction = $org;
        } elseif ($org instanceof AP5L_Math_Point2d && $direction instanceof AP5L_Math_Point2d) {
            $result = new AP5L_Math_Vector2d();
            $result -> org = $org;
            $result -> direction = $direction;
        } else {
            throw new AP5L_Exception(
                'AP5L_Math_Vector2d::factory: invalid arguments (' . get_class($org) . ', '
                . get_class($direction) . ')');
        }
        return $result;
    }

    /**
     * Create a vector from four numbers, origin x and y, then opposite corner x
     * and y.
     * @param int|float Origin x position.
     * @param int|float Origin y position.
     * @param int|float Endpoint x position (absolute).
     * @param int|float Endpoint y position (absolute).
     * @return AP5L_Math_Vector2d Vector from origin to end point.
     */
    static function &factoryI4Abs($ox, $oy, $ex, $ey) {
        return AP5L_Math_Vector2d::factory(
            AP5L_Math_Point2d::factory($ox, $oy),
            AP5L_Math_Point2d::factory($ex - $ox, $ey - $oy)
        );
    }

    /**
     * Create a vector from four numbers, origin x and y, then size x and y.
     * @param int|float Origin x position.
     * @param int|float Origin y position.
     * @param int|float Size in x.
     * @param int|float Size in y.
     * @return AP5L_Math_Vector2d Vector from origin to size.
     */
    static function &factoryI4Rel($ox, $oy, $sx, $sy) {
        return AP5L_Math_Vector2d::factory(
            AP5L_Math_Point2d::factory($ox, $oy),
            AP5L_Math_Point2d::factory($sx, $sy)
        );
    }

    /**
     * Get the angular direction of the vector, in degrees.
     *
     * @return float The direction this vector is pointing, in degrees.
     */
    function getAngle() {
        $angle = rad2deg(atan2($this -> direction -> y, $this -> direction -> x));
    }

    function getEnd() {
        return AP5L_Math_Point2d::factory(
            $this -> org -> x + $this -> direction -> x,
            $this -> org -> y + $this -> direction -> y
        );
    }

    /**
     * Get a point representing the absolute size of the vector.
     *
     * @return AP5L_Math_Point2d A point containing the vector's absolute x and
     * y dimensions.
     */
    function getSize() {
        return AP5L_Math_Point2d::factory(abs($this -> direction -> x), abs($this -> direction -> y));
    }

    /**
     * Get the x magnitude of the vector.
     *
     * @return int The x magnitude.
     */
    function getSizeX() {
        return abs($this -> direction -> x);
    }

    /**
     * Get the y magnitude of the vector.
     *
     * @return int The y magnitude.
     */
    function getSizeY() {
        return abs($this -> direction -> y);
    }

    function length() {
        return $this -> direction -> length();
    }

    /**
     * Move the base of this vector.
     *
     * @param int|float|AP5L_Math_Point2d|AP5L_Math_Vector2d If a vector is
     * passed, the move is based on the vector's direction. If a point is
     * passed, the movement is based on a vector from the origin to the point.
     * If a numeric value is passed, then it is the amount of movement in the x
     * axis.
     * @param int|float Optional, only used if x is a number. This is the amount
     * of movement in the y axis.
     * @return AP5L_Math_Vector2d The current vector with a new origin.
     */
    function &move($point, $y = 0) {
        $result = clone $this;
        if ($point instanceof AP5L_Math_Vector2d) {
            $result -> org -> add($point -> direction);
        } elseif ($point instanceof AP5L_Math_Point2d) {
            $result -> org -> add($point);
        } else {
            $result -> org -> x += $point;
            $result -> org -> y += $y;
        }
        return $result;
    }

    /**
     * Move the vector origin by subtraction a point/vector.
     *
     * If a point is passed, the point value is subtracted from the vector
     * origin. If a vector is passed, the vector's direction is subtracted from
     * the current vector.
     *
     * @param AP5L_Math_Point2dAP5L_Math_Vector2d Amount to subtract.
     * @return AP5L_Math_Vector2d The repositioned vector.
     */
    function &subtract($pointVec) {
        $result = clone $this;
        if ($pointVec instanceof AP5L_Math_Point2d) {
            $result -> org -> x -= $pointVec -> x;
            $result -> org -> y -= $pointVec -> y;
        } elseif ($pointVec instanceof AP5L_Math_Vector2d) {
            $result -> org -> x -= $pointVec -> direction -> x;
            $result -> org -> y -= $pointVec -> direction -> y;
        }
        return $result;
    }

    /**
     * Exchange x and y values.
     *
     * @return AP5L_Math_Vector2d A new vector with x and y exchanged.
     */
    function &swap() {
        $result = clone $this;
        $result -> org = $this -> org -> swap();
        $result -> direction = $this -> direction -> swap();
        return $result;
    }

    /**
     * Apply a 2x2 transformation matrix.
     *
     * @param array A 2x2 array of numbers.
     * @return AP5L_Math_Vector2d The vector after transformation.
     */
    function &transform($matrix) {
        $result = clone $this;
        $result -> org = $this -> org -> transform($matrix);
        $result -> direction = $this -> direction -> transform($matrix);
        return $result;
    }

}
