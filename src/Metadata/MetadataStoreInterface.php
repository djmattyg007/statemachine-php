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

/**
 * MetadataStoreInterface is able to fetch metadata for a specific state machine.
 *
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 */
interface MetadataStoreInterface
{
    /**
     * @return array
     */
    public function getStateMachineMetadata(): array;

    /**
     * @param string $place
     * @return array
     */
    public function getPlaceMetadata(string $place): array;

    /**
     * @param TransitionInterface $transition
     * @return array
     */
    public function getTransitionMetadata(TransitionInterface $transition): array;

    /**
     * Returns the metadata for a specific subject.
     *
     * This is a proxy method.
     *
     * @param string|TransitionInterface|null $subject Use null to get state machine metadata
     *                                                 Use a string (the place name) to get place metadata
     *                                                 Use a TransitionInterface instance to get transition metadata
     */
    public function getMetadata(string $key, $subject = null);
}
