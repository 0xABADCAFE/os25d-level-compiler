<?php

/**
 * ZoneParser. Main entrypoint for loading, validating and normalising zone editor data.
 */
class ZoneParser {

  private
    /** @var ILog $oLog */
    $oLog              = null,
    
    /** @var Zone[] $aZones */
    $aZones            = null,
    
    /** @var ZoneDataValidator[] $aZoneValidators */
    $aZoneValidators   = null,
    
    /** @var ConnectionMatrix $oConnectionMatrix */
    $oConnectionMatrix = null
  ;
  
  public static function get() {
    return new self;
  }

  protected function __construct() {
    $this->oLog = new SimpleLog();
    $this->aZoneValidators = [
      new ZoneSetHeaderValidator($this->oLog),
      new ZoneSetDefinitionValidator($this->oLog),
      new ZoneSetGeometryValidator($this->oLog)
    ];
    $this->oLog->debug("ZoneParser instantiated");
  }

  /** @return ConnectionMatrix */
  public function getConnectionMatrix() {
    return $this->oConnectionMatrix;
  }

  /** @return Zone[] */
  public function &getZoneList() {
    return $this->aZones;
  }

  /**
   * Attempts to load and parse thhe z_*.json zone files in the specified directory
   *
   * @param string $sDirname 
   */
  public function processDirectory($sDirname) {
    $this->aZones = [];
    $aZoneFiles   = $this->getDirectoryList($sDirname);
    foreach ($aZoneFiles as $sZoneFile) {
      try {
        $aZones = $this->loadZones($sZoneFile);
        foreach ($aZones as $oZone) {
          $sIdent = $oZone->getIdent();
          if (!isset($this->aZones[$sIdent])) {
            $this->aZones[$sIdent] = $oZone;
          } else {
            $this->oLog->warn("Degenerate or duplicate zone in " . $sZoneFile);
          }
        }
      } catch (Exception $e) {
        $this->oLog->warn(
          "Unable to load zone file " . $sZoneFile .
          " skipping\n\t" . get_class($e) . " : " . $e->getMessage()
        );
      }
    }
    return $this;
  }

  /**
   * Runs an analysis on the loaded zones to find those that may share an edge. This is only possible if
   * the bounding box of a pair of zones are in contact, otherwise we can assume no edge sharing is possible.
   *
   * Each Zone is tested against every Zone that comes after it, rather than every other Zone. This is
   * because finding that Zone A contacts Zone B means we can skip testing whether zone B contacts Zone A as
   * this does not tell us anything new. Overall, this reduces the total comparisons by a factor of 2.
   *
   * All the Zones that are found that contact the Zone under iteration are added to it for the subsequent
   * edge share detection stage.
   */
  public function runBoundingBoxAnalysis() {
    $iNumZones = count($this->aZones);

    $this->oLog->info("Beginning Bounding Box Analysis for $iNumZones Zones");

    for ($i = 0; $i < $iNumZones; $i++) {
      for ($j = $i + 1; $j < $iNumZones; $j++) {
        $oZoneA = $this->aZones[$i];
        $oZoneB = $this->aZones[$j];
        if (
          $oZoneA
            ->getBoundingBox()
            ->contacts($oZoneB->getBoundingBox())
        ) {
          $this->oLog->debug("Zone {$oZoneA->getRuntimeId()} boundary contacts with Zone {$oZoneB->getRuntimeId()} boundary");
          $oZoneA->addZoneForContactTest($oZoneB);
        }
      }
    }
    $this->oLog->info("Completed Bounding Box Analysis");
    return $this;
  }

  /**
   * Runs an analysis on the loaded Zones to find those that are connected through a shared edge.
   *
   * For each Zone, we obtain the set of potentially connected Zones determined in the bounding box stage
   * and compare each edge definition in the Zone under iteration with the edges in the Zones being tested.
   * An edge is shared if they pass through the same pair of coordinates in antiparallel directions; i.e
   * if Zone A has Edge 1 as points P2 -> P1 and Zone B has Edge 3 P1 -> P2 then the edge is considered to
   * be shared and the Zones are connected as part of a larger structure.
   *
   * The test locates the relevant Edge in each Zone and records both in the ConnectionMatrix
   */

  public function runZoneConnectionAnalysis() {

    $this->oLog->info("Beginning Zone Connection Analysis");

    $this->oConnectionMatrix = new ConnectionMatrix(count($this->aZones));
    foreach ($this->aZones as $oZoneA) {
      $aEdgesA = $oZoneA->getEdgesRev();
      foreach ($oZoneA->getZonesForContactTest() as $oZoneB) {
        $aEdgesB = $oZoneB->getEdges();
        foreach ($aEdgesA as $sKey => $iEdgeA) {
          if (isset($aEdgesB[$sKey])) {
            $iEdgeB = $aEdgesB[$sKey];
            $this->oLog->debug("Zone {$oZoneA->getRuntimeId()} shares edge $iEdgeA with Zone {$oZoneB->getRuntimeId()} edge $iEdgeB");
            $this->oConnectionMatrix->addConnection(
              $oZoneA->getRuntimeId(),
              $oZoneB->getRuntimeId(),
              $iEdgeA
            );
            $this->oConnectionMatrix->addConnection(
              $oZoneB->getRuntimeId(),
              $oZoneA->getRuntimeId(),
              $iEdgeB
            );
          }
        }
      }    
    }
    $this->oConnectionMatrix->normalise();
    $this->oLog->info("Completed Zone Connection Analysis");
    return $this;
  }

  private function loadZones($sZoneFile) {
    $oZoneData = null;
    $aZones    = [];
    if (
      !file_exists($sZoneFile) ||
      !is_readable($sZoneFile) ||
      !($oZoneData = json_decode(file_get_contents($sZoneFile)))
    ) {
      throw new IOReadException($sZoneFile);
    }
    foreach ($this->aZoneValidators as $oZoneValidator) {
      $oZoneValidator->validate($oZoneData);
    }
    foreach ($oZoneData->zoneList as $oSingleZone) {
      $aZones[] = new Zone($oSingleZone);
    }
    return $aZones;
  }

  private function getDirectoryList($sDirname) {
    $sDirname = rtrim($sDirname, '/');
    if (
      !is_dir($sDirname) ||
      !is_readable($sDirname)
    ) {
      throw new IOReadException($sDirname);
    }
    
    $aFiles = glob($sDirname . '/z_*.json');
    sort($aFiles);
    return $aFiles;
  }

}
