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
                if (!is_string($instance)) {
                    $this->pushSchemaToken('type');
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
                    $this->popSchemaToken();
                } else {
                    $this->pushError();
                }
                $this->popSchemaToken();

              break;
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
