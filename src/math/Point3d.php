<?php
/**
 * Three dimensional point.
 *
 * @package AP5L
 * @subpackage Math
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2008, Alan Langford
 * @version $Id: Point3d.php 94 2009-08-21 03:07:30Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * A basic three dimensional point.
 */
class AP5L_Math_Point3d {
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
    function __construct($x = 0, $y = 0, $z = 0) {
        if (is_array($x)) {
            while (count($x) < 3) {
                $x[] = 0;
            }
            $this -> x = reset($x);
            $this -> y = next($x);
            $this -> z = next($x);
        } else {
            $this -> x = $x;
            $this -> y = $y;
            $this -> z = $z;
        }
    }

    /**
     * Convert the point to a string.
     *
     * @return string The class name followed by (x,y).
     */
    function __toString() {
        return get_class($this) . '(' . $this -> x
            . ', ' . $this -> y
            . ', ' . $this -> z
            . ')';
    }

    /**
     * Add another point to this one and return the sum.
     *
     * @param AP5L_Math_Point3d The point to be added.
     * @return AP5L_Math_Point3d The sum of the points.
     */
    function &add($point) {
        $result = clone $this;
        $result -> x += $point -> x;
        $result -> y += $point -> y;
        $result -> z += $point -> z;
        return $result;
    }

    /**
     * Return the dot product of two points, treated as vectors from the origin.
     *
     * @param AP5L_Point3d Point to comput product with.
     * @return float Dot product.
     */
    function dot(AP5L_Math_Point3d $v) {
        return $this -> x * $v -> x + $this -> y * $v -> y + $this -> z * $v -> z;
    }

    /**
     * Create a new point.
     *
     * @param int|AP5L_Math_Point3d The x coordinate of the new point (optional,
     * defaults to 0) or a point to be cloned.
     * @param int The y coordinate (optional, defaults to 0, ignored if x is a
     * point).
     * @param int The z coordinate (optional, defaults to 0, ignored if x is a
     * point).
     * @return AP5L_Math_Point2d The new point.
     */
    static function &factory($x = 0, $y = 0, $z = 0) {
        if ($x instanceof AP5L_Math_Point3d) {
            $result = clone $x;
        } else {
            $result = new AP5L_Math_Point3d($x, $y, $z);
        }
        return $result;
    }

    /**
     * Return true if inPoint.x less than x, inPoint.y less than y and inPoint.
     * z less than z.
     */
    function isInside($inPoint) {
        return ($this -> x > $inPoint -> x)
            && ($this -> y > $inPoint -> y)
            && ($this -> z > $inPoint -> z)
        ;
    }

    /**
     * Get the "length" of the point, computed as the distance from another
     * point.
     *
     * @param AP5L_Math_Point3d Optional reference point, if not provided,
     * (0,0,0) is used.
     * @return float Distance from reference to this point.
     */
    function length($refPoint = null) {
        if ($refPoint) {
            $x = $this -> x - $refPoint -> x;
            $y = $this -> y - $refPoint -> y;
            $z = $this -> z - $refPoint -> z;
        } else {
            $x = $this -> x;
            $y = $this -> y;
            $z = $this -> z;
        }
        return sqrt($x * $x + $y * $y + $z * $z);
    }

    /**
     * Get the maximum of a set of points.
     *
     * @param AP5L_Math_Point3d|array $points The points to be processed.
     * @return AP5L_Math_Point3d A point containing the largest x, y and z
     * values of the parameters.
     */
    static function &max() {
        $first = true;
        foreach (func_get_args() as $arg) {
            if ($arg instanceof AP5L_Math_Point3d) {
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
                    if ($result -> z > $arg -> z) {
                        $result -> z = $arg -> z;
                    }
                }
            } elseif (is_array($arg)) {
                foreach ($arg as $arglet) {
                    if ($arglet instanceof AP5L_Math_Point3d) {
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
                            if ($result -> z > $arglet -> z) {
                                $result -> z = $arglet -> z;
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
            if ($arg instanceof AP5L_Math_Point3d) {
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
                    if ($result -> z < $arg -> z) {
                        $result -> z = $arg -> z;
                    }
                }
            } elseif (is_array($arg)) {
                foreach ($arg as $arglet) {
                    if ($arglet instanceof AP5L_Math_Point3d) {
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
                            if ($result -> z < $arglet -> z) {
                                $result -> z = $arglet -> z;
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
     * Scale this point and return the new point.
     *
     * @param int|float The scale factor.
     * @return AP5L_Math_Point3d The scaled point.
     */
    function &scale($factor) {
        $result = clone $this;
        $result -> x *= $factor;
        $result -> y *= $factor;
        $result -> z *= $factor;
        return $result;
    }

    /**
     * Subtract another point to this one and return the difference.
     *
     * @param AP5L_Math_Point3d The point to be subtracted.
     * @return AP5L_Math_Point3d This point less the passed point.
     */
    function &subtract($point) {
        $result = clone $this;
        $result -> x -= $point -> x;
        $result -> y -= $point -> y;
        $result -> z -= $point -> z;
        return $result;
    }

    /**
     * Apply a 3x3 transformation matrix.
     *
     * @param array A 3x3 array of numbers with numerical indexes starting at
     * zero.
     * @return AP5L_Math_Point3d The point after transformation.
     */
    function &transform($matrix) {
        $result = clone $this;
        $result -> x = $this -> x * $matrix[0][0]
            + $this -> y * $matrix[0][1]
            + $this -> z * $matrix[0][2]
        ;
        $result -> y = $this -> x * $matrix[1][0]
            + $this -> y * $matrix[1][1]
            + $this -> z * $matrix[1][2]
        ;
        $result -> z = $this -> x * $matrix[2][0]
            + $this -> y * $matrix[2][1]
            + $this -> z * $matrix[2][2]
        ;
        return $result;
    }

}
