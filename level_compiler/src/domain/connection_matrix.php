<?php

/**
 * ConnectionMatrix class. This is a sparse matrix implementation that, for every zone, details the edges through which that zone connects
 * with any other in the map.
 *
 */
class ConnectionMatrix implements IBinaryExportable {

  private
    $iDimension   = 0,
    $aConnections = []
  ;  

  public function __construct($iDimension) {
    $this->iDimension = (int)$iDimension;
  }

  public function normalise() {
    ksort($this->aConnections);
  }
  
  /**
   * Obtain the zero-span encoded version of the data for use in the C/C++ engine:
   *
   * Assume an uncompressed representation of 1 byte per row/column intersection:
   * For each row:
   *   Read the next byte:
   *     If the first bit is 0, the entry represents a span of 1-128 empty intersections (1).
   *     If the first bit is 1, the entry represents an intersection, in which:
   *       The lower 4 bits encode the edge number, 1-16
   *       The next 3 bits are reserved (2)
   *
   * (1) If two successive bytes encode zero spans, the second byte represents 1-128 spans of empty intersections of length 128. This
   *     allows for an empty row (a zone with no connections) in a map with up to 2^14 Zones to be encoded as 2 bytes.
   * (2) Vis and/or AI crossing data to be defined.
   *
   * @return binary
   */
  public function getBinaryData() {
    return ''; // TODO
  }
  
  /** @return char[4] */
  public function getBinaryIdent() {
    return 'ZCMatrix';
  }
  
  public function getConnection($iFromZoneId, $iToZoneId) {
    return isset($this->aConnections[$iFromZoneId][$iToZoneId]) ?
      $this->aConnections[$iFromZoneId][$iToZoneId] :
      0;
  }

  public function addConnection($iFromZoneId, $iToZoneId, $iViaEdge) {
    if (isset($this->aConnections[$iFromZoneId][$iToZoneId])) {
      throw new IllegalSharedEdgeCountException();    
    }
    
    if (!isset($this->aConnections[$iFromZoneId])) {
      $this->aConnections[$iFromZoneId] = [$iToZoneId => $iViaEdge];
    } else {
      $this->aConnections[$iFromZoneId][$iToZoneId] = $iViaEdge;    
    }
  }
}
