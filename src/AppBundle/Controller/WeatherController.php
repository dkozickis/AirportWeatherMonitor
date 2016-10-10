<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class WeatherController.
 *
 * @Route("/weather")
 */
class WeatherController extends Controller
{
    /**
     * @param $alternate
     *
     * @return JsonResponse
     *
     * @throws \Exception
     *
     * @Route("/get/json/actual/{alternate}", name="weather_json_actual", defaults={"alternate" = 0})
     */
    public function jsonWeatherAction($alternate)
    {
        $em = $this->getDoctrine()->getManager();
        $weatherProcessor = $this->get('weather_processor');
        $weatherHelper = $this->get('weather_helper');
        $season = $weatherHelper->getDateSeason();

        $airportsQuery = $em->getRepository('AppBundle:MonitoredAirport')->getSeasonActiveAirports($alternate, $season);
        $airports = $airportsQuery->getResult();
        $airports = $weatherHelper->airportsObjectToArray($airports);

        if (count($airports) > 0) {
            $airports = $weatherProcessor->getGeoJsonWeather($airports);
        }

        $response = new JsonResponse();
        $response->setData($airports);

        return $response;
    }

    /**
     * @return JsonResponse
     *
     * @throws \Exception
     *
     * @Route("/get/json/old", name="weather_json_old")
     */
    public function jsonOldWeatherAirportsAction()
    {
        $em = $this->getDoctrine()->getManager();
        $weatherHelper = $this->get('weather_helper');
        $season = $weatherHelper->getDateSeason();
        $referenceTime = $weatherHelper->getReferenceTime(60);

        $airportsQuery = $em->getRepository('AppBundle:MonitoredAirport')->getAirportsWithOldMetar(
            $season,
            $referenceTime
        );

        $airports = $airportsQuery->getResult();
        $airports = $weatherHelper->airportsObjectToArray($airports);

        $airportArray = array_keys($airports);

        $response = new JsonResponse();
        $response->setData($airportArray);

        return $response;
    }
}
