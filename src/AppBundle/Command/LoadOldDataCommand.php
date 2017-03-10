<?php

namespace AppBundle\Command;

use AppBundle\Entity\MonitoredAirport;
use Ddeboer\DataImport\Reader\CsvReader;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class LoadOldData. Not forward facing, will not test.
 *
 * @codeCoverageIgnore
 */
class LoadOldData extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('app:load-old-data')->setDescription('Loads data from old system from CSV');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();

        $masterAirports = $em->getRepository('AppBundle:AirportsMasterData');

        $file = new \SplFileObject($this->getContainer()->get('kernel')->getRootDir().'/../oldData.csv');
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
