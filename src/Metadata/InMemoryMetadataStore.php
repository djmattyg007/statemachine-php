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

namespace MattyG\StateMachine\Metadata;

use MattyG\StateMachine\TransitionInterface;
use SplObjectStorage;

/**
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 */
final class InMemoryMetadataStore implements MetadataStoreInterface
{
    use GetMetadataTrait;

    /**
     * @var array
     */
    private $stateMachineMetadata;

    /**
     * @var array<string, array>
     */
    private $placesMetadata;

    /**
     * @var SplObjectStorage
     */
    private $transitionsMetadata;

    /**
     * @param array $stateMachineMetadata
     * @param array<string, array> $placesMetadata
     * @param SplObjectStorage|null $transitionsMetadata
     */
    public function __construct(array $stateMachineMetadata = [], array $placesMetadata = [], SplObjectStorage $transitionsMetadata = null)
    {
        $this->stateMachineMetadata = $stateMachineMetadata;
        $this->placesMetadata = $placesMetadata;
        $this->transitionsMetadata = $transitionsMetadata ?: new SplObjectStorage();
    }

    /**
     * {@inheritdoc}
     */
    public function getStateMachineMetadata(): array
    {
        return $this->stateMachineMetadata;
    }

    /**
     * {@inheritdoc}
     */
    public function getPlaceMetadata(string $place): array
    {
        return $this->placesMetadata[$place] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function getTransitionMetadata(TransitionInterface $transition): array
    {
        return $this->transitionsMetadata[$transition] ?? [];
    }
}
