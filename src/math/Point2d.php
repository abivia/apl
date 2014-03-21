<?php
/**
 * Two dimensional point.
 *
 * @package AP5L
 * @subpackage Math
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: Point2d.php 100 2011-03-21 18:26:21Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * A basic two dimensional point.
 */
class AP5L_Math_Point2d {
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
    function __construct($x = 0, $y = 0) {
        $this -> x = $x;
        $this -> y = $y;
    }

    /**
     * Convert the point to a string.
     *
     * @return string The class name followed by (x,y).
     */
    function __toString() {
        return get_class($this) . '(' . $this -> x . ', ' . $this -> y . ')';
    }

    /**
     * Add another point to this one and return the sum.
     *
     * @param AP5L_Math_Point2d|int The point to be added or the x value if integer.
     * @param int Optional. The y value, when the first argument is an integer.
     * @return AP5L_Math_Point2d The sum of the points.
     */
    function &add($pointX, $y = null) {
        $result = clone $this;
        if ($pointX instanceof AP5L_Math_Point2d) {
            $result -> x += $pointX -> x;
            $result -> y += $pointX -> y;
        } else {
            $result -> x += $pointX;
            $result -> y += $y;
        }
        return $result;
    }

    /**
     * Return the dot product of two points, treated as vectors from the origin.
     *
     * @param AP5L_Point3d Point to comput product with.
     * @return float Dot product.
     */
    function dot(AP5L_Math_Point2d $v) {
        return $this -> x * $v -> x + $this -> y * $v -> y;
    }

    /**
     * Compare points.
     *
     * @param AP5L_Math_Point2d The point to be compared.
     * @return boolean True if the points are equal.
     */
    function equals($point) {
        return $this -> x == $point -> x && $this -> y == $point -> y;
    }

    /**
     * Create a new point.
     *
     * @param int|AP5L_Math_Point2d The x coordinate of the new point (optional,
     * defaults to 0) or a point to be cloned.
     * @param int The y coordinate (optional, defaults to 0, ignored if x is a
     * point).
     * @return AP5L_Math_Point2d The new point.
     */
    static function &factory($x = 0, $y = 0) {
        if ($x instanceof AP5L_Math_Point2d) {
            $result = clone $x;
        } else {
            $result = new AP5L_Math_Point2d($x, $y);
        }
        return $result;
    }

    /**
     * Return true if inPoint.x less than x and inPoint.y less than y.
     */
    function isInside($inPoint) {
        return ($this -> x > $inPoint -> x) && ($this -> y > $inPoint -> y);
    }

    /**
     * Get the "length" of the point, computed as the distance from another
     * point.
     *
     * @param AP5L_Math_Point2d Optional reference point, if not provided,
     * (0,0,0) is used.
     * @return float Distance from reference to this point.
     */
    function length($refPoint = null) {
        if ($refPoint) {
            $x = $this -> x - $refPoint -> x;
            $y = $this -> y - $refPoint -> y;
        } else {
            $x = $this -> x;
            $y = $this -> y;
        }
        return sqrt($x * $x + $y * $y);
    }

    /**
     * Get the maximum of a set of points.
     *
     * @param AP5L_Math_Point2d|array $points The points to be processed.
     * @return AP5L_Math_Point2d A point containing the largest x and y values
     * of the parameters.
     */
    static function &max() {
        $first = true;
        foreach (func_get_args() as $arg) {
            if ($arg instanceof AP5L_Math_Point2d) {
                if ($first) {
                    $result = $arg;
                    $first = false;
                } else {
                    if ($result -> x > $arg -> x) {
                        $result -> x = $arg -> x;
                    }
                    if ($result -> y > $arg -> y) {
                        $result -> y = $arg -> y;
                    }
                }
            } elseif (is_array($arg)) {
                foreach ($arg as $arglet) {
                    if ($arglet instanceof AP5L_Math_Point2d) {
                        if ($first) {
                            $result = $arglet;
                            $first = false;
                        } else {
                            if ($result -> x > $arglet -> x) {
                                $result -> x = $arglet -> x;
                            }
                            if ($result -> y > $arglet -> y) {
                                $result -> y = $arglet -> y;
                            }
                        }
                    }
                }
            }
        }
        if ($first) {
            throw new AP5L_Math_Exception('Invalid arguments.');
        }
        return $result;
    }

    /**
     * Get the minimum of a set of points.
     *
     * @param AP5L_Math_Point2d|array $points The points to be processed.
     * @return AP5L_Math_Point2d A point containing the smallest x and y values
     * of the parameters.
     */
    static function &min() {
        $first = true;
        foreach (func_get_args() as $arg) {
            if ($arg instanceof AP5L_Math_Point2d) {
                if ($first) {
                    $result = $arg;
                    $first = false;
                } else {
                    if ($result -> x < $arg -> x) {
                        $result -> x = $arg -> x;
                    }
                    if ($result -> y < $arg -> y) {
                        $result -> y = $arg -> y;
                    }
                }
            } elseif (is_array($arg)) {
                foreach ($arg as $arglet) {
                    if ($arglet instanceof AP5L_Math_Point2d) {
                        if ($first) {
                            $result = $arglet;
                            $first = false;
                        } else {
                            if ($result -> x < $arglet -> x) {
                                $result -> x = $arglet -> x;
                            }
                            if ($result -> y < $arglet -> y) {
                                $result -> y = $arglet -> y;
                            }
                        }
                    }
                }
            }
        }
        if ($first) {
            throw new AP5L_Math_Exception('Invalid arguments.');
        }
        return $result;
    }

    /**
     * Clone this point, scale and return it.
     *
     * @param int|float The scale factor.
     * @return AP5L_Math_Point2d The scaled point.
     */
    function &scale($factor) {
        $result = clone $this;
        $result -> x *= $factor;
        $result -> y *= $factor;
        return $result;
    }

    /**
     * Subtract another point to this one and return the difference.
     *
     * @param AP5L_Math_Point2d The point to be subtracted.
     * @return AP5L_Math_Point2d This point less the passed point.
     */
    function &subtract($point) {
        $result = clone $this;
        $result -> x -= $point -> x;
        $result -> y -= $point -> y;
        return $result;
    }

    /**
     * Swap the x and y coordinates.
     *
     * @return AP5L_Math_Point2d A new point with x and y swapped.
     */
    function &swap() {
        $result = clone $this;
        $result -> x = $this -> y;
        $result -> y = $this -> x;
        return $result;
    }

    /**
     * Apply a 2x2 transformation matrix.
     *
     * @param array A 2x2 array of numbers with numerical indexes starting at
     * zero.
     * @return AP5L_Math_Point2d The point after transformation.
     */
    function &transform($matrix) {
        $result = clone $this;
        $result -> x = $this -> x * $matrix[0][0] + $this -> y * $matrix[0][1];
        $result -> y = $this -> x * $matrix[1][0] + $this -> y * $matrix[1][1];
        return $result;
    }

}
