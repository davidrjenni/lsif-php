<?php

declare(strict_types=1);

namespace Tests\Types\TestData;

trait Trait1
{

    protected Class1|Class3|null $t1p1 = null;

    public function t1m1(Class3 $t1m1p1): Class1
    {
        return $t1m1p1->c3p1();
    }
}
