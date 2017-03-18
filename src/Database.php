<?php
/**
 * Database class
 */

namespace PhilWaters\Database;

use PDO;
use \PhilWaters\Database\Rows;
use \Exception;

/**
 * Database interface
 */
class Database
{
    /**
     * Stores PDO connection
     *
     * @var \PDO
     */
    private $connection;

    /**
     * Constructor
     *
     * @param \PDO $connection PDO connection
     */
    public function __construct($connection)
    {
        $this->connection = $connection;
    }

    /**
     * Executes a query
     *
     * @param string $query  SQL query string
     * @param array  $params Parameters
     *
     * @throws Exception
     *
     * @return Rows or number of affected rows
     */
    public function query($query, $params = array())
    {
        $stmt = $this->connection->prepare($query);

        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }

        if (!$stmt->execute()) {
            throw new Exception(json_encode($stmt->errorInfo()));
        }

        if ($stmt->columnCount() == 0) { // not a select query
            return $stmt->rowCount();
        }

        return new Rows($stmt, $stmt->rowCount());
    }

    /**
     * Initiates a transaction
     *
     * @return boolean
     */
    public function beginTransaction()
    {
        return $this->connection->beginTransaction();
    }

    /**
     * Rolls back a transaction
     *
     * @return boolean
     */
    public function rollback()
    {
        return $this->connection->rollback();
    }

    /**
     * Commits a transaction
     *
     * @return boolean
     */
    public function commit()
    {
        $this->connection->commit();
    }

    /**
     * Renames a table
     *
     * @param string $oldName Old table name
     * @param string $newName New table name
     *
     * @return void
     */
    public function renameTable($oldName, $newName)
    {
        $query = "RENAME TABLE $oldName TO $newName";

        $this->query($query);
    }

    /**
     * Swaps two tables
     *
     * @param string $table1 Table name
     * @param string $table2 Table name
     *
     * @return void
     */
    public function swapTables($table1, $table2)
    {
        $tmpTable = $table1 . preg_replace("`[^a-z0-9]+`", "", uniqid("_tmp", true));

        $query =
            "RENAME TABLE $table2 TO $tmpTable,
                          $table1 TO $table2,
                          $tmpTable TO $table1";

        $this->query($query);
    }

    /**
     * Truncates a table
     *
     * @param string $table Table name
     *
     * @return void
     */
    public function truncate($table)
    {
        $query = "TRUNCATE TABLE $table";

        return $this->query($query);
    }
}
