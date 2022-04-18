<?php

declare(strict_types=1);

namespace Tests\Types\TestData;

class Class5 extends AbstractClass1
{
    use Trait2;

    public function ac1m1(): Class3
    {
        $v1 = new Class3(new Class1());
        if ($this->t1p1 !== null) {
            $this->t1m1($v1);
        }
        $this->t2m1($this);
        return $v1;
    }

    final protected function ac1m2(): Class3
    {
        return $this->ac1m1();
    }
}
