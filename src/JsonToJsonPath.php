<?php

declare(strict_types=1);

namespace SquidIT\Json;

use JsonException;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

class JsonToJsonPath
{
    private const string ROOT_OBJECT = '$';

    /** @var array<mixed, mixed> */
    private array $decodedJson;

    /** @var array<string, JsonPathExpression> */
    private array $pathList = [];

    /**
     * @param array<mixed>|string $json if $json is an array it needs to be decoded using "associative === true"
     *
     * @throws JsonException
     */
    public function __construct(string|array $json)
    {
        if (is_string($json)) {
            $json = \trim($json);

            if (empty($json)) {
                throw new JsonException('Can not decode an empty string to JSON');
            }

            $this->decodedJson = $this->decodeJson($json);
        } else {
            $this->decodedJson = $json;
        }

        $this->parseDecodedJson();
    }

    /**
     * @return array<string, JsonPathExpression>
     */
    public function getPathList(): array
    {
        return $this->pathList;
    }

    /**
     * @throws JsonException
     *
     * @return array<mixed, mixed>
     */
    private function decodeJson(string $json): array
    {
        if (function_exists('simdjson_decode')) {
            /** @var array<mixed>|object $jsonObject */
            $jsonObject = \simdjson_decode($json, true, 512);
        } else {
            /** @var array<mixed>|object $jsonObject */
            $jsonObject = \json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        }

        if (empty($jsonObject)) {
            throw new JsonException('Received empty JSON, unable to create JSONPath');
        }

        // PHP implements a superset of JSON and decodes scalar types, we are not interested in a superset
        if (\is_array($jsonObject) === false) {
            throw new JsonException('Received invalid JSON object, unable to create JSONPath');
        }

        return $jsonObject;
    }

    private function parseDecodedJson(): void
    {
        // Initialize RecursiveIteratorIterator
        $iterator = new RecursiveIteratorIterator(
            new RecursiveArrayIterator($this->decodedJson),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $k => $v) { // Loop through each iterator
            $parentPath   = self::ROOT_OBJECT;
            $previousType = $this->getType($this->decodedJson);

            // add pre-path parts
            if (isset($this->pathList[$parentPath]) === false) {
                $this->addPathToPathList($parentPath, $previousType, null);
            }

            for ($i = 0, $z = $iterator->getDepth(); $i <= $z; $i++) { // Loop and push each path
                $key   = $iterator->getSubIterator($i)->key();
                $value = $iterator->getSubIterator($i)->current();

                if ($previousType === 'object') {
                    /** @var string $key */
                    $format   = \preg_match('/^[a-z_0-9$]+$/i', $key) ? '%s.%s' : '%s["%s"]';
                    $jsonPath = \sprintf($format, $parentPath, $key);
                } else {
                    /** @var int $key */
                    $jsonPath = \sprintf('%s[%s]', $parentPath, $key);
                }

                $parentPath   = $jsonPath;
                $previousType = $this->getType($value);

                if (isset($this->pathList[$jsonPath]) === false) {
                    $value = ($previousType === 'object' || $previousType === 'array') ? null : $value;
                    $this->addPathToPathList($jsonPath, $previousType, $value);
                }
            }
        }
    }

    private function addPathToPathList(string $jsonPath, string $type, mixed $value): void
    {
        $this->pathList[$jsonPath] = new JsonPathExpression($jsonPath, $type, $value);
    }

    private function getType(mixed $var): string
    {
        $type = \gettype($var);

        return match ($type) {
            'array'  => \array_is_list($var) ? 'array' : 'object',
            'double' => 'float',
            default  => $type,
        };
    }
}
