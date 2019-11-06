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
use Symfony\Component\EventDispatcher\EventDispatcher;
use MattyG\StateMachine\Event\GuardEvent;
use MattyG\StateMachine\StateMachine;
use MattyG\StateMachine\TransitionBlocker;

class StateMachineTest extends TestCase
{
    use StateMachineBuilderTrait;

    public function testCan()
    {
        $definition = $this->createComplexStateMachineDefinition2();

        $net = new StateMachine($definition);
        $subject = new Subject();

        // If you are in place "a" you should be able to apply "t1"
        $subject->setState('a');
        $this->assertTrue($net->can($subject, 't1'));
        $subject->setState('d');
        $this->assertTrue($net->can($subject, 't1'));

        $subject->setState('b');
        $this->assertFalse($net->can($subject, 't1'));
    }

    public function testCanWithMultipleTransition()
    {
        $definition = $this->createComplexStateMachineDefinition2();

        $net = new StateMachine($definition);
        $subject = new Subject();

        // If you are in place "b" you should be able to apply "t1" and "t2"
        $subject->setState('b');
        $this->assertTrue($net->can($subject, 't2'));
        $this->assertTrue($net->can($subject, 't3'));
    }

    public function testBuildTransitionBlockerList()
    {
        $definition = $this->createComplexStateMachineDefinition2();

        $net = new StateMachine($definition);
        $subject = new Subject();

        $subject->setState('a');
        $this->assertTrue($net->buildTransitionBlockerList($subject, 't1')->isEmpty());
        $subject->setState('d');
        $this->assertTrue($net->buildTransitionBlockerList($subject, 't1')->isEmpty());

        $subject->setState('b');
        $this->assertFalse($net->buildTransitionBlockerList($subject, 't1')->isEmpty());
    }

    public function testBuildTransitionBlockerListWithMultipleTransitions()
    {
        $definition = $this->createComplexStateMachineDefinition2();

        $net = new StateMachine($definition);
        $subject = new Subject();

        $subject->setState('b');
        $this->assertTrue($net->buildTransitionBlockerList($subject, 't2')->isEmpty());
        $this->assertTrue($net->buildTransitionBlockerList($subject, 't3')->isEmpty());
    }

    public function testBuildTransitionBlockerListReturnsExpectedReasonOnBranchMerge()
    {
        $definition = $this->createComplexStateMachineDefinition2();

        $dispatcher = new EventDispatcher();
        $net = new StateMachine($definition, null, $dispatcher);

        $dispatcher->addListener('statemachine.guard', function (GuardEvent $event) {
            $event->addTransitionBlocker(new TransitionBlocker(sprintf('Transition blocker of place %s', $event->getPreviousState()), 'blocker'));
        });

        $subject = new Subject();

        // There may be multiple transitions with the same name. Make sure that transitions
        // that are not enabled for the current state are evaluated.
        // see https://github.com/symfony/symfony/issues/28432

        // Test if when you are in place "a" trying transition "t1" then returned
        // blocker list contains guard blocker instead blockedByState
        $subject->setState('a');
        $transitionBlockerList = $net->buildTransitionBlockerList($subject, 't1');
        $this->assertCount(1, $transitionBlockerList);
        $blockers = iterator_to_array($transitionBlockerList);

        $this->assertSame('Transition blocker of place a', $blockers[0]->getMessage());
        $this->assertSame('blocker', $blockers[0]->getCode());

        // Test if when you are in place "d" trying transition "t1" then
        // returned blocker list contains guard blocker instead blockedByState
        $subject->setState('d');
        $transitionBlockerList = $net->buildTransitionBlockerList($subject, 't1');
        $this->assertCount(1, $transitionBlockerList);
        $blockers = iterator_to_array($transitionBlockerList);

        $this->assertSame('Transition blocker of place d', $blockers[0]->getMessage());
        $this->assertSame('blocker', $blockers[0]->getCode());
    }
}
