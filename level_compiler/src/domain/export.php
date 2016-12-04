<?php

/**
 * Basic interface for entities that should support a serialisation to binary.
 */
interface IBinaryExportable {
  public function getBinaryData();
  public function getBinaryIdent();
}

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
