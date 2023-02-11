# PHP Parser Closure (work in progress)

## Quick Start

```php
$phpParserClosure = new PhpParserClosure();

$closure = function (int $number) {
  return $number * 2;
};

$node = $phpParserClosure->getNode($closure);
$code = $node->getCode($nodeClosure);

echo $code;
// "function (int $number) {
//   return $number * 2;
// }"
```

Under the hood this library uses [nikic/php-parser](https://github.com/nikic/PHP-Parser).