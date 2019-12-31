<?php

declare(strict_types=1);

namespace Jddf;

class Validator
{
    public $maxDepth;
    public $maxErrors;

    public function validate(Schema $schema, $instance): array
    {
        $vm = new VM();
        $vm->rootSchema = $schema;
        $vm->maxDepth = $this->maxDepth;
        $vm->maxErrors = $this->maxErrors;
        $vm->instanceTokens = [];
        $vm->schemaTokens = [[]];
        $vm->errors = [];

        try {
            $vm->validate($schema, $instance, null);
        } catch (MaxErrorsDummyException $e) {
            // Intentionally left blank.
        }

        return $vm->errors;
    }
}

class Vm
{
    public $rootSchema;
    public $maxDepth;
    public $maxErrors;
    public $instanceTokens;
    public $schemaTokens;
    public $errors;

    public function validate(Schema $schema, $instance, ?string $parentTag = null): void
    {
        switch ($schema->getForm()) {
            case Form::REF:
                if (count($this->schemaTokens) === $this->maxDepth) {
                    throw new MaxDepthExceededException();
                }

                $this->schemaTokens[] = ['definitions', $schema->ref];
                $this->validate($this->rootSchema->definitions[$schema->ref], $instance);
                array_pop($this->schemaTokens);

                break;
            case Form::TYPE:
              $this->pushSchemaToken('type');
              switch ($schema->type) {
                  case 'boolean':
                      if (!is_bool($instance)) {
                          $this->pushError();
                      }

                      break;
                  case 'float32':
                  case 'float64':
                      if (!is_float($instance) && !is_int($instance)) {
                          $this->pushError();
                      }

                      break;
                  case 'int8':
                      $this->validateInt(-128, 127, $instance);

                      break;
                  case 'uint8':
                    $this->validateInt(0, 255, $instance);

                    break;
                  case 'int16':
                      $this->validateInt(-32768, 32767, $instance);

                      break;
                  case 'uint16':
                    $this->validateInt(0, 65535, $instance);

                    break;
                  case 'int32':
                      $this->validateInt(-2147483648, 2147483647, $instance);

                      break;
                  case 'uint32':
                    $this->validateInt(0, 4294967295, $instance);

                    break;
                  case 'string':
                      if (!is_string($instance)) {
                          $this->pushError();
                      }

                      break;
                  case 'timestamp':
                      if (!is_string($instance) || false === \DateTime::createFromFormat(\DateTime::RFC3339_EXTENDED, $instance)) {
                          $this->pushError();
                      }

                      break;
                }

                $this->popSchemaToken();

                break;
            case Form::ENUM:
                if (!in_array($instance, $schema->enum)) {
                    $this->pushSchemaToken('enum');
                    $this->pushError();
                    $this->popSchemaToken();
                }

                break;
            case Form::ELEMENTS:
              $this->pushSchemaToken('elements');
                if (is_array($instance)) {
                    foreach ($instance as $index => $value) {
                        $this->pushInstanceToken(strval($index));
                        $this->validate($schema->elements, $value);
                        $this->popInstanceToken();
                    }
                } else {
                    $this->pushError();
                }
                $this->popSchemaToken();

                break;
            case Form::PROPERTIES:
                if (is_object($instance)) {
                    if (!is_null($schema->properties)) {
                        $this->pushSchemaToken('properties');

                        foreach ($schema->properties as $property => $subSchema) {
                            $this->pushSchemaToken($property);

                            if (property_exists($instance, $property)) {
                                $this->pushInstanceToken($property);
                                $this->validate($subSchema, $instance->{$property});
                                $this->popInstanceToken();
                            } else {
                                $this->pushError();
                            }

                            $this->popSchemaToken();
                        }

                        $this->popSchemaToken();
                    }

                    if (!is_null($schema->optionalProperties)) {
                        $this->pushSchemaToken('optionalProperties');

                        foreach ($schema->optionalProperties as $property => $subSchema) {
                            $this->pushSchemaToken($property);

                            if (property_exists($instance, $property)) {
                                $this->pushInstanceToken($property);
                                $this->validate($subSchema, $instance->{$property});
                                $this->popInstanceToken();
                            }

                            $this->popSchemaToken();
                        }

                        $this->popSchemaToken();
                    }

                    if (!$schema->additionalProperties) {
                        foreach ($instance as $property => $value) {
                            if ($property !== $parentTag && !isset($schema->properties[$property]) && !isset($schema->optionalProperties[$property])) {
                                $this->pushInstanceToken($property);
                                $this->pushError();
                                $this->popInstanceToken();
                            }
                        }
                    }
                } else {
                    if (is_null($schema->properties)) {
                        $this->pushSchemaToken('optionalProperties');
                    } else {
                        $this->pushSchemaToken('properties');
                    }

                    $this->pushError();

                    $this->popSchemaToken();
                }

                break;
            case Form::VALUES:
                $this->pushSchemaToken('values');
                if (is_object($instance)) {
                    foreach ($instance as $property => $value) {
                        $this->pushInstanceToken($property);
                        $this->validate($schema->values, $value);
                        $this->popInstanceToken();
                    }
                } else {
                    $this->pushError();
                }
                $this->popSchemaToken();

              break;
            case Form::DISCRIMINATOR:
                $this->pushSchemaToken('discriminator');

                if (is_object($instance)) {
                    if (property_exists($instance, $schema->discriminator->tag)) {
                        $tagValue = $instance->{$schema->discriminator->tag};
                        if (is_string($tagValue)) {
                            $this->pushSchemaToken('mapping');

                            if (isset($schema->discriminator->mapping[$tagValue])) {
                                $this->pushSchemaToken($tagValue);
                                $this->validate($schema->discriminator->mapping[$tagValue], $instance, $schema->discriminator->tag);
                                $this->popSchemaToken();
                            } else {
                                $this->pushInstanceToken($schema->discriminator->tag);
                                $this->pushError();
                                $this->popInstanceToken();
                            }

                            $this->popSchemaToken();
                        } else {
                            $this->pushSchemaToken('tag');
                            $this->pushInstanceToken($schema->discriminator->tag);
                            $this->pushError();
                            $this->popInstanceToken();
                            $this->popSchemaToken();
                        }
                    } else {
                        $this->pushSchemaToken('tag');
                        $this->pushError();
                        $this->popSchemaToken();
                    }
                } else {
                    $this->pushError();
                }

                $this->popSchemaToken();

                break;
        }
    }

    private function validateInt(float $min, float $max, $instance)
    {
        if (is_int($instance) || is_float($instance)) {
            if (floor($instance) !== (float) $instance || $instance < $min || $instance > $max) {
                $this->pushError();
            }
        } else {
            $this->pushError();
        }
    }

    private function pushInstanceToken(string $token)
    {
        $this->instanceTokens[] = $token;
    }

    private function popInstanceToken()
    {
        array_pop($this->instanceTokens);
    }

    private function pushSchemaToken(string $token)
    {
        $this->schemaTokens[count($this->schemaTokens) - 1][] = $token;
    }

    private function popSchemaToken()
    {
        array_pop($this->schemaTokens[count($this->schemaTokens) - 1]);
    }

    private function pushError()
    {
        $this->errors[] = new ValidationError($this->instanceTokens, $this->schemaTokens[count($this->schemaTokens) - 1]);

        if (count($this->errors) === $this->maxErrors) {
            throw new MaxErrorsDummyException();
        }
    }
}

class MaxErrorsDummyException extends \Exception
{
}
