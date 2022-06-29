<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel;

use Huanhyperf\Excel\Concerns\ShouldQueue;
use Huanhyperf\Excel\Files\Filesystem;
use Huanhyperf\Excel\Files\TemporaryFile;
use Huanhyperf\Excel\Helpers\FileTypeDetector;
use Hyperf\Utils\Collection;
use PhpOffice\PhpSpreadsheet\Exception;

class Excel implements Exporter, Importer
{
    use RegistersCustomConcerns;

    public const XLSX = 'Xlsx';

    public const CSV = 'Csv';

    public const TSV = 'Csv';

    public const ODS = 'Ods';

    public const XLS = 'Xls';

    public const SLK = 'Slk';

    public const XML = 'Xml';

    public const GNUMERIC = 'Gnumeric';

    public const HTML = 'Html';

    public const MPDF = 'Mpdf';

    public const DOMPDF = 'Dompdf';

    public const TCPDF = 'Tcpdf';

    /**
     * @var Writer
     */
    protected $writer;

    /**
     * @var QueuedWriter
     */
    protected $queuedWriter;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var Reader
     */
    private $reader;

    public function __construct(
        Writer $writer,
        QueuedWriter $queuedWriter,
        Reader $reader,
        Filesystem $filesystem
    ) {
        $this->writer = $writer;
        $this->reader = $reader;
        $this->filesystem = $filesystem;
        $this->queuedWriter = $queuedWriter;
    }

    /**
     * {@inheritdoc}
     */
    public function download($export, string $fileName, string $writerType = null, array $headers = [])
    {
        return response()->download(
            $this->export($export, $fileName, $writerType)->getLocalPath(),
            $fileName,
            $headers
        )->deleteFileAfterSend(true);
    }

    /**
     * {@inheritdoc}
     */
    public function store($export, string $filePath, string $diskName = null, string $writerType = null, $diskOptions = [])
    {
        if ($export instanceof ShouldQueue) {
            return $this->queue($export, $filePath, $diskName, $writerType, $diskOptions);
        }

        $temporaryFile = $this->export($export, $filePath, $writerType);

        $exported = $this->filesystem->disk($diskName, $diskOptions)->copy(
            $temporaryFile,
            $filePath
        );

        $temporaryFile->delete();

        return $exported;
    }

    /**
     * {@inheritdoc}
     */
    public function queue($export, string $filePath, string $disk = null, string $writerType = null, $diskOptions = [])
    {
        $writerType = FileTypeDetector::detectStrict($filePath, $writerType);

        return $this->queuedWriter->store(
            $export,
            $filePath,
            $disk,
            $writerType,
            $diskOptions
        );
    }

    /**
     * {@inheritdoc}
     */
    public function raw($export, string $writerType)
    {
        $temporaryFile = $this->writer->export($export, $writerType);

        $contents = $temporaryFile->contents();
        $temporaryFile->delete();

        return $contents;
    }

    /**
     * {@inheritdoc}
     */
    public function import($import, $filePath, string $disk = null, string $readerType = null)
    {
        $readerType = FileTypeDetector::detect($filePath, $readerType);
        $response = $this->reader->read($import, $filePath, $readerType, $disk);

        // @todo support pending dispatch

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray($import, $filePath, string $disk = null, string $readerType = null): array
    {
        $readerType = FileTypeDetector::detect($filePath, $readerType);

        return $this->reader->toArray($import, $filePath, $readerType, $disk);
    }

    /**
     * {@inheritdoc}
     */
    public function toCollection($import, $filePath, string $disk = null, string $readerType = null): Collection
    {
        $readerType = FileTypeDetector::detect($filePath, $readerType);

        return $this->reader->toCollection($import, $filePath, $readerType, $disk);
    }

    /**
     * {@inheritdoc}
     */
    public function queueImport(ShouldQueue $import, $filePath, string $disk = null, string $readerType = null)
    {
        return $this->import($import, $filePath, $disk, $readerType);
    }

    /**
     * @param object      $export
     * @param null|string $fileName
     *
     * @throws Exceptions\NoTypeDetectedException
     * @throws Exception
     */
    protected function export($export, string $fileName, string $writerType = null): TemporaryFile
    {
        $writerType = FileTypeDetector::detectStrict($fileName, $writerType);

        return $this->writer->export($export, $writerType);
    }
}
