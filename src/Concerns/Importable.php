<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Concerns;

use Huanhyperf\Excel\Exceptions\NoFilePathGivenException;
use Huanhyperf\Excel\Importer;
use Hyperf\Utils\Collection;
use InvalidArgumentException;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Style\SymfonyStyle as OutputStyle;
use Symfony\Component\HttpFoundation\File\UploadedFile;

trait Importable
{
    /**
     * @var null|OutputStyle
     */
    protected $output;

    /**
     * @param null|string|UploadedFile $filePath
     *
     *@throws NoFilePathGivenException
     *
     * @return \Huanhyperf\Excel\Reader
     */
    public function import($filePath = null, string $disk = null, string $readerType = null)
    {
        $filePath = $this->getFilePath($filePath);

        return $this->getImporter()->import(
            $this,
            $filePath,
            $disk ?? $this->disk ?? null,
            $readerType ?? $this->readerType ?? null
        );
    }

    /**
     * @param null|string|UploadedFile $filePath
     *
     * @throws NoFilePathGivenException
     */
    public function toArray($filePath = null, string $disk = null, string $readerType = null): array
    {
        $filePath = $this->getFilePath($filePath);

        return $this->getImporter()->toArray(
            $this,
            $filePath,
            $disk ?? $this->disk ?? null,
            $readerType ?? $this->readerType ?? null
        );
    }

    /**
     * @param null|string|UploadedFile $filePath
     *
     * @throws NoFilePathGivenException
     */
    public function toCollection($filePath = null, string $disk = null, string $readerType = null): Collection
    {
        $filePath = $this->getFilePath($filePath);

        return $this->getImporter()->toCollection(
            $this,
            $filePath,
            $disk ?? $this->disk ?? null,
            $readerType ?? $this->readerType ?? null
        );
    }

    /**
     * @param null|string|UploadedFile $filePath
     *
     * @throws NoFilePathGivenException
     * @throws InvalidArgumentException
     */
    public function queue($filePath = null, string $disk = null, string $readerType = null)
    {
        if (! $this instanceof ShouldQueue) {
            throw new InvalidArgumentException('Importable should implement ShouldQueue to be queued.');
        }

        return $this->import($filePath, $disk, $readerType);
    }

    /**
     * @return $this
     */
    public function withOutput(OutputStyle $output)
    {
        $this->output = $output;

        return $this;
    }

    public function getConsoleOutput(): OutputStyle
    {
        if (! $this->output instanceof OutputStyle) {
            $this->output = new OutputStyle(new StringInput(''), new NullOutput());
        }

        return $this->output;
    }

    /**
     * @param null|string|UploadedFile $filePath
     *
     * @throws NoFilePathGivenException
     *
     * @return string|UploadedFile
     */
    private function getFilePath($filePath = null)
    {
        $filePath ??= $this->filePath ?? null;

        if (null === $filePath) {
            throw NoFilePathGivenException::import();
        }

        return $filePath;
    }

    private function getImporter(): Importer
    {
        return make(Importer::class);
    }
}
