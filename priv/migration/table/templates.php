<?php
#- keep templates information
$table = "templates";
$fields = "
  id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  agency_id INT(10) UNSIGNED,
  user_id INT(10) UNSIGNED,
  permalink VARCHAR(100) NULL,
  title VARCHAR(100) NULL,
  content TEXT NULL,
  inserted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
";

print_r(query_sql("table", $table, $fields));
