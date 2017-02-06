<?php
/**
 * Created by PhpStorm.
 * User: Denis
 * Date: 01/02/16
 * Time: 15:05.
 */
namespace AppBundle\Services;

class WeatherProvider
{
    private $server;

    public function __construct($server)
    {
        $this->server = $server;
    }

    public function getWeather($airports)
    {
        $weatherData = json_decode(
            file_get_contents(rtrim($this->server, '/').'/weather?airports='.implode(',', array_values($airports))),
            true
        );

        return $weatherData;
    }
}
