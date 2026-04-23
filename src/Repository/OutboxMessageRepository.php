<?php
/** @noinspection PhpMultipleClassDeclarationsInspection */

declare(strict_types=1);

namespace App\Repository;

use App\Entity\OutboxMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OutboxMessage>
 */
final class OutboxMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OutboxMessage::class);
    }

    /**
     * @return list<OutboxMessage>
     */
    public function findCreatedOrdered(int $limit = 100): array
    {
        /** @var list<OutboxMessage> $messages */
        $messages = $this->createQueryBuilder('outbox')->andWhere('outbox.status = :status')->setParameter(
                'status',
                OutboxMessage::STATUS_CREATED
            )->orderBy('outbox.createdAt', 'ASC')->setMaxResults($limit)->getQuery()->getResult();

        return $messages;
    }
}
