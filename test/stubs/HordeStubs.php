<?php

/**
 * Stub file for Horde classes needed by tests.
 *
 * This allows tests to run without full Horde framework dependencies.
 */

// Horde_Variables stub
if (!class_exists('Horde_Variables')) {
    class Horde_Variables implements ArrayAccess, Countable, IteratorAggregate
    {
        protected $_vars = [];
        protected $_expectedVars = [];

        public function __construct($vars = [])
        {
            $this->_vars = $vars;
        }

        public function get($name, $default = null)
        {
            return $this->_vars[$name] ?? $default;
        }

        public function set($name, $value)
        {
            $this->_vars[$name] = $value;
        }

        public function add($name, $value)
        {
            $this->_vars[$name] = $value;
        }

        public function remove($name)
        {
            unset($this->_vars[$name]);
        }

        public function exists($name)
        {
            return array_key_exists($name, $this->_vars);
        }

        // ArrayAccess
        public function offsetExists($offset): bool
        {
            return $this->exists($offset);
        }

        public function offsetGet($offset): mixed
        {
            return $this->get($offset);
        }

        public function offsetSet($offset, $value): void
        {
            $this->set($offset, $value);
        }

        public function offsetUnset($offset): void
        {
            $this->remove($offset);
        }

        // Countable
        public function count(): int
        {
            return count($this->_vars);
        }

        // IteratorAggregate
        public function getIterator(): Traversable
        {
            return new ArrayIterator($this->_vars);
        }

        public static function getDefaultVariables()
        {
            return new self();
        }
    }
}

// Horde_String stub
if (!class_exists('Horde_String')) {
    class Horde_String
    {
        public static function lower($text, $charset = 'UTF-8')
        {
            return mb_strtolower($text, $charset);
        }

        public static function length($text, $charset = 'UTF-8')
        {
            return mb_strlen($text, $charset);
        }

        public static function convertCharset($text, $from, $to)
        {
            return mb_convert_encoding($text, $to, $from);
        }
    }
}

// Horde class stub
if (!class_exists('Horde')) {
    class Horde
    {
        public static function log($message, $priority = 0)
        {
            // Stub: do nothing
        }
    }
}

// Horde_Translation stubs
if (!class_exists('Horde_Translation_Autodetect')) {
    class Horde_Translation_Autodetect
    {
        public static function t($message)
        {
            return $message;
        }

        public static function detect($class)
        {
            return new self();
        }
    }
}

// Horde_Form_Translation stub
if (!class_exists('Horde_Form_Translation')) {
    class Horde_Form_Translation extends Horde_Translation_Autodetect
    {
        // Inherits static t() from parent
    }
}

// Horde_Exception stub
if (!class_exists('Horde_Exception')) {
    class Horde_Exception extends Exception {}
}

// Horde_Browser_Exception stub
if (!class_exists('Horde_Browser_Exception')) {
    class Horde_Browser_Exception extends Exception {}
}

// Horde_Util stub
if (!class_exists('Horde_Util')) {
    class Horde_Util
    {
        public static function dispelMagicQuotes($input)
        {
            return $input;
        }
    }
}

// PEAR stub for legacy error handling
if (!class_exists('PEAR')) {
    class PEAR
    {
        public static function raiseError($message)
        {
            return new PEAR_Error($message);
        }
    }

    class PEAR_Error
    {
        public $message;

        public function __construct($message)
        {
            $this->message = $message;
        }
    }
}
