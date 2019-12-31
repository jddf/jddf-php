<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class SchemaTest extends TestCase
{
    public function testFromJson(): void
    {
        $schema = Jddf\Schema::fromJson(json_decode(<<<'JSON'
            {
                "definitions": {
                    "foo": { "type": "string" }
                },
                "ref": "foo",
                "type": "string",
                "enum": ["foo"],
                "elements": { "type": "string" },
                "properties": {
                    "foo": { "type": "string" }
                },
                "optionalProperties": {
                    "foo": { "type": "string" }
                },
                "additionalProperties": true,
                "values": { "type": "string" },
                "discriminator": {
                    "tag": "foo",
                    "mapping": {
                        "foo": { "type": "string" }
                    }
                }
            }
        JSON));

        $this->assertEquals('string', $schema->definitions['foo']->type);
        $this->assertEquals('foo', $schema->ref);
        $this->assertEquals('string', $schema->type);
        $this->assertEquals(['foo'], $schema->enum);
        $this->assertEquals('string', $schema->elements->type);
        $this->assertEquals('string', $schema->properties['foo']->type);
        $this->assertEquals('string', $schema->optionalProperties['foo']->type);
        $this->assertEquals(true, $schema->additionalProperties);
        $this->assertEquals('string', $schema->values->type);
        $this->assertEquals('foo', $schema->discriminator->tag);
        $this->assertEquals('string', $schema->discriminator->mapping['foo']->type);
    }

    public function testForm(): void
    {
        $this->assertEquals(Jddf\Form::EMPTY, Jddf\Schema::fromJson(json_decode('{}'))->getForm());
        $this->assertEquals(Jddf\Form::REF, Jddf\Schema::fromJson(json_decode('{"ref": ""}'))->getForm());
        $this->assertEquals(Jddf\Form::TYPE, Jddf\Schema::fromJson(json_decode('{"type": "int8"}'))->getForm());
        $this->assertEquals(Jddf\Form::ENUM, Jddf\Schema::fromJson(json_decode('{"enum": [""]}'))->getForm());
        $this->assertEquals(Jddf\Form::ELEMENTS, Jddf\Schema::fromJson(json_decode('{"elements": {}}'))->getForm());
        $this->assertEquals(Jddf\Form::PROPERTIES, Jddf\Schema::fromJson(json_decode('{"properties": {}}'))->getForm());
        $this->assertEquals(Jddf\Form::PROPERTIES, Jddf\Schema::fromJson(json_decode('{"optionalProperties": {}}'))->getForm());
        $this->assertEquals(Jddf\Form::VALUES, Jddf\Schema::fromJson(json_decode('{"values": {}}'))->getForm());
        $this->assertEquals(Jddf\Form::DISCRIMINATOR, Jddf\Schema::fromJson(json_decode('{"discriminator": {"tag": "", "mapping": {}}}'))->getForm());
    }

    public function specTestProvider(): array
    {
        $testCases = [];
        foreach (json_decode(file_get_contents('spec/tests/invalid-schemas.json')) as $testCase) {
            $testCases[$testCase->name] = [$testCase->schema];
        }

        return $testCases;
    }

    /**
     * @dataProvider specTestProvider
     *
     * @param mixed $schema
     */
    public function testSpec($schema): void
    {
        $ok = false;

        try {
            Jddf\Schema::fromJson($schema)->verify();
        } catch (TypeError $e) {
            $ok = true;
        } catch (InvalidArgumentException $e) {
            $ok = true;
        }

        $this->assertTrue($ok);
    }
}
