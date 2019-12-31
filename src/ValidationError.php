<?php

declare(strict_types=1);

namespace Jddf;

class ValidationError
{
    public $instancePath;
    public $schemaPath;

    public function __construct(array $instancePath, array $schemaPath)
    {
        $this->instancePath = $instancePath;
        $this->schemaPath = $schemaPath;
    }
}
