<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel;

use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Cell\Cell as SpreadsheetCell;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** @mixin SpreadsheetCell */
class Cell
{
    use DelegatedMacroable;

    /**
     * @var SpreadsheetCell
     */
    private $cell;

    public function __construct(SpreadsheetCell $cell)
    {
        $this->cell = $cell;
    }

    /**
     * @return static
     */
    public static function make(Worksheet $worksheet, string $coordinate)
    {
        return new static($worksheet->getCell($coordinate));
    }

    public function getDelegate(): SpreadsheetCell
    {
        return $this->cell;
    }

    /**
     * @param $nullValue
     * @param $calculateFormulas
     * @param $formatData
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     *
     * @return null|mixed|string
     */
    public function getValue($nullValue = null, $calculateFormulas = false, $formatData = true)
    {
        $value = $nullValue;
        if (null !== $this->cell->getValue()) {
            if ($this->cell->getValue() instanceof RichText) {
                $value = $this->cell->getValue()->getPlainText();
            } elseif ($calculateFormulas) {
                try {
                    $value = $this->cell->getCalculatedValue();
                } catch (Exception $e) {
                    $value = $this->cell->getOldCalculatedValue();
                }
            } else {
                $value = $this->cell->getValue();
            }

            if ($formatData) {
                $style = $this->cell->getWorksheet()->getParent()->getCellXfByIndex($this->cell->getXfIndex());
                $value = NumberFormat::toFormattedString(
                    $value,
                    ($style && $style->getNumberFormat()) ? $style->getNumberFormat()->getFormatCode() : NumberFormat::FORMAT_GENERAL
                );
            }
        }

        return $value;
    }
}
