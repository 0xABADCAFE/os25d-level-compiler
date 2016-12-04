<?php
/*

Standard Exceptions

Exception
  LogicException
    BadFunctionCallException
      BadMethodCallException
    DomainException
    LengthException
    InvalidArgumentException
    OutOfRangeException
            
  RuntimeException
    OutOfBoundsException
    OverflowException
    RangeException
    UnderflowException
    UnexpectedValueException
*/

class IOException extends RuntimeException { }

class IOReadException extends IOException { }

class IOWriteException extends IOException { }

