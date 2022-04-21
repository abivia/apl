<?php
declare(strict_types=1);

namespace Abivia\Apl\Math;

/**
 * A two-dimensional box, where the origin is always closer to (0, 0) than the
 * size.
 */
class Box2d extends Vector2d
{

    /**
     * Arrange coordinates so the origin is closer to (0,0) than the direction.
     */
    public function arrange()
    {
        if ($this->direction->x < 0) {
            $this->org->x += $this->direction->x;
            $this->direction->x = -$this->direction->x;
        }
        if ($this->direction->y < 0) {
            $this->org->y += $this->direction->y;
            $this->direction->y = -$this->direction->y;
        }
    }

    /**
     * Create a vector.
     * @param Box2d|Vector2d|Point2d Box/Vector:  object to be cloned; Point: start point.
     * @param Point2d End point; only required if first argument is a Point2d.
     * @return Box2d
     * @throws MathException on bad parameters
     */
    static public function &factory($org, $direction = null): Box2d
    {
        if ($org instanceof Box2d) {
            $result = clone $org;
        } elseif ($org instanceof Vector2d) {
            $result = new Box2d();
            $result->org = $org->org;
            $result->direction = $org->direction;
        } elseif ($org instanceof Point2d && $direction instanceof Point2d) {
            $result = new Box2d();
            $result->org = $org;
            $result->direction = $direction;
        } else {
            throw new MathException(
                'Box2d::factory: invalid arguments (' . get_class($org) . ', '
                . get_class($direction) . ')');
        }
        $result->arrange();
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
     * @throws MathException
     */
    static public function &factoryI4Abs($ox, $oy, $ex, $ey): Box2d
    {
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
        return Box2d::factory(
            Point2d::factory($ox, $oy),
            Point2d::factory($ex - $ox, $ey - $oy)
        );
    }

    /**
     * Create a box from four numbers, origin x and y, then size x and y.
     * @param int|float Origin x position.
     * @param int|float Origin y position.
     * @param int|float Size in x.
     * @param int|float Size in y.
     * @return Box2d Vector from origin to size.
     * @throws MathException
     */
    static public function &factoryI4Rel($ox, $oy, $sx, $sy): Box2d
    {
        if ($sx < 0) {
            $ox += $sx;
            $sx = -$sx;
        }
        if ($sy < 0) {
            $oy += $sy;
            $sy = -$sy;
        }
        return Box2d::factory(
            Point2d::factory($ox, $oy),
            Point2d::factory($sx, $sy)
        );
    }

    public function getHeight()
    {
        return $this->direction->y;
    }

    public function getSize(): Point2d
    {
        return $this->direction;
    }

    public function getWidth()
    {
        return $this->direction->x;
    }

    /**
     * Return true if inTest is within the bounds of this box.
     */
    public function isInside($inBox): bool
    {
        $this->arrange();
        if ($inBox instanceof Point2d) {
            return ($this->org->x <= $inBox->x) && ($this->direction->x >= $inBox->x)
                && ($this->org->y <= $inBox->y) && ($this->direction->y >= $inBox->y);
        } else {
            return $this->isInside($inBox->org) && $this->isInside($inBox->direction);
        }
    }

}

