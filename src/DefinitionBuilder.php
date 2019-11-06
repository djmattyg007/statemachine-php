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

use MattyG\StateMachine\Metadata\MetadataStoreInterface;

/**
 * Builds a definition.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class DefinitionBuilder
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
     * @var MetadataStoreInterface|null
     */
    private $metadataStore = null;

    /**
     * @param string[] $places
     * @param TransitionInterface[] $transitions
     */
    public function __construct(array $places = [], array $transitions = [])
    {
        $this->addPlaces($places);
        $this->addTransitions($transitions);
    }

    /**
     * @return Definition
     */
    public function build(): Definition
    {
        return new Definition($this->places, $this->transitions, $this->initialPlace, $this->metadataStore);
    }

    /**
     * @param string $initialPlace
     */
    public function setInitialPlace(string $initialPlace): void
    {
        $this->initialPlace = $initialPlace;
    }

    /**
     * @param string $place
     */
    public function addPlace(string $place): void
    {
        if ($this->initialPlace === null) {
            $this->initialPlace = $place;
        }

        $this->places[$place] = $place;
    }

    /**
     * @param string[] $places
     */
    public function addPlaces(array $places): void
    {
        foreach ($places as $place) {
            $this->addPlace($place);
        }
    }

    /**
     * @param TransitionInterface $transition
     */
    public function addTransition(TransitionInterface $transition): void
    {
        $this->transitions[] = $transition;
    }

    /**
     * @param TransitionInterface[] $transitions
     */
    public function addTransitions(array $transitions): void
    {
        foreach ($transitions as $transition) {
            $this->addTransition($transition);
        }
    }

    /**
     * @param MetadataStoreInterface $metadataStore
     */
    public function setMetadataStore(MetadataStoreInterface $metadataStore): void
    {
        $this->metadataStore = $metadataStore;
    }
}
