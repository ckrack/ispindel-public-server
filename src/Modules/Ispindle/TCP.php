<?php

/*
 * This file is part of the hydrometer public server project.
 *
 * @author Clemens Krack <info@clemenskrack.com>
 */

namespace App\Modules\Ispindle;

use App\Entity\DataPoint;
use App\Entity\Fermentation;
use App\Entity\Hydrometer;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class TCP
{
    protected $logger;
    protected $em;

    /**
     * Use League\Container for auto-wiring dependencies into the controller.
     *
     * @param LoggerInterface $logger [description]
     */
    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger
    ) {
        $this->em = $em;
        $this->logger = $logger;
    }

    /**
     * Wake db connection up.
     */
    public function wakeupDb()
    {
        $connection = $this->em->getConnection();
        if (false === $connection->ping()) {
            $connection->close();
            $connection->connect();
        }
    }

    /**
     * Put the dbal connection to sleep.
     */
    public function sleepDb()
    {
        $connection = $this->em->getConnection();
        if (!$connection->isConnected()) {
            $connection->close();
        }
    }

    public function validateInput($input)
    {
        $input = trim($input);

        // first sign {
        if (0 === !mb_strpos($input, '{')) {
            $this->logger->error('First sign not {');

            return false;
        }

        // last sign }
        if (!mb_strpos($input, '}') === mb_strlen($input)) {
            $this->logger->error('Last sign not }');

            return false;
        }

        return true;
    }

    public function saveData($data, $hydrometer, $fermentation)
    {
        $hydrometer = $this->em->getRepository(Hydrometer::class)->find($hydrometer);

        // set the hydrometer name if specified
        if (isset($data['name'])) {
            $hydrometer->setName($data['name']);
        }

        // set the hydrometer id if specified
        if (isset($data['ID'])) {
            $hydrometer->setEspId($data['ID']);
        }

        $this->logger->debug('Spindel: Receive data for Hydrometer', [$hydrometer, $data, $fermentation]);

        $dataPoint = new DataPoint();

        unset($data['id'], $data['ID'], $data['token']);
        $dataPoint->import($data);

        if ($fermentation) {
            $fermentation = $this->em->getRepository(Fermentation::class)->find($fermentation);
            $dataPoint->setFermentation($fermentation);
        }

        $dataPoint->setHydrometer($hydrometer);

        $this->em->persist($hydrometer);
        $this->em->persist($dataPoint);

        $this->em->flush();
    }
}
