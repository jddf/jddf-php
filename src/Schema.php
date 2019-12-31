<?php

declare(strict_types=1);

namespace Jddf;

class Schema
{
    public static $TYPES = [
        'boolean',
        'float32',
        'float64',
        'int8',
        'uint8',
        'int16',
        'uint16',
        'int32',
        'uint32',
        'string',
        'timestamp',
    ];

    public $definitions;
    public $ref;
    public $type;
    public $enum;
    public $elements;
    public $properties;
    public $optionalProperties;
    public $additionalProperties;
    public $values;
    public $discriminator;

    public static function fromJson(object $json): self
    {
        $schema = new Schema();

        if (isset($json->definitions)) {
            if (!is_object($json->definitions)) {
                throw new \TypeError('definitions must be an object');
            }

            $schema->definitions = [];
            foreach ($json->definitions as $key => $value) {
                $schema->definitions[$key] = Schema::fromJson($value);
            }
        }

        if (isset($json->ref)) {
            if (!is_string($json->ref)) {
                throw new \TypeError('ref must be a string');
            }

            $schema->ref = $json->ref;
        }

        if (isset($json->type)) {
            if (!in_array($json->type, Schema::$TYPES, true)) {
                throw new \TypeError('type must be in Schema::$TYPES');
            }

            $schema->type = $json->type;
        }

        if (isset($json->enum)) {
            if (!is_array($json->enum) || empty($json->enum)) {
                throw new \TypeError('enum must be a nonempty array');
            }

            foreach ($json->enum as $value) {
                if (!is_string($value)) {
                    throw new \TypeError('enum must be an array of strings');
                }
            }

            $schema->enum = $json->enum;
        }

        if (isset($json->elements)) {
            $schema->elements = Schema::fromJson($json->elements);
        }

        if (isset($json->properties)) {
            if (!is_object($json->properties)) {
                throw new \TypeError('properties must be an object');
            }

            $schema->properties = [];
            foreach ($json->properties as $key => $value) {
                $schema->properties[$key] = Schema::fromJson($value);
            }
        }

        if (isset($json->optionalProperties)) {
            if (!is_object($json->optionalProperties)) {
                throw new \TypeError('optionalProperties must be an object');
            }

            $schema->optionalProperties = [];
            foreach ($json->optionalProperties as $key => $value) {
                $schema->optionalProperties[$key] = Schema::fromJson($value);
            }
        }

        if (isset($json->additionalProperties)) {
            if (!is_bool($json->additionalProperties)) {
                throw new \TypeError('additionalProperties must be a bool');
            }

            $schema->additionalProperties = $json->additionalProperties;
        }

        if (isset($json->values)) {
            $schema->values = Schema::fromJson($json->values);
        }

        if (isset($json->discriminator)) {
            $schema->discriminator = Discriminator::fromJson($json->discriminator);
        }

        return $schema;
    }

    public function verify(): self
    {
        return $this->verifyWithRoot($this);
    }

    public function getForm(): string
    {
        if (!is_null($this->ref)) {
            return Form::REF;
        }
        if (!is_null($this->type)) {
            return Form::TYPE;
        }
        if (!is_null($this->enum)) {
            return Form::ENUM;
        }
        if (!is_null($this->elements)) {
            return Form::ELEMENTS;
        }
        if (!is_null($this->properties) || !is_null($this->optionalProperties)) {
            return Form::PROPERTIES;
        }
        if (!is_null($this->values)) {
            return Form::VALUES;
        }
        if (!is_null($this->discriminator)) {
            return Form::DISCRIMINATOR;
        }

        return Form::EMPTY;
    }

    private function verifyWithRoot(self $root): self
    {
        $is_empty = true;

        if (!is_null($this->definitions)) {
            if ($this !== $root) {
                throw new \InvalidArgumentException('definitions must only appear at root level');
            }

            foreach ($this->definitions as $value) {
                $value->verifyWithRoot($root);
            }
        }

        if (!is_null($this->ref)) {
            if (!$is_empty) {
                throw new \InvalidArgumentException('invalid schema form');
            }

            $is_empty = false;

            if (is_null($root->definitions) || !isset($root->definitions[$this->ref])) {
                throw new \InvalidArgumentException('reference to non-existent definition');
            }
        }

        if (!is_null($this->type)) {
            if (!$is_empty) {
                throw new \InvalidArgumentException('invalid schema form');
            }

            $is_empty = false;
        }

        if (!is_null($this->enum)) {
            if (!$is_empty) {
                throw new \InvalidArgumentException('invalid schema form');
            }

            $is_empty = false;

            if (count($this->enum) != count(array_unique($this->enum))) {
                throw new \InvalidArgumentException('enum contains repated values');
            }
        }

        if (!is_null($this->elements)) {
            if (!$is_empty) {
                throw new \InvalidArgumentException('invalid schema form');
            }

            $is_empty = false;

            $this->elements->verifyWithRoot($root);
        }

        if (!is_null($this->properties) || !is_null($this->optionalProperties)) {
            if (!$is_empty) {
                throw new \InvalidArgumentException('invalid schema form');
            }

            $is_empty = false;

            if (!is_null($this->properties) && !is_null($this->optionalProperties)) {
                if (!empty(array_intersect_key($this->properties, $this->optionalProperties))) {
                    throw new \InvalidArgumentException('properties and optionalProperties share key');
                }
            }
        }

        if (!is_null($this->values)) {
            if (!$is_empty) {
                throw new \InvalidArgumentException('invalid schema form');
            }

            $is_empty = false;

            $this->values->verifyWithRoot($root);
        }

        if (!is_null($this->discriminator)) {
            if (!$is_empty) {
                throw new \InvalidArgumentException('invalid schema form');
            }

            $is_empty = false;

            foreach ($this->discriminator->mapping as $value) {
                $value->verifyWithRoot($root);

                if (Form::PROPERTIES !== $value->getForm()) {
                    throw new \InvalidArgumentException('discriminator mapping value is not of properties form');
                }

                if (!is_null($value->properties) && isset($value->properties[$this->discriminator->tag])) {
                    throw new \InvalidArgumentException("discriminator mapping value has a property equal to tag's value");
                }

                if (!is_null($value->optionalProperties) && isset($value->optionalProperties[$this->discriminator->tag])) {
                    throw new \InvalidArgumentException("discriminator mapping value has an optional property equal to tag's value");
                }
            }
        }

        return $this;
    }
}
