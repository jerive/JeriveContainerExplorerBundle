<?php

namespace Jerive\Bundle\ContainerExplorerBundle\DependencyInjection\Dumper;

use Symfony\Component\DependencyInjection\Dumper\Dumper;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Alias;

class JsonDumper extends Dumper
{
    public function dump(array $options = array())
    {
        $this->nodes = $this->findNodes();

        foreach ($this->container->getDefinitions() as $id => $definition) {
            if ($definition->isPublic()) {
                $this->nodes[$id]['children'] = array_merge(
                    $this->findEdges($id, $definition->getArguments(), true, ''),
                    $this->findEdges($id, $definition->getProperties(), false, '')
                );

                foreach ($definition->getMethodCalls() as $call) {
                    $this->nodes[$id]['children'] = array_merge(
                        $this->nodes[$id]['children'],
                        $this->findEdges($id, $call[1], false, $call[0])
                    );
                }
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
                $definition = $this->resolveServiceDefinition((string) $argument);
                if ($definition instanceof Definition) {
                    if ($definition->isPublic()) {
                        $edges[] = (string) $argument;
                    }
                }

                if (!$this->container->has((string) $argument)) {
                    $this->nodes[(string) $argument] = $name;
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
                    $nodes[$id] = array('name' => array(
                        'class'     => $definition->getClass(),
                        'public'    => $definition->isPublic(),
                        'abstract'  => $definition->isAbstract(),
                        'synthetic' => $definition->isSynthetic(),
                        'tags'      => array_keys($definition->getTags()),
                    ));
                }
            } else {
                $nodes[$id] = array('name' => array('class' => get_class($definition)));
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
