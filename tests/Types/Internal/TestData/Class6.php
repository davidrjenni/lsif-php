<?php

declare(strict_types=1);

namespace Tests\Types\Internal\TestData;

class Class6
{
    private const c6p1 = -1; // same name as property

    private AbstractClass1 $c6p1;

    public function __construct(AbstractClass1 $c6p1)
    {
        $this->c6p1 = $c6p1;
    }

    public function c6m1(): void
    {
        $this->c6m2()->c6p1->ac1m1()->c3p1->c1m1()::c2m2();
    }

    /** @return self */
    public function c6m2()
    {
        $this->c6m3()->ac1m1();
        return $this;
    }

    /** @return \Tests\Types\Internal\TestData\AbstractClass1 */
    public function c6m3()
    {
        return $this->c6p1;
    }
}
