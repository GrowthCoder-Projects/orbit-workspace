<?php

$phpBinary = 'C:\\Users\\ihsan\\AppData\\Local\\Programs\\FlyEnv-Data\\app\\php-8.5.7\\php.exe';
$artisanPath = 'C:\\www\\growthcoder-workspace\\artisan';
$logPath = 'C:\\www\\growthcoder-workspace\\storage\\logs\\test_backup.log';

$fullCommand = "\"{$phpBinary}\" \"{$artisanPath}\" backup:run --only-db --disable-notifications >> \"{$logPath}\" 2>&1";
$shellCommand = "start /B \"\" cmd /c \"{$fullCommand}\"";

echo 'Executing shell command: '.$shellCommand."\n";
pclose(popen($shellCommand, 'r'));

echo "Done. Waiting 5 seconds to let process run...\n";
sleep(5);

if (file_exists($logPath)) {
    echo "Log file created! Contents:\n";
    echo file_get_contents($logPath);
} else {
    echo "Log file NOT created.\n";
}
