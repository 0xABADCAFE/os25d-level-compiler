<?php

/////////////////////////////////////////////////////////////////////////////

interface Logger {
  public function warn($sWarn);
  public function notice($sNotice);
  public function info($sInfo);
  public function debug($sDebug);
}

/////////////////////////////////////////////////////////////////////////////

class NullLogger implements Logger {
  public function warn($sWarn) {}
  public function notice($sNote) {}
  public function info($sInfo) {}
  public function debug($sDebug) {}
}

/////////////////////////////////////////////////////////////////////////////

class SimpleLogger implements Logger {
  public function warn($sWarn) {
    $this->log("[WARN] " . $sWarn);
  }

  public function notice($sNote) {
    $this->log("[NOTE] " . $sNote);
  }

  public function info($sInfo) {
    $this->log("[INFO] " . $sInfo);
  }

  public function debug($sDebug) {
    $this->log("[DEBG] " . $sDebug);
  }

  protected function log($sMessage) {
    echo $sMessage, "\n";
  }
}
