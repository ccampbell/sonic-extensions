<?php
namespace Sonic\Database\Query;
use Sonic\Database\Query;

/**
 * Query Sort Class
 *
 * @category Sonic
 * @package Database
 * @subpackage Query
 * @author Craig Campbell
 */
class Sort
{
    /**
     * @var string
     */
    const ASC = 'ASC';

    /**
     * @var string
     */
    const DESC = 'DESC';

    /**
     * @var array
     */
    protected $_columns = array();

    /**
     * @var array
     */
    protected $_directions = array();

    /**
     * @var array
     */
    protected $_preserve_data = true;

    /**
     * adds a sort to process
     *
     * @param string $column
     * @param string $direction (self::ASC || self::DESC)
     * @return void
     */
    public function add($column, $direction)
    {
        $this->_columns[] = $column;
        $this->_directions[] = $direction;
    }

    /**
     * tells the sort that we can scrap the data when we are done
     *
     * @return void
     */
    public function scrapData()
    {
        $this->_preserve_data = false;
    }

    /**
     * used for processing a single sort
     *
     * @param array $rows
     * @param string $column column to sort on
     * @param string $direction direction to sort (self::DESC || self::ASC)
     * @param bool $preserve_data should we return just ids or the full data set?
     * @return array
     */
    protected function _process($rows, $column, $direction)
    {
        $map = $all_data = array();
        foreach ($rows as $key => $row) {
            if (!is_array($row)) {
                $map[$row] = $row;
                continue;
            }

            // if you want to sort without selecting id
            $id = isset($row['id']) ? $row['id'] : $key;

            if ($this->_preserve_data) {
                $all_data['id:' . $id] = $row;
                $map['id:' . $id] = $row[$column];
                continue;
            }
            $map[$id] = $row[$column];
        }

        natcasesort($map);

        if ($direction == self::DESC) {
            $map = array_reverse($map, true);
        }

        if ($this->_preserve_data) {
            $all_data = array_merge($map, $all_data);
            return array_values($all_data);
        }

        return array_keys($map);
    }

    /**
     * called from Query to process the sorts based on the given data in the db
     *
     * @param array $rows data from database
     * @return array
     */
    public function process($rows)
    {
        if (count($rows) == 0) {
            return array();
        }

        // optimization if a single sort is present
        if (count($this->_columns) == 1) {
            return $this->_process($rows, $this->_columns[0], $this->_directions[0]);
        }

        // multiple sorts
        $columns = $directions = array();

        // loop through all the data
        foreach ($rows as $row) {

            // make arrays for each column to sort on with the same index as the parent row
            foreach ($this->_columns as $key => $column) {
                $columns[$key][] = $row[$column];
            }
        }

        // set up arguments to pass into array_multisort
        $args = array();
        foreach ($columns as $key => $column) {

            // to pass by reference the var needs a unique name
            $$key = $column;
            $args[] = &$$key;
            $directions[$key] = $this->_directions[$key] == self::DESC ? SORT_DESC : SORT_ASC;
            $args[] = &$directions[$key];
        }

        // finally append the actual data
        $args[] = &$rows;

        call_user_func_array('array_multisort', $args);

        return $rows;
    }
}
