# PHP Parser Closure (work in progress)

## Quick Start

```php
$parser = new \Kedniko\PhpParserClosure\PhpParserClosure();

$closure = function (int $number) {
  return $number * 2;
};

$parser->parse($closure);
$code = $parser->getCode();

echo $code;
// "function (int $number) {
//   return $number * 2;
// }"
```

Under the hood this library uses [nikic/php-parser](https://github.com/nikic/PHP-Parser).