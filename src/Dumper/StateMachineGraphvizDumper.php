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

namespace MattyG\StateMachine\Dumper;

use MattyG\StateMachine\Definition;

class StateMachineGraphvizDumper extends GraphvizDumper
{
    /**
     * {@inheritdoc}
     *
     * Dumps the state machine as a graphviz graph.
     *
     * Available options:
     *
     *  * graph: The default options for the whole graph
     *  * node: The default options for nodes (places)
     *  * edge: The default options for edges
     */
    public function dump(Definition $definition, ?string $state = null, array $options = []): string
    {
        $places = $this->findPlaces($definition, $state);
        $edges = $this->findEdges($definition);

        /** @var array $options */
        $options = array_replace_recursive(self::$defaultOptions, $options);

        return $this->startDot($options)
            .$this->addPlaces($places)
            .$this->addEdges($edges)
            .$this->endDot()
        ;
    }

    /**
     * @param Definition $definition
     * @return array<string, array<int, array{name: string, to: string, attributes: array}>>
     */
    protected function findEdges(Definition $definition): array
    {
        $stateMachineMetadata = $definition->getMetadataStore();

        $edges = [];

        foreach ($definition->getTransitions() as $transition) {
            $attributes = [];

            $transitionName = $stateMachineMetadata->getMetadata('label', $transition) ?? $transition->getName();

            $labelColor = $stateMachineMetadata->getMetadata('color', $transition);
            if (null !== $labelColor) {
                $attributes['fontcolor'] = $labelColor;
            }
            $arrowColor = $stateMachineMetadata->getMetadata('arrow_color', $transition);
            if (null !== $arrowColor) {
                $attributes['color'] = $arrowColor;
            }

            $edge = [
                "name" => $transitionName,
                "to" => $transition->getTo(),
                "attributes" => $attributes,
            ];
            $edges[$transition->getFrom()][] = $edge;
        }

        return $edges;
    }

    /**
     * @param array<string, array<int, array{name: string, to: string, attributes: array}>> $edges
     * @return string
     */
    protected function addEdges(array $edges): string
    {
        $code = '';

        foreach ($edges as $id => $edges) {
            foreach ($edges as $edge) {
                $code .= sprintf(
                    "  place_%s -> place_%s [label=\"%s\" style=\"%s\"%s];\n",
                    $this->dotize($id),
                    $this->dotize($edge['to']),
                    $this->escape($edge['name']),
                    'solid',
                    $this->addAttributes($edge['attributes'])
                );
            }
        }

        return $code;
    }
}
