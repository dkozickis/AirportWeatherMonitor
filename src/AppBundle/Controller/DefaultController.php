<?php

namespace AppBundle\Controller;

use AppBundle\Services\WeatherForView;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $airports = $em->getRepository('AppBundle:Airports')->getAirportsData();
        $airportsForView = new WeatherForView($airports, $em);

        dump($airportsForView->prepareForView());

        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', array(
            'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..'),
        ));
    }
}
