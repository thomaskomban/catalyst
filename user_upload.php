<?php
$csvFile = array_slice($argv, -1, 1);

$parsed = false;
$createTable = false;
$dryRun = false;
$DBuser = '';
$DBpwd = '';
$DBhost = '';
$help = false;

foreach($argv as $k => $directives){
    if ('--file' === $directives) {
        $parsed = true;
    } elseif ('--create_table' === $directives) {
        $createTable = true;
    } elseif ('--dry_run' === $directives && $parsed) {
        $dryRun = true;
    } elseif ('-u' === $directives) {
        $DBuser = $argv[$k + 1];
    } elseif ('-p' === $directives) {
        $DBpwd = $argv[$k + 1];
    } elseif ('-h' === $directives) {
        $DBhost = $argv[$k + 1];
    } elseif ('--help' === $directives) {
        $help = true;
    }
}


