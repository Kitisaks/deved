<?php
#- all query db follow in here.
class Repo
{
  private $query = "";
  private $conn;

  function __construct()
  {
    $attributes = [
      PDO::ATTR_EMULATE_PREPARES => false,
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_PERSISTENT => true
    ];
    $this->conn =
      new PDO(
        "mysql:host=" . DB["host"] . ";dbname=" . DB["name"] . ";charset=utf8",
        DB["user"],
        DB["password"],
        $attributes
      );

    $this->conn->exec("set session sql_mode = traditional");
    $this->conn->exec("set session innodb_strict_mode = on");
  }

  public function distinct(bool $bool = false)
  {
    $this->distinct = $bool;
    return $this;
  }

  private function do_select($params)
  {
    if (is_array($params)) {
      $this->query .= join(",", $params);
    } else if (is_string($params)) {
      $this->query .= $params;
    }
  }

  public function select($params = "*")
  {
    if ($this->distinct()) {
      $this->query .= "select distinct ";
      $this->do_select($params);
      return $this;
    } else {
      $this->query .= "select ";
      $this->do_select($params);
      return $this;
    }
  }

  private function do_from($table)
  {
    if (is_array($table)) {
      $c = 0;
      foreach ($table as $t) {
        $c++;
        if ($c === count($table))
          $this->query .= $t;
        else
          $this->query .= $t . ",";
      }
    } else {
      $this->query .= $table;
    }
  }

  public function from($table)
  {
    if (empty($this->query)) {
      $this->query .= "select * from ";
      $this->do_from($table);
    } else {
      $this->query .= " from ";
      $this->do_from($table);
    }
    return $this;
  }

  public function where(string $clause)
  {
    $this->query .= " where {$clause}";
    return $this;
  }

  public function group_by(string $fields)
  {
    $this->query .= " group by {$fields}";
    return $this;
  }

  public function join($position, array $params)
  {
    foreach ($params as $key => $value) {
      $this->query .= " {$position} join {$key} on {$value}";
    }
    return $this;
  }

  public function order_by(array $params)
  {
    $this->query .= " order by ";
    $round = 0;
    foreach ($params as $key => $value) {
      $round++;
      if ($round > 1)
        $this->query .= ",{$value} {$key}";
      else
        $this->query .= "{$value} {$key}";
    }
    return $this;
  }

  public function limit(int $number)
  {
    $this->query .= " limit {$number}";
    return $this;
  }

  // public function preload(array $table)
  // {
  //   foreach ($table as $t) {
  //     $this
  //     ->select("*")
  //     ->from($table)
  //     ->where("id=");
  //   }
  // }

  #- Select one record
  public function one()
  {
    try {
      $stmt =
        $this
        ->conn
        ->prepare($this->query);
      $stmt->execute();
      $results = $stmt->fetch();
      $this->query = "";
      $this->conn = null;
      return $results;
    } catch (PDOException $e) {
      exit($e->getMessage());
    }
  }

  #- Select for universal
  public function all()
  {
    try {
      $stmt =
        $this
        ->conn
        ->prepare($this->query);
      $stmt->execute();
      $results = $stmt->fetchAll();
      $this->query = "";
      $this->conn = null;
      return $results;
    } catch (PDOException $e) {
      exit($e->getMessage());
    }
  }


  #- Fetch all record in table by ID
  public function get($table, int $id)
  {
    return
      $this
      ->select("*")
      ->from($table)
      ->where("id={$id}")
      ->one();
  }

  #- Fetch all record in table by specific params
  public function get_by($table, string $clause)
  {
    return
      $this
      ->select("*")
      ->from($table)
      ->where($clause)
      ->order_by(["desc" => "id"])
      ->one();
  }

  #- update with specific
  public function update($table, array $data, array $params)
  {
    foreach ($params as $key => $val) {
      if (is_string($val))
        $values[] = "{$key}='{$val}'";
      else
        $values[] = "{$key}={$val}";
    }
    $value = join(",", $values);
    try {
      $sql = "UPDATE {$table} SET {$value} WHERE id={$data['id']}";
      $this
        ->conn
        ->prepare($sql)
        ->execute();
      $this->conn = null;
      return $this->get($table, $data["id"]);
    } catch (PDOException $e) {
      exit($e->getMessage());
    }
  }

  public function insert($table, array $params)
  {
    foreach ($params as $key => $val) {
      $keys[] = $key;
      $binds[] = ":{$key}";
      $data[":{$key}"] = $val;
    }
    $col = join(",", $keys);
    $bind = join(",", $binds);
    try {
      $sql = "INSERT INTO {$table} ({$col}) VALUES ({$bind})";
      $this
        ->conn
        ->prepare($sql)
        ->execute($data);
      $this->conn = null;
      return true;
    } catch (PDOException $e) {
      exit($e->getMessage());
    }
  }

  public function delete($table, int $id)
  {
    try {
      $sql = "DELETE FROM {$table} WHERE id={$id}";
      $this
        ->conn
        ->prepare($sql)
        ->execute();
      $this->conn = null;
      return true;
    } catch (PDOException $e) {
      exit($e->getMessage());
    }
  }
}
