<?php

/*
 * This file is part of the hydrometer public server project.
 *
 * @author Clemens Krack <info@clemenskrack.com>
 */

namespace App\Tests;

use App\Entity\DataPoint;
use Liip\TestFixturesBundle\Test\FixturesTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TiltApiControllerTest extends WebTestCase
{
    use FixturesTrait;

    private $fixtures;
    private $entityManager;

    public function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->fixtures = $this->loadFixtures([
            'App\DataFixtures\AppFixtures',
        ])->getReferenceRepository();

        self::ensureKernelShutdown();

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // doing this is recommended to avoid memory leaks
        $this->entityManager->close();
        $this->entityManager = null;
    }

    public function testTiltHTTPAPI()
    {
        $client = static::createClient();

        // send raw post request
        $crawler = $client->request(
            'POST',
            '/api/tilt/'.$this->fixtures->getReference('test-token')->getValue(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"SG":"1.129","Temp":"64.0","Color":"BLUE","Timepoint":"43075.91344087963","Beer":"","Comment":"1.08"}'
        );

        $this->assertResponseIsSuccessful();

        $datapoint = $this->entityManager
            ->getRepository(DataPoint::class)
            ->findOneBy([
                'hydrometer' => $this->fixtures->getReference('test-hydrometer'),
            ])
        ;

        $this->assertSame(64.0, $datapoint->getTemperature());
        $this->assertSame(1.129, $datapoint->getGravity());
    }
}
