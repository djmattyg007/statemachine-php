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
use MattyG\StateMachine\Registry;
use MattyG\StateMachine\StateAccessor\StateAccessorInterface;
use MattyG\StateMachine\SupportStrategy\StateMachineSupportStrategyInterface;
use MattyG\StateMachine\Transition;
use MattyG\StateMachine\StateMachine;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use stdClass;

class RegistryTest extends TestCase
{
    private $registry;

    protected function setUp(): void
    {
        $this->registry = new Registry();

        $this->registry->addStateMachine(
            new StateMachine(
                new Definition(['a', 'b'], [new Transition('a_to_b', 'a', 'b')]),
                $this->getMockBuilder(StateAccessorInterface::class)->getMock(),
                $this->getMockBuilder(EventDispatcherInterface::class)->getMock(),
                'statemachine1'
            ),
            $this->createStateMachineSupportStrategy(Subject1::class)
        );
        $this->registry->addStateMachine(
            new StateMachine(
                new Definition(['m', 'n'], [new Transition('m_to_n', 'm', 'n')]),
                $this->getMockBuilder(StateAccessorInterface::class)->getMock(),
                $this->getMockBuilder(EventDispatcherInterface::class)->getMock(),
                'statemachine2'
            ),
            $this->createStateMachineSupportStrategy(Subject2::class)
        );
        $this->registry->addStateMachine(
            new StateMachine(
                new Definition(['x', 'y'], [new Transition('x_to_y', 'x', 'y')]),
                $this->getMockBuilder(StateAccessorInterface::class)->getMock(),
                $this->getMockBuilder(EventDispatcherInterface::class)->getMock(),
                'statemachine3'
            ),
            $this->createStateMachineSupportStrategy(Subject2::class)
        );
    }

    protected function tearDown(): void
    {
        $this->registry = null;
    }

    public function testGetWithSuccess()
    {
        $stateMachine = $this->registry->get(new Subject1());
        $this->assertInstanceOf(StateMachine::class, $stateMachine);
        $this->assertSame('statemachine1', $stateMachine->getName());

        $stateMachine = $this->registry->get(new Subject1(), 'statemachine1');
        $this->assertInstanceOf(StateMachine::class, $stateMachine);
        $this->assertSame('statemachine1', $stateMachine->getName());

        $stateMachine = $this->registry->get(new Subject2(), 'statemachine2');
        $this->assertInstanceOf(StateMachine::class, $stateMachine);
        $this->assertSame('statemachine2', $stateMachine->getName());
    }

    public function testGetWithMultipleMatch()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Too many state machines (statemachine2, statemachine3) match this subject (MattyG\StateMachine\Tests\Subject2); set a different name on each and use the second (name) argument of this method.');
        $this->registry->get(new Subject2());
        //$stateMachine = $this->registry->get(new Subject2());
        //$this->assertInstanceOf(StateMachine::class, $stateMachine);
        //$this->assertSame('statemachine1', $stateMachine->getName());
    }

    public function testGetWithNoMatch()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to find a state machine for class "stdClass".');
        $this->registry->get(new stdClass());
        //$stateMachine = $this->registry->get(new stdClass());
        //$this->assertInstanceOf(StateMachine::class, $stateMachine);
        //$this->assertSame('statemachine1', $stateMachine->getName());
    }

    public function testAllWithOneMatchWithSuccess()
    {
        $stateMachines = $this->registry->all(new Subject1());
        $this->assertIsArray($stateMachines);
        $this->assertCount(1, $stateMachines);
        $this->assertInstanceOf(StateMachine::class, $stateMachines[0]);
        $this->assertSame('statemachine1', $stateMachines[0]->getName());
    }

    public function testAllWithMultipleMatchWithSuccess()
    {
        $stateMachines = $this->registry->all(new Subject2());
        $this->assertIsArray($stateMachines);
        $this->assertCount(2, $stateMachines);
        $this->assertInstanceOf(StateMachine::class, $stateMachines[0]);
        $this->assertInstanceOf(StateMachine::class, $stateMachines[1]);
        $this->assertSame('statemachine2', $stateMachines[0]->getName());
        $this->assertSame('statemachine3', $stateMachines[1]->getName());
    }

    public function testAllWithNoMatch()
    {
        $stateMachines = $this->registry->all(new stdClass());
        $this->assertIsArray($stateMachines);
        $this->assertCount(0, $stateMachines);
    }

    private function createStateMachineSupportStrategy($supportedClassName)
    {
        $strategy = $this->getMockBuilder(StateMachineSupportStrategyInterface::class)->getMock();
        $strategy->expects($this->any())->method('supports')
            ->willReturnCallback(function ($stateMachine, $subject) use ($supportedClassName) {
                return $subject instanceof $supportedClassName;
            });

        return $strategy;
    }
}

class Subject1
{
}
class Subject2
{
}
