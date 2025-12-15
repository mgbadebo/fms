<?php

namespace App\Services\Label;

use Illuminate\Support\Facades\View;

class MockLabelPrinterService implements LabelPrinterInterface
{
    /**
     * Render the label template with the given data.
     */
    public function renderTemplate(string $templateBody, string $templateEngine, array $data): string
    {
        return match ($templateEngine) {
            'BLADE' => $this->renderBladeTemplate($templateBody, $data),
            'ZPL' => $this->renderZplTemplate($templateBody, $data),
            'RAW' => $this->renderRawTemplate($templateBody, $data),
            default => $this->renderRawTemplate($templateBody, $data),
        };
    }

    /**
     * Render Blade template.
     */
    protected function renderBladeTemplate(string $templateBody, array $data): string
    {
        // Create a temporary view file
        $tempPath = storage_path('app/temp_label_' . uniqid() . '.blade.php');
        file_put_contents($tempPath, $templateBody);

        try {
            $rendered = View::file($tempPath, $data)->render();
        } finally {
            @unlink($tempPath);
        }

        return $rendered;
    }

    /**
     * Render ZPL template (simple placeholder replacement for now).
     */
    protected function renderZplTemplate(string $templateBody, array $data): string
    {
        return $this->renderRawTemplate($templateBody, $data);
    }

    /**
     * Render raw template with simple placeholder replacement.
     */
    protected function renderRawTemplate(string $templateBody, array $data): string
    {
        $rendered = $templateBody;

        foreach ($data as $key => $value) {
            $rendered = str_replace(
                ['{{' . $key . '}}', '{{ $' . $key . ' }}', '{{' . $key . '}}'],
                (string) $value,
                $rendered
            );
        }

        return $rendered;
    }

    /**
     * Mock print - just returns true without actually printing.
     */
    public function print(string $renderedLabel, ?string $printerName = null): bool
    {
        // In a real implementation, this would send to the printer
        // For now, we just log it or return success
        \Log::info('Mock print', [
            'printer' => $printerName,
            'label_length' => strlen($renderedLabel),
        ]);

        return true;
    }
}

