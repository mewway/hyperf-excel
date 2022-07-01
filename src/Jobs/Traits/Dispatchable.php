<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Jobs\Traits;

use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\AsyncQueue\Driver\DriverInterface;
use Hyperf\AsyncQueue\JobInterface;

/**
 * @property string $queue
 * @property int    $delay
 */
trait Dispatchable
{
    public static function dispatch(...$arguments)
    {
        /**
         * @var Dispatchable|JobInterface $job
         */
        $job = new static(...$arguments);
        $driver = self::getQueueDriver($job->getQueueName());

        return $driver->push($job, $job->getDelay());
    }

    public static function dispatchNow(...$arguments)
    {
        /**
         * @var Dispatchable|JobInterface $job
         */
        $job = new static(...$arguments);
        $driver = self::getQueueDriver($job->getQueueName());

        return $driver->push($job);
    }

    protected function getQueueName()
    {
        return $this->queue;
    }

    protected function getDelay()
    {
        return $this->delay;
    }

    protected static function getQueueDriver(?string $queueName = null): DriverInterface
    {
        self::getQueueConfig($queueName);

        return make(DriverFactory::class)->get($queueName);
    }

    private static function getQueueConfig(?string &$queueName = null): array
    {
        $queueName ??= 'default';
        $config = config('async_queue.' . $queueName, []);
        if (empty($config) || ! isset($config['channel'])) {
            throw new \LogicException(sprintf('queue %s or its channel not exists', $queueName), -1);
        }

        return $config;
    }
}
