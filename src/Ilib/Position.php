<?php
/**
 * Handles custom sorting of a table
 *
 * PHP Version 4 and 5
 *
 * @category Database
 * @package  Ilib_Position
 * @author   Sune Jensen <sj@sunet.dk>
 * @author   Lars Olesen <lars@legestue.net>
 *
 */
require_once 'DB/Sql.php';

/**
 * Handles custom sorting of a table
 *
 * Requires the table to have a field to handle the positions.
 *
 * <code>
 * $position = new Ilib_Position('cms_page', 'site_id='.$cmssite->get('id'), 'position', 'id');
 * $position->moveUp();
 * </code>
 *
 * @category Database
 * @package  Ilib_Position
 * @author   Sune Jensen <sj@sunet.dk>
 * @author   Lars Olesen <lars@legestue.net>
 */
class Ilib_Position
{
    private $tabel;
    private $ekstrawhere;
    private $postionsfelt;
    private $idfelt;
    private $id;

    /**
     * Constructor
     *
     * <code>
     * $position_set = new Ilib_Position("indhold_site", "barn_af = 0 AND sprog = $session_sprog", "position", "id");
     * </code>
     *
     * @param string  $tabel         Navnet p� tabellen i databasen
     * @param integer $id            The id for the stuff to move
     * @param string  $ekstrawhere   @todo should this perhaps be an array? Bruges til at s�tte ekstraparameter i SQL-s�tning. Uden "AND" i starten af strengen.
     * @param string  $positionsfelt Indeholder navnet der indeholder postens position, ofte position
     * @param string  $idfelt        Unikt felt for tabellen. Ofte id.
     *
     * @return void
     */
    function __construct($tabel = '', $id, $ekstrawhere = '', $positionsfelt = 'position', $idfelt = 'id')
    {
        $this->tabel         = $tabel;
        $this->ekstrawhere   = $ekstrawhere;
        $this->positionsfelt = $positionsfelt;
        $this->idfelt        = $idfelt;
        $this->id            = intval($id);
    }

    /**
     * Bruges til at �ndre $ekstrawhere parameteren
     *
     * <code>
     * $position_set->newWhere("barn_af = 1 AND sprog = $session_sprog");
     * </code>
     *
     * @param string $ekstrawhere Ny where s�tning. Uden "AND" i starten
     *
     * @return void
     */
    function setWhere($ekstrawhere)
    {
        $this->ekstrawhere = $ekstrawhere;
    }

    /**
     * Gets the position for an id
     *
     * @return mixed
     */
    function getPosition()
    {
        if ($this->ekstrawhere != '') {
            $ekstrawhere = " AND ".$this->ekstrawhere;
        } else {
            $ekstrawhere = '';
        }

        $db = new DB_Sql;
        $sql = "SELECT $this->positionsfelt, $this->idfelt FROM $this->tabel WHERE $this->idfelt = " . $this->id . " $ekstrawhere LIMIT 1";
        $db->query($sql);
        if ($db->nextRecord()) {
            return $db->f($this->positionsfelt);
        }
        return false;
    }

    /**
     * Flytter en post en position op ad
     *
     * <code>
     * $position->moveUp();
     * </code>
     *
     * @return boolean
     */
    function moveUp()
    {
        $db = new DB_Sql;
        $db2 = new DB_Sql;

        $this->reposition();

        if ($this->ekstrawhere != '') {
            $ekstrawhere = " AND ".$this->ekstrawhere;
        } else {
            $ekstrawhere = '';
        }

        // Finder position for post
        $sql = "SELECT $this->positionsfelt, $this->idfelt FROM $this->tabel WHERE $this->idfelt = " . $this->id . " $ekstrawhere LIMIT 1";
        $db->query($sql);

        if ($db->nextRecord()) {
            if ($db->f($this->positionsfelt) == 1) {
                //trigger_error("Denne post er den �verste og kan ikke flyttes op", E_USER_WARNING);
                return false;
            } else {
                $sql = "SELECT " . $this->idfelt . " FROM " . $this->tabel . " WHERE " . $this->positionsfelt . " < " .$db->f($this->positionsfelt)." " . $ekstrawhere . " ORDER BY " . $this->positionsfelt . " DESC";
                $db2->query($sql);
                if ($db2->nextRecord()) {
                    $sql = "UPDATE " . $this->tabel . " SET " . $this->positionsfelt . "=" . $this->positionsfelt . "+1 WHERE " . $this->idfelt ."=".$db2->f($this->idfelt)." " . $ekstrawhere;
                    $db2->query($sql);
                    $sql = "UPDATE " . $this->tabel . " SET " . $this->positionsfelt ."=". $this->positionsfelt ."-1 WHERE " . $this->idfelt ."=".$db->f($this->idfelt)." " . $ekstrawhere;
                    $db2->query($sql);
                    return true;
                } else {
                    //trigger_error("Kunne ikke flytte posten. Ingen post f�r", E_USER_WARNING);
                    return false;
                }
            }
        } else {
            //trigger_error("Kunne ikke flytte posten. Posten eksisterede ikke.", E_USER_WARNING);
            return false;
        }
        $this->reposition();
        return true;
    }

    /**
     * Flytter posten 1 ned
     *
     * <code>
     * $position->moveDown();
     * </code>
     *
     * @return boolean
     */
    function moveDown()
    {
        $db = new DB_Sql;
        $db2 = new DB_Sql;

        $this->reposition();

        if ($this->ekstrawhere != '') {
            $ekstrawhere = " AND ".$this->ekstrawhere;
        } else {
            $ekstrawhere = '';
        }

        // Finder position for post
        $sql = "SELECT $this->positionsfelt, $this->idfelt FROM $this->tabel WHERE $this->idfelt = $this->id $ekstrawhere LIMIT 1";
        $db->query($sql);
        if ($db->nextRecord()) {
            if ($db->f($this->positionsfelt) == $this->maxpos()) {
                //trigger_error("Denne er allerede nederst, s� den kunne ikke flyttes ned", E_USER_WARNING);
                return false;
            } else {
                $sql = "SELECT $this->idfelt FROM $this->tabel WHERE $this->positionsfelt > ".$db->f($this->positionsfelt)." $ekstrawhere ORDER BY $this->positionsfelt";
                $db2->query($sql);
                if ($db2->nextRecord()) {
                    $sql = "UPDATE $this->tabel SET $this->positionsfelt = $this->positionsfelt - 1 WHERE $this->idfelt = ".$db2->f($this->idfelt)." $ekstrawhere";
                    $db2->query($sql);
                    $sql = "UPDATE $this->tabel SET $this->positionsfelt = $this->positionsfelt + 1 WHERE $this->idfelt = ".$db->f($this->idfelt)." $ekstrawhere";
                    $db2->query($sql);
                    return true;

                } else {
                    //trigger_error("Kunne ikke flytte posten. Ingen post efter", E_USER_WARNING);
                    return false;
                }
            }
        } else {
            //trigger_error("Kunne ikke flytte posten. Posten eksisterede ikke.", E_USER_WARNING);
            return false;
        }
        $this->reposition();
        return true;
    }

    /**
     * Bruges til at placere en post p� en bestemt id.
     * Mangler et eksempel p� et godt interface.
     *
     * @param integer $position Den position id'en skal have
     *
     * @return boolean
     */
    function moveTo($position)
    {
        // f�rst l�gger vi en til alle posterne fra det nummer denne post vil have
        $this->reposition($position, $position+1);
        $db = new DB_Sql;

        if ($this->ekstrawhere != '') {
            $ekstrawhere = " AND " . $this->ekstrawhere;
        } else {
            $ekstrawhere = '';
        }


        $sql = "UPDATE " . $this->tabel . " SET " . $this->positionsfelt . " = " . $position . " WHERE " . $this->idfelt . " = ".$this->id . $ekstrawhere;
        $db->query($sql);
        return true;
    }

    /**
     * Bruges til at placere en ny post p� den sidste id.
     *
     * <code>
     * $position->moveToMax();
     * </code>
     *
     * @param integer $id Id p� den post der skal flyttes til sidste post
     *
     * @return boolean
     */
    function moveToMax()
    {
        $db = new DB_Sql;

        if ($this->ekstrawhere != '') {
            $ekstrawhere = " AND " . $this->ekstrawhere;
        } else {
            $ekstrawhere = '';
        }

        $maxpos = $this->maxpos() + 1;

        $sql = "UPDATE " . $this->tabel . " SET " . $this->positionsfelt . " = " . $maxpos . " WHERE " . $this->idfelt . " = ".$this->id . $ekstrawhere;
        $db->query($sql);
        return true;
    }

    /**
     * Repositionere alle poster, s� de f�r l�bende positioner startende fra $position
     *
     * <code>
     * $position->reposition();
     * </code>
     *
     * @param integer $start_from_position Det tal repositioneringen skal starte fra. Optional.
     * @param integer $new_position        Den nye position posterne f�r
     *
     * @return void
     */
    function reposition($start_from_position = 0, $new_position = 1)
    {
        $db = new DB_Sql;
        $db2 = new DB_Sql;

        if ($this->ekstrawhere != "") {
            $where = " WHERE $this->ekstrawhere";
            $ekstrawhere = " AND " . $this->ekstrawhere; // bruges i db2
        } else {
            $where = 'WHERE 1=1';
            $ekstrawhere = '';
        }

        $sql = "SELECT $this->positionsfelt, $this->idfelt FROM $this->tabel $where AND $this->positionsfelt >= $start_from_position ORDER BY $this->positionsfelt";
        $db->query($sql);
        while ($db->nextRecord()) {
            $sql = "UPDATE ".$this->tabel." SET ".$this->positionsfelt." = ".$new_position." WHERE ".$this->idfelt." = ".$db->f($this->idfelt) . " " . $ekstrawhere;
            $db2->query($sql);
            $new_position++;
        }
    }

    /**
     * Finder den h�jeste position
     *
     * <code>
     * $position->maxpos();
     * </code>
     *
     * @return  integer Returnere tal med den h�jeste position
     */
    function maxpos()
    {
        $db = new DB_Sql;

        if ($this->ekstrawhere != "") {
            $where = " WHERE $this->ekstrawhere";
        } else {
            $where = '';
        }

        $sql = "SELECT MAX(".$this->positionsfelt.") AS maxpos FROM ".$this->tabel." ".$where;
        $db->query($sql);
        if ($db->nextRecord()) {
            return $db->f("maxpos");
        }

        return 0;
    }
}