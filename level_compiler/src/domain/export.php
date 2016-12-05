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
 * BinaryExport utility. Takes the binary representation of an IBinaryExportable instance and creates an encapsulation containing
 * the 4 byte ident, length and crc32 data. The resulting binary string is always zero padded to the next 4-byte boundary.
 */
class BinaryExport {

  public static function export(IBinaryExportable $oExportable) {
    $sIdent  = $oExportable->getBinaryIdent();
    $sBinary = $oExportable->getBinaryData();
    $iLength = strlen($sBinary);
    $iCheck  = crc32($sBinary);
    return $sIdent . pack(
      'NN',
      $iLength,
      $iCheck
    ) . $sBinary;
  }
}
