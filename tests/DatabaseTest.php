<?php
require_once __DIR__ . "/../src/Database.php";
require_once __DIR__ . "/../src/Rows.php";

use \PhilWaters\Database\Database;

class DatabaseTest extends PHPUnit_Framework_TestCase
{
    private $connection;

    public function __construct()
    {
        $this->connection = new \PDO(
            "mysql:host=127.0.0.1;dbname=testdb",
            "root",
            "password",
            array(
                PDO::ERRMODE_EXCEPTION
            )
        );
    }

    public function setUp()
    {
        $this->connection->exec("TRUNCATE TABLE a");

        $date = new DateTime("2015-01-01");
        $interval = new DateInterval('P1D');

        for ($i = 1 ; $i <= 100 ; $i++) {
            $this->connection->exec("INSERT INTO a VALUES($i, 'test$i', '" . $date->format("Y-m-d") . "')");
            $date->add($interval);
        }
    }

    public function testTruncate_exists()
    {
        $db = new Database($this->connection);
        $db->truncate("a");
        $rows = $db->query("SELECT * FROM a");

        $this->assertEquals(0, count($rows));
    }

    /**
     * @expectedException Exception
     */
    public function testTruncate_doesNotExist()
    {
        $db = new Database($this->connection);
        echo "T: " . $db->truncate("z");
    }

    public function testGet_all()
    {
        $db = new Database($this->connection);
        $rows = $db->query("SELECT * FROM a WHERE b <= :b ORDER BY b", array("b" => 5));

        $this->assertTrue(is_array($rows->all) && !($rows->all instanceof RowIterator));
        $n = 1;

        foreach ($rows->all as $i => $row) {
            $this->assertEquals("test" . $n++, $row['c']);
        }
    }

    public function testGet_null()
    {
        $db = new Database($this->connection);
        $rows = $db->query("SELECT * FROM a WHERE b <= :b ORDER BY b", array("b" => 5));

        $this->assertTrue(is_array($rows->all) && !($rows->all instanceof RowIterator));
        $n = 1;

        $this->assertEquals(null, $rows->this_will_be_null);
    }

    public function testQuery_selectSingleRow()
    {
        $db = new Database($this->connection);
        $rows = $db->query("SELECT * FROM a WHERE b = :b", array("b" => 1));

        $this->assertEquals(1, count($rows));
        $this->assertEquals("test1", $rows[0]['c']);
    }

    public function testQuery_select10Rows()
    {
        $db = new Database($this->connection);
        $rows = $db->query("SELECT * FROM a ORDER BY b LIMIT 10");

        $this->assertEquals(10, count($rows));

        $n = 1;

        foreach ($rows as $i => $row) {
            $this->assertEquals("test" . $n++, $row['c']);
            $this->assertEquals($i, current($rows));
        }
    }

    public function testQuery_arrayAccess_get()
    {
        $db = new Database($this->connection);
        $rows = $db->query("SELECT * FROM a ORDER BY b LIMIT 10");

        $this->assertEquals(10, count($rows));

        $this->assertEquals("test8", $rows[7]['c']);
        $this->assertEquals("test5", $rows[4]['c']);
        $this->assertEquals("test6", $rows[5]['c']);
        $this->assertEquals(null, $rows[10]);
        $this->assertEquals(null, $rows['test']);
    }

    public function testQuery_arrayAccess_isset()
    {
        $db = new Database($this->connection);
        $rows = $db->query("SELECT * FROM a ORDER BY b LIMIT 10");

        $this->assertTrue(isset($rows[0]));
        $this->assertFalse(isset($rows[10]));
    }

    /**
     * @expectedException Exception
     */
    public function testQuery_arrayAccess_unset()
    {
        $db = new Database($this->connection);
        $rows = $db->query("SELECT * FROM a ORDER BY b LIMIT 10");

        unset($rows[0]);
    }

    /**
     * @expectedException Exception
     */
    public function testQuery_arrayAccess_set()
    {
        $db = new Database($this->connection);
        $rows = $db->query("SELECT * FROM a ORDER BY b LIMIT 10");

        $rows[0] = "test";
    }

    public function testQuery_insertRows()
    {
        $db = new Database($this->connection);
        $result = $db->query("INSERT INTO a (b, c, d) VALUES (:b, :c, :d)", array(
            "b" => 9999,
            "c" => "test9999",
            "d" => "2015-10-01"
        ));

        $this->assertEquals(1, $result);

        $rows = $db->query("SELECT * FROM a WHERE b = :b", array("b" => 9999));

        $this->assertEquals(1, count($rows));
        $this->assertEquals("test9999", $rows[0]['c']);
    }

    public function testQuery_updateRows()
    {
        $db = new Database($this->connection);
        $result = $db->query("UPDATE a SET b = 9999 WHERE b = :b", array(
            "b" => 1
        ));
        $db->

        $this->assertEquals(1, $result);

        $result = $db->query("UPDATE a SET b = 9999 WHERE b = :b", array(
            "b" => 9999
        ));

        $this->assertEquals(0, $result);

        $rows = $db->query("SELECT * FROM a WHERE b = :b", array("b" => 9999));

        $this->assertEquals(1, count($rows));
        $this->assertEquals("test1", $rows[0]['c']);
    }

    public function testQuery_deleteRows()
    {
        $db = new Database($this->connection);
        $result = $db->query("DELETE FROM a WHERE b > :b", array(
            "b" => 10
        ));

        $this->assertEquals(90, $result);

        $rows = $db->query("SELECT * FROM a ORDER BY b");

        $this->assertEquals(10, count($rows));
    }

    /**
     * @expectedException Exception
     */
    public function testQuery_invalid()
    {
        $db = new Database($this->connection);
        $db->query("SELECT");
    }

    public function testRollback()
    {
        $db = new Database($this->connection);

        $db->beginTransaction();

        $result = $db->query("UPDATE a SET b = 9999 WHERE b = :b", array(
            "b" => 1
        ));

        $this->assertEquals(1, $result);

        $db->rollback();

        $rows = $db->query("SELECT * FROM a WHERE b = :b", array("b" => 9999));

        $this->assertEquals(0, count($rows));
    }

    public function testCommit()
    {
        $db = new Database($this->connection);

        $db->beginTransaction();

        $result = $db->query("UPDATE a SET b = 9999 WHERE b = :b", array(
            "b" => 1
        ));

        $this->assertEquals(1, $result);

        $db->commit();

        $rows = $db->query("SELECT * FROM a WHERE b = :b", array("b" => 9999));

        $this->assertEquals(1, count($rows));
    }

    public function testRenameTable_exists()
    {
        $db = new Database($this->connection);
        $this->connection->exec("DROP TABLE b");
        $this->connection->exec("DROP TABLE c");
        $this->connection->exec("CREATE TABLE b (c INTEGER)");

        $db->renameTable("b", "c");

        if ($this->connection->exec("DROP TABLE c") === false) {
            $this->fail();
        } else {
            $this->assertTrue(true);
        }
    }

    /**
     * @expectedException Exception
     */
    public function testRenameTable_doesNotExist()
    {
        $db = new Database($this->connection);

        $db->renameTable("b", "c");
    }

    public function testSwapTables_exists()
    {
        $db = new Database($this->connection);
        $this->connection->exec("DROP TABLE b");
        $this->connection->exec("DROP TABLE c");
        $this->connection->exec("CREATE TABLE b (c INTEGER)");
        $this->connection->exec("CREATE TABLE c (b INTEGER)");
        $this->connection->exec("INSERT INTO b VALUES (1)");
        $this->connection->exec("INSERT INTO c VALUES (2)");

        $db->swapTables("b", "c");

        $bRows = $db->query("SELECT * FROM b");
        $cRows = $db->query("SELECT * FROM c");

        $this->assertEquals(2, $bRows[0]['b']);
        $this->assertEquals(1, $cRows[0]['c']);

        $this->connection->exec("DROP TABLE b");
        $this->connection->exec("DROP TABLE c");
    }

    /**
     * @expectedException Exception
     */
    public function testSwapTables_doesNotExist()
    {
        $db = new Database($this->connection);

        $db->swapTables("b", "c");
    }
}
