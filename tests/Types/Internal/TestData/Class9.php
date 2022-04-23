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
}
