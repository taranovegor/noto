<?php

namespace App\Tests\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

class ComponentIsolationRule
{
    public function test_components_must_not_depend_on_app(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App\Component'))
            ->shouldNot()->dependOn()
            ->classes(
                Selector::AllOf(
                    Selector::inNamespace('App'),
                    Selector::NOT(Selector::inNamespace('App\Component'))
                )
            )
            ->because('Component must be isolated to be extractable into a package');
    }
}
