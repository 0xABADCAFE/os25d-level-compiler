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

trait TBinaryExportable {

  protected function padIdent(string $sIdent) : string {
    return str_pad(substr($sIdent, 0, 8), 8, "\0", STR_PAD_RIGHT);
  }

  protected function intToU8(int $i) : string {
    return pack('C', $i);
  }

  protected function intToU16(int $i) : string {
    return pack('n', $i);
  }

  protected function intToU32(int $i) : string {
    return pack('N', $i);
  }

  protected function arrayIntToU8(array $aInt) : string {
    return implode('', array_map([$this, 'intToU8'], $aInt));
  }

  protected function arrayIntToU16BE(array $aInt) : string {
    return implode('', array_map([$this, 'intToU16'], $aInt));
  }

  protected function arrayIntToU32BE(array $aInt) : string {
    return implode('', array_map([$this, 'intToU32'], $aInt));
  }

}

/**
 * BinaryExportFile class. Used to create the final level data to be used by the engine.
 */
class BinaryExportFile {

  use TBinaryExportable;

  public function __construct(string $sFile = null) {
    if (null !== $sFile) {
      $this->open($sFile);
    }
  }

  public function open(string $sFile) : self {
    if (!$this->rFile = fopen($sFile, 'w')) {
      throw new IOWriteException($sFile);
    }

    return $this;
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

  public function export(IBinaryExportable $oExportable) : self {
    if (!$this->rFile) {
      throw new IOWriteException();
    }
    $sIdent  = $this->padIdent($oExportable->getBinaryIdent());
    $sBinary = $oExportable->getBinaryData();
    $iLength = strlen($sBinary);
    $iCheck  = crc32($sBinary);
    $iPadLen = $iLength & 3;
    if ($iPadLen > 0) {
      $iPadLen = $iLength + 4 - $iPadLen;
      $sBinary = str_pad($sBinary, $iPadLen, "\0", STR_PAD_RIGHT);
    }
    $sPayload = $sIdent . $this->arrayIntToU32BE([$iLength, $iCheck]) . $sBinary;

    //echo bin2hex($sPayload), "\n";

    if (!fwrite($this->rFile, $sPayload)) {
      throw new IOWriteException();
    }

    return $this;
  }

  public function close() {
    if ($this->rFile) {
      fclose($this->rFile);
      $this->rFile = null;
    }
  }

  private $rFile = null;
}
