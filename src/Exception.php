<?php
declare(strict_types=1);

namespace Abivia\Apl;

/**
 * Apl MathException
 */
class Exception extends \Exception
{
    /**
     * Language independent exception details.
     *
     * @var array
     */
    public $details = array();

    function __toString()
    {
        $str = parent::__toString();
        foreach ($this->details as $info) {
            $str .= ' ' . $info;
        }
        return $str;
    }

    /**
     * Default factory method
     */
    static public function &factory($subType = '', $message = '', $code = 0, $details = [])
    {
        $subType = ($subType == '') ? 'Abivia\Apl\Exception' : $subType . 'MathException';
        $e = new $subType($message, $code);
        $e->details = $details;
        return $e;
    }

    /**
     * Factory method to throw a PEAR error as an exception
     */
    static public function &fromPEAR($pearError)
    {
        $e = self::factory(
            $pearError->getMessage(),
            $pearError->getCode()
        );
        return $e;
    }

}

