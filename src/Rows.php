<?php
/**
 * Rows class
 */

namespace PhilWaters\Database;

use PDO;
use PDOStatement;
use Countable;
use Iterator;
use ArrayAccess;
use \BadMethodCallException;

/**
 * Database table rows iterator
 */
class Rows implements Countable, Iterator, ArrayAccess
{
    /**
     * Stores the current row index
     *
     * @var integer
     */
    private $position = 0;

    /**
     * Stores the row cound
     *
     * @var integer
     */
    private $count = 0;

    /**
     * Stores the current row data
     *
     * @var array
     */
    private $row;

    /**
     * Caches all row data
     *
     * @var array
     */
    private $all = null;

    /**
     * Constructor
     *
     * @param PDOStatement $stmt  PDOStatement
     * @param integer      $count Row count
     */
    public function __construct(PDOStatement $stmt, $count)
    {
        $this->stmt = $stmt;
        $this->count = $count;
        $this->position = 0;
        $this->row = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get magic method to handle getting all rows
     *
     * @param string $name Name of the field to return
     *
     * @return array|null
     */
    public function __get($name)
    {
        if ($name == "all") {
            $this->rewind();

            if ($this->all === null) {
                $this->all = array_merge(
                    array($this->row),
                    $this->stmt->fetchAll(PDO::FETCH_ASSOC)
                );
            }

            return $this->all;
        }

        return null;
    }

    //////////// Countable ////////////

    /**
     * @inheritdoc
     */
    public function count()
    {
        return $this->count;
    }

    //////////// Iterator ////////////

    /**
     * @inheritdoc
     */
    function rewind()
    {
        if ($this->position > 0) {
            $this->position = 0;
            $this->stmt->execute();
            $this->row = $this->stmt->fetch(PDO::FETCH_ASSOC);
        }
    }

    /**
     * @inheritdoc
     */
    function current()
    {
        return $this->row;
    }

    /**
     * @inheritdoc
     */
    function key()
    {
        return $this->position;
    }

    /**
     * @inheritdoc
     */
    function next()
    {
        $this->row = $this->stmt->fetch(PDO::FETCH_ASSOC);
        $this->position++;
    }

    /**
     * @inheritdoc
     */
    function valid()
    {
        return $this->row !== false;
    }

    //////////// ArrayAccess ////////////

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
        throw new BadMethodCallException("Setting row value is not supported");
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return $offset >= 0 && $offset < $this->count;
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        throw new BadMethodCallException("Unsetting row value is not supported");
    }

    /**
     * @inheritdoc
     */
    public function offsetGet($offset)
    {
        /*
         * If database supports cursors, the following should be used instead
         * (database connection created with option: PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL)
         * return $this->stmt->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_FIRST, $offset);
         */

        if (!is_numeric($offset) || $offset >= $this->count || $offset < 0) {
            return null;
        }

        if ($offset == $this->position) {
            return $this->row;
        }

        if ($offset < $this->position && $this->position > 0) {
            $this->rewind();
        }

        while ($this->position < $offset) {
            $this->next();
        }

        return $this->row;
    }
}
