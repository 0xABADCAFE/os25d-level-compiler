<?php

class ConnectionMatrix {

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
  
  public function normalise() {
    ksort($this->aConnections);
  }
  
  private $aConnections = [];
}