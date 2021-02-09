<?php
$csvFile = array_slice($argv, -1, 1);

$parsed = false;
$createTable = false;
$dryRun = false;
$DBuser = '';
$DBpwd = '';
$DBhost = '';
$help = false;
$usersTable = 'CREATE TABLE users (
            id INT(6) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(30),
            surname VARCHAR(30),
            email VARCHAR(50) UNIQUE                    
        )';        
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
        if ($parsed) {
            if (file_exists("./{$csvFile[0]}")) {
                $row = 0;
                $records = [];
                if (($handle = fopen("./{$csvFile[0]}", "r")) !== FALSE) {
                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        $num = count($data);
                        if ($row === 0) {
                            foreach($data as $k => $v) { $fields[$k] = trim($v); };
                        } elseif ($row > 0) {
                            for ($c = 0; $c < $num; $c++) {
                                $records[$row - 1][$fields[$c]] = trim($data[$c]);
                            }
                        }
                        $row++;
                    }
                    fclose($handle);
                    if ($dryRun) {
                        print_r($fields);
                        echo "pre"; print_r($records); echo "pre";
                    } else {
                        if ($conn = dbConn($DBhost, $DBuser, $DBpwd)) {
                            $sql = "INSERT INTO users (" . implode(',', $fields) . ") VALUES ";
                            foreach($records as $record) { 
                                $s = ''; 
                                $err = false;                              
                                foreach($fields as $field) {
                                    // if (!($field === 'email' && preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i", $record[$field]))) {
                                    if (!filter_var($record[$field], FILTER_VALIDATE_EMAIL) && $field == 'email') {                                                                            
                                        $err = true;                                         
                                    } elseif ($field == 'name' || $fields == 'surname') {
                                        $record[$field] = ucwords($record[$field]);
                                    }
                                    $s .= "'" . addslashes($record[$field]) . "',";                                    
                                }
                                if ($err) {
                                    fwrite(STDOUT, $s . PHP_EOL);                                                                        
                                } else {
                                    $s = substr($s, 0, -1);
                                    $sql .= "({$s}),";                                    
                                }                               
                            }
                            fclose(STDOUT);
                            $sql = substr($sql, 0, -1);                            
                            if (mysqli_query($conn, $sql) === true) {
                                mysqli_close($conn);
                                throw new Exception('Records created');
                            } else {
                                throw new Exception($conn->error);
                            }
                        } else {
                            throw new Exception('Records not connected');
                        }
                    }
                } else {
                    throw new Exception("File does not exist");
                }
            } else {
                throw new Exception("File does not exist");
            }
        } 

        if ($createTable) {
            if ($conn = dbConn($DBhost, $DBuser, $DBpwd)) {
                
                if ($conn->query($usersTable) === true){
                    throw new Exception("'Users' table Created");
                } else {                    
                    $sql = "DROP TABLE IF EXISTS users";
                    if ($conn->query($sql)) {
                        if ($conn->query($usersTable) === true) {
                            throw new Exception("'Users' table rebuilt");
                        } else {
                            throw new Exception($conn->error);
                        }
                    } else {
                        throw new Exception($conn->error);
                    }
                }
            } else {
                throw new Exception('Database not connected');
            }
        }
    } catch (Exception $e) {
        echo $e->getMessage();
    }
} elseif ($help) {
    echo "\n    Syntax to call the file - php user_upload.php <directives> <csv filename with extension>

    --file [csv file name] – (syntax) php user_upload.php --file -u root -h localhost:3306 users.csv > output.txt
                             this is the name of the CSV to be parsed

    --create_table – (syntax) php user_upload.php --create_table -u root -h localhost:3306 users.csv 
                     this will cause the MySQL users table to be built (and no further action will be taken)

    --dry_run – (syntax) - php user_upload.php --file --dry_run users.csv 
                this will be used with the --file directive in case we want to run the
                script but not insert into the DB. All other functions will be executed, but the
                database won't be altered

    -u – MySQL username
    -p – MySQL password (no password, don't use -p)
    -h – MySQL hostname with port no eg.localhost:3306

    --help - (syntax) php user_upload.php --help users.csv
    \n";
}
// CLI: php user_upload.php --create_table -u root -h localhost:3306 users.csv
function dbConn($host, $uname, $pwd){ 
    @$mysqli = new mysqli($host, $uname, $pwd, 'catalyst');
    
    // Check connection
    if ($mysqli->connect_errno) {
        return false;
    } else {
        return $mysqli;
    }
}