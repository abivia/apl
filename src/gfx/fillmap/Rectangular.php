<?php
/**
 * Fill a rectangular area from any internal point.
 *
 * @package AP5L
 * @subpackage Gfx
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: Rectangular.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */
namespace \Apl\Gfx\FillMap;

/**
 * Fills from an internal anchor point to the edges of a rectangle.
 */
class Rectangular extends FillMap {

    const TYPE_LINEAR = 0;
    const TYPE_COS = 1;
    const TYPE_POW = 2;
    const TYPE_SIN = 3;

    /**
     * The anchor point
     *
     * @var \Apl\Math\Point2d
     */
    protected $_anchor;

    /**
     * Exponent for use in subtype "pow"
     *
     * @var flost
     */
    protected $_power;

    /**
     * Subtype controls the rate of transition from anchor to border
     *
     * @var int
     */
    protected $_subType;

    static protected $_typeMap = array(
        'cos' => self::TYPE_COS,
        'linear' => self::TYPE_LINEAR,
        'sin' => self::TYPE_SIN,
    );

    public $debug;

    function getColor($x, $y, $colors) {
        return $colors[0] -> blend($colors[1], $this -> getRatio($x, $y));
    }

    function getColorInt($x, $y, $colors) {
        return $colors[0] -> blendToInt($colors[1], $this -> getRatio($x, $y));
    }

    function getRatio($x, $y) {
        // Only one iteration mode
        //switch ($this -> iterateMode) {
        $deltaX = $x - $this -> _anchor -> x;
        $deltaY = $y - $this -> _anchor -> y;
        $rLength = sqrt($deltaX * $deltaX + $deltaY * $deltaY);
        if ($deltaX) {
            if ($deltaX > 0) {
                $borderX = $this -> endX - $this -> _anchor -> x;
            } else {
                $borderX = $this -> startX - $this -> _anchor -> x;
            }
            if ($deltaY > 0) {
                $borderY = $this -> endY - $this -> _anchor -> y;
                if (($crossY = $borderX / $deltaX * $deltaY) <= $borderY) {
                    $tLength = sqrt($borderX * $borderX + $crossY * $crossY);
                } else {
                    $crossX = $borderY / $deltaY * $deltaX;
                    $tLength = sqrt($crossX * $crossX + $borderY * $borderY);
                }
                $ratio = $rLength / $tLength;
            } elseif ($deltaY < 0) {
                $borderY = $this -> startY - $this -> _anchor -> y;
                if (($crossY = $borderX / $deltaX * $deltaY) >= $borderY) {
                    $tLength = sqrt($borderX * $borderX + $crossY * $crossY);
                } else {
                    $crossX = $borderY / $deltaY * $deltaX;
                    $tLength = sqrt($crossX * $crossX + $borderY * $borderY);
                }
                $ratio = $rLength / $tLength;
            } else {
                $ratio = $deltaX / $borderX;
            }
        } else {
            if ($deltaY > 0) {
                $ratio = $deltaY / ($this -> endY - $this -> _anchor -> y);
            } elseif ($deltaY < 0) {
                $ratio = $deltaY / ($this -> startY - $this -> _anchor -> y);
            } else {
                $ratio = 0;
            }
        }
        switch ($this -> _subType) {
            case self::TYPE_COS: {
                $ratio = cos(0.5 * M_PI * $ratio);
            }
            break;

            case self::TYPE_POW: {
                $ratio = pow($ratio, $this -> _power);
            }
            break;

            case self::TYPE_SIN: {
                $ratio = sin(0.5 * M_PI * $ratio);
            }
            break;

        }
        if ($this -> bands) {
            if ($this -> bandMidpoint) {
                $ratio = round($ratio * ($this -> bands - 1)) / ($this -> bands - 1);
            } elseif ($ratio != 1) {
                $ratio = floor($ratio * $this -> bands) / ($this -> bands - 1);
            }
            if ($this -> debug) {
                echo 'b='. $this -> bands
                    . ' m=' . ($this -> bandMidpoint ? 'T' : 'F')
                    . ' banded r=' . $ratio . chr(10);
            }
        }
        return $ratio;
    }

    function setup($subType, $area, $anchor) {
        /*
         * Setup is straightforward
         */
        $this -> _anchor = $anchor;
        $this -> iterateMode = 'xy';
        $this -> incrX = 1;
        $this -> incrY = 1;
        $this -> startX = 0;
        $this -> startY = 0;
        $this -> endX = $area -> direction -> x;
        $this -> endY = $area -> direction -> y;
        /*
         * Map the subtype to an integer for performance.
         */
        $subType = strtolower($subType);
        if (substr($subType, 0, 3) == 'pow') {
            $this -> _subType = self::TYPE_POW;
            $this -> _power = (float) substr($subType, 3);
        } else {
            $this -> _subType = isset(self::$_typeMap[$subType])
                ? self::$_typeMap[$subType] : self::TYPE_LINEAR;
        }
    }
}

