<?php

namespace Jerive\Bundle\ContainerExplorerBundle\DependencyInjection\Dumper;

use Symfony\Component\DependencyInjection\Dumper\Dumper;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Definition;

class JsonDumper extends Dumper
{
    public function dump(array $options = array())
    {
        $this->nodes = $this->findNodes();

        $this->edges = array();
        foreach ($this->container->getDefinitions() as $id => $definition) {
            $this->edges[$id] = array_merge(
                $this->findEdges($id, $definition->getArguments(), true, ''),
                $this->findEdges($id, $definition->getProperties(), false, '')
            );

            foreach ($definition->getMethodCalls() as $call) {
                $this->edges[$id] = array_merge(
                    $this->edges[$id],
                    $this->findEdges($id, $call[1], false, $call[0].'()')
                );
            }
        }

        return json_encode($this->nodes);
    }

    /**
     * Finds all edges belonging to a specific service id.
     *
     * @param string  $id        The service id used to find edges
     * @param array   $arguments An array of arguments
     * @param Boolean $required
     * @param string  $name
     *
     * @return array An array of edges
     */
    private function findEdges($id, $arguments, $required, $name)
    {
        $edges = array();
        foreach ($arguments as $argument) {
            if ($argument instanceof Parameter) {
                $argument = $this->container->hasParameter($argument) ? $this->container->getParameter($argument) : null;
            } elseif (is_string($argument) && preg_match('/^%([^%]+)%$/', $argument, $match)) {
                $argument = $this->container->hasParameter($match[1]) ? $this->container->getParameter($match[1]) : null;
            }

            if ($argument instanceof Reference) {
                if (!$this->container->has((string) $argument)) {
                    $this->nodes[(string) $argument] = array(
                        'name' => $name,
                        'required' => $required,
                        'class' => ''
                    );
                }

                $edges[] = array('name' => $name, 'required' => $required, 'to' => $argument);
            } elseif (is_array($argument)) {
                $edges = array_merge($edges, $this->findEdges($id, $argument, $required, $name));
            }
        }

        return $edges;
    }

    /**
     * Finds all nodes.
     *
     * @return array An array of all nodes
     */
    private function findNodes()
    {
        $nodes = array();

        $container = clone $this->container;

        foreach ($container->getDefinitions() as $id => $definition) {
            $nodes[$id] = array(
                'class' => str_replace('\\', '\\\\', $this->container->getParameterBag()->resolveValue($definition->getClass())),
                'attributes' => array()
            );

            $container->setDefinition($id, new Definition('stdClass'));
        }

        foreach ($container->getServiceIds() as $id) {
            $service = $container->get($id);

            if (in_array($id, array_keys($container->getAliases()))) {
                continue;
            }

            if (!$container->hasDefinition($id)) {
                $nodes[$id] = array('
                    class' => str_replace('\\', '\\\\', get_class($service)),
                );
            }
        }

        return $nodes;
    }
}