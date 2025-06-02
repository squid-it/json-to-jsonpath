<?php

declare(strict_types=1);

namespace SquidIT\Tests\Json\Benchmark;

use PhpBench\Attributes as Bench;
use SquidIT\Json\JsonToJsonPath;

class JsonToJsonPathBench
{
    public const string EXAMPLE_JSON = <<<END
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

    #[Bench\Iterations(5), Bench\Revs(1000), Bench\Warmup(2)]
    public function benchJsonToJsonPath(): void
    {
        new JsonToJsonPath(self::EXAMPLE_JSON);
    }
}
