<?php

declare(strict_types=1);

namespace Jddf;

class MaxDepthExceededException extends \Exception
{
    public function __construct($code = 0, Exception $previous = null)
    {
        parent::__construct('Max depth exceeded during JDDF validation', $code, $previous);
    }
}
