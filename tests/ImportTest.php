<?php

namespace HyperfTest;

use Huanhyperf\Excel\Excel;
use HyperfTest\Import\TestImport;

class ImportTest extends AbstractTestCase
{
    public function testImportCsv()
    {
        $import = new TestImport();
        $excel = $this->getExcel()->import($import, 'tests/FakeImportFiles/user_import.fake.csv', 'local', Excel::CSV);
        $this->assertInstanceOf(Excel::class, $excel);
    }

    public function getExcel(): Excel
    {
        return make(Excel::class);
    }
}