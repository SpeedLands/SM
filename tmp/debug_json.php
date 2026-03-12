<?php

require __DIR__.'/../vendor/autoload.php';

$json = file_get_contents('tmp/extracted_students.json');
echo 'JSON Length: '.strlen($json)."\n";
echo "JSON Content: $json\n";

$students = json_decode($json, true);
if ($students === null) {
    echo 'JSON Decode Error: '.json_last_error_msg()."\n";
} else {
    echo 'Students: '.count($students)."\n";
    print_r(array_keys($students));
}
