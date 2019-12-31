# jddf-php

This package is a PHP implementation of **JSON Data Definition Format**. You can
use this package to:

1. Validate input data against a schema,
2. Get a list of validation errors from that input data, or
3. Build your own tooling on top of JSON Data Definition Format

## Installation

You can add this package as a dependency using Composer:

```bash
composer require jddf/jddf
```

## Usage

Here's how you parse a schema, validate data against that schema, and see what
validation errors came back:

```php
<?php

// For the purposes of demonstration, we're going to parse a schema from JSON.
// You can also construct instances of Jddf\Schema yourself directly.
$schemaJson = <<<'JSON'
    {
        "properties": {
            "name": { "type": "string" },
            "age": { "type": "uint32" },
            "phones": {
              "elements": { "type": "string" }
            }
        }
    }
JSON;

// Parse a schema from JSON using json_decode and Schema::fromJson.
$schema = Jddf\Schema::fromJson(json_decode($schema));

// This input data is perfect. It satisfies all the schema requirements.
$inputOk = json_decode(<<<'JSON'
    {
        "name": "John Doe",
        "age":  43,
        "phones": [
          "+44 1234567",
          "+44 2345678",
        ],
    }
JSON);

// This input data has problems. "name" is missing, "age" has the wrong type,
// and "phones[1]" has the wrong type.
$inputBad = json_decode(<<<'JSON'
    {
        "name": "John Doe",
        "age":  43,
        "phones": [
          "+44 1234567",
          "+44 2345678",
        ],
    }
JSON);

// To keep things simple, we'll ignore errors here. In this example, errors
// are impossible. The docs explain in detail why an error might arise from
// validation.
$validator = new Jddf\Validator();
$resultOk = $validator->validate($schema, $inputOk);
$resultBad = $validator->validate($schema, $inputBad);

// Outputs an empty array.
var_dump($resultOk);

// [] ["properties", "name"] -- indicates that the root is missing "name"
var_dump($resultBad[0]->instancePath, $resultBad[0]->schemaPath);

// ["age"] ["properties", "age", "type"] -- indicates that "age" has the wrong
// type
var_dump($resultBad[1]->instancePath, $resultBad[1]->schemaPath);

// ["phones", "1"] ["properties", "phones", "elements", "type"] -- indicates
// that "phones[1]" has the wrong type
var_dump($resultBad[2]->instancePath, $resultBad[2]->schemaPath);
```
