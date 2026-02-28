<?php

namespace App;

final class Parser
{
    private const URL_PREFIX = 'https://stitcher.io';
    private const URL_PREFIX_LENGTH = 19;

    public function parse(string $inputPath, string $outputPath): void
    {
        $handle = fopen($inputPath, 'r');
        if ($handle === false) {
            return;
        }

        $visits = [];

        while (($line = fgets($handle)) !== false) {
            $lineLength = strlen($line);
            if ($lineLength < 27) {
                continue;
            }

            $eolLength = ($lineLength > 1 && $line[$lineLength - 2] === "\r") ? 2 : 1;
            $separatorPosition = $lineLength - (26 + $eolLength);
            if ($line[$separatorPosition] !== ',') {
                continue;
            }

            if (substr_compare($line, self::URL_PREFIX, 0, self::URL_PREFIX_LENGTH) !== 0) {
                continue;
            }

            $pathStart = self::URL_PREFIX_LENGTH;
            if ($pathStart >= $separatorPosition) {
                continue;
            }

            if ($line[$pathStart] !== '/') {
                $path = '/';
            } else {
                $queryStart = strpos($line, '?', $pathStart);
                $fragmentStart = strpos($line, '#', $pathStart);

                if ($queryStart === false || $queryStart > $separatorPosition) {
                    $queryStart = $separatorPosition;
                }

                if ($fragmentStart === false || $fragmentStart > $separatorPosition) {
                    $fragmentStart = $separatorPosition;
                }

                $pathEnd = min($queryStart, $fragmentStart);
                $path = substr($line, $pathStart, $pathEnd - $pathStart);
            }

            $date = substr($line, $separatorPosition + 1, 10);

            if (isset($visits[$path][$date])) {
                $visits[$path][$date]++;
            } else {
                $visits[$path][$date] = 1;
            }
        }

        fclose($handle);

        foreach ($visits as &$dailyVisits) {
            if (count($dailyVisits) > 1) {
                ksort($dailyVisits);
            }
        }
        unset($dailyVisits);

        $json = json_encode($visits, JSON_PRETTY_PRINT);

        if ($json === false) {
            return;
        }

        $json = str_replace("\n", PHP_EOL, $json);

        file_put_contents($outputPath, $json);
    }
}
