<?php

/**
 * Basic interface for entities that should support a serialisation to binary.
 */
interface IBinaryExportable {
  /** @return binary */
  public function getBinaryData();
  
  /** @return char[8] */
  public function getBinaryIdent();
}

/**
 * BinaryExportFile class. Used to create the final level data to be used by the engine.
 */
class BinaryExportFile {

  private $rFile = null;

  public function __construct($sFile = null) {
    if ($sFile) {
      $this->open($sFile);
    }
  }
  
  public function open($sFile) {
    if (!$this->rFile = fopen($sFile, 'w')) {
      throw new IOWriteException($sFile);
    }
  }

  /**
   * Writes an encapsulated IBinaryExportable record to the output file. The format of the payload at offset N is as follows:
   *
   *   N + 0       : Identifier (char[8])
   *   N + 8       : Length     (uint32)
   *   N + 12      : crc32      (uint32)
   *   N + 16      : data       (uint8[X])
   *   N + 16 + X  : Zero pad until next 4-byte aligned offset
   */
  
  public function export(IBinaryExportable $oExportable) {
    if (!$this->rFile) {
      throw new IOWriteException();
    }
    $sIdent  = str_pad(substr($oExportable->getBinaryIdent(), 0, 8), 8, "\0", STR_PAD_RIGHT);
    $sBinary = $oExportable->getBinaryData();
    $iLength = strlen($sBinary);
    $iCheck  = crc32($sBinary);
    $iPadLen = $iLength & 3; 
    if ($iPadLen > 0) {
      $iPadLen = $iLength + 4 - $iPadLen;
      $sBinary = str_pad($sBinary, $iPadLen, "\0", STR_PAD_RIGHT);
    }
    $sPayload = $sIdent . pack(
      'NN',
      $iLength,
      $iCheck
    ) . $sBinary;
    if (!fwrite($this->rFile, $sPayload)) {
      throw new IOWriteException();
    }
  }

  public function close() {
    if ($this->rFile) {
      fclose($this->rFile);
      $this->rFile = null;
    }
  }
}
