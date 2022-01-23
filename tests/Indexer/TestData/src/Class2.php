<?php

declare(strict_types=1);

namespace TestProject;

final class Class1
{
    private Class1 $c1p1;

    public function __construct(Class1 $c1m1a1)
    {
        $this->c1p1 = $c1m1a1;
    }

    protected function c1m1(): Class1
    {
        return $this->c1p1;
    }
}
