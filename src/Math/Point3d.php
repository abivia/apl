<?php
declare(strict_types=1);

namespace Abivia\Apl\Math;


/**
 * Three-dimensional point.
 */

/**
 * A basic three-dimensional point.
 */
class Point3d
{
    /**
     * The x coordinate.
     *
     * @var int|float
     */
    public $x = 0;

    /**
     * The y coordinate.
     *
     * @var int|float
     */
    public $y = 0;

    /**
     * The z coordinate.
     *
     * @var int|float
     */
    public $z = 0;

    /**
     * Construct a point.
     *
     * @param int|float|array The x value. Optional, defaults to zero. If an
     * array is provided, the first three elements are taken as x, y, z.
     * @param int|float The y value. Optional, defaults to zero.
     * @param int|float The z value. Optional, defaults to zero.
     */
    function __construct($x = 0, $y = 0, $z = 0)
    {
        if (is_array($x)) {
            while (count($x) < 3) {
                $x[] = 0;
            }
            $this->x = reset($x);
            $this->y = next($x);
            $this->z = next($x);
        } else {
            $this->x = $x;
            $this->y = $y;
            $this->z = $z;
        }
    }

    /**
     * Convert the point to a string.
     *
     * @return string The class name followed by (x,y).
     */
    function __toString()
    {
        return get_class($this) . '(' . $this->x
            . ', ' . $this->y
            . ', ' . $this->z
            . ')';
    }

    /**
     * Add another point to this one and return the sum.
     *
     * @param Point3d The point to be added.
     * @return Point3d The sum of the points.
     */
    public function &add($point): Point3d
    {
        $result = clone $this;
        $result->x += $point->x;
        $result->y += $point->y;
        $result->z += $point->z;
        return $result;
    }

    /**
     * Return the dot product of two points, treated as vectors from the origin.
     *
     * @param Point3d Point to compute the product with.
     * @return float Dot product.
     */
    public function dot(Point3d $v): float
    {
        return $this->x * $v->x + $this->y * $v->y + $this->z * $v->z;
    }

    /**
     * Create a new point.
     *
     * @param int|Point3d The x coordinate of the new point (optional,
     * defaults to 0) or a point to be cloned.
     * @param int The y coordinate (optional, defaults to 0, ignored if x is a
     * point).
     * @param int The z coordinate (optional, defaults to 0, ignored if x is a
     * point).
     * @return Point3d The new point.
     */
    static public function &factory($x = 0, $y = 0, $z = 0): Point3d
    {
        if ($x instanceof Point3d) {
            $result = clone $x;
        } else {
            $result = new Point3d($x, $y, $z);
        }
        return $result;
    }

    /**
     * Return true if inPoint.x less than x, inPoint.y less than y and inPoint.
     * z less than z.
     */
    public function isInside($inPoint): bool
    {
        return ($this->x > $inPoint->x)
            && ($this->y > $inPoint->y)
            && ($this->z > $inPoint->z);
    }

    /**
     * Get the "length" of the point, computed as the distance from another
     * point.
     *
     * @param Point3d Optional reference point, if not provided,
     * (0,0,0) is used.
     * @return float Distance from reference to this point.
     */
    public function length($refPoint = null): float
    {
        if ($refPoint) {
            $x = $this->x - $refPoint->x;
            $y = $this->y - $refPoint->y;
            $z = $this->z - $refPoint->z;
        } else {
            $x = $this->x;
            $y = $this->y;
            $z = $this->z;
        }
        return sqrt($x * $x + $y * $y + $z * $z);
    }

    /**
     * Get the maximum of a set of points.
     *
     * @param Point3d|array $points The points to be processed.
     * @return Point3d A point containing the largest x, y and z
     * values of the parameters.
     * @throws MathException
     */
    static public function &max(...$points): Point3d
    {
        $first = true;
        $result = new static();
        foreach ($points as $arg) {
            if ($arg instanceof Point3d) {
                if ($first) {
                    $result = $arg;
                    $first = false;
                } else {
                    $result->maxOf($arg);
                }
            } elseif (is_array($arg)) {
                foreach ($arg as $subArg) {
                    if ($subArg instanceof Point3d) {
                        if ($first) {
                            $result = $subArg;
                            $first = false;
                        } else {
                            $result->maxOf($subArg);
                        }
                    }
                }
            }
        }
        if ($first) {
            throw new MathException('Invalid arguments.');
        }
        return $result;
    }

    /**
     * Return the largest value on each axis.
     *
     * @param Point3d $point
     * @return $this
     */
    public function maxOf(Point3d $point): self
    {
        $this->x = max($this->x, $point->x);
        $this->y = max($this->y, $point->y);
        $this->z = max($this->z, $point->z);

        return $this;
    }

    /**
     * Get the minimum of a set of points.
     *
     * @param Point3d|array $points The points to be processed.
     * @return Point3d A point containing the smallest x and y values of the parameters.
     * @throws MathException
     */
    static public function &min(...$points): Point3d
    {
        $first = true;
        $result = new static();
        foreach ($points as $arg) {
            if ($arg instanceof Point3d) {
                if ($first) {
                    $result = $arg;
                    $first = false;
                } else {
                    $result->minOf($arg);
                }
            } elseif (is_array($arg)) {
                foreach ($arg as $subArg) {
                    if ($subArg instanceof Point3d) {
                        if ($first) {
                            $result = $subArg;
                            $first = false;
                        } else {
                            $result->minOf($subArg);
                        }
                    }
                }
            }
        }
        if ($first) {
            throw new MathException('Invalid arguments.');
        }
        return $result;
    }

    /**
     * Return the smallest value on each axis.
     *
     * @param Point3d $point
     * @return $this
     */
    public function minOf(Point3d $point): self
    {
        $this->x = min($this->x, $point->x);
        $this->y = min($this->y, $point->y);
        $this->z = min($this->z, $point->z);

        return $this;
    }

    /**
     * Scale this point and return the new point.
     *
     * @param int|float The scale factor.
     * @return Point3d The scaled point.
     */
    public function &scale($factor): Point3d
    {
        $result = clone $this;
        $result->x *= $factor;
        $result->y *= $factor;
        $result->z *= $factor;
        return $result;
    }

    /**
     * Subtract another point to this one and return the difference.
     *
     * @param Point3d The point to be subtracted.
     * @return Point3d This point less the passed point.
     */
    public function &subtract($point): Point3d
    {
        $result = clone $this;
        $result->x -= $point->x;
        $result->y -= $point->y;
        $result->z -= $point->z;
        return $result;
    }

    /**
     * Apply a 3x3 transformation matrix.
     *
     * @param array A 3x3 array of numbers with numerical indexes starting at
     * zero.
     * @return Point3d The point after transformation.
     */
    public function &transform($matrix): Point3d
    {
        $result = clone $this;
        $result->x = $this->x * $matrix[0][0]
            + $this->y * $matrix[0][1]
            + $this->z * $matrix[0][2];
        $result->y = $this->x * $matrix[1][0]
            + $this->y * $matrix[1][1]
            + $this->z * $matrix[1][2];
        $result->z = $this->x * $matrix[2][0]
            + $this->y * $matrix[2][1]
            + $this->z * $matrix[2][2];
        return $result;
    }

}
