<?php

declare(strict_types=1);

namespace Tests\Types\Internal\TestData;

/** Class2 is a class for testing. */
class Class2
{
    public function c2m1(): void
    {
        $this->c2m3([]);
    }

    public static function c2m2(): Class1
    {
        return new Class1();
    }

    /** @param string[] $c2m3p1 */
    private function c2m3(array $c2m3p1): void
    {
        foreach ($c2m3p1 as $k => $v) {
            echo ("$k: $v\n");
        }

        foreach ($c2m3p1 as $w) {
            echo ("$w\n");
        }

        for ($i = 0; $i < count($c2m3p1); $i++) {
            echo ($c2m3p1[$i] . "\n");
        }

        [$r, $g, $b] = [138, 146, 204];
        echo($r + $g + $b);

        [[$x, $y, $z]] = [['a', 'b', 'c']];
        echo("$x, $y, $z");

        $this->c2m4([['a', 'b'], ['c', 'd']]);
    }

    /** @param string[][] $c2m4p1 */
    private function c2m4(array $c2m4p1): void
    {
        foreach ($c2m4p1 as $i => [$j, $k]) {
            echo("$i, $j, $k\n");
        }
    }
}
