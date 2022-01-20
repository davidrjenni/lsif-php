<?php

declare(strict_types=1);

namespace Tests\Types\TestData;

trait Trait2
{
    use Trait1;

    protected function t2m1(AbstractClass1 $t2m1p1): ?Class2
    {
        $v1 = $t2m1p1->ac1m1();
        return $this->t1m1($v1)->c1m1();
    }
}
