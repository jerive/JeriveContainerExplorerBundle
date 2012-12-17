<?php

namespace Jerive\Bundle\ContainerExplorerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('JeriveContainerExplorerBundle:Default:index.html.twig', array('name' => $name));
    }
}
