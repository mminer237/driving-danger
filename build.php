<?php
require_once(__DIR__.'/vendor/autoload.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;

chdir(__DIR__);

if (!is_dir('site'))
	mkdir('site');

if (!is_dir('site/data'))
	mkdir('site/data');

/* Download FHWA Traffic Volume Trends data */
const FHWA_URL = 'https://www.fhwa.dot.gov/policyinformation/travel_monitoring/tvt.cfm';
$fhwaPage = file_get_contents(FHWA_URL);
preg_match('/<a href="([^"]*?(\d\d[a-z]{3}tvt\.xlsx))/', $fhwaPage, $matches);
$tvtExcelFile = file_get_contents('https://www.fhwa.dot.gov'.$matches[1]);
if (empty($tvtExcelFile))
	throw new Exception("Error: FHWA Traffic Volume Trends not found", 1);
$tvtExcelPath = 'site/data/'.$matches[2];
file_put_contents($tvtExcelPath, $tvtExcelFile);

/* Parse FHWA Traffic Volume Trends data */
$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
$reader->setReadDataOnly(true);
$reader->setLoadSheetsOnly(['Page1', 'Page 4', 'Page 5', 'Page 6', 'Data']);
$tvtSpreadsheets = $reader->load($tvtExcelPath);
$date = $tvtSpreadsheets->getSheetByName('Page1')->getCellByColumnAndRow(5, 10)->getCalculatedValue();

/* Minify assets */
foreach (scandir('site/js') as $file) {
	if ($file !== '.' && $file !== '..') {
		echo "Minifying js/$file...\n";
		shell_exec("terser site/js/$file -c -m -o site/js/".substr($file, 0, -2).'terser.js --source-map "root=\'https://drivingdanger.com/js/\',url=\''.substr($file, 0, -2).'terser.js.map\'"');
	}
}
foreach (scandir('site/css') as $file) {
	if ($file !== '.' && $file !== '..') {
		echo "Minifying css/$file...\n";
		shell_exec("csso site/css/$file --output site/css/".substr($file, 0, -3).'min.css --source-map file');
	}
}

?>