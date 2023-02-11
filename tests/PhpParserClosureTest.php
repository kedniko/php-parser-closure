<?php

use Kedniko\PhpParserClosure\PhpParserClosure;

test('simple-closure', function () {
    $phpParserClosure = new PhpParserClosure();
    $closure = function (int $number) {
        return $number * 2;
    };

    $phpParserClosure->parse($closure);
    $nodeClosure = $phpParserClosure->getNode($closure);
    $stringClosure = $phpParserClosure->getCode($nodeClosure);
    expect($stringClosure)->toBe(rn_to_n(<<<TEST
    function (int \$number) {
        return \$number * 2;
    }
    TEST));
});

test('closure-from-closure', function () {
    $phpParserClosure = new PhpParserClosure();

    $getClosure = function () {
        return function (int $number) {
            return $number * 2;
        };
    };

    $closure = $getClosure();
    $phpParserClosure->parse($closure);
    $nodeClosure = $phpParserClosure->getNode($closure);
    $stringClosure = $phpParserClosure->getCode($nodeClosure);
    expect($stringClosure)->toBe(rn_to_n(<<<TEST
    function (int \$number) {
        return \$number * 2;
    }
    TEST));
});

test('closure-from-method', function () {
    $phpParserClosure = new PhpParserClosure();

    $a = new class () {
        public function getClosure()
        {
            return function (int $number) {
                return $number * 2;
            };
        }
    };
    $closure = $a->getClosure();

    $phpParserClosure->parse($closure);
    $nodeClosure = $phpParserClosure->getNode($closure);
    $stringClosure = $phpParserClosure->getCode($nodeClosure);
    expect($stringClosure)->toBe(rn_to_n(<<<TEST
    function (int \$number) {
        return \$number * 2;
    }
    TEST));
});
