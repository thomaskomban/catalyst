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

if (!$help) {
    try {
        if ($parsed == true) {
            if (file_exists("./{$csvFile[0]}")) {
                // $handle = fopen("./{$csvFile[0]}", "r");
                $row = 0;
                $records = [];
                if (($handle = fopen("./{$csvFile[0]}", "r")) !== FALSE) {
                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        $num = count($data);
                        if ($row === 0) {
                            $fields = $data;
                        } elseif ($row > 0) {
                            for ($c = 0; $c < $num; $c++) {
                                $records[$row - 1][$fields[$c]] = $data[$c];
                            }
                        }
                        $row++;
                    }
                    fclose($handle);
                    // print_r($fields);
                    // var_dump($records);

                } else {
                    throw new Exception("File does not exist");
                }
            } else {
                throw new Exception("File does not exist");
            }
        } elseif ($parsed == false) {
            throw new Exception('directive "--file" to enable parse file');
        }
    } catch (Exception $e) {
        echo $e->getMessage();
    }
} elseif ($help) {
    echo "\n    --file [csv file name] – this is the name of the CSV to be parsed
    --create_table – this will cause the MySQL users table to be built (and no further
                    action will be taken)
    --dry_run – this will be used with the --file directive in case we want to run the
            script but not insert into the DB. All other functions will be executed, but the
            database won't be altered
    -u – MySQL username
    -p – MySQL password
    -h – MySQL host
    \n";
}

