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
}
