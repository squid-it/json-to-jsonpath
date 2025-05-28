<?php

declare(strict_types=1);

namespace SquidIT\Json;

use UnexpectedValueException;

class JsonPathExpression
{
    public function __construct(
        public readonly string $jsonPath,
        public readonly string $type,
        public readonly mixed $value,
    ) {
        if (($type === 'object' || $type === 'array') && $value !== null) {
            throw new UnexpectedValueException('Could not set JsonPathExpression value, object|array can not have a $value other than NULL');
        }
    }
}
