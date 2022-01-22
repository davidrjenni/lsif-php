<?php

declare(strict_types=1);

namespace Tests\Types\TestData;

class Class4
{

    public function c4m1(): self
    {
        return self::c4m2();
    }

    public static function c4m2(): self
    {
        return new Class4();
    }

    private function c4m3(): Class1
    {
        self::c4m2()->c4m4()->c3m1();

        return self::c4m2()->c4m4()->c3p1;
    }

    private function c4m4(): Class3
    {
        return new Class3($this->c4m3());
    }
}
