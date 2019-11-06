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

namespace MattyG\StateMachine;

use MattyG\StateMachine\Event\AnnounceEvent;
use MattyG\StateMachine\Event\CompletedEvent;
use MattyG\StateMachine\Event\EnteredEvent;
use MattyG\StateMachine\Event\EnterEvent;
use MattyG\StateMachine\Event\GuardEvent;
use MattyG\StateMachine\Event\LeaveEvent;
use MattyG\StateMachine\Event\TransitionEvent;
use MattyG\StateMachine\Exception\LogicException;
use MattyG\StateMachine\Exception\NotEnabledTransitionException;
use MattyG\StateMachine\Exception\UndefinedTransitionException;
use MattyG\StateMachine\Metadata\MetadataStoreInterface;
use MattyG\StateMachine\StateAccessor\MethodStateAccessor;
use MattyG\StateMachine\StateAccessor\StateAccessorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class StateMachine implements StateMachineInterface
{
    /**
     * @var Definition
     */
    private $definition;

    /**
     * @var StateAccessorInterface
     */
    private $stateAccessor;

    /**
     * @var EventDispatcherInterface|null
     */
    private $dispatcher = null;

    /**
     * @var string
     */
    private $name = 'unnamed';

    /**
     * @param Definition $definition
     * @param StateAccessorInterface|null $stateAccessor
     * @param EventDispatcherInterface|null $dispatcher
     * @param string $name
     */
    public function __construct(Definition $definition, StateAccessorInterface $stateAccessor = null, EventDispatcherInterface $dispatcher = null, string $name = 'unnamed')
    {
        $this->definition = $definition;
        $this->stateAccessor = $stateAccessor ?: MethodStateAccessor::fromProperty("state");
        $this->dispatcher = $dispatcher;
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function getState(object $subject): string
    {
        $state = $this->stateAccessor->getState($subject);

        // check that the subject has a known place
        $places = $this->definition->getPlaces();
        if (!isset($places[$state])) {
            throw new LogicException(sprintf('State "%s" is not valid for state machine "%s".', $state, $this->name));
        }

        return $state;
    }

    /**
     * {@inheritdoc}
     */
    public function can(object $subject, string $transitionName): bool
    {
        $transitions = $this->definition->getTransitions();
        $state = $this->getState($subject);

        foreach ($transitions as $transition) {
            if ($transition->getName() !== $transitionName) {
                continue;
            }

            $transitionBlockerList = $this->buildTransitionBlockerListForTransition($subject, $state, $transition);

            if ($transitionBlockerList->isEmpty()) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function buildTransitionBlockerList(object $subject, string $transitionName): TransitionBlockerList
    {
        $transitions = $this->definition->getTransitions();
        $state = $this->getState($subject);
        $transitionBlockerList = null;

        foreach ($transitions as $transition) {
            if ($transition->getName() !== $transitionName) {
                continue;
            }

            $transitionBlockerList = $this->buildTransitionBlockerListForTransition($subject, $state, $transition);

            if ($transitionBlockerList->isEmpty()) {
                return $transitionBlockerList;
            }

            // We prefer to return transitions blocker by something else than
            // state. Because it means the state was OK. Transitions are
            // deterministic: it's not possible to have many transitions enabled
            // at the same time that match the same state with the same name.
            if (!$transitionBlockerList->has(TransitionBlocker::BLOCKED_BY_STATE)) {
                return $transitionBlockerList;
            }
        }

        if (!$transitionBlockerList) {
            throw new UndefinedTransitionException($subject, $transitionName, $this);
        }

        return $transitionBlockerList;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(object $subject, string $transitionName, array $context = []): string
    {
        $originalState = $this->getState($subject);

        $transitionBlockerList = null;
        $approvedTransition = null;

        foreach ($this->definition->getTransitions() as $transition) {
            if ($transition->getName() !== $transitionName) {
                continue;
            }

            $transitionBlockerList = $this->buildTransitionBlockerListForTransition($subject, $originalState, $transition);
            if ($transitionBlockerList->isEmpty()) {
                $approvedTransition = $transition;
                break;
            }
        }

        if (!$transitionBlockerList) {
            throw new UndefinedTransitionException($subject, $transitionName, $this);
        }

        if (!$approvedTransition) {
            throw new NotEnabledTransitionException($subject, $transitionName, $this, $transitionBlockerList);
        }

        $newState = $approvedTransition->getTo();

        $this->leave($subject, $approvedTransition);

        $context = $this->transition($subject, $approvedTransition, $context);

        $this->enter($subject, $approvedTransition);

        $this->stateAccessor->setState($subject, $newState, $context);

        $this->entered($subject, $approvedTransition);

        $this->completed($subject, $approvedTransition);

        $this->announce($subject, $approvedTransition);

        return $newState;
    }

    /**
     * {@inheritdoc}
     */
    public function getEnabledTransitions(object $subject): array
    {
        $enabledTransitions = [];
        $state = $this->getState($subject);

        foreach ($this->definition->getTransitions() as $transition) {
            $transitionBlockerList = $this->buildTransitionBlockerListForTransition($subject, $state, $transition);
            if ($transitionBlockerList->isEmpty()) {
                $enabledTransitions[] = $transition;
            }
        }

        return $enabledTransitions;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition(): Definition
    {
        return $this->definition;
    }

    /**
     * {@inheritdoc}
     */
    public function getStateAccessor(): StateAccessorInterface
    {
        return $this->stateAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataStore(): MetadataStoreInterface
    {
        return $this->definition->getMetadataStore();
    }

    /**
     * @param object $subject
     * @param string $state
     * @param TransitionInterface $transition
     * @return TransitionBlockerList
     */
    private function buildTransitionBlockerListForTransition(object $subject, string $state, TransitionInterface $transition): TransitionBlockerList
    {
        $from = $transition->getFrom();
        if ($from !== $state) {
            return new TransitionBlockerList([
                TransitionBlocker::createBlockedByState($state),
            ]);
        }

        $isAvailable = $transition->checkIsAvailable($subject, $this);
        if ($isAvailable === false) {
            return new TransitionBlockerList([
                TransitionBlocker::createBlockedByAvailabilityGuard(),
            ]);
        }

        $event = $this->guardTransition($subject, $transition);
        if ($event !== null && $event->isBlocked()) {
            return $event->getTransitionBlockerList();
        }

        return new TransitionBlockerList();
    }

    /**
     * @param object $subject
     * @param TransitionInterface $transition
     * @return GuardEvent|null
     */
    private function guardTransition(object $subject, TransitionInterface $transition): ?GuardEvent
    {
        if (null === $this->dispatcher) {
            return null;
        }

        $event = new GuardEvent($subject, $transition, $this);

        $this->dispatcher->dispatch($event, 'statemachine.guard');
        $this->dispatcher->dispatch($event, sprintf('statemachine.guard.%s', $this->name));
        $this->dispatcher->dispatch($event, sprintf('statemachine.guard.%s.%s', $this->name, $transition->getName()));

        return $event;
    }

    /**
     * @param object $subject
     * @param TransitionInterface $transition
     * @throws LogicException
     */
    private function leave(object $subject, TransitionInterface $transition): void
    {
        $transition->checkCanLeave($subject, $this);

        if (null === $this->dispatcher) {
            return;
        }

        $event = new LeaveEvent($subject, $transition, $this);

        $this->dispatcher->dispatch($event, 'statemachine.leave');
        $this->dispatcher->dispatch($event, sprintf('statemachine.leave.%s', $this->name));
        $this->dispatcher->dispatch($event, sprintf('statemachine.leave.%s.%s', $this->name, $transition->getFrom()));
    }

    /**
     * @param object $subject
     * @param TransitionInterface $transition
     * @param array $context
     * @return array
     * @throws LogicException
     */
    private function transition(object $subject, TransitionInterface $transition, array $context): array
    {
        if (null === $this->dispatcher) {
            return $context;
        }

        $event = new TransitionEvent($subject, $transition, $this, $context);

        $this->dispatcher->dispatch($event, 'statemachine.transition');
        $this->dispatcher->dispatch($event, sprintf('statemachine.transition.%s', $this->name));
        $this->dispatcher->dispatch($event, sprintf('statemachine.transition.%s.%s', $this->name, $transition->getName()));

        return $event->getContext();
    }

    /**
     * @param object $subject
     * @param TransitionInterface $transition
     * @throws LogicException
     */
    private function enter(object $subject, TransitionInterface $transition): void
    {
        $transition->checkCanEnter($subject, $this);

        if (null === $this->dispatcher) {
            return;
        }

        $event = new EnterEvent($subject, $transition, $this);

        $this->dispatcher->dispatch($event, 'statemachine.enter');
        $this->dispatcher->dispatch($event, sprintf('statemachine.enter.%s', $this->name));
        $this->dispatcher->dispatch($event, sprintf('statemachine.enter.%s.%s', $this->name, $transition->getTo()));
    }

    /**
     * @param object $subject
     * @param TransitionInterface $transition
     */
    private function entered(object $subject, TransitionInterface $transition): void
    {
        if (null === $this->dispatcher) {
            return;
        }

        $event = new EnteredEvent($subject, $transition, $this);

        $this->dispatcher->dispatch($event, 'statemachine.entered');
        $this->dispatcher->dispatch($event, sprintf('statemachine.entered.%s', $this->name));
        $this->dispatcher->dispatch($event, sprintf('statemachine.entered.%s.%s', $this->name, $transition->getTo()));
    }

    /**
     * @param object $subject
     * @param TransitionInterface $transition
     */
    private function completed(object $subject, TransitionInterface $transition): void
    {
        if (null === $this->dispatcher) {
            return;
        }

        $event = new CompletedEvent($subject, $transition, $this);

        $this->dispatcher->dispatch($event, 'statemachine.completed');
        $this->dispatcher->dispatch($event, sprintf('statemachine.completed.%s', $this->name));
        $this->dispatcher->dispatch($event, sprintf('statemachine.completed.%s.%s', $this->name, $transition->getName()));
    }

    /**
     * @param object $subject
     * @param TransitionInterface $initialTransition
     */
    private function announce(object $subject, TransitionInterface $initialTransition): void
    {
        if (null === $this->dispatcher) {
            return;
        }

        $event = new AnnounceEvent($subject, $initialTransition, $this);

        $this->dispatcher->dispatch($event, 'statemachine.announce');
        $this->dispatcher->dispatch($event, sprintf('statemachine.announce.%s', $this->name));

        foreach ($this->getEnabledTransitions($subject) as $transition) {
            $this->dispatcher->dispatch($event, sprintf('statemachine.announce.%s.%s', $this->name, $transition->getName()));
        }
    }
}
