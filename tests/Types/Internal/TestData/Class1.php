<?php

declare(strict_types=1);

namespace Tests\Types\Internal\TestData;

class Class1
{
    public function c1m1(): ?Class2
    {
        return new Class2();
    }
}
