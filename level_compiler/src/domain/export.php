<?php

/**
 * Basic interface for entities that should support a serialisation to binary.
 */
interface IBinaryExportable {
  /** @return binary */
  public function getBinaryData() : string;

  /** @return char[4] */
  public function getBinaryIdent() : string ;
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

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

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * BinaryExportFile class. Used to create the final level data to be loaded and used by the engine. The file begins with a header,
 * then an index table of all the objects contained, and finally the objects themselves. The index table allows the runtime to quickly
 * seek() to an object for loading.
 *
 * File Format:
 *
 * [  0 : File ID        : uint8[8]             ] File identifier
 * [  8 : Version        : uint32               ] File version
 * [ 12 : Hunk Count     : uint32               ] Number of Hunks in file
 * [ 16 : Hunk Index [0] : { uint8[4], uint32 } ] Hunk ID / Seek offset pair for first Hunk
 * [ ...                                   ]
 * [ XX : Hunk Index [N] : { uint8[4], uint32 } ] Hunk ID / Seek offset pair for final Hunk
 * [ YY : Hunk [0] ]                              First Hunk
 * [ ........ ]
 * [ ZZ : Hunk [N] ]                              Last Hunk
 */
class BinaryExportFile {

  const
    VERSION      = 1,
    ALIGN_SIZE   = 4,
    IDENT_SIZE   = 4,
    LOOKUP_SIZE  = 8,
    FILE_ID      = 'OS25DLvl',
    HEADER_SIZE  = 16
  ;

  use TBinaryExportable;

  public function __construct(string $sFile = null) {
    if (null !== $sFile) {
      $this->open($sFile);
    }
  }

  public function open(string $sFile) : self {
    if (
      (!$this->rFile = fopen($sFile, 'w'))   ||
      (!fwrite($this->rFile, self::FILE_ID)) ||
      (!fwrite($this->rFile, $this->intToU32BE(self::VERSION)))
    ) {
      throw new IOWriteException($sFile);
    }
    return $this;
  }

  /**
   * Exports an encapsulated IBinaryExportable record to the hunk array for writing. The encoded payload is as follows
   *
   * [  0      : Identifier : uint8[4] ] Hunk identifier
   * [  8      : Length     : uint32   ] Hunk data length (not including header and padding)
   * [ 12      : crc32      : uint32   ] Hunk data CRC32
   * [ 16      : data       : uint8[X] ] Hunk data
   * [ 16 + X  : Zero pad   : uint8[N] ] Zero pad until next ALIGN_SIZE byte aligned offset, may be zero
   */

  public function export(IBinaryExportable $oExportable) : self {
    $sIdent   = $this->padToBoundary(substr($oExportable->getBinaryIdent(), 0, self::IDENT_SIZE), self::IDENT_SIZE);
    $sBinary  = $oExportable->getBinaryData();
    $iLength  = strlen($sBinary);
    $iCheck   = crc32($sBinary);
    $sBinary  = $this->padToBoundary($sBinary, self::ALIGN_SIZE);
    $sPayload = $sIdent . $this->arrayIntToU32BE([$iLength, $iCheck]) . $sBinary;

    $this->aHunks[] = (object)[
      'id'   => $sIdent,
      'data' => $sPayload
    ];

    return $this;
  }

  public function close() {
    if ($this->rFile) {
      $this->writeIndex();
      $this->writeHunks();
      fclose($this->rFile);
      $this->rFile = null;
    }
  }

  private function writeIndex() {

    if (!fwrite($this->rFile, $this->intToU32BE(count($this->aHunks)))) {
      throw new IOWriteException();
    }
    $iSeek = self::HEADER_SIZE + (count($this->aHunks) * self::LOOKUP_SIZE);
    foreach ($this->aHunks as $oHunk) {
      $sOffset = $this->intToU32BE($iSeek);
      if (!fwrite($this->rFile, $oHunk->id . $sOffset)) {
        throw new IOWriteException();
      }
      $iSeek += strlen($oHunk->data);
    }

  }

  private function writeHunks() {
    foreach ($this->aHunks as $oHunk) {
      if (!fwrite($this->rFile, $oHunk->data)) {
        throw new IOWriteException();
      }
    }
  }

  private
    $rFile   = null,
    $aHunks  = []
  ;
}

