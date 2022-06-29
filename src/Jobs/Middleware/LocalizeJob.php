<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Jobs\Middleware;

use Closure;
use Hyperf\Contract\TranslatorInterface;
use Hyperf\Utils\ApplicationContext;

class LocalizeJob
{
    /**
     * @var object
     */
    private $localizable;

    /**
     * LocalizeJob constructor.
     *
     * @param object $localizable
     */
    public function __construct($localizable)
    {
        $this->localizable = $localizable;
    }

    /**
     * Handles the job.
     *
     * @param mixed $job
     *
     * @return mixed
     */
    public function handle($job, Closure $next)
    {
        $locale = value(function () {
            return null;
        });

        return $this->withLocale($locale, function () use ($next, $job) {
            return $next($job);
        });
    }

    /**
     * Run the callback with the given locale.
     *
     * @param string   $locale
     * @param \Closure $callback
     *
     * @return mixed
     */
    public function withLocale($locale, $callback)
    {
        if (! $locale) {
            return $callback();
        }

        $translator = ApplicationContext::getContainer()->get(TranslatorInterface::class);
        $original = $translator->getLocale();

        try {
            $translator->setLocale($locale);

            return $callback();
        } finally {
            $translator->setLocale($original);
        }
    }
}
