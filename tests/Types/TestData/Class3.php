<?php

declare(strict_types=1);

namespace Tests\Types\TestData;

class Class3
{

    public Class1 $c3p1;

    public function __construct(Class1 $p1)
    {
        $this->c3p1 = $p1;
    }

    public function c3m1(): void
    {
        $this->c3p1->c1m1()->c2m1();
    }
}
