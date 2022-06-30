<?php

namespace Huanhyperf\Excel\Concerns;

use Symfony\Component\Console\Style\SymfonyStyle as OutputStyle;

interface WithProgressBar
{
    /**
     * @return OutputStyle
     */
    public function getConsoleOutput(): OutputStyle;
}
