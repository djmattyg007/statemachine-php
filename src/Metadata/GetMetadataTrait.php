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

use MattyG\StateMachine\Exception\InvalidArgumentException;
use MattyG\StateMachine\TransitionInterface;

/**
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 */
trait GetMetadataTrait
{
    public function getMetadata(string $key, $subject = null)
    {
        if ($subject === null) {
            return $this->getStateMachineMetadata()[$key] ?? null;
        }

        if (\is_string($subject)) {
            $metadataBag = $this->getPlaceMetadata($subject);
            if (!$metadataBag) {
                return null;
            }

            return $metadataBag[$key] ?? null;
        }

        if ($subject instanceof TransitionInterface) {
            $metadataBag = $this->getTransitionMetadata($subject);
            if (!$metadataBag) {
                return null;
            }

            return $metadataBag[$key] ?? null;
        }

        throw new InvalidArgumentException(sprintf(
            'Could not find a MetadataBag for the subject of type "%s".',
            \is_object($subject) ? \get_class($subject) : \gettype($subject)
        ));
    }
}
