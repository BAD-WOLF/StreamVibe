<?php

declare(strict_types=1);

namespace App\Tests\Helper;

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Trait for database operations in tests
 *
 * Provides common database setup and cleanup functionality
 * to reduce code duplication across test classes.
 */
trait DatabaseTestTrait
{
    private EntityManagerInterface $entityManager;

    /**
     * Clean all user data from database
     */
    private function cleanDatabase(): void
    {
        $purger = new ORMPurger($this->entityManager);

        $purger->purge();

        $this->entityManager->clear();
    }

    /**
     * Clean specific entity from database
     */
    private function cleanEntity(string $entityClass): void
    {
        $this->entityManager
            ->createQuery("DELETE FROM {$entityClass} e")
            ->execute();

        $this->entityManager->clear();
    }

    /**
     * Flush pending changes to database
     */
    private function flushDatabase(): void
    {
        $this->entityManager->flush();
    }

    /**
     * Clear entity manager cache
     */
    private function clearEntityManager(): void
    {
        $this->entityManager->clear();
    }

    /**
     * Get fresh instance of entity from database
     */
    private function refreshEntity(object $entity): object
    {
        $this->entityManager->refresh($entity);

        return $entity;
    }

    /**
     * Setup database for testing
     */
    private function setupTestDatabase(): void
    {
        $this->cleanDatabase();
        $this->clearEntityManager();
    }

    /**
     * Teardown database after testing
     */
    private function teardownTestDatabase(): void
    {
        if (isset($this->entityManager)) {
            $this->cleanDatabase();
            $this->entityManager->close();
        }
    }

    /**
     * Execute database operation in transaction
     */
    private function executeInTransaction(callable $operation): void
    {
        $this->entityManager->getConnection()->beginTransaction();

        try {
            $operation();
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();

            throw $e;
        }
    }

    /**
     * Count entities in database
     */
    private function countEntities(string $entityClass): int
    {
        return (int) $this->entityManager
            ->createQuery("SELECT COUNT(e) FROM {$entityClass} e")
            ->getSingleScalarResult();
    }

    /**
     * Verify database is empty for given entity
     */
    private function assertDatabaseIsEmpty(string $entityClass): void
    {
        $count = $this->countEntities($entityClass);

        $this->assertEquals(
            0,
            $count,
            "Database should be empty for {$entityClass}"
        );
    }

    /**
     * Verify entity count in database
     */
    private function assertEntityCount(
        string $entityClass,
        int $expectedCount
    ): void {
        $count = $this->countEntities($entityClass);

        $this->assertEquals(
            $expectedCount,
            $count,
            "Expected {$expectedCount} entities of {$entityClass}, found {$count}"
        );
    }
}

