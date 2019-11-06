<?php

/*
 * This file was forked from the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 * (c) Matthew Gamble <git@matthewgamble.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MattyG\StateMachine\Tests;

use PHPUnit\Framework\TestCase;
use MattyG\StateMachine\Definition;
use MattyG\StateMachine\Exception\InvalidArgumentException;
use MattyG\StateMachine\Exception\LogicException;
use MattyG\StateMachine\Transition;

class DefinitionTest extends TestCase
{
    public function testAddPlaces()
    {
        $places = range('a', 'e');
        $definition = new Definition($places, [new Transition('a_to_c', 'a', 'c')]);

        $this->assertCount(5, $definition->getPlaces());

        $this->assertEquals('a', $definition->getInitialPlace());
    }

    public function testSetInitialPlace()
    {
        $places = range('a', 'e');
        $definition = new Definition($places, [new Transition('a_to_d', 'a', 'd')], $places[3]);

        $this->assertEquals($places[3], $definition->getInitialPlace());
    }

    public function testSetInitialPlaceAndPlaceIsNotDefined()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Place "d" cannot be the initial place as it does not exist.');
        new Definition(['a', 'b'], [new Transition('a_to_b', 'a', 'b')], 'd');
    }

    public function testAddTransition()
    {
        $places = range('a', 'b');

        $transition = new Transition('name', $places[0], $places[1]);
        $definition = new Definition($places, [$transition]);

        $this->assertCount(1, $definition->getTransitions());
        $this->assertSame($transition, $definition->getTransitions()[0]);
    }

    public function testAddTransitionAndFromPlaceIsNotDefined()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Place "c" referenced in transition "name" does not exist.');
        $places = range('a', 'b');

        new Definition($places, [new Transition('name', 'c', $places[1])]);
    }

    public function testAddTransitionAndToPlaceIsNotDefined()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Place "c" referenced in transition "name" does not exist.');
        $places = range('a', 'b');

        new Definition($places, [new Transition('name', $places[0], 'c')]);
    }

    public function testNoPlaces()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot have a state machine definition with no places.');
        new Definition([], []);
    }

    public function testNoTransitions()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot have a state machine definition with no transitions.');
        new Definition(['a', 'b'], []);
    }
}
