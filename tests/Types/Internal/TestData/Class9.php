<?php

declare(strict_types=1);

namespace Tests\Types\Internal\TestData;

/**
 * @mixin Class1
 */
final class Class9
{
    public function c9m1(): void
    {
        $this->c1m1()->c2m4();
    }

    /** @return Class2[][] */
    private function c9m2(): array
    {
        $cs = [];
        foreach (range(0, 10) as $i) {
            $cs[] = new Class2();
        }
        return [$cs, $cs, $cs];
    }

    public function c9m3(): void
    {
        $c9v1 = $this->c9m2();
        $c9v3 = $c9v1[1][5];
        $c9v3->c2m2()->c1m2();
    }
}
