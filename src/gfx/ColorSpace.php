<?php
/**
 * Abivia PHP Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: ColorSpace.php 100 2011-03-21 18:26:21Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */
namespace Apl\Gfx;

/**
 * Multiple model representation of a color.
 *
 * The ColorSpace class provides for the manipulation of colours using both
 * the RGB and HSB models.
 *
 * The class incorporates information on human visual perception to provide
 * functions that help indicate how different a human will perceive colors to
 * be.
 *
 * @package Apl
 * @subpackage Gfx
 */
class ColorSpace extends \Apl\Php\InflexibleObject {
    /**
     * Alpha channel, common to all models, range 0 to 1.
     *
     * @var float
     */
    protected $_alpha = 0.0;

    /**
     * Red, a component of the RGB model, range 0 to 1.
     *
     * @var float
     */
    protected $_blue = 0.0;

    /**
     * Brightness, a component of the HSB model, range 0 to 1.
     *
     * @var float
     */
    protected $_bright = 0.0;

    /**
     * Green, a component of the RGB model, range 0 to 1.
     *
     * @var float
     */
    protected $_green = 0.0;

    /**
     * Hue, a component of the HSB model, range 0 to 1.
     *
     * @var float
     */
    protected $_hue = 0.0;

    /**
     * Hue Deltas
     *
     * The hue delata are weighting factors that define how different the human
     * eye perceives a unit change in hue values.
     */
    static protected $_hueDeltas;

    /**
     * Named colors as defined in HTML 4.0, with enhancements.
     *
     * @var array
     */
    static protected $_namedColors = array(
        'aqua'      => 0x0000FFFF,
        'black'     => 0x00000000,
        'blue'      => 0x000000FF,
        'clear'     => 0x7F000000,
        'fuchsia'   => 0x00FF00FF,
        'gray'      => 0x00808080,
        'green'     => 0x00008000,
        'lime'      => 0x0000FF00,
        'maroon'    => 0x00800000,
        'navy'      => 0x00000080,
        'olive'     => 0x00808000,
        'red'       => 0x00FF0000,
        'silver'    => 0x00C0C0C0,
        'teal'      => 0x00008080,
        'white'     => 0x00FFFFFF,
        'yellow'    => 0x00FFFF00,
        'purple'    => 0x00800080,
    );

    /**
     * Red, a component of the RGB model, range 0 to 1.
     *
     * @var float
     */
    protected $_red = 0.0;

    /**
     * Saturation, a component of the HSB model, range 0 to 1.
     *
     * @var float
     */
    protected $_saturation = 0.0;

    /**
     * Class constructor -- use factory method instead.
     * @param mixed Red component.
     * @param mixed Green component.
     * @param mixed Blue component
     * @param mixed Alpha component.
     * @return void
     */
    function __construct($r = 0.0, $g = 0.0, $b = 0.0, $a = 0.0) {
        /*
         * If this is the first time we're constructing an object, initialize
         * the hue delta factors.
         */
        if (! is_array(self::$_hueDeltas)) {
            // magenta
            // 400, 9.0; 410, 5.5; 420, 2.0; 430, 2.0; 440, 4.0;
            // 450, 4.0; 460, 3.0; 470, 1.2; 480, 0.9; 490, 1.1;
            // 500, 2.0; 510, 3.0; 520, 3.6; 530, 3.8; 540, 3.9;
            // 550, 1.0; 560, 0.8; 570, 0.8; 580, 1.0; 590, 1.7;
            // 600, 1.9; 610, 2.4; 620, 3.1; 630, 4.3; 640, 6.0;
            // 650, 8.0; 660, 11.0;
            // red
            $delta = array(9.0, 5.5, 2.0, 2.0, 4.0, 4.0, 3.0, 1.2, 0.9, 1.1,
                2.0, 3.0, 3.6, 3.8, 3.9, 1.0, 0.8, 0.8, 1.0, 1.7,
                1.9, 2.4, 3.1, 4.3, 6.0, 8.0, 11.0);
            self::$_hueDeltas = array();
            $step = 1.0 / (count($delta) + 1);
            $d = 0;
            for ($ind = count($delta) - 1, $hStep = 0; $ind >= 0; --$ind, $hStep += $step) {
                self::$_hueDeltas[] = array($hStep, $d);
                $d += 1/$delta[$ind];
            }
            for ($ind = 0; $ind < count(self::$_hueDeltas); ++$ind) {
                self::$_hueDeltas[$ind][1] /= $d;
            }
            self::$_hueDeltas[] = array(1.0, 1.0);
        }
        $this -> setRgba($r, $g, $b, $a);
    }

    function __toString() {
        return __CLASS__ . '[r='. $this -> _red . ' g=' . $this -> _green
            . ' b=' . $this -> _blue .' a=' . $this -> _alpha
            . ' h=' . $this -> _hue .' s=' . $this -> _saturation
            . ' b=' . $this -> _bright . ']';
    }

    protected function _calcHsb() {
        $max = (float) max($this -> _red, $this -> _green, $this -> _blue);
        $min = (float) min($this -> _red, $this -> _green, $this -> _blue);
        $this -> _bright = $max;
        if ($max) {
            $this -> _saturation = ($max - $min) / $max;
        } else {
            $this -> _saturation = 0.0;
        }
        if ($this -> _saturation) {
            $delta = $max - $min;
            if ($this -> _red == $max) {
                $h = ($this -> _green - $this -> _blue) / $delta;
            } else if ($this -> _green == $max) {
                $h = 2 + ($this -> _blue - $this -> _red) / $delta;
            } else {
                $h = 4 + ($this -> _red - $this -> _green) / $delta;
            }
            $h /= 6;
            if ($h < 0) {
                $h += 1.0;
            }
            if ($h == 1.0) {
                $h = 0.0;
            }
            $this -> _hue = $h;
        } else {
            $this -> _hue = 0.0;
        }
    }

    protected function _calcRgb() {
        if ($this -> _saturation) {
            $h = $this -> _hue * 6;
            $sector = floor($h);
            $spos = $h - $sector;
            $c1 = $this -> _bright * (1 - $this -> _saturation);
            $c2 = $this -> _bright * (1 - ($this -> _saturation * $spos));
            $c3 = $this -> _bright * (1 - ($this -> _saturation * (1 - $spos)));
            //echo 'sec=' . $sector . ' spos=' . $spos . ' c1=' . $c1 . ' c2=' . $c2 . ' c3=' . $c3 . '<br/>';
            switch ($sector) {
                case 0: {
                    $this -> _red = $this -> _bright;
                    $this -> _green = $c3;
                    $this -> _blue = $c1;
                } break;

                case 1: {
                    $this -> _red = $c2;
                    $this -> _green = $this -> _bright;
                    $this -> _blue = $c1;
                } break;

                case 2: {
                    $this -> _red = $c1;
                    $this -> _green = $this -> _bright;
                    $this -> _blue = $c3;
                } break;

                case 3: {
                    $this -> _red = $c1;
                    $this -> _green = $c2;
                    $this -> _blue = $this -> _bright;
                } break;

                case 4: {
                    $this -> _red = $c3;
                    $this -> _green = $c1;
                    $this -> _blue = $this -> _bright;
                } break;

                case 5: {
                    $this -> _red = $this -> _bright;
                    $this -> _green = $c1;
                    $this -> _blue = $c2;
                } break;

            }
        } else {
            $this -> _red = $this -> _bright;
            $this -> _green = $this -> _bright;
            $this -> _blue = $this -> _bright;
        }
    }

    static protected function _limit($f) {
        if ($f < 0) {
            return 0.0;
        } else if ($f > 1) {
            return 1.0;
        } else {
            return $f;
        }
    }

    /**
     * Raw add another color to this one.
     *
     * This is useful in computation, e.g. when the delta is an incremental
     * transition between two colors.
     *
     * @param AP5L_Gfx_ColorSpace The incremental values.
     */
    function add($delta) {
        $this -> _alpha = self::_limit($this -> _alpha + $delta -> getAlpha());
        $this -> _red = self::_limit($this -> _red + $delta -> getRed());
        $this -> _green = self::_limit($this -> _green + $delta -> getGreen());
        $this -> _blue = self::_limit($this -> _blue + $delta -> getBlue());
        $this -> _calcHsb();
    }

    /**
     * Calculate an intermediate color from two colors
     *
     * This is useful in computation, e.g. to compute an incremental transition
     * between two colors.
     *
     * @param AP5L_Gfx_ColorSpace The color to transition to.
     * @param float Linear point between colors, where 0 is no change and 1.0
     * returns the mix color. Negative values are accepted; the absolute value
     * of this argument is used.
     * @return AP5L_Gfx_ColorSpace The blended color.
     */
    function &blend($mix, $ratio, $blendAlpha = false) {
        $ratio = self::_limit(abs($ratio));
        if ($blendAlpha) {
            $ratioA = $ratio;
        } else {
            $ratio *= (1 - $mix -> getAlpha());
            $ratioA = 0;
        }
        $result = self::factory(
            $this -> _red + $ratio * ($mix -> getRed() - $this -> _red),
            $this -> _green + $ratio * ($mix -> getGreen() - $this -> _green),
            $this -> _blue + $ratio * ($mix -> getBlue() - $this -> _blue),
            $this -> _alpha + $ratioA * ($mix -> getAlpha() - $this -> _alpha)
        );
        return $result;
    }

    /**
     * Calculate an intermediate color from two colors, return as integer.
     *
     * This is useful in computation, e.g. to compute an incremental transition
     * between two colors. It has a speed advantage over blend() because no
     * ColorSpace object is created.
     *
     * @param AP5L_Gfx_ColorSpace The color to transition to.
     * @param float Linear point between colors, where 0 is no change and 1.0
     * returns the mix color. Negative values are accepted; the absolute value
     * of this argument is used.
     * @return int The blended color.
     */
    function &blendToInt($mix, $ratio, $blendAlpha = false) {
        $ratio = self::_limit(abs($ratio));
        if ($blendAlpha) {
            $ratioA = $ratio;
        } else {
            $ratio *= (1 - $mix -> getAlpha());
            $ratioA = 0;
        }
        $result = self::rgbaToInt(
            $this -> _red + $ratio * ($mix -> getRed() - $this -> _red),
            $this -> _green + $ratio * ($mix -> getGreen() - $this -> _green),
            $this -> _blue + $ratio * ($mix -> getBlue() - $this -> _blue),
            $this -> _alpha + $ratioA * ($mix -> getAlpha() - $this -> _alpha)
        );
        return $result;
    }

    /**
     * Get difference between two colours
     *
     * This is useful in computation, e.g. to compute an incremental transition
     * between two colors.
     *
     * @param AP5L_Gfx_ColorSpace The color to transition to.
     * @param int The number of steps in the transition. Optional, defaults to
     * 1.
     * @return AP5L_Gfx_ColorSpace A color containing the stepwise difference
     * between the two colours.
     */
    function &delta($delta, $steps = 1) {
        $result = self::factory(
            ($delta -> getRed() - $this -> _red) / $steps,
            ($delta -> getGreen() - $this -> _green) / $steps,
            ($delta -> getBlue() - $this -> _blue) / $steps,
            ($delta -> getAlpha() - $this -> _alpha) / $steps
        );
        return $result;
    }

    /**
     * ColorSpace factory; create a colorspace based on variable parameters.
     *
     * @param array|int|float|string|ColorSpace If an array is provided,
     * the array elements are decomposed to scalars (up to the maximum
     * defined) and handled as scalars. If a numeric value is provided, this
     * is the red component of the color. Integers are trated as being in the
     * range 0-255; floats in the range 0.0-1.0. String values are parsed as
     * hexadecimal RGB values. ColorSpace values are effectively cloned.
     * Defaults to integer zero.
     * @param int|float The green component of the colour. Integer 0-255 or
     * float 0.0-1.0. Defaults to zero.
     * @param int|float The blue component of the colour. Integer 0-255 or
     * float 0.0-1.0. Defaults to zero.
     * @param int|float The alpha component of the colour. If an integer is
     * provided, it is treated as being in the range 0-127; floats are in the
     * range 0.0-1.0. Defaults to zero.
     * @return \Apl\Gfx\ColorSpace The new ColorSpace object.
     */
    static function &factory($r = 0, $g = 0, $b = 0, $a = 0) {
        if (is_array($r)) {
            while (count($r) < 4) {
                $r[] = 0;
            }
            list($r, $g, $b, $a) = $r;
        }
        if ($r instanceof ColorSpace) {
            $c = new ColorSpace(
                $r -> getRed(), $r -> getGreen(), $r -> getBlue(), $r -> getAlpha()
            );
        } else {
            if (is_string($r)) {
                try {
                    $c = new ColorSpace();
                    $c -> setHex($r);
                    return $c;
                } catch (Exception $e) {
                }
            }
            $c = new ColorSpace($r, $g, $b, $a);
        }
        return $c;
    }

    function getAlpha() {
        return $this -> _alpha;
    }

    function getAlphaInt() {
        return (int) round(127 * $this -> _alpha);
    }

    function getBlue() {
        return $this -> _blue;
    }

    function getBlueInt() {
        return (int) round(255 * $this -> _blue);
    }

    function getBright() {
        return $this -> _bright;
    }

    function getBrightInt() {
        return (int) round(255 * $this -> _bright);
    }

    function getGray() {
        return 0.30 * $this -> _red + 0.59 * $this -> _green + 0.11 * $this -> _blue;
    }

    function getGreen() {
        return $this -> _green;
    }

    function getGreenInt() {
        return (int) round(255 * $this -> _green);
    }

    /**
     * Return the hexadecimal equivalent of the colour, optionally with alpha
     * value.
     */
    function getHex($withAlpha = false) {
        $hex = '';
        $hex .= substr(dechex(256 + round(255 * $this -> _red)), 1)
            . substr(dechex(256 + round(255 * $this -> _green)), 1)
            . substr(dechex(256 + round(255 * $this -> _blue)), 1);
        if ($withAlpha) {
            $hex .= substr(dechex(256 + round(127 * $this -> _alpha)), 1);
        }
        return $hex;
    }

    function getHue() {
        return $this -> _hue;
    }

    /*
     * Calculate a normalized hue, on a scale of 0 to 1, using the scale of
     * human perception of colour differences in ColorSpaceDeltas.
     */
    function getHueHuman() {
        return  self::hueToHuman($this -> _hue);
    }

    function getHueInt() {
        return (int) round(255 * $this -> _hue);
    }

    /**
     * Return the RGB values, optionally with alpha value.
     */
    function &getRgba($withAlpha = true) {
        $vals = array(
            $this -> _red,
            $this -> _green,
            $this -> _blue,
        );
        if ($withAlpha) {
            $vals[] = $this -> _alpha;
        }
        return $vals;
    }

    /**
     * Return the integer equivalent of the colour, optionally with alpha value.
     */
    function getRgbaInt($withAlpha = true) {
        return self::rgbaToInt(
            $this -> _red,
            $this -> _green,
            $this -> _blue,
            $withAlpha ? $this -> _alpha : 0
        );
    }

    function getRed() {
        return $this -> _red;
    }

    function getRedInt() {
        return (int) round(255 * $this -> _red);
    }

    function getSaturation() {
        return $this -> _saturation;
    }

    function getSaturationInt() {
        return (int) round(255 * $this -> _saturation);
    }

    /**
     * Return the relative "distance" between two hues, based on human
     * perception factors.
     */
    function hueDistance($cs) {
        $hd = 2 * abs(self::hueToHuman($this -> _hue) - self::hueToHuman($cs -> getHue()));
        if ($hd > 1) {
            $hd = 2 - $hd;
        }
    }

    /*
     * Calculate a normalized hue, on a scale of 0 to 1, using the scale of
     * human perception of colour differences in ColorSpaceDeltas.
     */
    static function hueToHuman($hue) {
        if (! $hue) return 0.0;
        for ($ind = 0; $ind < count(self::$_hueDeltas); ++$ind) {
            if (self::$_hueDeltas[$ind][0] >= $hue) {
                $csd = self::$_hueDeltas[$ind][0] - self::$_hueDeltas[$ind - 1][0];
                $hsd = $hue - self::$_hueDeltas[$ind - 1][0];
                $mcsd = self::$_hueDeltas[$ind][1] - self::$_hueDeltas[$ind - 1][1];
                return  self::$_hueDeltas[$ind - 1][1] + ($hsd / $csd * $mcsd);
            }
        }
    }

    /*
     * Calculate a hue, from a factor representing human perception of color
     * differences
     */
    static function humantoHue($hh) {
        if (! $hh) return 0.0;
        for ($ind = 0; $ind < count(self::$_hueDeltas); ++$ind) {
            if (self::$_hueDeltas[$ind][1] >= $hh) {
                $csd = self::$_hueDeltas[$ind][1] - self::$_hueDeltas[$ind - 1][1];
                $hsd = $hh - self::$_hueDeltas[$ind - 1][1];
                $mcsd = self::$_hueDeltas[$ind][0] - self::$_hueDeltas[$ind - 1][0];
                return  self::$_hueDeltas[$ind - 1][0] + ($hsd / $csd * $mcsd);
            }
        }
    }

    static function rgbaIntBlend($colL, $colR, $ratio, $blendAlpha = false) {
        $ratio = (float) $ratio;
        $aL = ($colL >> 24) & 0x0FF;
        $aR = ($colR >> 24) & 0x0FF;
        if ($blendAlpha) {
            $a = $aL + $ratio * ($aR - $aL);
        } else {
            $a = $aL;
            $ratio *= (1 - $aR / 127.0);
        }
        // Red
        $rL = ($colL >> 16) & 0x0FF;
        $rR = ($colR >> 16) & 0x0FF;
        $r = $rL + $ratio * ($rR - $rL);
        // Green
        $gL = ($colL >> 8) & 0x0FF;
        $gR = ($colR >> 8) & 0x0FF;
        $g = $gL + $ratio * ($gR - $gL);
        // Blue
        $bL = $colL & 0x0FF;
        $bR = $colR & 0x0FF;
        $b = $bL + $ratio * ($bR - $bL);
        // Build the integer
        $intVal= (round($a) << 24)
            | (round($r) << 16)
            | (round($g) << 8)
            | round($b)
            ;
        return $intVal;
    }

    static function rgbaToInt($r, $g, $b, $a) {
        $intVal= (round(127 * $a) << 24)
            | (round(255 * $r) << 16)
            | (round(255 * $g) << 8)
            | round(255 * $b)
            ;
        return $intVal;
    }

    /**
     * Return a monochrome equivalent of the color.
     *
     * This method uses the YIQ color model, returning the Y (luminance)
     * component. Alpha is preserved.
     *
     * @return ColorSpace The monochrome equivalent color.
     */
    function &monochrome() {
        $gray = $this -> getGray();
        $result = self::factory($gray, $gray, $gray, $this -> _alpha);
        return $result;
    }

    /**
     * Posterize the color.
     *
     * @param float A positive, non-zero value. If greater than one, this is
     * considered to be the (rounded integer) number of posterization bands. If
     * less than or equal to one, this is the rounding increment.
     */
    function &posterize($quantum) {
        if ($quantum > 1) {
            $bands = round($quantum);
        } elseif ($quantum > 0) {
            $bands = round(1 / $quantum);
        } else {
            throw new Exception($quantum . ' is an invalid posterization quantum.');
        }
        if ($bands == 1) {
            // One band... everything is gray
            $result = self::factory(0.5, 0.5, 0.5, $this -> _alpha);
        } else {
            /*
             * The banding formula returns results greater than one; the color
             * constructor handles this edge case.
             */
            $result = self::factory(
                (floor($bands * $this -> _red)) / ($bands - 1),
                (floor($bands * $this -> _green)) / ($bands - 1),
                (floor($bands * $this -> _blue)) / ($bands - 1),
                $this -> _alpha
            );
        }
        return $result;
    }

    /**
     * Add component values into a running sum.
     */
    function runningSum(&$sum, $weight = 1) {
        $sum[0] += $weight * $this -> _red;
        $sum[1] += $weight * $this -> _green;
        $sum[2] += $weight * $this -> _blue;
        $sum[3] += $weight * $this -> _alpha;
    }

    function setAlpha($a) {
        if (is_int($a)) {
            $a /= 127.0;
        }
        $this -> _alpha = $this -> _limit($a);
    }

    function setBlue($b) {
        if (is_int($b)) {
            $b /= 255.0;
        }
        $this -> _blue = $this -> _limit($b);
        $this -> _calcHsb();
    }

    function setBright($b) {
        if (is_int($b)) {
            $b /= 255.0;
        }
        $this -> _bright = $this -> _limit($b);
        $this -> _calcRgb();
    }

    function setGray($g) {
        if (is_int($g)) {
            $g /= 255.0;
        }
        $this -> _red = $this -> _limit($g);
        $this -> _green = $this -> _limit($g);
        $this -> _blue = $this -> _limit($g);
        $this -> _calcHsb();
    }

    function setGreen($g) {
        if (is_int($g)) {
            $g /= 255.0;
        }
        $this -> _green = $this -> _limit($g);
        $this -> _calcHsb();
    }

    /**
     * Set color based on a HTML style hexadecimal string.
     *
     * @param string All non-hex characters are filtered from the string. The
     * resulting string must have 3, 4, 6, or 8 digits.
     * @throws Apl\Gfx\Exception If the string is not valid hex.
     * @return Apl\Gfx\ColorSpace The current object.
     */
    function setHex($hex) {
        $clean = '';
        for ($ind = 0; $ind < strlen($hex); ++$ind) {
            if (stripos('.0123456789abcdef', substr($hex, $ind, 1))) {
                $clean .= substr($hex, $ind, 1);
            }
        }
        switch (strlen($clean)) {
            case 3: {
                $a = 0.0;
                $r = hexdec($clean[0] . $clean[0]) / 255.0;
                $g = hexdec($clean[1] . $clean[1]) / 255.0;
                $b = hexdec($clean[2] . $clean[2]) / 255.0;
            } break;

            case 4: {
                $r = hexdec($clean[0] . $clean[0]) / 255.0;
                $g = hexdec($clean[1] . $clean[1]) / 255.0;
                $b = hexdec($clean[2] . $clean[2]) / 255.0;
                $a = hexdec($clean[3] . $clean[3]) / 127.0;
            } break;

            case 6: {
                $a = 0.0;
                $r = hexdec($clean[0] . $clean[1]) / 255.0;
                $g = hexdec($clean[2] . $clean[3]) / 255.0;
                $b = hexdec($clean[4] . $clean[5]) / 255.0;
            } break;

            case 8: {
                $r = hexdec($clean[0] . $clean[1]) / 255.0;
                $g = hexdec($clean[2] . $clean[3]) / 255.0;
                $b = hexdec($clean[4] . $clean[5]) / 255.0;
                $a = hexdec($clean[6] . $clean[7]) / 127.0;
            } break;

            default: {
                throw new Exception('Unable to parse "' . $hex . '" as hex color.');
            }

        }
        $this -> setRgba($r, $g, $b, $a);
        return $this;
    }

    /**
     * Set color using HSB values, without affecting alpha.
     */
    function setHsb($h, $s = 0, $b = 0) {
        if (is_array($h)) {
            if (count($h) < 3) {
                throw new Exception(
                    'Array passed to ' . __FUNCTION__ . ' must have 3 elements.'
                );
            }
            $temp = array_shift($h);
            $s = array_shift($h);
            $b = array_shift($h);
            $h = $temp;
        }
        if (is_int($b)) {
            $b /= 255.0;
        }
        if (is_int($h)) {
            $h /= 255.0;
        }
        if (is_int($s)) {
            $s /= 255.0;
        }
        $this -> _bright = $this -> _limit($b);
        $this -> _hue = $this -> _limit($h);
        $this -> _saturation = $this -> _limit($s);
        $this -> _calcRgb();
    }

    function setHsba($h, $s = 0, $b = 0, $a = 0) {
        if (is_array($h)) {
            if (count($h) < 4) {
                throw new Exception(
                    'Array passed to ' . __FUNCTION__ . ' must have 4 elements.'
                );
            }
            $temp = array_shift($h);
            $s = array_shift($h);
            $b = array_shift($h);
            $a = array_shift($h);
            $h = $temp;
        }
        if (is_int($a)) {
            $a /= 127.0;
        }
        $this -> _alpha = $this -> _limit($a);
        $this -> setHsb($h, $s, $b);
    }

    function setHue($h) {
        if (is_int($h)) {
            $h /= 255.0;
        }
        if ($h < 0) {
            $h = 0.0;
        } else if ($h > 1) {
            $h = 0.0;
        }
        $this -> _hue = $h;
        $this -> _calcRgb();
    }

    function setHueHuman($hh) {
        $this -> _hue = self::humantoHue($hh);
        $this -> _calcRgb();
    }

    function setNamed($name, $alpha = 0) {
        $name = strtolower($name);
        if (! isset(self::$_namedColors[$name])) {
            throw new Exception('Unknown named color: ' . $name);
        }
        $this -> setRgbInt(self::$_namedColors[$name]);
        $this -> setAlpha($alpha);
    }

    function setRed($r) {
        if (is_int($r)) {
            $r /= 255.0;
        }
        $this -> _red = $this -> _limit($r);
        $this -> _calcHsb();
    }

    /**
     * Set color using RGB values, without affecting alpha.
     */
    function setRgb($r, $g = 0, $b = 0) {
        if (is_array($r)) {
            if (count($r) < 3) {
                throw new Exception(
                    'Array passed to ' . __FUNCTION__ . ' must have 3 elements.'
                );
            }
            $temp = array_shift($r);
            $g = array_shift($r);
            $b = array_shift($r);
            $r = $temp;
        }
        if (is_int($b)) {
            $b /= 255.0;
        }
        if (is_int($g)) {
            $g /= 255.0;
        }
        if (is_int($r)) {
            $r /= 255.0;
        }
        $this -> _blue = $this -> _limit($b);
        $this -> _green = $this -> _limit($g);
        $this -> _red = $this -> _limit($r);
        $this -> _calcHsb();
    }

    /**
     * Set RGB color components.
     *
     * @param array|int|float If an array, elements are converted to scalar
     * parameters. If scalar, red component. Integer in the range 0..255 or
     * float in the range 0..1.
     * @param int|float Green component. Integer in the range 0..255 or float
     * in the range 0..1.
     * @param int|float Blue component. Integer in the range 0..255 or float
     * in the range 0..1.
     * @param int|float Alpha component. Integer in the range 0..127 or float
     * in the range 0..1.
     * @return AP5L_Gfx_ColorSpace The current object.
     */
    function &setRgba($r, $g = 0, $b = 0, $a = 0) {
        if (is_array($r)) {
            while (count($r) < 4) {
                $r[] = 0;
            }
            list($r, $g, $b, $a) = $r;
        }
        if (is_int($a)) {
            $a /= 127.0;
        }
        $this -> _alpha = $this -> _limit($a);
        $this -> setRgb($r, $g, $b);
        return $this;
    }

    function setRgbInt($intVal) {
        $this -> setRgba(
            ($intVal >> 16) & 0x0FF,
            ($intVal >> 8) & 0x0FF,
            $intVal & 0x0FF,
            ($intVal >> 24) & 0x07F
        );
    }

    function setSaturation($s) {
        if (is_int($s)) {
            $s /= 255.0;
        }
        $this -> _saturation = $this -> _limit($s);
        $this -> _calcRgb();
    }

}
