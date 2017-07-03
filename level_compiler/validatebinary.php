#!/usr/bin/php
<?php

/**
 * Level file validator. TODO
 */

error_reporting(-1);

  /**
   * Exports an encapsulated IBinaryExportable record to the hunk array for writing. The encoded payload is as follows
   *
   * [  0      : Identifier : uint8[4] ] Hunk identifier
   * [  8      : Length     : uint32   ] Hunk data length (not including header and padding)
   * [ 12      : crc32      : uint32   ] Hunk data CRC32
   * [ 16      : data       : uint8[X] ] Hunk data
   * [ 16 + X  : Zero pad   : uint8[N] ] Zero pad until next ALIGN_SIZE byte aligned offset, may be zero
   */

class Chunk {

  const
    MIN_SIZE = 16 // ID + Length + CRC32 + data (pad to 4)
  ;

  public function __construct(string $sRaw) {

    if (strlen($sRaw) < self::MIN_SIZE) {
      throw new RuntimeException();
    }

    $aData = unpack('c4ident/Nlength/Ncheck', $sRaw);

    print_r($aData);
  }

  private
    $sIdent,
    $iLength,
    $iCheck,
    $sData
  ;
}

$sContent = file_get_contents('test.lbin');
