<?php

declare(strict_types=1);

namespace SquidIT\Tests\Json;

use PHPUnit\Framework\TestCase;
use SquidIT\Json\JsonPathExpression;
use UnexpectedValueException;

class JsonPathExpressionTest extends TestCase
{
    public function testThrowsUnexpectedValueExceptionWhenArrayTypeHasAValue(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Could not set JsonPathExpression value, object|array can not have a $value other than NULL');
        new JsonPathExpression('$.path', 'array', [1]);
    }

    public function testThrowsUnexpectedValueExceptionWhenObjectTypeHasAValue(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Could not set JsonPathExpression value, object|array can not have a $value other than NULL');
        new JsonPathExpression('$.path', 'object', (object) ['test' => 'value']);
    }
}
