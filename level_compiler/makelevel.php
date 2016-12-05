<?php

/**
 * Level Compiler, main program.
 */
 
error_reporting(-1);

$aOpts = getopt('d:o:');

if (!isset($aOpts['d'])) {
  echo "Usage makelevel.php -d <level directory> -o <output file>\n";
  exit();
}

$sLevelDir = trim($aOpts['d']);

require_once 'src/include.php';

$oParser = ZoneParser::get()
  ->processDirectory($sLevelDir)
  ->runBoundingBoxAnalysis()
  ->runZoneConnectionAnalysis();

$aZones = $oParser->getZoneList();
foreach ($aZones as $oZone) {
  print($oZone->describe() . "\n");
  print(bin2hex(BinaryExport::export($oZone)) . "\n");
}

$oMatrix = $oParser->getConnectionMatrix();
$oMatrix->normalise();

print_r($oMatrix);
print(bin2hex(BinaryExport::export($oMatrix)) . "\n");
