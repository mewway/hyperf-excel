<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel;

interface Exporter
{
    /**
     * @param object      $export
     * @param null|string $fileName
     * @param string      $writerType
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download($export, string $fileName, string $writerType = null, array $headers = []);

    /**
     * @param object $export
     * @param string $writerType
     * @param mixed  $diskOptions
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     *
     * @return bool
     */
    public function store($export, string $filePath, string $disk = null, string $writerType = null, $diskOptions = []);

    /**
     * @param object $export
     * @param string $writerType
     * @param mixed  $diskOptions
     *
     * @return Huanhyperf\Excel\PendingDispatch
     */
    public function queue($export, string $filePath, string $disk = null, string $writerType = null, $diskOptions = []);

    /**
     * @param object $export
     *
     * @return string
     */
    public function raw($export, string $writerType);
}
