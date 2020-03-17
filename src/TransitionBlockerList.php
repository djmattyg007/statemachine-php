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

use ArrayIterator;
use Countable;
use Iterator;
use IteratorAggregate;

use function count;

/**
 * A list of transition blockers.
 *
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 */
final class TransitionBlockerList implements IteratorAggregate, Countable
{
    /**
     * @var TransitionBlocker[]
     */
    private $blockers = [];

    /**
     * @param TransitionBlocker[] $blockers
     */
    public function __construct(array $blockers = [])
    {
        foreach ($blockers as $blocker) {
            $this->add($blocker);
        }
    }

    /**
     * @param TransitionBlocker $blocker
     */
    public function add(TransitionBlocker $blocker): void
    {
        $this->blockers[] = $blocker;
    }

    /**
     * @param string $code
     * @return bool
     */
    public function has(string $code): bool
    {
        foreach ($this->blockers as $blocker) {
            if ($code === $blocker->getCode()) {
                return true;
            }
        }

        return false;
    }

    public function clear(): void
    {
        $this->blockers = [];
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return count($this->blockers) === 0;
    }

    /**
     * @return Iterator<TransitionBlocker>
     */
    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->blockers);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->blockers);
    }
}
