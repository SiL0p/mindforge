<?php

declare(strict_types=1);

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__) . '/vendor/autoload.php';

if (class_exists(Dotenv::class)) {
    (new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');
}

$path = $argv[1] ?? '/';
$runs = isset($argv[2]) ? max(1, (int) $argv[2]) : 10;

$kernel = new Kernel('dev', true);
$kernel->boot();

$times = [];
$peakBefore = memory_get_peak_usage(true);

for ($i = 0; $i < $runs; $i++) {
    $request = Request::create($path, 'GET', [], [], [], [
        'HTTP_HOST' => '127.0.0.1',
        'SERVER_PORT' => '8000',
        'REQUEST_SCHEME' => 'http',
    ]);

    $start = microtime(true);
    $response = $kernel->handle($request);
    $kernel->terminate($request, $response);
    $times[] = (microtime(true) - $start) * 1000;
}

$avg = array_sum($times) / count($times);
$min = min($times);
$max = max($times);
$peakAfter = memory_get_peak_usage(true);

$result = [
    'path' => $path,
    'runs' => $runs,
    'avg_ms' => round($avg, 2),
    'min_ms' => round($min, 2),
    'max_ms' => round($max, 2),
    'peak_memory_mb' => round($peakAfter / 1048576, 2),
    'peak_memory_delta_mb' => round(($peakAfter - $peakBefore) / 1048576, 2),
];

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
