<?php

/**
 * Basic Log Interface. All methods expected to be implemented fluently.
 */
 
interface ILog {
  const
    I_DEBUG  = 1,
    I_INFO   = 2,
    I_NOTICE = 4,
    I_WARN   = 8,
    I_ERROR  = 16,
    I_ALL    = 31
  ;

  public function enable($iLog);
  public function disable($iLog);
  public function error($sError);
  public function warn($sWarn);
  public function notice($sNotice);
  public function info($sInfo);
  public function debug($sDebug);
}

/**
 * Simple implementation of ILog that writes to stdout and allows each severity to be
 * individually toggled.
 */

class SimpleLog implements ILog {

  public function __construct($iLog = ILog::I_ALL) {
    $this->fMark = microtime(true);
    $this->iLog  = (int)$iLog;
  }

  public function enable($iLog) {
    $this->iLog |= (int)$iLog;
    return $this;
  }

  public function disable($iLog) {
    $this->iLog &= ~((int)$iLog);
    return $this;
  }

  public function error($sError) {
    return $this->log(ILog::I_ERROR, $sError);
  }

  public function warn($sWarn) {
    return $this->log(ILog::I_WARN, $sWarn);
  }

  public function notice($sNote) {
    return $this->log(ILog::I_NOTICE, $sNote);
  }
  
  public function info($sInfo) {
    return $this->log(ILog::I_INFO, $sInfo);
  }

  public function debug($sDebug) {
    return $this->log(ILog::I_DEBUG, $sDebug);
  }

  protected function log($iLog, $sMessage) {
    if ($this->iLog & $iLog) {
      $fElapsed = microtime(true) - $this->fMark;
      printf("[%8.6f] [%s] %s\n", $fElapsed, self::$aLevel[$iLog], $sMessage);
    }
    return $this;
  }

  private
    $fMark,
    $iLog,
    $sLast,
    $iTimes
  ;
  
  private static $aLevel = [
    self::I_DEBUG  => 'D',
    self::I_INFO   => 'I',
    self::I_NOTICE => 'N',
    self::I_WARN   => 'W',
    self::I_ERROR  => 'E',
  ];
}

/*
$oLog = new SimpleLog();
$oLog
  ->error('Test Error')
  ->warn('Test Warning')
  ->notice('Test Notice')
  ->info('Test Info')
  ->debug('Test Debug');
*/
