<?php

declare(strict_types=1);

namespace Tests\Types\TestData;

class Class8
{

    /** Class8 constants */
    public const
        C8C1 = 23,
        C8C2 = 42;

    /** @param  Class7  $c8p1  is a test property */
    public function __construct(private Class7 $c8p1)
    {
    }

    private function c8m1(): void
    {
        $this->c8p1->ac1m1()->c3m1();
    }

    public function c8m2(): void
    {
        $c8v1 = $this->c8p1->c7m1()->ac1m1();
        $c8v2 = $c8v1->c3p1;

        $c8v3 = fn (Class1 $c8v3p1) => $c8v3p1->c1m1();

        $c8v4 = function (Class1 $x) use ($c8v3): string {
            $c8v4v1 = $x->c1m1();
            $c8v3($c8v4v1::c2m2());

            $c8v4v2 = new class('test')
            {

                private string $c8v4v2p1;

                public function __construct(string $c8v4v2p1)
                {
                    $this->c8v4v2p1 = $c8v4v2p1;
                }

                public function c8v4v2m1(): string
                {
                    return $this->c8v4v2p1;
                }
            };

            return $c8v4v2->c8v4v2m1();
        };

        $c8v2->c1m1()->c2m1();
    }

    public function c8m3(): void
    {
        $this->c8p1->c7m4()->c2m1();;
    }
}
