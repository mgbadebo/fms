<?php

namespace App\Services\Scale;

interface ScaleServiceInterface
{
    /**
     * Read weight from the scale device.
     *
     * @param array $config Connection configuration
     * @return array ['gross' => float|null, 'tare' => float|null, 'net' => float, 'unit' => string]
     */
    public function readWeight(array $config): array;

    /**
     * Check if the scale is connected and ready.
     *
     * @param array $config Connection configuration
     * @return bool
     */
    public function isConnected(array $config): bool;
}

