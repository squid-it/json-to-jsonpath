<?php

declare(strict_types=1);

namespace Tests\SquidIT\Json;

use JsonException;
use PHPUnit\Framework\TestCase;
use SquidIT\Json\JsonPathExpression;
use SquidIT\Json\JsonToJsonPath;

class JsonToJsonPathTest extends TestCase
{
    private const EXAMPLE_JSON = <<<END
                { "store": {
                    "book": [ 
                      { "category": "reference",
                        "author": "Nigel Rees",
                        "title": "Sayings of the Century",
                        "price": 8.95
                      },
                      { "category": "fiction",
                        "author": "Evelyn Waugh",
                        "title": "Sword of Honour",
                        "price": 12.99
                      },
                      { "category": "fiction",
                        "author": "Herman Melville",
                        "title": "Moby Dick",
                        "isbn": "0-553-21311-3",
                        "price": 8.99
                      },
                      { "category": "fiction",
                        "author": "J. R. R. Tolkien",
                        "title": "The Lord of the Rings",
                        "isbn": "0-395-19395-8",
                        "price": 22.99
                      }
                    ],
                    "bicycle": {
                      "color": "red",
                      "price": 19.95
                    }
                  }
                }
        END;

    private const EXAMPLE_JSON_RESULT = [
        '$'                        => 'object',
        '$.store'                  => 'object',
        '$.store.book'             => 'array',
        '$.store.book[0]'          => 'object',
        '$.store.book[0].category' => 'string',
        '$.store.book[0].author'   => 'string',
        '$.store.book[0].title'    => 'string',
        '$.store.book[0].price'    => 'float',
        '$.store.book[1]'          => 'object',
        '$.store.book[1].category' => 'string',
        '$.store.book[1].author'   => 'string',
        '$.store.book[1].title'    => 'string',
        '$.store.book[1].price'    => 'float',
        '$.store.book[2]'          => 'object',
        '$.store.book[2].category' => 'string',
        '$.store.book[2].author'   => 'string',
        '$.store.book[2].title'    => 'string',
        '$.store.book[2].isbn'     => 'string',
        '$.store.book[2].price'    => 'float',
        '$.store.book[3]'          => 'object',
        '$.store.book[3].category' => 'string',
        '$.store.book[3].author'   => 'string',
        '$.store.book[3].title'    => 'string',
        '$.store.book[3].isbn'     => 'string',
        '$.store.book[3].price'    => 'float',
        '$.store.bicycle'          => 'object',
        '$.store.bicycle.color'    => 'string',
        '$.store.bicycle.price'    => 'float',
    ];

    public function testGetPathListReturnsProperJsonPathEntries(): void
    {
        $jsonToJsonPath   = new JsonToJsonPath(self::EXAMPLE_JSON);
        $jsonPathList     = $jsonToJsonPath->getPathList();
        $jsonPathListKeys = array_keys($jsonPathList);

        self::assertContainsOnlyInstancesOf(JsonPathExpression::class, $jsonPathList);
        self::assertEquals($jsonPathListKeys[0], $jsonPathList[$jsonPathListKeys[0]]->jsonPath);
        self::assertEquals('object', $jsonPathList[$jsonPathListKeys[0]]->type);
        self::assertEquals(array_keys(self::EXAMPLE_JSON_RESULT), $jsonPathListKeys);

        foreach (self::EXAMPLE_JSON_RESULT as $jsonPath => $jsonPathType) {
            $errorMsg = sprintf('Could not validate JSONPath: %s, is of type: %s, found: %s', $jsonPath, $jsonPathType, $jsonPathList[$jsonPath]->type);
            self::assertEquals($jsonPathType, $jsonPathList[$jsonPath]->type, $errorMsg);
        }
    }

    /**
     * @throws JsonException
     */
    public function testJsonArrayOfArraysReturnsProperResult(): void
    {
        $exampleData = '[
            [      1,      "Johnson, Smith, and Jones Co.",      345.33    ],
            [      99,      "Acme Food Inc.",      2993.55    ]
        ]';

        $result = [
            '$',
            '$[0]',
            '$[0][0]',
            '$[0][1]',
            '$[0][2]',
            '$[1]',
            '$[1][0]',
            '$[1][1]',
            '$[1][2]',
        ];

        $jsonToJsonPath   = new JsonToJsonPath($exampleData);
        $jsonPathList     = $jsonToJsonPath->getPathList();
        $jsonPathListKeys = array_keys($jsonPathList);

        self::assertEquals('array', $jsonPathList[$jsonPathListKeys[0]]->type);
        self::assertEquals($result, $jsonPathListKeys);
    }

    /**
     * Accessing elements within an object that contain characters not permitted under PHP's naming convention
     * (e.g. the hyphen) can be accomplished by encapsulating the element name within braces and the apostrophe.
     *
     * $json = '{"foo-bar": 12345}';
     *
     * $obj = json_decode($json);
     * print $obj->{'foo-bar'}; // 12345
     *
     * @throws JsonException
     */
    public function testPhpNamingConventionDoesNotImpactAccessingElements(): void
    {
        $exampleData = '{
            "foo-bar": 12345,
            "some \"random\" strings": {
                "firstString": "string1",
                "first_String": "string1"
            },
            "$tigersAndBears": "bear"
        }';

        $result = [
            '$',
            '$["foo-bar"]',
            '$["some "random" strings"]',
            '$["some "random" strings"].firstString',
            '$["some "random" strings"].first_String',
            '$.$tigersAndBears',
        ];

        $jsonToJsonPath   = new JsonToJsonPath($exampleData);
        $jsonPathList     = $jsonToJsonPath->getPathList();
        $jsonPathListKeys = array_keys($jsonPathList);

        self::assertEquals($result, $jsonPathListKeys);
    }

    public function testEmptyJsonStringThrowsJsonException(): void
    {
        $this->expectException(JsonException::class);
        $this->expectExceptionMessage('Can not decode an empty string to JSON');
        new JsonToJsonPath(' ');
    }

    public function testEmptyJsonArrayStringThrowsJsonException(): void
    {
        $this->expectException(JsonException::class);
        $this->expectExceptionMessage('Received empty JSON, unable to create JSONPath');
        new JsonToJsonPath('[]');
    }

    public function testEmptyJsonObjectStringThrowsJsonException(): void
    {
        $this->expectException(JsonException::class);
        $this->expectExceptionMessage('Received empty JSON object, unable to create JSONPath');
        new JsonToJsonPath('{}');
    }

    /**
     * PHP implements a superset of JSON - it will also encode and decode scalar types and NULL.
     * The JSON standard only supports these values when they are nested inside an array or an object.
     */
    public function testPhpJsonSupersetStringThrowsJsonException(): void
    {
        $this->expectException(JsonException::class);
        $this->expectExceptionMessage('Received invalid JSON object, unable to create JSONPath');
        new JsonToJsonPath('1');
    }
}
