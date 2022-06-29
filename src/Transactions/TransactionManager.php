<?php

namespace Huanhyperf\Excel\Transactions;

use Hyperf\DbConnection\Db as DB;

class TransactionManager extends Manager
{
    /**
     * @return string
     */
    public function getDefaultDriver()
    {
        return config('excel.transactions.handler');
    }

    /**
     * @return NullTransactionHandler
     */
    public function createNullDriver()
    {
        return new NullTransactionHandler();
    }

    /**
     * @return DbTransactionHandler
     */
    public function createDbDriver()
    {
        return new DbTransactionHandler(
            DB::connection(config('excel.transactions.db.connection'))
        );
    }
}
