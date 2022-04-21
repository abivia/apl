<?php
/**
 * Base class for fill maps.
 *
 * @package AP5L
 * @subpackage Gfx
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: FillMap.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */
namespace \Apl\Gfx;

/**
 * Base fill mapping class.
 */
abstract class FillMap extends \Apl\Php\InflexibleObject {

    /**
     * Use midpoint of band at endpoints.
     *
     * @var boolean
     */
    public $bandMidpoint = true;

    /**
     * Number of bands to generate. Zero if continuous.
     *
     * @var int
     */
    public $bands = 0;

    /**
     * Ending x value in an iteration.
     *
     * @var int
     */
    public $endX;

    /**
     * Ending y value in an iteration.
     *
     * @var int
     */
    public $endY;

    /**
     * X iteration increment.
     *
     * @var int
     */
    public $incrX;

    /**
     * Y iteration increment.
     *
     * @var int
     */
    public $incrY;

    /**
     * Iteration mode, one of x, y, xy.
     */
    public $iterateMode;

    /**
     * Starting x value in an iteration.
     *
     * @var int
     */
    public $startX;

    /**
     * Starting y value in an iteration.
     *
     * @var int
     */
    public $startY;

    abstract function getColor($x, $y, $colors);

    abstract function getColorInt($x, $y, $colors);

    abstract function getRatio($x, $y);

    function setup() {
    }
}

