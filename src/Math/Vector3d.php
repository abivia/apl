<?php
declare(strict_types=1);

namespace Abivia\Apl\Math;

/**
 * Two-dimensional vector.
 */
class Vector3d
{
    /**
     * @var Point3d The origin of the vector.
     */
    public $org;

    /**
     * The direction of the vector.
     *
     * @var Point3d
     */
    public $direction;

    /**
     * Construct a new vector.
     */
    function __construct()
    {
        $this->org = new Point3d();
        $this->direction = new Point3d();
    }

    /**
     * Convert vector to string.
     *
     * @return string Representation of this vector as a string.
     */
    function __toString()
    {
        return get_class($this) . '(' . $this->org->__toString()
            . '->' . $this->direction->__toString() . ')';
    }

    /**
     * Move the vector origin by adding a point/vector.
     *
     * If a point is passed, the point value is added to the vector origin. If a
     * vector is passed, the vector's direction is added to the current vector.
     *
     * @param Point3d|Vector3d Amount to add.
     * @return Vector3d The repositioned vector.
     */
    public function &add($pointVec): Vector3d
    {
        $result = clone $this;
        if ($pointVec instanceof Point3d) {
            $result->org->x += $pointVec->x;
            $result->org->y += $pointVec->y;
            $result->org->z += $pointVec->z;
        } elseif ($pointVec instanceof Vector3d) {
            $result->org->x += $pointVec->direction->x;
            $result->org->y += $pointVec->direction->y;
            $result->org->z += $pointVec->direction->z;
        }
        return $result;
    }

    /**
     * Create a vector from another vector or point.
     *
     * @param Vector3d|Point3d Vector: vector to be cloned;
     * Point: If direction is provided, this is the vector's start point. If it
     * is not, then the result is a vector from (0,0) to this point.
     * @param Point3d End point.
     * @return Vector3d
     * @throws MathException on bad parameters
     */
    static public function &factory($org, $direction = null): Vector3d
    {
        if ($org instanceof Vector3d) {
            $result = clone $org;
        } elseif ($org instanceof Point3d && $direction === null) {
            $result = new Vector3d();
            $result->org = new Point3d();
            $result->direction = $org;
        } elseif ($org instanceof Point3d && $direction instanceof Point3d) {
            $result = new Vector3d();
            $result->org = $org;
            $result->direction = $direction;
        } else {
            throw new MathException(
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
     * @return Vector3d Vector from origin to end point.
     * @throws MathException
     */
    static public function &factoryI6Abs($ox, $oy, $oz, $ex, $ey, $ez): Vector3d
    {
        return Vector3d::factory(
            Point3d::factory($ox, $oy, $oz),
            Point3d::factory($ex - $ox, $ey - $oy, $ez - $oz)
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
     * @return Vector3d Vector from origin to size.
     * @throws MathException
     */
    static public function &factoryI4Rel($ox, $oy, $oz, $sx, $sy, $sz): Vector3d
    {
        return Vector3d::factory(
            Point3d::factory($ox, $oy, $oz),
            Point3d::factory($sx, $sy, $sz)
        );
    }

    public function getEnd(): Point3d
    {
        return Point3d::factory(
            $this->org->x + $this->direction->x,
            $this->org->y + $this->direction->y,
            $this->org->z + $this->direction->z
        );
    }

    public function length(): float
    {
        return $this->direction->length();
    }

    /**
     * Get a point representing the absolute size of the vector.
     *
     * @return Point3d A point containing the vector's absolute x, y
     * and z dimensions.
     */
    public function getSize(): Point3d
    {
        return Point3d::factory(
            abs($this->direction->x),
            abs($this->direction->y),
            abs($this->direction->z)
        );
    }

    /**
     * Get the x magnitude of the vector.
     *
     * @return float The x magnitude.
     */
    public function getSizeX(): float
    {
        return abs($this->direction->x);
    }

    /**
     * Get the y magnitude of the vector.
     *
     * @return float The y magnitude.
     */
    public function getSizeY(): float
    {
        return abs($this->direction->y);
    }

    /**
     * Get the z magnitude of the vector.
     *
     * @return float The z magnitude.
     */
    public function getSizeZ(): float
    {
        return abs($this->direction->z);
    }

    /**
     * Move the base of this vector.
     *
     * @param int|float|Point3d|Vector3d If a vector is
     * passed, the move is based on the vector's direction. If a point is
     * passed, the movement is based on a vector from the origin to the point.
     * If a numeric value is passed, then it is the amount of movement in the x
     * axis.
     * @param int|float Optional, only used if x is a number. This is the amount
     * of movement in the y axis.
     * @param int|float Optional, only used if x is a number. This is the amount
     * of movement in the z axis.
     * @return Vector3d The current vector with a new origin.
     */
    public function &move($point, $y = 0, $z = 0): Vector3d
    {
        $result = clone $this;
        if ($point instanceof Vector3d) {
            $result->org = $result->org->add($point->direction);
        } elseif ($point instanceof Point3d) {
            $result->org = $result->org->add($point);
        } else {
            $result->org->x += $point;
            $result->org->y += $y;
            $result->org->z += $z;
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
     * @param Point3d|Vector3d Amount to subtract.
     * @return Vector3d The repositioned vector.
     */
    public function &subtract($pointVec): Vector3d
    {
        $result = clone $this;
        if ($pointVec instanceof Point3d) {
            $result->org->x -= $pointVec->x;
            $result->org->y -= $pointVec->y;
            $result->org->z -= $pointVec->z;
        } elseif ($pointVec instanceof Vector3d) {
            $result->org->x -= $pointVec->direction->x;
            $result->org->y -= $pointVec->direction->y;
            $result->org->z -= $pointVec->direction->z;
        }
        return $result;
    }

    /**
     * Apply a 3x3 transformation matrix.
     *
     * @param array A 3x3 array of numbers.
     * @return Vector3d The vector after transformation.
     */
    public function &transform($matrix): Vector3d
    {
        $result = clone $this;
        $result->org = $this->org->transform($matrix);
        $result->direction = $this->direction->transform($matrix);
        return $result;
    }

}
