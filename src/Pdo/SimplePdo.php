<?php
namespace Beehive\Pdo;

use PDO;
use Exception;

/**
 * App Facade
 *
 * @author Ewenlaz
 */
class App extends PDO
{
    public function fetchOne($sql)
    {
        $stm = $this->query($sql, PDO::FETCH_ASSOC);
        if (!$stm) {
            throw new Exception($this->errorInfo()[2], 1);
        }
        $data = $stm->fetchAll();
        if ($data) {
            return $data[0];
        } else {
            return [];
        }
    }

    public function fetch($sql)
    {
        $stm = $this->query($sql, PDO::FETCH_ASSOC);
        if (!$stm) {
            throw new Exception($this->errorInfo()[2], 1);
        }
        $data = $stm->fetchAll();
        if ($data) {
            return $data;
        } else {
            return [];
        }
    }

    public function update($table, $fields = [], $where = '1 <> 1')
    {
        $sets = [];
        foreach ($fields as $filed => $val) {
            if ($val === null) {
                $val = 'null';
            } else {
                $val = $this->quote($val);
            }
            $sets[] = '`' . $filed . '` = ' . $val;
        }
        $sql = 'UPDATE %s SET %s WHERE %s';
        $count = $this->exec(sprintf(
            $sql,
            $table,
            implode(', ', $sets),
            $where
        ));

        return $count ? true : false;
    }

    public function exec($sql)
    {
        return parent::exec($sql);
    }

    public function count($table, $filed, $where)
    {
        $sql = 'SELECT COUNT(%s) AS __COUNT_FILED__ FROM %s WHERE %s LIMIT 1';
        $sql = sprintf($sql, $filed, $table, $where);
        $data = $this->fetchOne($sql);
        return isset($data['__COUNT_FILED__']) ? (int)$data['__COUNT_FILED__'] : 0;
    }

    public function insert($table, $fields = [])
    {
        foreach ($fields as $filed => &$val) {
            if ($val === null) {
                $val = 'null';
            } else {
                $val = $this->quote($val);
            }
        }
        $sql = 'INSERT INTO %s (%s) VALUES (%s)';
        $count = $this->exec(sprintf(
            $sql,
            $table,
            '`' . implode('`, `', array_keys($fields)) . '`',
            implode(', ', array_values($fields))
        ));
        return $count ? true : false;
    }

    public function insertAndGetId($table, $fields = [])
    {
        $res = $this->insert($table, $fields);
        if ($res) {
            return $this->lastInsertId();
        }
        return false;
    }

    public function execute($sql)
    {
        return $this->exec($sql);
    }
}