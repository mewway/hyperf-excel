<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Transactions;

use Hyperf\Database\ConnectionInterface;

class DbTransactionHandler implements TransactionHandler
{
    /**
     * @var ConnectionInterface
     */
    private $connection;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @throws \Throwable
     *
     * @return mixed
     */
    public function __invoke(callable $callback)
    {
        return $this->connection->transaction($callback);
    }
}
