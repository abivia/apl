<?php
/**
 * Support for two dimensional vectors
 *
 * @package AP5L
 * @subpackage Math
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2008, Alan Langford
 * @version $Id: Vector3d.php 94 2009-08-21 03:07:30Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Two-dimensional vector.
 */
class AP5L_Math_Vector3d {
    /**
     * The origin of the vector.
     *
     * @var AP5L_Math_Point3d
     */
    public $org;

    /**
     * The direction of the vector.
     *
     * @var AP5L_Math_Point3d
     */
    public $direction;

    /**
     * Construct a new vector.
     */
    function __construct() {
        $this -> org = new AP5L_Math_Point3d();
        $this -> direction = new AP5L_Math_Point3d();
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
     * @param AP5L_Math_Point3dAP5L_Math_Vector3d Amount to add.
     * @return AP5L_Math_Vector3d The repositioned vector.
     */
    function &add($pointVec) {
        $result = clone $this;
        if ($pointVec instanceof AP5L_Math_Point3d) {
            $result -> org -> x += $pointVec -> x;
            $result -> org -> y += $pointVec -> y;
            $result -> org -> z += $pointVec -> z;
        } elseif ($pointVec instanceof AP5L_Math_Vector3d) {
            $result -> org -> x += $pointVec -> direction -> x;
            $result -> org -> y += $pointVec -> direction -> y;
            $result -> org -> z += $pointVec -> direction -> z;
        }
        return $result;
    }

    /**
     * Create a vector from another vector or point.
     * 
     * @param AP5L_Math_Vector3d|AP5L_Math_Point3d Vector: vector to be cloned;
     * Point: If direction is provided, this is the vector's start point. If it
     * is not, then the result is a vector from (0,0) to this point.
     * @param AP5L_Math_Point3d End point.
     * @throws Exception on bad parameters
     * @return AP5L_Math_Vector3d
     */
    static function &factory($org, $direction = null) {
        if ($org instanceof AP5L_Math_Vector3d) {
            $result = clone $org;
        } elseif ($org instanceof AP5L_Math_Point3d && $direction === null) {
            $result = new AP5L_Math_Vector3d();
            $result -> org = new AP5L_Math_Point3d();
            $result -> direction = $org;
        } elseif ($org instanceof AP5L_Math_Point3d && $direction instanceof AP5L_Math_Point3d) {
            $result = new AP5L_Math_Vector3d();
            $result -> org = $org;
            $result -> direction = $direction;
        } else {
            throw new AP5L_Exception(
                __CLASS__ . '::factory: invalid arguments (' . get_class($org) . ', '
                . get_class($direction) . ')');
        }
        return $result;
    }

    /**
     * Create a vector from six numbers, origin x, y, z; then opposite corner x,
     * y, z.
     * @param int|float Origin x position.
     * @param int|float Origin y position.
     * @param int|float Origin z position.
     * @param int|float Endpoint x position (absolute).
     * @param int|float Endpoint y position (absolute).
     * @param int|float Endpoint z position (absolute).
     * @return AP5L_Math_Vector3d Vector from origin to end point.
     */
    static function &factoryI6Abs($ox, $oy, $oz, $ex, $ey, $ez) {
        return AP5L_Math_Vector3d::factory(
            AP5L_Math_Point3d::factory($ox, $oy, $oz),
            AP5L_Math_Point3d::factory($ex - $ox, $ey - $oy, $ez - $oz)
        );
    }

    /**
     * Create a vector from six numbers, origin x, y, z; then size x, y, z.
     * @param int|float Origin x position.
     * @param int|float Origin y position.
     * @param int|float Origin z position.
     * @param int|float Size in x.
     * @param int|float Size in y.
     * @param int|float Size in z.
     * @return AP5L_Math_Vector3d Vector from origin to size.
     */
    static function &factoryI4Rel($ox, $oy, $oz, $sx, $sy, $sz) {
        return AP5L_Math_Vector3d::factory(
            AP5L_Math_Point3d::factory($ox, $oy, $oz),
            AP5L_Math_Point3d::factory($sx, $sy, $sz)
        );
    }

    function getEnd() {
        return AP5L_Math_Point3d::factory(
            $this -> org -> x + $this -> direction -> x,
            $this -> org -> y + $this -> direction -> y,
            $this -> org -> z + $this -> direction -> z
        );
    }

    function getLength() {
        return $this -> direction -> getLength();
    }

    /**
     * Get a point representing the absolute size of the vector.
     *
     * @return AP5L_Math_Point3d A point containing the vector's absolute x, y
     * and z dimensions.
     */
    function getSize() {
        return AP5L_Math_Point3d::factory(
            abs($this -> direction -> x), 
            abs($this -> direction -> y),
            abs($this -> direction -> z)
        );
    }

    /**
     * Get the x magnitude of the vector.
     *
     * @return float The x magnitude.
     */
    function getSizeX() {
        return abs($this -> direction -> x);
    }

    /**
     * Get the y magnitude of the vector.
     *
     * @return float The y magnitude.
     */
    function getSizeY() {
        return abs($this -> direction -> y);
    }

    /**
     * Get the z magnitude of the vector.
     *
     * @return float The z magnitude.
     */
    function getSizeZ() {
        return abs($this -> direction -> z);
    }

    /**
     * Move the base of this vector.
     *
     * @param int|float|AP5L_Math_Point3d|AP5L_Math_Vector3d If a vector is
     * passed, the move is based on the vector's direction. If a point is
     * passed, the movement is based on a vector from the origin to the point.
     * If a numeric value is passed, then it is the amount of movement in the x
     * axis.
     * @param int|float Optional, only used if x is a number. This is the amount
     * of movement in the y axis.
     * @param int|float Optional, only used if x is a number. This is the amount
     * of movement in the z axis.
     * @return AP5L_Math_Vector3d The current vector with a new origin.
     */
    function &move($point, $y = 0, $z = 0) {
        $result = clone $this;
        if ($point instanceof AP5L_Math_Vector3d) {
            $result -> org -> add($point -> direction);
        } elseif ($point instanceof AP5L_Math_Point3d) {
            $result -> org -> add($point);
        } else {
            $result -> org -> x += $point;
            $result -> org -> y += $y;
            $result -> org -> z += $z;
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
     * @param AP5L_Math_Point3dAP5L_Math_Vector3d Amount to subtract.
     * @return AP5L_Math_Vector3d The repositioned vector.
     */
    function &subtract($pointVec) {
        $result = clone $this;
        if ($pointVec instanceof AP5L_Math_Point3d) {
            $result -> org -> x -= $pointVec -> x;
            $result -> org -> y -= $pointVec -> y;
            $result -> org -> z -= $pointVec -> z;
        } elseif ($pointVec instanceof AP5L_Math_Vector3d) {
            $result -> org -> x -= $pointVec -> direction -> x;
            $result -> org -> y -= $pointVec -> direction -> y;
            $result -> org -> z -= $pointVec -> direction -> z;
        }
        return $result;
    }

    /**
     * Apply a 3x3 transformation matrix.
     *
     * @param array A 3x3 array of numbers.
     * @return AP5L_Math_Vector3d The vector after transformation.
     */
    function &transform($matrix) {
        $result = clone $this;
        $result -> org = $this -> org -> transform($matrix);
        $result -> direction = $this -> direction -> transform($matrix);
        return $result;
    }

}
