<?php

namespace App\Services\Scale;

class MockScaleService implements ScaleServiceInterface
{
    /**
     * Read weight from the mock scale device.
     * Simulates reading weight by generating random values.
     */
    public function readWeight(array $config): array
    {
        // Simulate reading delay
        usleep(500000); // 0.5 seconds

        // Generate mock weight values
        $gross = round(rand(1000, 5000) / 100, 2); // 10.00 to 50.00 kg
        $tare = round(rand(50, 200) / 100, 2); // 0.50 to 2.00 kg
        $net = round($gross - $tare, 2);

        return [
            'gross' => $gross,
            'tare' => $tare,
            'net' => $net,
            'unit' => $config['unit'] ?? 'kg',
        ];
    }

    /**
     * Mock scale is always "connected".
     */
    public function isConnected(array $config): bool
    {
        return true;
    }
}

