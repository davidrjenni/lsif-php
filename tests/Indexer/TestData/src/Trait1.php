<?php

declare(strict_types=1);

namespace TestProject;

trait Trait1
{
    public function t1m1(): Class1
    {
        return parent::c2m1();
    }
}
