<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace HyperfTest;

use Hyperf\Cache\Driver\CoroutineMemoryDriver;
use Hyperf\Cache\Driver\RedisDriver;
use Hyperf\Config\Config;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\ContainerInterface;
use Hyperf\Di\Container;
use Hyperf\Di\Definition\DefinitionSourceFactory;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Arr;
use Hyperf\Utils\Packer\PhpSerializerPacker;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class AbstractTestCase extends TestCase
{
    /**
     * @var ContainerInterface|\Mockery\Mock
     */
    protected $container;

    public function setUp(): void
    {
        ! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__));
        $container = \Mockery::mock(Container::class, [(new DefinitionSourceFactory(true))()])->makePartial();
        $this->container = $container;
        ApplicationContext::setContainer($this->container);
        $this->container->define(\Psr\Container\ContainerInterface::class, function () use ($container) {
            return $container;
        });
        $this->container->define(ConfigInterface::class, function () {
            $config = \Mockery::mock(Config::class);
            $config->shouldReceive('get')->andReturnUsing([$this, 'getFakeConfig']);

            return $config;
        });
    }

    public function tearDown(): void
    {
        \Mockery::close();
    }

    public function getFakeConfig(string $key, $default = null)
    {
        $fakeConfig = [
            'cache' => [
                'default' => [
                    'driver' => RedisDriver::class,
                    'packer' => PhpSerializerPacker::class,
                    'prefix' => 'c:',
                ],
                'memory' => [
                    'driver' => CoroutineMemoryDriver::class,
                    'packer' => PhpSerializerPacker::class,
                ],
            ],
            'file' => [
                'default' => 'local',
                'storage' => [
                    'local' => [
                        'driver' => \Hyperf\Filesystem\Adapter\LocalAdapterFactory::class,
                        'root' => BASE_PATH,
                    ],
                ],
            ],
        ];

        return Arr::get($fakeConfig, $key, $default);
    }
}
