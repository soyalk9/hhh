<?php
namespace Core {
    class Hestia
    {
        public function __construct(private array $config)
        {
        }

        public function hestia(string $command, ...$params): array
        {
            $sudo = rtrim($this->config['sudo_path'], '/');
            $bin = rtrim($this->config['hestia_bin'], '/');
            $escaped = array_map(static fn($p) => escapeshellarg((string)$p), $params);
            $cmd = sprintf('%s %s/%s %s 2>&1', escapeshellcmd($sudo), $bin, escapeshellcmd($command), implode(' ', $escaped));
            exec($cmd, $output, $code);
            $raw = implode("\n", $output);
            return [
                'success' => $code === 0,
                'raw' => $raw,
                'formatted' => $this->formatOutput($raw),
                'command' => $cmd,
                'exit_code' => $code,
            ];
        }

        private function formatOutput(string $raw): array|string
        {
            $trimmed = trim($raw);
            if ($trimmed === '') {
                return [];
            }
            $json = json_decode($trimmed, true);
            return json_last_error() === JSON_ERROR_NONE ? $json : $trimmed;
        }
    }
}

namespace {
    if (!function_exists('hestia')) {
        function hestia(string $command, ...$params): array
        {
            global $hestiaClient, $config;
            if (isset($hestiaClient) && $hestiaClient instanceof \Core\Hestia) {
                return $hestiaClient->hestia($command, ...$params);
            }
            $cfg = $config ?? (require __DIR__ . '/../config/app.php');
            $client = new \Core\Hestia($cfg);
            return $client->hestia($command, ...$params);
        }
    }
}
