<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Concerns;

use Huanhyperf\Excel\Exceptions\NoFilenameGivenException;
use Huanhyperf\Excel\Exceptions\NoFilePathGivenException;
use Huanhyperf\Excel\Exporter;
use Hyperf\HttpServer\Request;
use Hyperf\HttpServer\Response;
use Huanhyperf\Excel\PendingDispatch;

trait Exportable
{
    /**
     * @param string $fileName
     * @param array  $headers
     *
     * @throws NoFilenameGivenException
     *
     * @return Response|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download(string $fileName = null, string $writerType = null, array $headers = null)
    {
        $headers ??= $this->headers ?? [];
        $fileName ??= $this->fileName ?? null;
        $writerType ??= $this->writerType ?? null;

        if (null === $fileName) {
            throw new NoFilenameGivenException();
        }

        return $this->getExporter()->download($this, $fileName, $writerType, $headers);
    }

    /**
     * @param string $filePath
     * @param mixed  $diskOptions
     *
     * @throws NoFilePathGivenException
     *
     * @return bool|PendingDispatch
     */
    public function store(string $filePath = null, string $disk = null, string $writerType = null, $diskOptions = [])
    {
        $filePath ??= $this->filePath ?? null;

        if (null === $filePath) {
            throw NoFilePathGivenException::export();
        }

        return $this->getExporter()->store(
            $this,
            $filePath,
            $disk ?? $this->disk ?? null,
            $writerType ?? $this->writerType ?? null,
            $diskOptions ?: $this->diskOptions ?? []
        );
    }

    /**
     * @param mixed $diskOptions
     *
     * @throws NoFilePathGivenException
     *
     * @return PendingDispatch
     */
    public function queue(string $filePath = null, string $disk = null, string $writerType = null, $diskOptions = [])
    {
        $filePath ??= $this->filePath ?? null;

        if (null === $filePath) {
            throw NoFilePathGivenException::export();
        }

        return $this->getExporter()->queue(
            $this,
            $filePath,
            $disk ?? $this->disk ?? null,
            $writerType ?? $this->writerType ?? null,
            $diskOptions ?: $this->diskOptions ?? []
        );
    }

    /**
     * @param null|string $writerType
     *
     * @return string
     */
    public function raw($writerType = null)
    {
        $writerType ??= $this->writerType ?? null;

        return $this->getExporter()->raw($this, $writerType);
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param Request $request
     *
     * @throws NoFilenameGivenException
     *
     * @return Response
     */
    public function toResponse($request)
    {
        return $this->download();
    }

    private function getExporter(): Exporter
    {
        return make(Exporter::class);
    }
}
