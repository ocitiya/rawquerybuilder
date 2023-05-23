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
  private $sl = array();
  private $fr;
  private $lj = array();
  private $wh = array("query" => null, "params" => array());
  private $od = array();
  private $lm = null;
  private $ofs = null;
  private $gr = array();

  public function __construct($table) {
    $this->fr = "FROM " . $table;
  }

  public function select($lists, $params = array()) {
    if (is_array($lists)) {
      foreach ($lists as $item) {
        if (!in_array($item, $this->sl)) {
          $this->sl[] = $item;
        }
      }
    } else {
      if (!in_array($lists, $this->sl)) {
        $this->sl[] = $lists;
      }
    }

    foreach ($params as $p) {
      $this->wh['params'][] = $p;
    }

    return $this;
  }

  public function where($text, $params = array()) {
    if ($this->wh['query'] === null) {
      $this->wh['query'] = "WHERE $text ";
    } else {
      $this->wh['query'] .= "AND $text ";
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
  }

  public function offset($offset) {
    $this->ofs = $offset;
  }

  private function __generateSql($limit = true) {
    $params = array();
    
    if (count($this->sl) == 0) $this->sl = ["*"];
    $selects = implode(", ", $this->sl);
    $query = "SELECT " . $selects . " " . $this->fr . " ";

    if (count($this->lj) > 0) {
      $leftJoins = implode(" ", $this->lj);
      $query .= $leftJoins . " ";
    }

    if ($this->wh['query'] !== null) {
      $query .= $this->wh['query'] . " ";
      foreach ($this->wh['params'] as $p) {
        $params[] = $p;
      }
    }

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

    $data = DB::select($sql->raw, $sql->bindings);
    return $data;
  }

  public function first() {
    $this->lm = 1;
    $sql = $this->__generateSql();

    $data = DB::select($sql->raw, $sql->bindings);
    if (empty($data)) return null;
    else return $data[0];
  }

  public function count(String $column = null) {
    if (!is_null($column)) $this->sl = ["COUNT({$column}) AS total"];
    else $this->sl = ["COUNT(*) AS total"];

    $sql = $this->__generateSql(false);
    $data = DB::select($sql->raw, $sql->bindings);
    return $data[0]->total;
  }

  public function paginate($limit = 10, $page = 1) {
    $this->limit($limit);
    $this->offset(($page - 1) * $limit);

    $obj1 = clone $this;
    $obj2 = clone $this;

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

  public function toSql() {
    return $this->__generateSql();
  }
}