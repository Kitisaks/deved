<?php
$table = "notifications";
$fields = "
  id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  agency_id INT(10) UNSIGNED,
  user_id INT(10) UNSIGNED,
  page_id INT(10) UNSIGNED,
  email BOOLEAN DEFAULT FALSE,
  line BOOLEAN DEFAULT FALSE,
  sms BOOLEAN DEFAULT FALSE,
  inserted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (agency_id) REFERENCES agencies(id),
  FOREIGN KEY (page_id) REFERENCES pages(id)
";

print_r(query_sql("table", $table, $fields));
