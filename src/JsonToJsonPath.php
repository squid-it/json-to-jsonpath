<?php

declare(strict_types=1);

namespace SquidIT\Json;

use JsonException;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

class JsonToJsonPath
{
    private const ROOT_OBJECT = '$';

    /**
     * @var array<mixed, mixed>|object
     */
    private array|object $decodedJson;

    /** @var array<string, JsonPathExpression> */
    private array $pathList = [];

    /**
     * @throws JsonException
     */
    public function __construct(string $json)
    {
        $json = trim($json);

        if (empty($json)) {
            throw new JsonException('Can not decode an empty string to JSON');
        }

        $this->decodedJson = $this->decodeJson($json);
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
     * @return array<mixed, mixed>|object
     */
    private function decodeJson(string $json): object|array
    {
        $jsonObject = json_decode($json, false, 512, JSON_THROW_ON_ERROR);

        if (empty($jsonObject)) {
            throw new JsonException('Received empty JSON, unable to create JSONPath');
        }

        if (is_object($jsonObject) && empty(get_object_vars($jsonObject))) {
            throw new JsonException('Received empty JSON object, unable to create JSONPath');
        }

        // PHP implements a superset of JSON and decodes scalar types, we are not interested in a superset
        if (is_object($jsonObject) === false && is_array($jsonObject) === false) {
            throw new JsonException('Received invalid JSON object, unable to create JSONPath');
        }

        return $jsonObject;
    }

    /**
     * @throws JsonException
     */
    private function parseDecodedJson(): void
    {
        // Initialize RecursiveIteratorIterator
        $iterator = new RecursiveIteratorIterator(
            new RecursiveArrayIterator($this->decodedJson), /* @phpstan-ignore-line */
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $k => $v) { // Loop through each iterator
            $parentPath   = self::ROOT_OBJECT;
            $previousType = $this->getType($this->decodedJson);

            // add pre path parts
            if (isset($this->pathList[$parentPath]) === false) {
                $this->addPathToPathList($parentPath, $previousType, null);
            }

            for ($i = 0, $z = $iterator->getDepth(); $i <= $z; $i++) { // Loop and push each path
                $key   = $iterator->getSubIterator($i)->key();
                $value = $iterator->getSubIterator($i)->current();

                if ($previousType === 'object') {
                    /** @var string $key */
                    $format   = preg_match('/^[a-z_0-9$]+$/i', $key) ? '%s.%s' : '%s["%s"]';
                    $jsonPath = sprintf($format, $parentPath, $key);
                } else {
                    /** @var int $key */
                    $jsonPath = sprintf('%s[%s]', $parentPath, $key);
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

    /**
     * @throws JsonException
     */
    private function addPathToPathList(string $jsonPath, string $type, mixed $value): void
    {
        $this->pathList[$jsonPath] = new JsonPathExpression($jsonPath, $type, $value);
    }

    private function getType(mixed $var): string
    {
        $type = gettype($var);

        // make type result non PHP specific
        if ($type === 'double') {
            $type = 'float';
        }

        return $type;
    }
}
