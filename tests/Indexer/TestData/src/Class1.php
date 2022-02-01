<?php

declare(strict_types=1);

namespace TestProject;

use TestProjectDep\Foo;

final class Class1
{
    public function foo(): Foo
    {
        return new Foo();
    }
}
