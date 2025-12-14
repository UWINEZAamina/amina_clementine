<?php
declare(strict_types=1);

// CLI or browser-friendly script to execute the SQL in schema.sql

$isCli = PHP_SAPI === 'cli';

function out(string $s = ''): void
{
    global $isCli;
    if ($isCli) {
        echo $s . PHP_EOL;
    } else {
        echo htmlspecialchars($s) . "<br>\n";
    }
}

// include DB bootstrap (uses get_pdo())
$configPath = __DIR__ . '/../includes/config.php';
if (!file_exists($configPath)) {
    out('Could not find includes/config.php at ' . $configPath);
    exit(1);
}
require_once $configPath;

$schemaFile = __DIR__ . '/schema.sql';
if (!file_exists($schemaFile)) {
    out('schema.sql not found at ' . $schemaFile);
    exit(1);
}

$sql = file_get_contents($schemaFile);
if ($sql === false) {
    out('Failed to read schema.sql');
    exit(1);
}

// Remove MySQL style comments and split statements by semicolon.
// This is a simple splitter suitable for schema files without complex delimiter usage.
$lines = preg_split("/\r?\n/", $sql);
$clean = [];
foreach ($lines as $line) {
    $trim = trim($line);
    if ($trim === '' || strpos($trim, '--') === 0 || strpos($trim, '#') === 0) {
        continue;
    }
    $clean[] = $line;
}

$sql = implode("\n", $clean);
$statements = preg_split('/;\s*\n/', $sql);

$pdo = null;
try {
    $pdo = get_pdo();
} catch (Throwable $e) {
    out('Failed to create PDO connection. Check your DB settings.');
    exit(1);
}

$executed = 0;
$errors = [];

foreach ($statements as $stmt) {
    $stmt = trim($stmt);
    if ($stmt === '') {
        continue;
    }

    try {
        $pdo->exec($stmt);
        $executed++;
    } catch (PDOException $e) {
        $errors[] = $e->getMessage();
    }
}

out(sprintf('Executed %d statements.', $executed));
if (!empty($errors)) {
    out('Encountered ' . count($errors) . ' errors:');
    foreach ($errors as $err) {
        out(' - ' . $err);
    }
    exit(1);
}

out('Schema applied successfully.');

if (!$isCli) {
    echo '<p><a href="../public/index.php">Return to site</a></p>';
}
