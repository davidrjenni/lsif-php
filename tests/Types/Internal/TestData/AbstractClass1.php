<?php

declare(strict_types=1);

namespace Tests\Types\Internal\TestData;

abstract class AbstractClass1
{
    abstract public function ac1m1(): Class3;

    abstract protected function ac1m2(): Class3;
}
