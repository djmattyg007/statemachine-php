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
use MattyG\StateMachine\Definition;
use MattyG\StateMachine\Event\Event;
use MattyG\StateMachine\Event\GuardEvent;
use MattyG\StateMachine\Event\TransitionEvent;
use MattyG\StateMachine\Exception\LogicException;
use MattyG\StateMachine\Exception\NotEnabledTransitionException;
use MattyG\StateMachine\Exception\UndefinedTransitionException;
use MattyG\StateMachine\StateAccessor\MethodStateAccessor;
use MattyG\StateMachine\StateAccessor\StateAccessorInterface;
use MattyG\StateMachine\Transition;
use MattyG\StateMachine\TransitionBlocker;
use MattyG\StateMachine\TransitionGuardManager;
use MattyG\StateMachine\StateMachine;

class WorkflowTest extends TestCase
{
    use StateMachineBuilderTrait;

    public function testGetStateWithImpossiblePlace()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('State "nope" is not valid for state machine "unnamed".');
        $subject = new Subject('nope');
        $stateMachine = new StateMachine(new Definition(['a', 'b'], [new Transition('a_to_b', 'a', 'b')]));

        $stateMachine->getState($subject);
    }

    public function testGetStateWithExistingState()
    {
        $definition = $this->createComplexStateMachineDefinition1();
        $subject = new Subject('b');
        $stateMachine = new StateMachine($definition);

        $state = $stateMachine->getState($subject);

        $this->assertSame('b', $state);
    }

    public function testCanWithUnexistingTransition()
    {
        $definition = $this->createComplexStateMachineDefinition1();
        $subject = new Subject('a');
        $stateMachine = new StateMachine($definition);

        $this->assertFalse($stateMachine->can($subject, 'foobar'));
    }

    public function testCan()
    {
        $definition = $this->createComplexStateMachineDefinition1();
        $subject = new Subject('a');
        $stateMachine = new StateMachine($definition);

        $this->assertTrue($stateMachine->can($subject, 't1-1'));
        $this->assertTrue($stateMachine->can($subject, 't1-2'));
        $this->assertFalse($stateMachine->can($subject, 't2'));

        $subject->setState('b');
        $this->assertFalse($stateMachine->can($subject, 't1-1'));
        $this->assertFalse($stateMachine->can($subject, 't1-2'));
        $this->assertTrue($stateMachine->can($subject, 't2'));

        $subject->setState('c');
        $this->assertFalse($stateMachine->can($subject, 't1-1'));
        $this->assertFalse($stateMachine->can($subject, 't1-2'));
        $this->assertTrue($stateMachine->can($subject, 't2'));

        $subject->setState('f');
        $this->assertFalse($stateMachine->can($subject, 't5'));
        $this->assertTrue($stateMachine->can($subject, 't6'));
    }

    public function testCanWithGuard()
    {
        $definition = $this->createComplexStateMachineDefinition1();
        $subject = new Subject('a');
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener('statemachine.guard.statemachine_name.t1-1', function (GuardEvent $event) {
            $event->setBlocked(true);
        });
        $stateMachine = new StateMachine($definition, null, $eventDispatcher, 'statemachine_name');

        $this->assertFalse($stateMachine->can($subject, 't1-1'));
    }

    public function testCanDoesNotTriggerGuardEventsForNotEnabledTransitions()
    {
        $definition = $this->createComplexStateMachineDefinition1();
        $subject = new Subject('a');

        $dispatchedEvents = [];
        $eventDispatcher = new EventDispatcher();

        $stateMachine = new StateMachine($definition, null, $eventDispatcher, 'statemachine_name');
        $stateMachine->apply($subject, 't1-1');
        $stateMachine->apply($subject, 't2');

        $eventDispatcher->addListener('statemachine.guard.statemachine_name.t3', function () use (&$dispatchedEvents) {
            $dispatchedEvents[] = 'guard.statemachine_name.t3';
        });
        $eventDispatcher->addListener('statemachine.guard.statemachine_name.t4', function () use (&$dispatchedEvents) {
            $dispatchedEvents[] = 'guard.statemachine_name.t4';
        });

        $stateMachine->can($subject, 't3');

        $this->assertSame(['guard.statemachine_name.t3'], $dispatchedEvents);
    }

    public function testCanWithSameNameTransition()
    {
        $definition = $this->createStateMachineWithSameNameTransition();
        $stateMachine = new StateMachine($definition);

        $subject = new Subject('a');
        $this->assertTrue($stateMachine->can($subject, 'a_to_b'));
        $this->assertTrue($stateMachine->can($subject, 'a_to_c'));
        $this->assertFalse($stateMachine->can($subject, 'b_to_c'));
        $this->assertFalse($stateMachine->can($subject, 'to_a'));

        $subject->setState('b');
        $this->assertFalse($stateMachine->can($subject, 'a_to_b'));
        $this->assertFalse($stateMachine->can($subject, 'a_to_c'));
        $this->assertTrue($stateMachine->can($subject, 'b_to_c'));
        $this->assertTrue($stateMachine->can($subject, 'to_a'));

        $subject->setState('c');
        $this->assertFalse($stateMachine->can($subject, 'a_to_b'));
        $this->assertFalse($stateMachine->can($subject, 'a_to_c'));
        $this->assertFalse($stateMachine->can($subject, 'b_to_c'));
        $this->assertTrue($stateMachine->can($subject, 'to_a'));
    }

    public function testBuildTransitionBlockerListReturnsUndefinedTransition()
    {
        $this->expectException(UndefinedTransitionException::class);
        $this->expectExceptionMessage('Transition "404 Not Found" is not defined for state machine "unnamed".');
        $definition = $this->createSimpleStateMachineDefinition();
        $subject = new Subject('a');
        $stateMachine = new StateMachine($definition);

        $stateMachine->buildTransitionBlockerList($subject, '404 Not Found');
    }

    public function testBuildTransitionBlockerList()
    {
        $definition = $this->createComplexStateMachineDefinition1();
        $subject = new Subject('a');
        $stateMachine = new StateMachine($definition);

        $this->assertTrue($stateMachine->buildTransitionBlockerList($subject, 't1-1')->isEmpty());
        $this->assertTrue($stateMachine->buildTransitionBlockerList($subject, 't1-2')->isEmpty());
        $this->assertFalse($stateMachine->buildTransitionBlockerList($subject, 't2')->isEmpty());
        $this->assertFalse($stateMachine->buildTransitionBlockerList($subject, 't3')->isEmpty());

        $subject->setState('b');
        $this->assertFalse($stateMachine->buildTransitionBlockerList($subject, 't1-1')->isEmpty());
        $this->assertFalse($stateMachine->buildTransitionBlockerList($subject, 't1-2')->isEmpty());
        $this->assertTrue($stateMachine->buildTransitionBlockerList($subject, 't2')->isEmpty());
        $this->assertFalse($stateMachine->buildTransitionBlockerList($subject, 't3')->isEmpty());

        $subject->setState('c');
        $this->assertFalse($stateMachine->buildTransitionBlockerList($subject, 't1-1')->isEmpty());
        $this->assertFalse($stateMachine->buildTransitionBlockerList($subject, 't1-2')->isEmpty());
        $this->assertTrue($stateMachine->buildTransitionBlockerList($subject, 't2')->isEmpty());
        $this->assertFalse($stateMachine->buildTransitionBlockerList($subject, 't3')->isEmpty());

        $subject->setState('f');
        $this->assertFalse($stateMachine->buildTransitionBlockerList($subject, 't5')->isEmpty());
        $this->assertTrue($stateMachine->buildTransitionBlockerList($subject, 't6')->isEmpty());
    }

    public function testBuildTransitionBlockerListReturnsReasonsProvidedByState()
    {
        $definition = $this->createComplexStateMachineDefinition1();
        $subject = new Subject('a');
        $stateMachine = new StateMachine($definition);

        $transitionBlockerList = $stateMachine->buildTransitionBlockerList($subject, 't2');
        $this->assertCount(1, $transitionBlockerList);
        $blockers = iterator_to_array($transitionBlockerList);
        $this->assertSame('The state does not enable the transition.', $blockers[0]->getMessage());
        $this->assertSame('29beefc4-6b3e-4726-0d17-f38bd6d16e34', $blockers[0]->getCode());
    }

    public function testBuildTransitionBlockerListReturnsReasonsProvidedByAvailabilityGuard()
    {
        $availabilityGuard = function (): bool {
            return false;
        };

        $places = ['a', 'b'];
        $transitionGuardManager = new TransitionGuardManager([$availabilityGuard], [], []);
        $transition = new Transition('a_to_b', 'a', 'b', $transitionGuardManager);
        $definition = new Definition($places, [$transition]);

        $subject = new Subject('a');
        $stateMachine = new StateMachine($definition);

        $transitionBlockerList = $stateMachine->buildTransitionBlockerList($subject, 'a_to_b');
        $this->assertCount(1, $transitionBlockerList);
        $blockers = iterator_to_array($transitionBlockerList);
        $this->assertSame('A transition availability guard blocks the transition.', $blockers[0]->getMessage());
        $this->assertSame('1cdf608a-32df-40cb-b167-c555cee491ad', $blockers[0]->getCode());
    }

    public function testBuildTransitionBlockerListReturnsReasonsProvidedInGuards()
    {
        $definition = $this->createSimpleStateMachineDefinition();
        $subject = new Subject('a');
        $dispatcher = new EventDispatcher();
        $stateMachine = new StateMachine($definition, null, $dispatcher);

        $dispatcher->addListener('statemachine.guard', function (GuardEvent $event) {
            $event->addTransitionBlocker(new TransitionBlocker('Transition blocker 1', 'blocker_1'));
            $event->addTransitionBlocker(new TransitionBlocker('Transition blocker 2', 'blocker_2'));
        });
        $dispatcher->addListener('statemachine.guard', function (GuardEvent $event) {
            $event->addTransitionBlocker(new TransitionBlocker('Transition blocker 3', 'blocker_3'));
        });
        $dispatcher->addListener('statemachine.guard', function (GuardEvent $event) {
            $event->setBlocked(true);
        });

        $transitionBlockerList = $stateMachine->buildTransitionBlockerList($subject, 't1');
        $this->assertCount(4, $transitionBlockerList);
        $blockers = iterator_to_array($transitionBlockerList);
        $this->assertSame('Transition blocker 1', $blockers[0]->getMessage());
        $this->assertSame('blocker_1', $blockers[0]->getCode());
        $this->assertSame('Transition blocker 2', $blockers[1]->getMessage());
        $this->assertSame('blocker_2', $blockers[1]->getCode());
        $this->assertSame('Transition blocker 3', $blockers[2]->getMessage());
        $this->assertSame('blocker_3', $blockers[2]->getCode());
        $this->assertSame('Unknown reason.', $blockers[3]->getMessage());
        $this->assertSame('e8b5bbb9-5913-4b98-bfa6-65dbd228a82a', $blockers[3]->getCode());
    }

    public function testApplyWithNotExisingTransition()
    {
        $this->expectException(UndefinedTransitionException::class);
        $this->expectExceptionMessage('Transition "404 Not Found" is not defined for state machine "unnamed".');
        $definition = $this->createComplexStateMachineDefinition1();
        $subject = new Subject('a');
        $stateMachine = new StateMachine($definition);

        $stateMachine->apply($subject, '404 Not Found');
    }

    public function testApplyWithNotEnabledTransition()
    {
        $definition = $this->createComplexStateMachineDefinition1();
        $subject = new Subject('a');
        $stateMachine = new StateMachine($definition);

        try {
            $stateMachine->apply($subject, 't2');

            $this->fail('Should throw an exception');
        } catch (NotEnabledTransitionException $e) {
            $this->assertSame('Transition "t2" is not valid for subject in state "a" for state machine "unnamed".', $e->getMessage());
            $this->assertCount(1, $e->getTransitionBlockerList());
            $list = iterator_to_array($e->getTransitionBlockerList());
            $this->assertSame('The state does not enable the transition.', $list[0]->getMessage());
            $this->assertSame($e->getStateMachine(), $stateMachine);
            $this->assertSame($e->getSubject(), $subject);
            $this->assertSame($e->getTransitionName(), 't2');
        }
    }

    public function testApply()
    {
        $definition = $this->createComplexStateMachineDefinition1();
        $subject = new Subject('a');
        $stateMachine = new StateMachine($definition);

        $newState = $stateMachine->apply($subject, 't1-1');

        $this->assertNotSame('a', $newState);
        $this->assertSame('b', $newState);
    }

    public function testApplyWithSameNameTransition()
    {
        $subject = new Subject('a');
        $definition = $this->createStateMachineWithSameNameTransition();
        $stateMachine = new StateMachine($definition);

        $newState = $stateMachine->apply($subject, 'a_to_b');
        $this->assertSame('b', $newState);

        $newState = $stateMachine->apply($subject, 'to_a');
        $this->assertSame('a', $newState);

        $newState = $stateMachine->apply($subject, 'a_to_c');
        $this->assertSame('c', $newState);

        $newState = $stateMachine->apply($subject, 'to_a');
        $this->assertSame('a', $newState);
    }

    public function testApplyWithSameNameTransition2()
    {
        $subject = new Subject('a');

        $places = range('a', 'd');
        $transitions = [];
        $transitions[] = new Transition('t', 'a', 'c');
        $transitions[] = new Transition('t', 'b', 'd');
        $definition = new Definition($places, $transitions);
        $stateMachine = new StateMachine($definition);

        $newState = $stateMachine->apply($subject, 't');
        $this->assertSame('c', $newState);
    }

    public function testApplyWithSameNameTransition3()
    {
        $subject = new Subject('a');

        $places = range('a', 'd');
        $transitions = [];
        $transitions[] = new Transition('t', 'a', 'b');
        $transitions[] = new Transition('t', 'b', 'c');
        $transitions[] = new Transition('t', 'c', 'd');
        $definition = new Definition($places, $transitions);
        $stateMachine = new StateMachine($definition);

        $newState = $stateMachine->apply($subject, 't');
        $this->assertSame('b', $newState);
    }

    public function testApplyWithEventDispatcher()
    {
        $definition = $this->createComplexStateMachineDefinition1();
        $subject = new Subject('a');
        $eventDispatcher = new EventDispatcherMock();
        $stateMachine = new StateMachine($definition, null, $eventDispatcher, 'statemachine_name');

        $eventNameExpected = [
            'statemachine.guard',
            'statemachine.guard.statemachine_name',
            'statemachine.guard.statemachine_name.t1-1',
            'statemachine.leave',
            'statemachine.leave.statemachine_name',
            'statemachine.leave.statemachine_name.a',
            'statemachine.transition',
            'statemachine.transition.statemachine_name',
            'statemachine.transition.statemachine_name.t1-1',
            'statemachine.enter',
            'statemachine.enter.statemachine_name',
            'statemachine.enter.statemachine_name.b',
            'statemachine.entered',
            'statemachine.entered.statemachine_name',
            'statemachine.entered.statemachine_name.b',
            'statemachine.completed',
            'statemachine.completed.statemachine_name',
            'statemachine.completed.statemachine_name.t1-1',
            // Following events are fired because of announce() method
            'statemachine.announce',
            'statemachine.announce.statemachine_name',
            'statemachine.guard',
            'statemachine.guard.statemachine_name',
            'statemachine.guard.statemachine_name.t2',
            'statemachine.announce.statemachine_name.t2',
        ];

        $stateMachine->apply($subject, 't1-1');

        $this->assertSame($eventNameExpected, $eventDispatcher->dispatchedEvents);
    }

    public function testApplyDoesNotTriggerExtraGuardWithEventDispatcher()
    {
        $transitions = [
            new Transition('a-b', 'a', 'b'),
            new Transition('a-c', 'a', 'c'),
        ];
        $definition = new Definition(['a', 'b', 'c'], $transitions);

        $subject = new Subject('a');
        $eventDispatcher = new EventDispatcherMock();
        $stateMachine = new StateMachine($definition, null, $eventDispatcher, 'statemachine_name');

        $eventNameExpected = [
            'statemachine.guard',
            'statemachine.guard.statemachine_name',
            'statemachine.guard.statemachine_name.a-b',
            'statemachine.leave',
            'statemachine.leave.statemachine_name',
            'statemachine.leave.statemachine_name.a',
            'statemachine.transition',
            'statemachine.transition.statemachine_name',
            'statemachine.transition.statemachine_name.a-b',
            'statemachine.enter',
            'statemachine.enter.statemachine_name',
            'statemachine.enter.statemachine_name.b',
            'statemachine.entered',
            'statemachine.entered.statemachine_name',
            'statemachine.entered.statemachine_name.b',
            'statemachine.completed',
            'statemachine.completed.statemachine_name',
            'statemachine.completed.statemachine_name.a-b',
            'statemachine.announce',
            'statemachine.announce.statemachine_name',
        ];

        $stateMachine->apply($subject, 'a-b');

        $this->assertSame($eventNameExpected, $eventDispatcher->dispatchedEvents);
    }

    public function testApplyWithContext()
    {
        $definition = $this->createComplexStateMachineDefinition1();
        $subject = new Subject('a');
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener('statemachine.transition', function (TransitionEvent $event) {
            $event->setContext(array_merge($event->getContext(), ['user' => 'admin']));
        });
        $stateMachine = new StateMachine($definition, null, $eventDispatcher);

        $stateMachine->apply($subject, 't1-1', ['foo' => 'bar']);

        $this->assertSame(['foo' => 'bar', 'user' => 'admin'], $subject->getContext());
    }

    public function testEventName()
    {
        $definition = $this->createComplexStateMachineDefinition1();
        $subject = new Subject('a');
        $dispatcher = new EventDispatcher();
        $name = 'statemachine_name';
        $stateMachine = new StateMachine($definition, null, $dispatcher, $name);

        $assertStateMachineName = function (Event $event) use ($name) {
            $this->assertEquals($name, $event->getStateMachineName());
        };

        $eventNames = [
            'statemachine.guard',
            'statemachine.leave',
            'statemachine.transition',
            'statemachine.enter',
            'statemachine.entered',
            'statemachine.announce',
        ];

        foreach ($eventNames as $eventName) {
            $dispatcher->addListener($eventName, $assertStateMachineName);
        }

        $stateMachine->apply($subject, 't1-1');
    }

    public function testGetEnabledTransitions()
    {
        $definition = $this->createComplexStateMachineDefinition1();
        $subject = new Subject('a');
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener('statemachine.guard.statemachine_name.t1-1', function (GuardEvent $event) {
            $event->setBlocked(true);
        });
        $eventDispatcher->addListener('statemachine.guard.statemachine_name.t1-2', function (GuardEvent $event) {
            $event->setBlocked(true);
        });
        $stateMachine = new StateMachine($definition, null, $eventDispatcher, 'statemachine_name');

        $this->assertEmpty($stateMachine->getEnabledTransitions($subject));

        $subject->setState('d');
        $transitions = $stateMachine->getEnabledTransitions($subject);
        $this->assertCount(2, $transitions);
        $this->assertSame('t3', $transitions[0]->getName());
        $this->assertSame('t4', $transitions[1]->getName());

        $subject->setState('e');
        $transitions = $stateMachine->getEnabledTransitions($subject);
        $this->assertCount(1, $transitions);
        $this->assertSame('t5', $transitions[0]->getName());
    }

    public function testGetEnabledTransitionsWithSameNameTransition()
    {
        $definition = $this->createStateMachineWithSameNameTransition();
        $subject = new Subject('a');
        $stateMachine = new StateMachine($definition);

        $transitions = $stateMachine->getEnabledTransitions($subject);
        $this->assertCount(2, $transitions);
        $this->assertSame('a_to_b', $transitions[0]->getName());
        $this->assertSame('a_to_c', $transitions[1]->getName());

        $subject->setState('b');
        $transitions = $stateMachine->getEnabledTransitions($subject);
        $this->assertCount(2, $transitions);
        $this->assertSame('b_to_c', $transitions[0]->getName());
        $this->assertSame('to_a', $transitions[1]->getName());

        $subject->setState('c');
        $transitions = $stateMachine->getEnabledTransitions($subject);
        $this->assertCount(1, $transitions);
        $this->assertSame('to_a', $transitions[0]->getName());
    }
}

class EventDispatcherMock implements \Symfony\Component\EventDispatcher\EventDispatcherInterface
{
    public $dispatchedEvents = [];

    public function dispatch($event, string $eventName = null): object
    {
        $this->dispatchedEvents[] = $eventName;

        return $event;
    }

    public function addListener($eventName, $listener, $priority = 0)
    {
    }

    public function addSubscriber(\Symfony\Component\EventDispatcher\EventSubscriberInterface $subscriber)
    {
    }

    public function removeListener($eventName, $listener)
    {
    }

    public function removeSubscriber(\Symfony\Component\EventDispatcher\EventSubscriberInterface $subscriber)
    {
    }

    public function getListeners($eventName = null): array
    {
    }

    public function getListenerPriority($eventName, $listener): ?int
    {
    }

    public function hasListeners($eventName = null): bool
    {
    }
}
