<?php

use App\Models\Student;

$name1 = 'AGUILAR PONCE FERNANDA'; // From parent sheet extraction
$name2 = 'AGUILAR PONCE FERNANDA'; // From student sheet extraction

// Fetch all students to see what's really in the DB
$students = Student::where('name', 'LIKE', '%AGUILAR%')->get();

echo "Students in DB containing 'AGUILAR':\n";
foreach ($students as $s) {
    echo "- '{$s->name}' (length: ".strlen($s->name).", group: {$s->group_name})\n";
    $sim = 0;
    similar_text(strtoupper($name1), strtoupper($s->name), $sim);
    echo '  Similarity with exact string: '.$sim."%\n";
}

echo "\nTest exact search:\n";
$exact = Student::where('name', $name1)->first();
echo 'Exact match: '.($exact ? "FOUND ({$exact->id})" : 'NOT FOUND')."\n";
