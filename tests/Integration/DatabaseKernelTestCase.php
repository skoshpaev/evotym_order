<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

abstract class DatabaseKernelTestCase extends KernelTestCase
{
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel([
            'environment' => 'test',
            'debug' => false,
        ]);

        /** @var ManagerRegistry $doctrine */
        $doctrine = static::getContainer()->get(ManagerRegistry::class);
        $this->entityManager = $doctrine->getManager();

        $this->recreateSchema();
        $this->resetTransports();
    }

    protected function tearDown(): void
    {
        if (isset($this->entityManager)) {
            $this->entityManager->clear();
            $this->entityManager->getConnection()->close();
        }

        parent::tearDown();
    }

    protected function getTransport(string $name): InMemoryTransport
    {
        $transport = static::getContainer()->get(sprintf('messenger.transport.%s', $name));

        \assert($transport instanceof InMemoryTransport);

        return $transport;
    }

    /**
     * @return list<string>
     */
    protected function transportNames(): array
    {
        return [];
    }

    private function recreateSchema(): void
    {
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->entityManager);

        if ($metadata === []) {
            return;
        }

        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    private function resetTransports(): void
    {
        foreach ($this->transportNames() as $transportName) {
            $this->getTransport($transportName)->reset();
        }
    }
}
