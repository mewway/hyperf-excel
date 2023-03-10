<?php

namespace HyperfTest;

use Huanhyperf\Excel\Excel;
use HyperfTest\Import\TestImport;
use HyperfTest\Import\TestImportCsv;
use PhpOffice\PhpSpreadsheet\Reader\Csv;

class ImportTest extends AbstractTestCase
{
    public function testImportCsv()
    {
        $import = new TestImport();
        $excel = $this->getExcel()->import($import, 'tests/FakeImportFiles/user_import.fake.csv', 'local', Excel::CSV);
        $this->assertInstanceOf(Excel::class, $excel);
    }

    public function testImportCsvSingleLineOver512KB()
    {
        $import = new TestImport();
        $excel = $this->getExcel()->import($import, 'tests/FakeImportFiles/csv_over_512kb_single_line.csv', 'local', Excel::CSV);
        $this->assertInstanceOf(Excel::class, $excel);
    }

    public function testImportCsvSingleLineOver1M()
    {
        $import = new TestImportCsv();
        $excel = $this->getExcel()->toArray($import, 'tests/FakeImportFiles/csv_over_1M_single_line.csv', 'local', Excel::CSV);
        $this->assertIsArray($excel);
        var_dump($excel);
    }

    public function testFGetCsv()
    {
        $file = 'tests/FakeImportFiles/csv_over_1M_single_line.csv';
        $resource = fopen($file, 'r');
        $this->assertNotEmpty($resource);
        $headline = fgetcsv($resource, 0);
        $contentLine = fgetcsv($resource, 0);
        $this->assertIsArray($headline);
        $this->assertIsArray($contentLine);
        var_dump($contentLine);
    }

    public function testCsv()
    {
        $csv = new Csv();
        $file = 'tests/FakeImportFiles/csv_over_1M_single_line.csv';
        $resp = $csv->loadIntoExisting($file);
        $this->assertIsArray($resp);
        var_dump($resp);
    }

    public function getExcel(): Excel
    {
        return make(Excel::class);
    }
}