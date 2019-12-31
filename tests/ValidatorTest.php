<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class ValidatorTest extends TestCase
{
    public function testValidateMaxDepth(): void
    {
        $this->expectException(Jddf\MaxDepthExceededException::class);
        $validator = new Jddf\Validator();
        $validator->maxDepth = 3;
        $schema = Jddf\Schema::fromJson(json_decode(<<<'JSON'
            {
                "definitions": {
                    "": { "ref": "" }
                },
                "ref": ""
            }
        JSON));

        $validator->validate($schema, null);
    }

    public function testValidateMaxErrors(): void
    {
        $validator = new Jddf\Validator();
        $validator->maxErrors = 3;
        $schema = Jddf\Schema::fromJson(json_decode(<<<'JSON'
            {
                "elements": {
                    "type": "string"
                }
            }
        JSON));

        $this->assertCount(3, $validator->validate($schema, [null, null, null, null, null]));
    }

    /**
     * @dataProvider validateSpecProvider
     *
     * @param mixed $instance
     */
    public function testValidateSpec(Jddf\Schema $schema, $instance, array $errors): void
    {
        $validator = new Jddf\Validator();
        $this->assertEquals($errors, $validator->validate($schema, $instance));
    }

    public function validateSpecProvider(): array
    {
        $testCases = [];

        foreach (glob('spec/tests/validation/002-ref.json') as $file) {
            foreach (json_decode(file_get_contents($file)) as $testSuite) {
                $schema = Jddf\Schema::fromJson($testSuite->schema);
                foreach ($testSuite->instances as $index => $testCase) {
                    $errors = [];
                    foreach ($testCase->errors as $error) {
                        $instancePath = explode('/', $error->instancePath);
                        $schemaPath = explode('/', $error->schemaPath);

                        array_shift($instancePath);
                        array_shift($schemaPath);

                        $errors[] = new Jddf\ValidationError($instancePath, $schemaPath);
                    }

                    $testCases[$file.'/'.$testSuite->name.'/'.$index] = [$schema, $testCase->instance, $errors];
                }
            }
        }

        return $testCases;
    }
}
