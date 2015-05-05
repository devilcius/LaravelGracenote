<?php

namespace Devilcius\LaravelGracenote\WebAPI;

/**
 * Extend normal PHP exceptions by includes an additional information field we can utilize.
 *
 */
class GNException extends \Exception
{

    private $extraInfo; // Additional information on the exception.

    public function __construct($code = 0, $extraInfo = "")
    {
        parent::__construct(GNError::getMessage($code), $code);
        $this->extraInfo = $extraInfo;
        echo("exception: code=" . $code . ", message=" . GNError::getMessage($code) . ", ext=" . $extraInfo . "\n");
    }

    public function getExtraInfo()
    {
        return $this->extraInfo;
    }

}
