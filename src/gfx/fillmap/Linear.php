<?php
/**
 * Two point linear fill at a specified angle.
 *
 * @package AP5L
 * @subpackage Gfx
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: Linear.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */
namespace \Apl\Gfx\FillMap;

/**
 * Encapsulates calculations for a two point linear fill
 */
class Linear extends FillMap {

    /**
     * The remapped fill angle.
     *
     * @var float
     */
    protected $_angle;

    /**
     * The length of the projection of the fill area onto a vector parallel to
     * the fill axis.
     *
     * @var float
     */
    protected $_blendLength;

    /**
     * Set if from/to swapped
     *
     * @var boolean
     */
    protected $_reverse;

    public $debug;

    function getAngle() {
        return $this -> _angle;
    }

    function getColor($x, $y, $colors) {
        return $colors[0] -> blend($colors[1], $this -> getRatio($x, $y));
    }

    function getColorInt($x, $y, $colors) {
        return $colors[0] -> blendToInt($colors[1], $this -> getRatio($x, $y));
    }

    function getRatio($x, $y) {
        switch ($this -> iterateMode) {
            case 'x': {
                $ratio = $x / $this -> endX;
            }
            break;

            case 'xy': {
                if ($this -> incrX < 0) {
                    $x = $this -> startX - $x;
                }
                if ($this -> _angle) {
                    $length = sqrt($y * $y + $x * $x);
                    $angle = $x ? rad2deg(atan($y / $x)) : 90;
                    $map = $length * cos(deg2rad($angle - $this -> _angle));
                } else {
                    /*
                     * Angle=0. Optimize this case and get rid of trig rounding
                     * issues.
                     */
                    $map = $x;
                }
                $ratio = $map / $this -> _blendLength;
                if ($this -> debug) {
                    echo 'x=' . $x . ' y=' . $y . ' l=' . $length
                        . ' _angle=' . $this -> _angle . ' angle=' . $angle
                        . ' da=' . ($angle - $this -> _angle)
                        . ' c(da)=' . cos(deg2rad($angle - $this -> _angle))
                        . ' map=' . $map . ' r=' . $ratio . chr(10);
                }
            }
            break;

            case 'y': {
                $ratio = $y / $this -> endY;
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
        return $this -> _reverse ? 1 - $ratio : $ratio;
    }

    function getReversed() {
        return $this -> _reverse;
    }

    function setup($area, $angle) {
        /*
         * Start by resetting all the iteration values.
         */
        $this -> _reverse = false;
        $this -> iterateMode = '';
        $this -> endX = 0;
        $this -> endY = 0;
        $this -> incrX = 1;
        $this -> incrY = 1;
        $this -> startX = 0;
        $this -> startY = 0;
        $this -> _angle = fmod($angle, 360);
        if ($this -> _angle < 0) {
            $this -> _angle += 360;
        }
        if ($this -> _angle == 90 || $this -> _angle == 270) {
            // simplified case: parallel to the y axis
            $steps = floor($area -> direction -> y);
            // Ignore degenerate case
            if (! $steps) {
                return;
            }
            if ($this -> _angle == 270) {
                $this -> _reverse = true;
                $this -> _angle = 90;
            }
            $this -> iterateMode = 'xy';
            $this -> startX = 0;
            $this -> endX = $area -> direction -> x;
            $this -> incrX = 1;
            //$this -> iterateMode = 'y';
            //$this -> incrX = $this -> endX;
            $this -> startY = 0;
            $this -> endY = $steps;
            $this -> incrY = 1;
            $this -> _blendLength = $this -> endY;
        } elseif ($this -> _angle == 0 || $this -> _angle == 180) {
            // simplified case: parallel to the x axis
            $steps = floor($area -> direction -> x);
            // Ignore degenerate case
            if (! $steps) {
                return;
            }
            if ($this -> _angle) {
                $this -> _reverse = true;
                $this -> _angle = 0;
            }
            $this -> iterateMode = 'xy';
            $this -> startX = 0;
            $this -> endX = $steps;
            $this -> incrX = 1;
            $this -> startY = 0;
            $this -> endY = $area -> direction -> y;
            $this -> incrY = 1;
            //$this -> iterateMode = 'x';
            //$this -> incrY = $this -> endY;
            $this -> _blendLength = $this -> endX;
        } else {
            /*
             * We need some geometry. First we flip things to get a single case.
             */
            if ($this -> _angle > 180) {
                $this -> _angle -= 180;
                $this -> _reverse = true;
            }
            $this -> iterateMode = 'xy';
            $this -> startY = 0;
            $this -> endY = $area -> direction -> y;
            $this -> incrY = 1;
            if ($this -> _angle > 90) {
                $this -> _angle = 180 - $this -> _angle;
                $this -> startX = $area -> getSizeX();
                $this -> endX = 0;
                $this -> incrX = -1;
            } else {
                $this -> startX = 0;
                $this -> endX = $area -> getSizeX();
                $this -> incrX = 1;
            }
            /*
             * Compute the length of the transition along a line parallel to the
             * requested angle.
             */
            $diagonal = $area -> length();
            $diagDeg = rad2deg(acos($area -> getSizeX() / $diagonal));
            $this -> _blendLength = $diagonal * cos(deg2rad($this -> _angle - $diagDeg));
            if ($this -> debug) {
                echo 'diag=' . $diagonal . ' diagDeg=' . $diagDeg
                    . ' _blendLength=' . $this -> _blendLength . chr(10);
            }
        }
    }
}
