<?php

/**
 * ConnectionMatrix class. This is a sparse matrix implementation that, for every zone, details the edges through which that zone connects
 * with any other in the map.
 *
 */
class ConnectionMatrix implements IBinaryExportable {

  use TBinaryExportable;

  public function __construct(int $iDimension) {
    $this->iDimension = $iDimension;
  }

  public function normalise() {
    foreach ($this->aConnections as &$aConnection) {
      ksort($aConnection);
    }
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
   *
   * [ Dimension    : U16 ] Number of rows / columns
   * [ Row 1 Offset : U16 ] Offset to Row[1] data. There is no offset to Row[0] data as this would just be zero.
   * [ Row 2 Offset : U16 ]
   * [ ...          : U16 ]
   * [ Final Offset : U16 ] Offset to Row[d-1] data, the final row in the encoded set.
   * [ RLE Row Data : U8  ] Zero span encoded byte data.
   *
   * @return binary
   */
  public function getBinaryData() : string {
    $sHeaderBin = $this->intToU16BE($this->iDimension);
    $sBodyBin   = '';
    for ($i = 0; $i < $this->iDimension; $i++) {
      $sBodyBin = $this->encodeRow($i);
      $sHeaderBin .= $this->intToU16BE(strlen($sBodyBin));
    }
    return $sHeaderBin . $sBodyBin;
  }

  /** @return char[8] */
  public function getBinaryIdent() : string {
    return 'CMtx';
  }

  public function getConnection(int $iFromZoneId, int $iToZoneId) : int {
    return isset($this->aConnections[$iFromZoneId][$iToZoneId]) ?
      $this->aConnections[$iFromZoneId][$iToZoneId] :
      0;
  }

  public function addConnection(int $iFromZoneId, int $iToZoneId, int $iViaEdge) {
    if (isset($this->aConnections[$iFromZoneId][$iToZoneId])) {
      throw new IllegalSharedEdgeCountException();
    }

    if (!isset($this->aConnections[$iFromZoneId])) {
      $this->aConnections[$iFromZoneId] = [$iToZoneId => $iViaEdge];
    } else {
      $this->aConnections[$iFromZoneId][$iToZoneId] = $iViaEdge;
    }
  }

  private function encodeRow(int $iFromZoneId) : string {
    $aRow  = [];
    $iLast = 0;
    foreach ($this->aConnections[$iFromZoneId] as $iToZoneId => $iViaEdge) {

      // See how many zones connections were skipped before we hit iZoneToId and encode as a zero span
      $iZeroSpan = $iToZoneId - $iLast;
      if ($iZeroSpan > 0) {
        $this->encodeZeroSpan($aRow, $iZeroSpan);
      }

      // Encode the connection entry and update the last empty zone to be one after the current
      $aRow[] = 0x80 | $iViaEdge;
      $iLast  = $iToZoneId + 1;
    }

    // Deal with trailing empty zone connections and encode as a zero span
    $iZeroSpan = $this->iDimension - $iLast;
    if ($iZeroSpan > 0) {
      $this->encodeZeroSpan($aRow, $iZeroSpan);
    }

    return $this->arrayIntToU8($aRow);
  }

  private function encodeZeroSpan(array& $aRow, int $iLength) {
    if ($iLength > 128) {
      // Encode up to the first 128 zeros as usual, then encode how many additional  spans of 128
    }
    $aRow[] = $iLength - 1;
  }

  private
    $iDimension   = 0,
    $aConnections = []
  ;
}
