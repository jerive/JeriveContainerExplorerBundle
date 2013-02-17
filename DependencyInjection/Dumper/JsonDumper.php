<?php

namespace Jerive\Bundle\ContainerExplorerBundle\DependencyInjection\Dumper;

use Symfony\Component\DependencyInjection\Dumper\Dumper;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

class JsonDumper extends Dumper
{
    public function dump(array $options = array())
    {
        $this->nodes = $this->findNodes();

        foreach ($this->container->getDefinitions() as $id => $definition) {
            if ($definition->isPublic()) {
                $this->edges[$id] = array_merge(
                    $this->findEdges($id, $definition->getArguments(), true, ''),
                    $this->findEdges($id, $definition->getProperties(), false, '')
                );

                foreach ($definition->getMethodCalls() as $call) {
                    $this->edges[$id] = array_merge(
                        $this->edges[$id],
                        $this->findEdges($id, $call[1], false, $call[0])
                    );
                }
            }
        }

        return json_encode(array(
            'nodes' => array_values($this->nodes),
            'edges' => array_reduce($this->edges, 'array_merge', array()),
        ));
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
                try {
                    $definition = $this->resolveServiceDefinition((string) $argument);
                    if ($definition instanceof Definition) {
                        if ($definition->isPublic()) {
                            $edges[] = array($id, (string) $argument, $name);
                        }
                    } else {
                        //var_dump((string) $definition, get_class($definition));
                    }
                } catch (InvalidArgumentException $e) {
                    continue;
                }
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

        foreach ($this->container->getServiceIds() as $id) {
            $definition = $this->resolveServiceDefinition($id);

            if ($definition instanceof Alias) {
                continue;
            }

            if ($definition instanceof Definition) {
                if ($definition->isPublic()) {
                    $class = $definition->getClass();
                    if (strpos($class, '%') === 0) {
                        $class = $this->container->getParameter(trim($class, '%'));
                    }
                    $nodes[$id] = array(
                        'id'        => $id,
                        'class'     => $class,
                        'public'    => $definition->isPublic(),
                        'abstract'  => $definition->isAbstract(),
                        'synthetic' => $definition->isSynthetic(),
                        'tags'      => array_keys($definition->getTags()),
                    );
                }
            } else {
                $nodes[$id] = array(
                    'id'        => $id,
                    'class'     => get_class($definition),
                    'public'    => false,
                    'abstract'  => false,
                    'synthetic' => false,
                    'tags'      => array(),
                );
            }
        }

        return $nodes;
    }

    /**
     * Given an array of service IDs, this returns the array of corresponding
     * Definition and Alias objects that those ids represent.
     *
     * @param string $serviceId The service id to resolve
     *
     * @return \Symfony\Component\DependencyInjection\Definition|\Symfony\Component\DependencyInjection\Alias
     */
    protected function resolveServiceDefinition($serviceId)
    {
        if ($this->container->hasDefinition($serviceId)) {
            return $this->container->getDefinition($serviceId);
        }

        // Some service IDs don't have a Definition, they're simply an Alias
        if ($this->container->hasAlias($serviceId)) {
            return $this->container->getAlias($serviceId);
        }

        // the service has been injected in some special way, just return the service
        return $this->container->get($serviceId);
    }
}
