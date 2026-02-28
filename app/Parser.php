<?php

namespace App;

final class Parser
{
    private const PATH_START = 19;

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

            $pathStart = self::PATH_START;
            if ($pathStart >= $separatorPosition) {
                continue;
            }

            if ($line[$pathStart] !== '/') {
                $path = '/';
            } else {
                $pathLength = strcspn($line, '?#,', $pathStart);
                $path = substr($line, $pathStart, $pathLength);
            }

            $date = substr($line, $separatorPosition + 1, 10);
            $visits[$path][$date] = ($visits[$path][$date] ?? 0) + 1;
        }

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

        if (PHP_EOL !== "\n") {
            $json = str_replace("\n", PHP_EOL, $json);
        }

        file_put_contents($outputPath, $json);
    }
}
