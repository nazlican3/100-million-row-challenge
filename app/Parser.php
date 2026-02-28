<?php

namespace App;

use Exception;

final class Parser
{
    public function parse(string $inputPath, string $outputPath): void
    {
        $handle = $this->openInputFile($inputPath);

        $visits = [];

        while (($line = fgets($handle)) !== false) {
            $separatorPosition = strrpos($line, ',');

            if ($separatorPosition === false) {
                continue;
            }

            $path = $this->extractPathFromLine($line, $separatorPosition);

            if ($path === null || $path === '') {
                continue;
            }

            $date = substr($line, $separatorPosition + 1, 10);

            if (! isset($date[9])) {
                continue;
            }

            if (isset($visits[$path][$date])) {
                $visits[$path][$date]++;
            } else {
                $visits[$path][$date] = 1;
            }
        }

        fclose($handle);

        foreach ($visits as &$dailyVisits) {
            ksort($dailyVisits);
        }

        unset($dailyVisits);

        $json = json_encode($visits, JSON_PRETTY_PRINT);

        if ($json === false) {
            throw new Exception('Failed to encode output JSON');
        }

        $json = str_replace("\n", PHP_EOL, $json);

        if (file_put_contents($outputPath, $json) === false) {
            throw new Exception("Unable to write output file: {$outputPath}");
        }
    }

    private function openInputFile(string $inputPath)
    {
        $handle = fopen($inputPath, 'r');

        if ($handle === false) {
            throw new Exception("Unable to open input file: {$inputPath}");
        }

        return $handle;
    }

    private function extractPathFromLine(string $line, int $separatorPosition): ?string
    {
        $schemeSeparatorPosition = strpos($line, '://');

        if ($schemeSeparatorPosition === false || $schemeSeparatorPosition >= $separatorPosition) {
            return null;
        }

        $pathStart = strpos($line, '/', $schemeSeparatorPosition + 3);

        if ($pathStart === false || $pathStart > $separatorPosition) {
            return '/';
        }

        $queryStart = strpos($line, '?', $pathStart);
        $fragmentStart = strpos($line, '#', $pathStart);

        if ($queryStart === false || $queryStart > $separatorPosition) {
            $queryStart = $separatorPosition;
        }

        if ($fragmentStart === false || $fragmentStart > $separatorPosition) {
            $fragmentStart = $separatorPosition;
        }

        $pathEnd = min($queryStart, $fragmentStart);

        return substr($line, $pathStart, $pathEnd - $pathStart);
    }
}
