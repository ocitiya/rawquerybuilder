<?php
namespace App\Http\Helpers;

/*
  MIT License

  Copyright (c) 2023 Muhammad Rasyidi
  https://github.com/ocitiya/rawquerybuilder.git

  Permission is hereby granted, free of charge, to any person obtaining a copy
  of this software and associated documentation files (the "Software"), to deal
  in the Software without restriction, including without limitation the rights
  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
  copies of the Software, and to permit persons to whom the Software is
  furnished to do so, subject to the following conditions:

  The above copyright notice and this permission notice shall be included in all
  copies or substantial portions of the Software.

  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
  SOFTWARE. 
*/

use Illuminate\Support\Facades\DB;

class RawQueryBuilder {
    private $sl;
    private $fr;
    private $lj = array();
    private $wh = array("query" => null, "params" => array());
    private $od = array();
    private $lm = null;
    private $ofs = null;
    private $gr = array();
    private $set = array();

    private $table = null;
    private $primaryKey = "id";

    public static function table($table, $primaryKey = null) {
        return (new self($table, $primaryKey));
    }

    public function __construct($table, $primaryKey = null) {
        $this->sl = (object) ["query" => [], "params" => []];

        $this->table = $table;
        $this->fr = "FROM " . $table;
        
        if ($primaryKey) $this->primaryKey = $primaryKey;
    }

    public function set($key, $value) {
        array_push($this->set, "SET {$key} = {$value};");
        return $this;
    }

    public function select($lists, $params = array()) {
        if (is_array($lists)) {
            foreach ($lists as $item) {
                if (!in_array($item, $this->sl->query)) {
                    $this->sl->query[] = $item;
                }
            }
        } else {
            if (!in_array($lists, $this->sl->query)) {
                $this->sl->query[] = $lists;
            }
        }

        foreach ($params as $p) {
            $this->sl->params[] = $p;
        }

        return $this;
    }

    /**
     * @param string Text that contains "where query"
     * @param string|int|array Parameter value of query
     */
    public function where(string $text, $params = array()) {
        if ($this->wh['query'] === null) {
            $this->wh['query'] = "WHERE $text ";
        } else {
            $this->wh['query'] .= "AND $text ";
        }

        if (is_array($params)) {
            foreach ($params as $p) {
                $this->wh['params'][] = $p;
            }
        } else if (is_string($params)) {
            $this->wh['params'][] = $params;
        } else if (is_numeric($params)) {
            $this->wh['params'][] = $params;
        }

        return $this;
    }

    public function whereIn(string $text, array $data, $params = array()) {
        $value = implode(",", $data);

        if ($this->wh['query'] === null) {
            $this->wh['query'] = "WHERE {$text} IN ({$value})";
        } else {
            $this->wh['query'] .= "AND $text IN ({$value})";
        }

        foreach ($params as $p) {
            $this->wh['params'][] = $p;
        }

        return $this;
    }

    public function orWhere($text, $params = array()) {
        if ($this->wh['query'] === null) {
            $this->wh['query'] = "WHERE $text ";
        } else {
            $this->wh['query'] .= "OR $text ";
        }

        foreach ($params as $p) {
            $this->wh['params'][] = $p;
        }

        return $this;
    }

    public function leftJoin($lists, $params = array()) {
        if (is_array($lists)) {
            foreach ($lists as $item) {
                $query = "LEFT JOIN $item";
                if (!in_array($query, $this->lj)) {
                    $this->lj[] = $query;
                }
            }
        } else {
            $query = "LEFT JOIN $lists";
            if (!in_array($query, $this->lj)) {
                $this->lj[] = $query;
            }
        }

        foreach ($params as $p) {
            $this->wh['params'][] = $p;
        }
        
        return $this;
    }

    public function order($lists) {
        if (is_array($lists)) {
            foreach ($lists as $item) {
                if (!in_array($item, $this->od)) {
                    $this->od[] = $item;
                }
            }
        } else {
            if (!in_array($lists, $this->od)) {
                $this->od[] = $lists;
            }
        }

        return $this;
    }

    public function group($lists) {
        if (is_array($lists)) {
            foreach ($lists as $item) {
                if (!in_array($item, $this->gr)) {
                    $this->gr[] = $item;
                }
            }
        } else {
            if (!in_array($lists, $this->gr)) {
                $this->gr[] = $lists;
            }
        }

        return $this;
    }

    public function limit($limit) {
        $this->lm = $limit;
        return $this;
    }

    public function offset($offset) {
        $this->ofs = $offset;
        return $this;
    }

    private function _generateWhereQuery(&$query, &$bindings) {
        if ($this->wh['query'] !== null) {
            $query .= " " . $this->wh['query'] . " ";
            foreach ($this->wh['params'] as $p) {
                $bindings[] = $p;
            }
        }
    }

    private function __generateSql($limit = true) {
        $params = [];
        $query = "";

        if (count($this->sl->query) == 0) $this->sl->query = ["*"];
        $selects = implode(", ", $this->sl->query);
        $query .= "SELECT " . $selects . " " . $this->fr . " ";

        foreach($this->sl->params as $p) {
            $params[] = $p;
        }
        

        if (count($this->lj) > 0) {
            $leftJoins = implode(" ", $this->lj);
            $query .= $leftJoins . " ";
        }

        $this->_generateWhereQuery($query, $params);

        if (count($this->gr) > 0) {
            $groups = implode(", ", $this->gr);
            $query .= "GROUP BY $groups ";
        }

        if (count($this->od) > 0) {
            $orders = implode(", ", $this->od);
            $query .= "ORDER BY $orders ";
        }

        if ($limit) {
            if (!empty($this->lm)) {
                $query .= "LIMIT {$this->lm} ";
            }
        
            if (!empty($this->ofs)) {
                $query .= "OFFSET {$this->ofs} ";
            }
        }

        return (object) array('raw' => $query, 'bindings' => $params);
    }

    public function get() {
        $sql = $this->__generateSql();

        foreach ($this->set as $set) {
            DB::select($set);
        }

        $data = DB::select($sql->raw, $sql->bindings);
        return $data;
    }

    public function first() {
        $this->lm = 1;
        $sql = $this->__generateSql();

        foreach ($this->set as $set) {
            DB::select($set);
        }

        $data = DB::select($sql->raw, $sql->bindings);
        if (empty($data)) return null;
        else return $data[0];
    }

    public function count(String $column = null) {
        if (!is_null($column)) $this->sl->query = ["COUNT({$column}) AS total"];
        else $this->sl->query = ["COUNT(*) AS total"];
        $this->sl->params = [];

        $sql = $this->__generateSql(false);

        foreach ($this->set as $set) {
            DB::select($set);
        }
        
        $data = DB::select($sql->raw, $sql->bindings);
        return $data[0]->total;
    }

    public function paginate($limit = 10, $page = 1) {
        $this->limit($limit);
        $this->offset(($page - 1) * $limit);

        $obj1 = (clone $this);
        $obj2 = (clone $this);

        $data = $obj1->get();
        $count = $obj2->count();

        return (object) [
            "data" => $data,
            "pagination" => [
                "total_rows" => $count,
                "limit" => $limit, 
                "page" => $page,
                "last_page" => ceil($count / $limit)
            ]
        ];
    }

    public function update (Array $data) {
        $query = "UPDATE {$this->table} SET ";

        $sets = [];
        $bindings = [];
        foreach ($data as $column => $value) {
            array_push($sets, "{$column} = ?");
            array_push($bindings, $value);
        }

        $query .= implode(", ", $sets);
        $this->_generateWhereQuery($query, $bindings);
        DB::update($query, $bindings);
    }

    public function insert(Array $data) {
        $query = "INSERT INTO {$this->table} ";
    
        $columns = [];
        $bindings = [];
    
        foreach ($data as $column => $value) {
            if (!in_array($column, $columns)) array_push($columns, $column);
            array_push($bindings, $value);
        }
    
        $query .= "(" . implode(", ", $columns) . ") VALUES (" . rtrim(str_repeat("?, ", count($columns)), ', ') . ")";
      
        DB::insert($query, $bindings);
    }

    public function toSql() {
        return $this->__generateSql();
    }

    public function clone() {
        $clone = new RawQueryBuilder($this->table, $this->primaryKey);
        $clone->sl = clone $this->sl;
        $clone->fr = $this->fr;
        $clone->lj = $this->lj;
        $clone->wh = $this->wh;
        $clone->od = $this->od;
        $clone->lm = $this->lm;
        $clone->ofs = $this->ofs;
        $clone->gr = $this->gr;
        $clone->set = $this->set;
    
        return $clone;
    }
    
}