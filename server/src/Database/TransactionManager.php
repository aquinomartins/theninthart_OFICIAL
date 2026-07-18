<?php
declare(strict_types=1);

namespace Tna\Database;

final class TransactionManager
{
    public function __construct(private readonly ConnectionFactory $connectionFactory)
    {
    }

    /** @template T @param callable(\PDO):T $callback @return T */
    public function run(callable $callback): mixed
    {
        $pdo = $this->connectionFactory->getConnection();
        $pdo->beginTransaction();
        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (\Throwable $throwable) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $throwable;
        }
    }
}
