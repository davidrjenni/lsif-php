<?php

declare(strict_types=1);

namespace Tests\Types\TestData;

/** Class2 is a class for testing. */
class Class2
{

    public function c2m1(): void
    {
    }

    public static function c2m2(): Class1
    {
        return new Class1();
    }
}
