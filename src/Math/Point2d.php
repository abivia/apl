<?php
declare(strict_types=1);

namespace Abivia\Apl\Math;
/**
 * Two-dimensional point.
 */

/**
 * A basic two-dimensional point.
 */
class Point2d
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
     * Construct a point.
     *
     * @param int|float The x value. Optional, defaults to zero.
     * @param int|float The y value. Optional, defaults to zero.
     */
    function __construct($x = 0, $y = 0)
    {
        $this->x = $x;
        $this->y = $y;
    }

    /**
     * Convert the point to a string.
     *
     * @return string The class name followed by (x,y).
     */
    function __toString()
    {
        return get_class($this) . '(' . $this->x . ', ' . $this->y . ')';
    }

    /**
     * Add another point to this one and return the sum.
     *
     * @param Point2d|int The point to be added or the x value if integer.
     * @param int Optional. The y value, when the first argument is an integer.
     * @return Point2d The sum of the points.
     */
    public function &add($pointX, $y = null): Point2d
    {
        $result = clone $this;
        if ($pointX instanceof Point2d) {
            $result->x += $pointX->x;
            $result->y += $pointX->y;
        } else {
            $result->x += $pointX;
            $result->y += $y;
        }
        return $result;
    }

    /**
     * Return the dot product of two points, treated as vectors from the origin.
     *
     * @param Point2d Point to compute product with.
     * @return float Dot product.
     */
    public function dot(Point2d $v)
    {
        return $this->x * $v->x + $this->y * $v->y;
    }

    /**
     * Compare points.
     *
     * @param Point2d The point to be compared.
     * @return bool True if the points are equal.
     */
    public function equals($point): bool
    {
        return $this->x == $point->x && $this->y == $point->y;
    }

    /**
     * Create a new point.
     *
     * @param int|Point2d The x coordinate of the new point (optional,
     * defaults to 0) or a point to be cloned.
     * @param int The y coordinate (optional, defaults to 0, ignored if x is a
     * point).
     * @return Point2d The new point.
     */
    static public function &factory($x = 0, $y = 0): Point2d
    {
        if ($x instanceof Point2d) {
            $result = clone $x;
        } else {
            $result = new Point2d($x, $y);
        }
        return $result;
    }

    /**
     * Return true if inPoint.x less than x and inPoint.y less than y.
     */
    public function isInside($inPoint): bool
    {
        return ($this->x > $inPoint->x) && ($this->y > $inPoint->y);
    }

    /**
     * Get the "length" of the point, computed as the distance from another
     * point.
     *
     * @param Point2d Optional reference point, if not provided,
     * (0,0,0) is used.
     * @return float Distance from reference to this point.
     */
    public function length($refPoint = null): float
    {
        if ($refPoint) {
            $x = $this->x - $refPoint->x;
            $y = $this->y - $refPoint->y;
        } else {
            $x = $this->x;
            $y = $this->y;
        }
        return sqrt($x * $x + $y * $y);
    }

    /**
     * Get the maximum of a set of points.
     *
     * @param Point2d|array $points The points to be processed.
     * @return Point2d A point containing the largest x and y values of the parameters.
     * @throws MathException
     */
    static public function &max(...$points): Point2d
    {
        $first = true;
        $result = new static();
        foreach ($points as $arg) {
            if ($arg instanceof Point2d) {
                if ($first) {
                    $result = $arg;
                    $first = false;
                } else {
                    $result->maxOf($arg);
                }
            } elseif (is_array($arg)) {
                foreach ($arg as $subArg) {
                    if ($subArg instanceof Point2d) {
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
     * @param Point2d $point
     * @return $this
     */
    public function maxOf(Point2d $point): self
    {
        $this->x = max($this->x, $point->x);
        $this->y = max($this->y, $point->y);

        return $this;
    }

    /**
     * Get the minimum of a set of points.
     *
     * @param Point2d|array $points The points to be processed.
     * @return Point2d A point containing the smallest x and y values of the parameters.
     * @throws MathException
     */
    static public function &min(...$points): Point2d
    {
        $first = true;
        $result = new static();
        foreach ($points as $arg) {
            if ($arg instanceof Point2d) {
                if ($first) {
                    $result = $arg;
                    $first = false;
                } else {
                    $result->minOf($arg);
                }
            } elseif (is_array($arg)) {
                foreach ($arg as $subArg) {
                    if ($subArg instanceof Point2d) {
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
     * @param Point2d $point
     * @return $this
     */
    public function minOf(Point2d $point): self
    {
        $this->x = min($this->x, $point->x);
        $this->y = min($this->y, $point->y);

        return $this;
    }

    /**
     * Clone this point, scale and return it.
     *
     * @param int|float The scale factor.
     * @return Point2d The scaled point.
     */
    public function &scale($factor): Point2d
    {
        $result = clone $this;
        $result->x *= $factor;
        $result->y *= $factor;
        return $result;
    }

    /**
     * Subtract another point to this one and return the difference.
     *
     * @param Point2d The point to be subtracted.
     * @return Point2d This point less the passed point.
     */
    public function &subtract($point): Point2d
    {
        $result = clone $this;
        $result->x -= $point->x;
        $result->y -= $point->y;
        return $result;
    }

    /**
     * Swap the x and y coordinates.
     *
     * @return Point2d A new point with x and y swapped.
     * @noinspection PhpSuspiciousNameCombinationInspection
     */
    public function &swap(): Point2d
    {
        $result = clone $this;
        $result->x = $this->y;
        $result->y = $this->x;
        return $result;
    }

    /**
     * Apply a 2x2 transformation matrix.
     *
     * @param array A 2x2 array of numbers with numerical indexes starting at
     * zero.
     * @return Point2d The point after transformation.
     */
    public function &transform($matrix): Point2d
    {
        $result = clone $this;
        $result->x = $this->x * $matrix[0][0] + $this->y * $matrix[0][1];
        $result->y = $this->x * $matrix[1][0] + $this->y * $matrix[1][1];
        return $result;
    }

}
