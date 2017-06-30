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

echo $oParser->getZoneSet()->describe(), "\n";

$oBinFile = new BinaryExportFile($sOutputFile);
$oBinFile
  ->export($oParser->getZoneSet())
  ->export($oParser->getConnectionMatrix())
  ->close();
