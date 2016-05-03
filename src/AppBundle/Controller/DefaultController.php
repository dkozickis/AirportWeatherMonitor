<?php

namespace AppBundle\Controller;

use Ddeboer\DataImport\Reader\CsvReader;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\MonitoredAirport;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        return $this->render('AppBundle::leaflet.html.twig');
    }

    /**
     * @Route("/test")
     */
    public function testAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $airports = $em->getRepository('AppBundle:MonitoredAirport')->getSeasonActiveAirports();
        $airportsWeatherForView = $this->get('weather_processor');

        if (count($airports) > 0) {
            $airports = $airportsWeatherForView->getGeoJsonWeather($airports);
        }

        dump($airports);
    }

    /**
     * @Route("/weather/{alternate}", name="airport_json", defaults={"alternate" = 0})
     */
    public function jsonAction(Request $request, $alternate)
    {
        $em = $this->getDoctrine()->getManager();
        $airports = $em->getRepository('AppBundle:MonitoredAirport')->getSeasonActiveAirports($alternate);
        $airportsWeatherForView = $this->get('weather_processor');

        if (count($airports) > 0) {
            $airports = $airportsWeatherForView->getGeoJsonWeather($airports);
        }

        $response = new JsonResponse();
        $response->setData($airports);

        return $response;
    }

    /**
     * @Route("/write")
     */
    public function writeAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $masterAirports = $em->getRepository('AppBundle:AirportsMasterData');

        $file = new \SplFileObject($this->get('kernel')->getRootDir().'/../german_wings_stations.csv');
        $csvReader = new CsvReader($file);

        $csvReader->setHeaderRowNumber(0);

        foreach ($csvReader as $row) {
            $masterAirport = $masterAirports->findOneBy(
                array(
                    'airportIcao' => $row['station'],
                )
            );

            $airport = new MonitoredAirport();

            $airport->setMidWarningCeiling($row['2_ceil'])
                ->setHighWarningCeiling($row['3_ceil'])
                ->setMidWarningVis($row['2_vis'])
                ->setHighWarningVis($row['3_vis'])
                ->setHighWarningWind($row['3_wind'])
                ->setMidWarningWind($row['3_wind'])
                ->setActiveSummer($row['active_s'])
                ->setActiveWinter($row['active'])
                ->setAlternateSummer($row['alternate_s'])
                ->setAlternateWinter($row['alternate'])
                ->setAirportData($masterAirport);

            $em->persist($airport);
        }

        $em->flush();
    }
}
