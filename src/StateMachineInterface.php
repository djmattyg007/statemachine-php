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

use MattyG\StateMachine\Exception\LogicException;
use MattyG\StateMachine\Metadata\MetadataStoreInterface;
use MattyG\StateMachine\StateAccessor\StateAccessorInterface;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
interface StateMachineInterface
{
    /**
     * Returns the object's current state.
     *
     * @return string
     * @throws LogicException
     */
    public function getState(object $subject): string;

    /**
     * Returns true if the transition is enabled.
     *
     * @param object $subject
     * @param string $transitionName
     * @return bool true if the transition is enabled
     */
    public function can(object $subject, string $transitionName): bool;

    /**
     * Builds a TransitionBlockerList to know why a transition is blocked.
     *
     * @param object $subject
     * @param string $transitionName
     * @return TransitionBlockerList
     */
    public function buildTransitionBlockerList(object $subject, string $transitionName): TransitionBlockerList;

    /**
     * Fire a transition.
     *
     * @param object $subject
     * @param string $transitionName
     * @param array $context
     * @return string
     * @throws LogicException If the transition is not applicable
     */
    public function apply(object $subject, string $transitionName, array $context = []): string;

    /**
     * Returns all enabled transitions.
     *
     * @return TransitionInterface[] All enabled transitions
     */
    public function getEnabledTransitions(object $subject): array;

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return Definition
     */
    public function getDefinition(): Definition;

    /**
     * @return StateAccessorInterface
     */
    public function getStateAccessor(): StateAccessorInterface;

    /**
     * @var MetadataStoreInterface
     */
    public function getMetadataStore(): MetadataStoreInterface;
}
