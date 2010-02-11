<?php
require_once 'config.test.php';
require_once 'PHPUnit/Framework.php';

PHPUnit_Util_Filter::addDirectoryToWhitelist(realpath(dirname(__FILE__) . '/../src/'));

require_once 'MDB2.php';
require_once 'DB/Sql.php';
require_once '../src/Ilib/Position.php';

class PositionTest extends PHPUnit_Framework_TestCase
{
    private $db;
    private $table = 'position_test';

    function setUp()
    {
        $this->db = MDB2::factory(DB_DSN);
        if (PEAR::isError($this->db)) {
            die($this->db->getUserInfo());
        }
        $result = $this->db->exec('DROP TABLE ' . $this->table);
        /*
         TODO: DROP THE TABLE IF IT EXISTS

        $result = $this->db->exec('DROP TABLE ' . $this->table);

        if (PEAR::isError($result)) {
            die($result->getUserInfo());
        }
        */

        $result = $this->db->exec('CREATE TABLE ' . $this->table . '(
            id int(11) NOT NULL auto_increment, name varchar(255) NOT NULL, position int(11) NOT NULL, PRIMARY KEY  (id))'
        );

        if (PEAR::isError($result)) {
            die($result->getUserInfo());
        }

        $this->insertPosts();
    }

    function testConstructor()
    {
        $position = $this->createPosition();
        $this->assertTrue(is_object($position));
    }

    function testMoveUp()
    {
        $id = 3;
        $position = $this->createPosition($id);
        $this->assertTrue($position->moveUp());
        $this->assertEquals($position->getPosition(), $id - 1);
    }

    function testMoveDown()
    {
        $id = 3;
        $position = $this->createPosition($id);
        $this->assertTrue($position->moveDown());
        $this->assertEquals($position->getPosition(), $id + 1);
    }

    function testMoveToPosition()
    {
        $id = 3;
        $position = $this->createPosition($id);
        $pos = 6;
        $this->assertTrue($position->moveToPosition($pos));
        $this->assertEquals($position->getPosition(), $pos);
    }

    ///////////////////////////////////////////////////////////////////////////////

    function createPosition($id = 0)
    {
        return new Ilib_Position($this->db, $this->table, $id, '', 'position', 'id');
    }

    function insertPosts()
    {
        $data = array('one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten');
        foreach ($data as $key => $d) {
            $this->createPost($d, $key);
        }
    }

    function createPost($post, $position)
    {
        $result = $this->db->exec('INSERT INTO ' . $this->table . ' (name, position) VALUES ('.$this->db->quote($post, 'text').', '.$this->db->quote($position, 'integer').')');
        if (PEAR::isError($result)) {
            die($result->getUserInfo());
        }
    }

    function tearDown()
    {
        $result = $this->db->exec('DROP TABLE ' . $this->table);
    }
}
?>