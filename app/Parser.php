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
            $line = rtrim($line, "\r\n");

            $parsedVisit = $this->parseVisitLine($line);

            if ($parsedVisit === null) {
                continue;
            }

            [$path, $date] = $parsedVisit;
            $visits[$path][$date] = ($visits[$path][$date] ?? 0) + 1;
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

    private function parseVisitLine(string $line): ?array
    {
        if ($line === '') {
            return null;
        }

        $separatorPosition = strrpos($line, ',');

        if ($separatorPosition === false) {
            return null;
        }

        $url = substr($line, 0, $separatorPosition);
        $timestamp = substr($line, $separatorPosition + 1);

        if ($timestamp === '') {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return null;
        }

        $date = substr($timestamp, 0, 10);

        if (strlen($date) !== 10) {
            return null;
        }

        return [$path, $date];
    }
}
