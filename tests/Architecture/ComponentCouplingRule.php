<?php

namespace App\Tests\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

/**
 * Enforces acyclic dependency graph between components:
 *
 *   WebSocket  ←  Centrifugo  ←  Broadcaster
 *       ↑                           ↑
 *       └───────────────────────────┘
 *
 * Allowed direction: higher-level component → lower-level component.
 * Forbidden: reverse direction (would create a cycle).
 */
class ComponentCouplingRule
{
    public function test_websocket_must_not_depend_on_centrifugo(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App\Component\WebSocket'))
            ->shouldNot()->dependOn()
            ->classes(Selector::inNamespace('App\Component\Centrifugo'))
            ->because('Centrifugo depends on WebSocket transport primitives; reverse would create a cycle');
    }

    public function test_websocket_must_not_depend_on_broadcaster(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App\Component\WebSocket'))
            ->shouldNot()->dependOn()
            ->classes(Selector::inNamespace('App\Component\Broadcaster'))
            ->because('Broadcaster depends on WebSocket; reverse would create a cycle');
    }

    public function test_centrifugo_must_not_depend_on_broadcaster(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App\Component\Centrifugo'))
            ->shouldNot()->dependOn()
            ->classes(Selector::inNamespace('App\Component\Broadcaster'))
            ->because('Broadcaster depends on Centrifugo channel builder; reverse would create a cycle');
    }
}
