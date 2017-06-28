<?php

/**
 * Basic interface for entities that should support a serialisation to binary.
 */
interface IBinaryExportable {
  /** @return binary */
  public function getBinaryData();

  /** @return char[4] */
  public function getBinaryIdent();
}


/**
 * Trait for basic binary encoding tasks.
 */
trait TBinaryExportable {

  protected function intToU8(int $i) : string {
    if ($i < -128 || $i > 255) {
      throw new UnexpectedValueException();
    }
    return pack('C', $i);
  }

  protected function intToU16BE(int $i) : string {
    if ($i < -32768 || $i > 65536) {
      throw new UnexpectedValueException();
    }
    return pack('n', $i);
  }

  protected function intToU32BE(int $i) : string {
    if ($i < -2147483648 || $i > 4294967296) {
      throw new UnexpectedValueException();
    }
    return pack('N', $i);
  }

  protected function arrayIntToU8(array $a) : string {
    return implode('', array_map([$this, 'intToU8'], $a));
  }

  protected function arrayIntToU16BE(array $a) : string {
    return implode('', array_map([$this, 'intToU16BE'], $a));
  }

  protected function arrayIntToU32BE(array $a) : string {
    return implode('', array_map([$this, 'intToU32BE'], $a));
  }

  protected function padToBoundary(string $sBinary, int $iBoundary) : string {
    $iLength = strlen($sBinary);
    $iPadLen = $iLength & ($iBoundary - 1);
    if ($iPadLen > 0) {
      $iPadLen = $iLength + $iBoundary - $iPadLen;
      return str_pad($sBinary, $iPadLen, "\0", STR_PAD_RIGHT);
    }
    return $sBinary;
  }

}

/**
 * BinaryExportFile class. Used to create the final level data to be used by the engine.
 */
class BinaryExportFile {

  const
    ALIGN_SIZE = 4,
    IDENT_SIZE = 4
  ;

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
   *   N + 8       : Length     (uint32 big endian)
   *   N + 12      : crc32      (uint32 big endian)
   *   N + 16      : data       (uint8[X])
   *   N + 16 + X  : Zero pad until next ALIGN_SIZE byte aligned offset
   */

  public function export(IBinaryExportable $oExportable) : self {
    if (!$this->rFile) {
      throw new IOWriteException();
    }
    $sIdent   = $this->padToBoundary(substr($oExportable->getBinaryIdent(), 0, self::IDENT_SIZE), self::IDENT_SIZE);
    $sBinary  = $oExportable->getBinaryData();
    $iLength  = strlen($sBinary);
    $iCheck   = crc32($sBinary);
    $sBinary  = $this->padToBoundary($sBinary, self::ALIGN_SIZE);
    $sPayload = $sIdent . $this->arrayIntToU32BE([$iLength, $iCheck]) . $sBinary;

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
