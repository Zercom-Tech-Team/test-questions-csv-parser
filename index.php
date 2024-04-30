<?php
require_once __DIR__ . "/HandleCsv.php";

use HandleCsv\HandleCsv;

$handleCsv = new HandleCsv("TestQuestions.csv");
$handleCsv->readCSVFile("lessons_ID");
