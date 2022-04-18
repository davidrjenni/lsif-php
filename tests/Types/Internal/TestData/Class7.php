<?php

declare(strict_types=1);

namespace Tests\Types\Internal\TestData;

class Class7 extends Class5
{
    private Interface2 $c7p1;

    public function __construct(Interface2 $c7p1)
    {
        $this->c7p1 = $c7p1;
    }

    public function c7m1(): parent
    {
        return new Class5();
    }

    public function c7m2(): ?Class2
    {
        return $this->c7p1->i1m1()->c1m1();
    }

    public function c7m3(): Class1
    {
        return parent::ac1m1()->c3p1;
    }

    /** @return Class2 */
    public function c7m4()
    {
        return new Class2();
    }
}
