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

    public function enable(int $iLog) : ILog;
    public function disable(int $iLog) : ILog;
    public function error(string $sError) : ILog;
    public function warn(string $sWarn) : ILog;
    public function notice(string $sNotice) : ILog;
    public function info(string $sInfo) : ILog;
    public function debug(string $sDebug) : ILog;
}

/**
 * SimpleLog class. Minimal implementation of ILog that writes to stdout and allows each severity to be
 * individually toggled.
 */

class SimpleLog implements ILog {

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

    public function __construct(int $iLog = ILog::I_ALL) {
        $this->fMark = microtime(true);
        $this->iLog  = $iLog;
    }

    public function enable(int $iLog) : ILog {
        $this->iLog |= $iLog;
        return $this;
    }

    public function disable(int $iLog) : ILog {
        $this->iLog &= ~($iLog);
        return $this;
    }

    public function error(string $sError) : ILog {
        return $this->log(ILog::I_ERROR, $sError);
    }

    public function warn(string $sWarn) : ILog {
        return $this->log(ILog::I_WARN, $sWarn);
    }

    public function notice(string $sNote) : ILog {
        return $this->log(ILog::I_NOTICE, $sNote);
    }

    public function info(string $sInfo) : ILog {
        return $this->log(ILog::I_INFO, $sInfo);
    }

    public function debug(string $sDebug) : ILog {
        return $this->log(ILog::I_DEBUG, $sDebug);
    }

    protected function log(int $iLog, string $sMessage) : ILog {
        if ($this->iLog & $iLog) {
            $fElapsed = microtime(true) - $this->fMark;
            printf("[%8.6f] [%s] %s\n", $fElapsed, self::$aLevel[$iLog], $sMessage);
        }
        return $this;
    }

}
