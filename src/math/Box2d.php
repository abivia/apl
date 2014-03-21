<?php
/**
 * Two-dimensional box.
 *
 * @package AP5L
 * @subpackage Math
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: Box2d.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * A two-dimensional box, where the origin is always closer to (0, 0) than the
 * size.
 */
class AP5L_Math_Box2d extends AP5L_Math_Vector2d {

    /**
     * Arrange coordinates so the origin is closer to (0,0) than the direction.
     */
    function arrange() {
        if ($this -> direction -> x < 0) {
            $this -> org -> x += $this -> direction -> x;
            $this -> direction -> x = -$this -> direction -> x;
        }
        if ($this -> direction -> y < 0) {
            $this -> org -> y += $this -> direction -> y;
            $this -> direction -> y = -$this -> direction -> y;
        }
    }

    /**
     * Create a vector.
     * @param AP5L_Math_Box2d|AP5L_Math_Vector2d|AP5L_Math_Point2d Box/Vector:
     * object to be cloned; Point: start point.
     * @param AP5L_Math_Point2d End point; only required if first argument is a
     * AP5L_Math_Point2d.
     * @throws Exception on bad parameters
     * @return Box2d
     */
    static function &factory($org, $size = null) {
        if ($org instanceof AP5L_Math_Box2d) {
            $result = clone $org;
        } elseif ($org instanceof AP5L_Math_Vector2d) {
            $result = new AP5L_Math_Box2d();
            $result -> org = $org -> org;
            $result -> direction = $org -> direction;
        } elseif ($org instanceof AP5L_Math_Point2d && $size instanceof AP5L_Math_Point2d) {
            $result = new AP5L_Math_Box2d();
            $result -> org = $org;
            $result -> direction = $size;
        } else {
            throw new AP5L_Exception(
                'Box2d::factory: invalid arguments (' . get_class($org) . ', '
                . get_class($size) . ')');
        }
        $result -> arrange();
        return $result;
    }

    /**
     * Create a vector from four numbers, origin x and y, then opposite corner x
     * and y.
     * @param int|float Origin x position.
     * @param int|float Origin y position.
     * @param int|float Endpoint x position (absolute).
     * @param int|float Endpoint y position (absolute).
     * @return Box2d Vector from origin to end point.
     */
    static function &factoryI4Abs($ox, $oy, $ex, $ey) {
        if ($ox > $ex) {
            $t = $ex;
            $ex = $ox;
            $ox = $t;
        }
        if ($oy > $ey) {
            $t = $ey;
            $ey = $oy;
            $oy = $t;
        }
        return AP5L_Math_Box2d::factory(
            AP5L_Math_Point2d::factory($ox, $oy),
            AP5L_Math_Point2d::factory($ex - $ox, $ey - $oy)
        );
    }

    /**
     * Create a box from four numbers, origin x and y, then size x and y.
     * @param int|float Origin x position.
     * @param int|float Origin y position.
     * @param int|float Size in x.
     * @param int|float Size in y.
     * @return Box2d Vector from origin to size.
     */
    static function &factoryI4Rel($ox, $oy, $sx, $sy) {
        if ($sx < 0) {
            $ox += $sx;
            $sx = -$sx;
        }
        if ($sy < 0) {
            $oy += $sy;
            $sy = -$sy;
        }
        return AP5L_Math_Box2d::factory(
            AP5L_Math_Point2d::factory($ox, $oy),
            AP5L_Math_Point2d::factory($sx, $sy)
        );
    }

    function getHeight() {
        return $this -> direction -> y;
    }

    function getSize() {
        return $this -> direction;
    }

    function getWidth() {
        return $this -> direction -> x;
    }

    /**
     * Return true if inTest is within the bounds of this box.
     */
    function isInside($inTest) {
        $this -> arrange();
        if ($inTest instanceof AP5L_Math_Point2d) {
            return ($this -> org -> x <= $inBox -> x) && ($this -> direction -> x >= $inBox -> x)
                && ($this -> org -> y <= $inBox -> y) && ($this -> direction -> y >= $inBox -> y);
        } else {
            return $this -> isInside($inTest -> org) && $this -> isInside($inTest -> direction);
        }
    }

}

