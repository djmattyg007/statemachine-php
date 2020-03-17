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
use MattyG\StateMachine\Event\EventDispatcherInterface;
use MattyG\StateMachine\Event\GuardEvent;
use MattyG\StateMachine\Event\LeaveEvent;
use MattyG\StateMachine\Event\TransitionEvent;
use MattyG\StateMachine\Exception\LogicException;
use MattyG\StateMachine\Exception\NotEnabledTransitionException;
use MattyG\StateMachine\Exception\UndefinedTransitionException;
use MattyG\StateMachine\Metadata\MetadataStoreInterface;
use MattyG\StateMachine\StateAccessor\MethodStateAccessor;
use MattyG\StateMachine\StateAccessor\StateAccessorInterface;

use function sprintf;

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
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Definition
     */
    public function getDefinition(): Definition
    {
        return $this->definition;
    }

    /**
     * @return StateAccessorInterface
     */
    public function getStateAccessor(): StateAccessorInterface
    {
        return $this->stateAccessor;
    }

    /**
     * @return MetadataStoreInterface
     */
    public function getMetadataStore(): MetadataStoreInterface
    {
        return $this->definition->getMetadataStore();
    }

    /**
     * Returns the object's current state.
     *
     * @param object $subject
     * @return string
     * @throws LogicException
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
     * Returns true if the transition is enabled.
     *
     * @param object $subject
     * @param string $transitionName
     * @return bool True if the transition is enabled.
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
     * Builds a TransitionBlockerList to know why a transition is blocked.
     *
     * @param object $subject
     * @param string $transitionName
     * @return TransitionBlockerList
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
     * Fire a transition.
     *
     * @param object $subject
     * @param string $transitionName
     * @param array $context
     * @return string
     * @throws LogicException If the transition is not applicable.
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
     * Returns all enabled transitions.
     *
     * @return TransitionInterface[] All enabled transitions.
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
        if ($this->dispatcher === null) {
            return null;
        }

        $event = new GuardEvent($subject, $transition, $this);

        $this->dispatcher->dispatch('statemachine.guard', $event);
        $this->dispatcher->dispatch(sprintf('statemachine.guard.%s', $this->name), $event);
        $this->dispatcher->dispatch(sprintf('statemachine.guard.%s.%s', $this->name, $transition->getName()), $event);

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

        if ($this->dispatcher === null) {
            return;
        }

        $event = new LeaveEvent($subject, $transition, $this);

        $this->dispatcher->dispatch('statemachine.leave', $event);
        $this->dispatcher->dispatch(sprintf('statemachine.leave.%s', $this->name), $event);
        $this->dispatcher->dispatch(sprintf('statemachine.leave.%s.%s', $this->name, $transition->getFrom()), $event);
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
        if ($this->dispatcher === null) {
            return $context;
        }

        $event = new TransitionEvent($subject, $transition, $this, $context);

        $this->dispatcher->dispatch('statemachine.transition', $event);
        $this->dispatcher->dispatch(sprintf('statemachine.transition.%s', $this->name), $event);
        $this->dispatcher->dispatch(sprintf('statemachine.transition.%s.%s', $this->name, $transition->getName()), $event);

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

        if ($this->dispatcher === null) {
            return;
        }

        $event = new EnterEvent($subject, $transition, $this);

        $this->dispatcher->dispatch('statemachine.enter', $event);
        $this->dispatcher->dispatch(sprintf('statemachine.enter.%s', $this->name), $event);
        $this->dispatcher->dispatch(sprintf('statemachine.enter.%s.%s', $this->name, $transition->getTo()), $event);
    }

    /**
     * @param object $subject
     * @param TransitionInterface $transition
     */
    private function entered(object $subject, TransitionInterface $transition): void
    {
        if ($this->dispatcher === null) {
            return;
        }

        $event = new EnteredEvent($subject, $transition, $this);

        $this->dispatcher->dispatch('statemachine.entered', $event);
        $this->dispatcher->dispatch(sprintf('statemachine.entered.%s', $this->name), $event);
        $this->dispatcher->dispatch(sprintf('statemachine.entered.%s.%s', $this->name, $transition->getTo()), $event);
    }

    /**
     * @param object $subject
     * @param TransitionInterface $transition
     */
    private function completed(object $subject, TransitionInterface $transition): void
    {
        if ($this->dispatcher === null) {
            return;
        }

        $event = new CompletedEvent($subject, $transition, $this);

        $this->dispatcher->dispatch('statemachine.completed', $event);
        $this->dispatcher->dispatch(sprintf('statemachine.completed.%s', $this->name), $event);
        $this->dispatcher->dispatch(sprintf('statemachine.completed.%s.%s', $this->name, $transition->getName()), $event);
    }

    /**
     * @param object $subject
     * @param TransitionInterface $initialTransition
     */
    private function announce(object $subject, TransitionInterface $initialTransition): void
    {
        if ($this->dispatcher === null) {
            return;
        }

        $event = new AnnounceEvent($subject, $initialTransition, $this);

        $this->dispatcher->dispatch('statemachine.announce', $event);
        $this->dispatcher->dispatch(sprintf('statemachine.announce.%s', $this->name), $event);

        foreach ($this->getEnabledTransitions($subject) as $transition) {
            $this->dispatcher->dispatch(sprintf('statemachine.announce.%s.%s', $this->name, $transition->getName()), $event);
        }
    }
}
