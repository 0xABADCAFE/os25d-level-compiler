#!/usr/bin/php
<?php

/**
 * Level Compiler, main program.
 */
 
error_reporting(-1);

$aOpts = getopt('d:o:');

if (
  !isset($aOpts['d']) ||
  !isset($aOpts['o'])
) {
  echo "Usage makelevel.php -d <level directory> -o <output file>\n";
  exit();
}

$sLevelDir   = $aOpts['d'];
$sOutputFile = $aOpts['o'];

require_once 'src/include.php';

$oParser = ZoneParser::get()
  ->processDirectory($sLevelDir)
  ->runBoundingBoxAnalysis()
  ->runZoneConnectionAnalysis();

$aZones = $oParser->getZoneList();
foreach ($aZones as $oZone) {
  print($oZone->describe() . "\n");
}

$oMatrix = $oParser->getConnectionMatrix();

$oBinFile = new BinaryExportFile($sOutputFile);
foreach ($aZones as $oZone) {
  $oBinFile->export($oZone);
}
$oBinFile->export($oMatrix);
$oBinFile->close();
