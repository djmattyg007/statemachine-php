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

use MattyG\StateMachine\Exception\InvalidArgumentException;
use MattyG\StateMachine\Exception\LogicException;
use MattyG\StateMachine\Metadata\InMemoryMetadataStore;
use MattyG\StateMachine\Metadata\MetadataStoreInterface;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
final class Definition
{
    /**
     * @var string[]
     */
    private $places = [];

    /**
     * @var TransitionInterface[]
     */
    private $transitions = [];

    /**
     * @var string|null
     */
    private $initialPlace = null;

    /**
     * @var MetadataStoreInterface
     */
    private $metadataStore;

    /**
     * @param string[] $places
     * @param TransitionInterface[] $transitions
     * @param string|null $initialPlace
     * @param MetadataStoreInterface|null $metadataStore
     */
    public function __construct(array $places, array $transitions, ?string $initialPlace = null, MetadataStoreInterface $metadataStore = null)
    {
        foreach ($places as $place) {
            $this->addPlace($place);
        }
        if (count($this->places) === 0) {
            throw new InvalidArgumentException("Cannot have a state machine definition with no places.");
        }

        foreach ($transitions as $transition) {
            $this->addTransition($transition);
        }
        if (count($this->transitions) === 0) {
            throw new InvalidArgumentException("Cannot have a state machine definition with no transitions.");
        }

        $this->setInitialPlace($initialPlace);

        $this->metadataStore = $metadataStore ?: new InMemoryMetadataStore();
    }

    /**
     * @return string
     */
    public function getInitialPlace(): string
    {
        // There is guaranteed to be an initial place by the time this is called,
        // because we enforce the fact that there must be at least one place in
        // a state machine definition. This check takes place in the constructor.

        /** @var string $initialPlace */
        $initialPlace = $this->initialPlace;
        return $initialPlace;
    }

    /**
     * @return string[]
     */
    public function getPlaces(): array
    {
        return $this->places;
    }

    /**
     * @return TransitionInterface[]
     */
    public function getTransitions(): array
    {
        return $this->transitions;
    }

    /**
     * @return MetadataStoreInterface
     */
    public function getMetadataStore(): MetadataStoreInterface
    {
        return $this->metadataStore;
    }

    /**
     * @param string|null $place
     */
    private function setInitialPlace(?string $place = null): void
    {
        if (!$place) {
            return;
        }

        if (!isset($this->places[$place])) {
            throw new LogicException(sprintf('Place "%s" cannot be the initial place as it does not exist.', $place));
        }

        $this->initialPlace = $place;
    }

    /**
     * @param string $place
     */
    private function addPlace(string $place): void
    {
        if ($this->initialPlace === null) {
            $this->initialPlace = $place;
        }

        $this->places[$place] = $place;
    }

    /**
     * @param TransitionInterface $transition
     */
    private function addTransition(TransitionInterface $transition): void
    {
        $name = $transition->getName();
        $from = $transition->getFrom();
        $to = $transition->getTo();

        if (!isset($this->places[$from])) {
            throw new LogicException(sprintf('Place "%s" referenced in transition "%s" does not exist.', $from, $name));
        }

        if (!isset($this->places[$to])) {
            throw new LogicException(sprintf('Place "%s" referenced in transition "%s" does not exist.', $to, $name));
        }

        $this->transitions[] = $transition;
    }
}
