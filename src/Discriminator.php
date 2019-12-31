<?php

declare(strict_types=1);

namespace Jddf;

class Discriminator
{
    public $tag;
    public $mapping;

    public static function fromJson(object $json): self
    {
        $discriminator = new Discriminator();

        if (!isset($json->tag) || !is_string($json->tag)) {
            throw new \TypeError('tag is required and must be a string');
        }

        $discriminator->tag = $json->tag;

        if (!isset($json->mapping) || !is_object($json->mapping)) {
            throw new \TypeError('mapping is required and must be an object');
        }

        $discriminator->mapping = [];
        foreach ($json->mapping as $key => $value) {
            $discriminator->mapping[$key] = Schema::fromJson($value);
        }

        return $discriminator;
    }
}
