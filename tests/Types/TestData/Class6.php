<?php

declare(strict_types=1);

namespace Tests\Types\TestData;

class Class6
{
    private AbstractClass1 $c6p1;

    public function __construct(AbstractClass1 $c6p1)
    {
        $this->c6p1 = $c6p1;
    }

    public function c6m1(): void
    {
        $this->c6p1->ac1m1()->c3p1->c1m1()::c2m2();
    }
}
