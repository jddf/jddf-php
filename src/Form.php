<?php

declare(strict_types=1);

namespace Jddf;

abstract class Form
{
    public const EMPTY = 'empty';
    public const REF = 'ref';
    public const TYPE = 'type';
    public const ENUM = 'enum';
    public const ELEMENTS = 'elements';
    public const PROPERTIES = 'properties';
    public const VALUES = 'values';
    public const DISCRIMINATOR = 'discriminator';
}
