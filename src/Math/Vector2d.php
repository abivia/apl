<?php
declare(strict_types=1);

namespace Abivia\Apl\Math;

/**
 * Two-dimensional vector.
 */
class Vector2d
{
    /**
     * The origin of the vector.
     *
     * @var Point2d
     */
    public $org;

    /**
     * The direction of the vector.
     *
     * @var Point2d
     */
    public $direction;

    function __clone()
    {
        $this->org = clone $this->org;
        $this->direction = clone $this->direction;
    }

    /**
     * Construct a new vector.
     */
    function __construct()
    {
        $this->org = new Point2d();
        $this->direction = new Point2d();
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
     * @param Point2d|Vector2d Amount to add.
     * @return Vector2d The repositioned vector.
     */
    public function &add($pointVec): Vector2d
    {
        $result = clone $this;
        if ($pointVec instanceof Point2d) {
            $result->org->x += $pointVec->x;
            $result->org->y += $pointVec->y;
        } elseif ($pointVec instanceof Vector2d) {
            $result->org->x += $pointVec->direction->x;
            $result->org->y += $pointVec->direction->y;
        }
        return $result;
    }

    /**
     * Compare vectors.
     *
     * @param Vector2d Vector to compare.
     * @return boolean True if vectors match.
     */
    public function equals($vector): bool
    {
        return $this->org->equals($vector->org)
            && $this->direction->equals($vector->direction);
    }

    /**
     * Create a vector from another vector or point.
     *
     * @param Vector2d|Point2d Vector: vector to be cloned;
     * Point: If direction is provided, this is the vector's start point. If it
     * is not, then the result is a vector from (0,0) to this point.
     * @param Point2d End point.
     * @return Vector2d
     * @throws MathException on bad parameters
     */
    static public function &factory($org, $direction = null): Vector2d
    {
        if ($org instanceof Vector2d) {
            $result = clone $org;
        } elseif ($org instanceof Point2d && $direction === null) {
            $result = new Vector2d();
            $result->org = new Point2d();
            $result->direction = $org;
        } elseif ($org instanceof Point2d && $direction instanceof Point2d) {
            $result = new Vector2d();
            $result->org = $org;
            $result->direction = $direction;
        } else {
            throw new MathException(
                'Vector2d::factory: invalid arguments (' . get_class($org) . ', '
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
     * @return Vector2d Vector from origin to end point.
     * @throws MathException
     */
    static public function &factoryI4Abs($ox, $oy, $ex, $ey): Vector2d
    {
        return Vector2d::factory(
            Point2d::factory($ox, $oy),
            Point2d::factory($ex - $ox, $ey - $oy)
        );
    }

    /**
     * Create a vector from four numbers, origin x and y, then size x and y.
     * @param int|float Origin x position.
     * @param int|float Origin y position.
     * @param int|float Size in x.
     * @param int|float Size in y.
     * @return Vector2d Vector from origin to size.
     * @throws MathException
     */
    static public function &factoryI4Rel($ox, $oy, $sx, $sy): Vector2d
    {
        return Vector2d::factory(
            Point2d::factory($ox, $oy),
            Point2d::factory($sx, $sy)
        );
    }

    /**
     * Get the angular direction of the vector, in degrees.
     *
     * @return float The direction this vector is pointing, in degrees.
     */
    public function getAngle(): float
    {
        return rad2deg(atan2($this->direction->y, $this->direction->x));
    }

    public function getEnd(): Point2d
    {
        return Point2d::factory(
            $this->org->x + $this->direction->x,
            $this->org->y + $this->direction->y
        );
    }

    /**
     * Get a point representing the absolute size of the vector.
     *
     * @return Point2d A point containing the vector's absolute x and
     * y dimensions.
     */
    public function getSize(): Point2d
    {
        return Point2d::factory(abs($this->direction->x), abs($this->direction->y));
    }

    /**
     * Get the x magnitude of the vector.
     *
     * @return int|float The x magnitude.
     */
    public function getSizeX()
    {
        return abs($this->direction->x);
    }

    /**
     * Get the y magnitude of the vector.
     *
     * @return int|float The y magnitude.
     */
    public function getSizeY()
    {
        return abs($this->direction->y);
    }

    public function length(): float
    {
        return $this->direction->length();
    }

    /**
     * Return a vector with a new origin.
     *
     * @param int|float|Point2d|Vector2d If a vector is
     * passed, the move is based on the vector's direction. If a point is
     * passed, the movement is based on a vector from the origin to the point.
     * If a numeric value is passed, then it is the amount of movement in x.
     * @param int|float Optional, used if x is a number. This is the amount of movement in y.
     * @return Vector2d The current vector with a new origin.
     */
    public function &move($point, $y = 0): Vector2d
    {
        $result = clone $this;
        if ($point instanceof Vector2d) {
            $result->org = $result->org->add($point->direction);
        } elseif ($point instanceof Point2d) {
            $result->org = $result->org->add($point);
        } else {
            $result->org->x += $point;
            $result->org->y += $y;
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
     * @param Point2d|Vector2d Amount to subtract.
     * @return Vector2d The repositioned vector.
     */
    public function &subtract($pointVec)
    {
        $result = clone $this;
        if ($pointVec instanceof Point2d) {
            $result->org->x -= $pointVec->x;
            $result->org->y -= $pointVec->y;
        } elseif ($pointVec instanceof Vector2d) {
            $result->org->x -= $pointVec->direction->x;
            $result->org->y -= $pointVec->direction->y;
        }
        return $result;
    }

    /**
     * Exchange x and y values.
     *
     * @return Vector2d A new vector with x and y exchanged.
     */
    public function &swap(): Vector2d
    {
        $result = clone $this;
        $result->org = $this->org->swap();
        $result->direction = $this->direction->swap();
        return $result;
    }

    /**
     * Apply a 2x2 transformation matrix.
     *
     * @param array A 2x2 array of numbers.
     * @return Vector2d The vector after transformation.
     */
    public function &transform($matrix): Vector2d
    {
        $result = clone $this;
        $result->org = $this->org->transform($matrix);
        $result->direction = $this->direction->transform($matrix);
        return $result;
    }

}
