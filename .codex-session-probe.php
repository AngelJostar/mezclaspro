<?php

declare(strict_types=1);

if ($argc < 4) {
    fwrite(STDERR, "Usage: php .codex-session-probe.php <jsonl> <start-iso> <end-iso>\n");
    exit(1);
}

$path = $argv[1];
$start = strtotime($argv[2]);
$end = strtotime($argv[3]);

if ($start === false || $end === false || ! is_file($path)) {
    fwrite(STDERR, "Invalid path or date range.\n");
    exit(1);
}

$handle = fopen($path, 'rb');
$size = filesize($path);

if ($handle === false || $size === false) {
    fwrite(STDERR, "Unable to open session log.\n");
    exit(1);
}

function nextRecordAt($handle, int $offset, int $size): ?array
{
    if ($offset >= $size) {
        return null;
    }

    fseek($handle, max(0, $offset));
    if ($offset > 0) {
        fgets($handle);
    }

    for ($attempt = 0; $attempt < 200 && ! feof($handle); $attempt++) {
        $position = ftell($handle);
        $line = fgets($handle);
        if ($line === false) {
            return null;
        }

        $record = json_decode($line, true);
        $timestamp = isset($record['timestamp']) ? strtotime((string) $record['timestamp']) : false;
        if (is_array($record) && $timestamp !== false) {
            return [
                'position' => $position,
                'next' => ftell($handle),
                'timestamp' => $timestamp,
                'record' => $record,
            ];
        }
    }

    return null;
}

$low = 0;
$high = $size;

for ($iteration = 0; $iteration < 64 && $low < $high; $iteration++) {
    $middle = intdiv($low + $high, 2);
    $probe = nextRecordAt($handle, $middle, $size);

    if ($probe === null) {
        $high = $middle;
        continue;
    }

    if ($probe['timestamp'] < $start) {
        $low = max($middle + 1, $probe['next']);
    } else {
        $high = $middle;
    }
}

$scanStart = max(0, $low - 16 * 1024 * 1024);
fseek($handle, $scanStart);
if ($scanStart > 0) {
    fgets($handle);
}

$found = 0;
while (! feof($handle)) {
    $position = ftell($handle);
    $line = fgets($handle);
    if ($line === false) {
        break;
    }

    $record = json_decode($line, true);
    if (! is_array($record) || ! isset($record['timestamp'])) {
        continue;
    }

    $timestamp = strtotime((string) $record['timestamp']);
    if ($timestamp === false || $timestamp < $start) {
        continue;
    }
    if ($timestamp > $end) {
        break;
    }

    $type = (string) ($record['type'] ?? '');
    $payload = is_array($record['payload'] ?? null) ? $record['payload'] : [];
    $payloadType = (string) ($payload['type'] ?? '');
    $name = (string) ($payload['name'] ?? $payload['tool_name'] ?? '');
    $summary = '';

    foreach (['message', 'status', 'call_id', 'turn_id', 'id'] as $key) {
        if (isset($payload[$key]) && is_scalar($payload[$key])) {
            $value = preg_replace('/\s+/', ' ', (string) $payload[$key]);
            $summary .= " {$key}=" . substr($value, 0, 180);
        }
    }

    $encoded = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($encoded !== false && preg_match('/checkpoint|snapshot|git|tree|patch_apply|apply_patch|exec_command|assistant_message|task_complete/i', $encoded)) {
        $summary .= ' raw=' . substr(preg_replace('/\s+/', ' ', $encoded), 0, 900);
    }

    printf(
        "%s\t%d\t%s\t%s\t%s%s\n",
        $record['timestamp'],
        $position,
        $type,
        $payloadType,
        $name,
        $summary
    );
    $found++;
}

fclose($handle);
fwrite(STDERR, "records={$found} start_offset={$low} size={$size}\n");
