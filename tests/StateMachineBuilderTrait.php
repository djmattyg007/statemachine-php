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

use MattyG\StateMachine\Definition;
use MattyG\StateMachine\Metadata\InMemoryMetadataStore;
use MattyG\StateMachine\Transition;

trait StateMachineBuilderTrait
{
    private function createComplexStateMachineDefinition1()
    {
        $places = range('a', 'g');

        $transitions = [];
        $transitions[] = new Transition('t1-1', 'a', 'b');
        $transitions[] = new Transition('t1-2', 'a', 'c');
        $transitions[] = new Transition('t2', 'b', 'd');
        $transitions[] = new Transition('t2', 'c', 'd');
        $transitionWithMetadataDumpStyle = new Transition('t3', 'd', 'e');
        $transitions[] = $transitionWithMetadataDumpStyle;
        $transitions[] = new Transition('t4', 'd', 'f');
        $transitions[] = new Transition('t5', 'e', 'g');
        $transitions[] = new Transition('t6', 'f', 'g');

        $transitionsMetadata = new \SplObjectStorage();
        $transitionsMetadata[$transitionWithMetadataDumpStyle] = [
            'label' => 'My custom transition label 1',
            'color' => 'Red',
            'arrow_color' => 'Green',
        ];
        $inMemoryMetadataStore = new InMemoryMetadataStore([], [], $transitionsMetadata);

        return new Definition($places, $transitions, null, $inMemoryMetadataStore);

        // The graph looks like:
        // +---+     *======*     +---+     *====*     +----+     *====*     +----+     *====*     +---+
        // | a | --> | t1-1 | --> | b | --> | t2 | --> | d  | --> | t4 | --> | f  | --> | t6 | --> | g |
        // +---+     *======*     +---+     *====*     +----+     *====*     +----+     *====*     +---+
        //   |                                 ^         |                                           ^
        //   |                                 |         |                                           |
        //   v                                 |         v                                           |
        // *======*     +----+                 |       *====*     +----+     *====*                  |
        // | t1-2 | --> | c  | ----------------+       | t3 | --> | e  | --> | t5 | -----------------+
        // *======*     +----+                         *====*     +----+     *====*
    }

    private function createSimpleStateMachineDefinition()
    {
        $places = range('a', 'c');

        $transitions = [];
        $transitionWithMetadataDumpStyle = new Transition('t1', 'a', 'b');
        $transitions[] = $transitionWithMetadataDumpStyle;
        $transitionWithMetadataArrowColorPink = new Transition('t2', 'b', 'c');
        $transitions[] = $transitionWithMetadataArrowColorPink;

        $placesMetadata = [];
        $placesMetadata['c'] = [
            'bg_color' => 'DeepSkyBlue',
            'description' => 'My custom place description',
        ];

        $transitionsMetadata = new \SplObjectStorage();
        $transitionsMetadata[$transitionWithMetadataDumpStyle] = [
            'label' => 'My custom transition label 2',
            'color' => 'Grey',
            'arrow_color' => 'Purple',
        ];
        $transitionsMetadata[$transitionWithMetadataArrowColorPink] = [
            'arrow_color' => 'Pink',
        ];
        $inMemoryMetadataStore = new InMemoryMetadataStore([], $placesMetadata, $transitionsMetadata);

        return new Definition($places, $transitions, null, $inMemoryMetadataStore);

        // The graph looks like:
        // +---+     +----+     +---+     +----+     +---+
        // | a | --> | t1 | --> | b | --> | t2 | --> | c |
        // +---+     +----+     +---+     +----+     +---+
    }

    private function createStateMachineWithSameNameTransition()
    {
        $places = range('a', 'c');

        $transitions = [];
        $transitions[] = new Transition('a_to_b', 'a', 'b');
        $transitions[] = new Transition('a_to_c', 'a', 'c');
        $transitions[] = new Transition('b_to_c', 'b', 'c');
        $transitions[] = new Transition('to_a', 'b', 'a');
        $transitions[] = new Transition('to_a', 'c', 'a');

        return new Definition($places, $transitions);

        // The graph looks like:
        //                          *======*
        //   +--------------------- | to_a | <-----------------+
        //   |                      *======*                   |
        //   |                         ^                       |
        //   |                         |                       |
        //   |        *========*     +---+     *========*      |
        //   v    +-> | a_to_b | --> | b | --> | b_to_c |      |
        // +---+ -+   *========*     +---+     *========*      |
        // | a |                                   |           |
        // +---+ -+                                |           |
        //        |                                v           |
        //        |   *========*                 +---+         |
        //        +-> | a_to_c | --------------> | c | --------+
        //            *========*                 +---+
    }

    private function createComplexStateMachineDefinition2()
    {
        $places = ['a', 'b', 'c', 'd'];

        $transitions = [new Transition('t1', 'a', 'b')];
        $transitionWithMetadataDumpStyle = new Transition('t1', 'd', 'b');
        $transitions[] = $transitionWithMetadataDumpStyle;
        $transitionWithMetadataArrowColorBlue = new Transition('t2', 'b', 'c');
        $transitions[] = $transitionWithMetadataArrowColorBlue;
        $transitions[] = new Transition('t3', 'b', 'd');

        $transitionsMetadata = new \SplObjectStorage();
        $transitionsMetadata[$transitionWithMetadataDumpStyle] = [
            'label' => 'My custom transition label 3',
            'color' => 'Grey',
            'arrow_color' => 'Red',
        ];
        $transitionsMetadata[$transitionWithMetadataArrowColorBlue] = [
            'arrow_color' => 'Blue',
        ];
        $inMemoryMetadataStore = new InMemoryMetadataStore([], [], $transitionsMetadata);

        return new Definition($places, $transitions, null, $inMemoryMetadataStore);

        // The graph looks like:
        //                     t1
        //               +------------------+
        //               v                  |
        // +---+  t1   +-----+  t2   +---+  |
        // | a | ----> |  b  | ----> | c |  |
        // +---+       +-----+       +---+  |
        //               |                  |
        //               | t3               |
        //               v                  |
        //             +-----+              |
        //             |  d  | -------------+
        //             +-----+
    }
}
