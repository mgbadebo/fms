<?php

namespace App\Services\Label;

interface LabelPrinterInterface
{
    /**
     * Render the label template with the given data.
     *
     * @param string $templateBody Template body (ZPL, Blade, or RAW)
     * @param string $templateEngine Template engine type (ZPL, BLADE, RAW)
     * @param array $data Data to merge into template
     * @return string Rendered label string
     */
    public function renderTemplate(string $templateBody, string $templateEngine, array $data): string;

    /**
     * Send the rendered label to the printer.
     *
     * @param string $renderedLabel Rendered label string
     * @param string|null $printerName Printer name/identifier
     * @return bool Success status
     */
    public function print(string $renderedLabel, ?string $printerName = null): bool;
}

