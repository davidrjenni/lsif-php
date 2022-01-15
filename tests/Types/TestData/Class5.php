<?php

declare(strict_types=1);

namespace Tests\Types\TestData;

class Class5 extends AbstractClass1
{

    public function ac1m1(): Class3
    {
        return new Class3(new Class1());
    }
}
