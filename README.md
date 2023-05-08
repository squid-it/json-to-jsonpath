# JSON To JSONPath
Convert a JSON string into JSONPath

This packages allows you to extract a list of JSONPath expressions

### Example
```php
<?php

declare(strict_types=1);

// Make sure composer autoload has been loaded
use SquidIT\Json\JsonToJsonPath;

$jsonString = '{
  "store": {
    "book": [
      {
        "category": "reference",
        "author": "Nigel Rees",
        "title": "Sayings of the Century",
        "price": 8.95
      },
      {
        "category": "fiction",
        "author": "Evelyn Waugh",
        "title": "Sword of Honour",
        "sub-title": "Sword of Honour",
        "price": 12.99
      }
    ],
    "bicycle": {
      "manufacturer name": "honda",
      "color": "red",
      "price": 19.95
    }
  }
}';

$jsonToJsonPath   = new JsonToJsonPath($jsonString);
$jsonPathList     = $jsonToJsonPath->getPathList();

echo 'Pathlist: '.PHP_EOL;
print_r(array_keys($jsonPathList));

echo 'JsonPathExpression: '.PHP_EOL;
var_dump($jsonPathList[array_key_last($jsonPathList)]);
```
Output:
```php
Pathlist: 
Array
(
    [0] => $
    [1] => $.store
    [2] => $.store.book
    [3] => $.store.book[0]
    [4] => $.store.book[0].category
    [5] => $.store.book[0].author
    [6] => $.store.book[0].title
    [7] => $.store.book[0].price
    [8] => $.store.book[1]
    [9] => $.store.book[1].category
    [10] => $.store.book[1].author
    [11] => $.store.book[1].title
    [12] => $.store.book[1]["sub-title"]
    [13] => $.store.book[1].price
    [14] => $.store.bicycle
    [15] => $.store.bicycle["manufacturer name"]
    [16] => $.store.bicycle.color
    [17] => $.store.bicycle.price
)

JsonPathExpression: 
object(SquidIT\Json\JsonPathExpression)#473 (3) {
  ["jsonPath"]=>
  string(21) "$.store.bicycle.price"
  ["type"]=>
  string(5) "float"
  ["value"]=>
  float(19.95)
}
```

### Note:
To keep memory usage low the `JsonPathExpression::class` object does not include a value for object or array types
