<?php
class AuthController
{

  function __construct()
  {
    $this->repo = new Repo();
    if (isset($_SESSION["current_user"])) {
      header("location: /home");
    }
  }

  #- login
  public function login()
  {
    if (!empty($_POST['token'])) {
      if (hash_equals($_SESSION['token'], $_POST['token'])) {
        $user_mail = trim(strtolower($_POST["user_mail"]));
        $password = md5(trim($_POST["password"]));
        try {
          $results =
            $this
            ->repo
            ->one("SELECT * FROM users WHERE (username = '$user_mail' OR email = '$user_mail') AND password = '$password'");

          switch ($results) {
            case false:
              $_SESSION["errno"] = [
                  "status" => 0,
                  "message" => "Username or password incorrect!"
                ];
              header("location: /auth");
              break;

            case true:
              $_SESSION["current_user"] = json_encode($results);
              header("location: /home");
              break;
          }
        } catch (PDOException $e) {
          echo "Error: " . $e->getMessage();
        }
      } else {
        header("location: /notfound");
      }
    }
  }

  public function signup()
  {
    $agencies = 
      $this
        ->repo
        ->all("SELECT cname, uuid FROM agencies ORDER BY cname ASC");
    $GLOBALS["agencies"] = $agencies;
  }

  #- recieve post method action
  public function create()
  {
    if (!empty($_POST['token'])) {
      if (hash_equals($_SESSION['token'], $_POST['token'])) {
        $e = $this->check($_POST["username"], $_POST["email"]);
        switch ($e) {
          case true:
            $_SESSION["errno"] = [
              "status" => 0,
              "message" => "Username or email already taken!"
            ];
            header("location: /auth/signup");
            break;

          case false:
            $this->init_register();
            break;
        }
      } else {
        header($_SERVER["SERVER_PROTOCOL"] . "405 Method Not Allowed");
      }
    }
  }

  #- check exist user in db
  private function check($username, $email)
  {
    $check =
      $this
      ->repo
      ->one("SELECT username, email FROM users WHERE username = '$username' OR email = '$email'");

    return $check;
  }

  #- start to register the form
  private function init_register()
  {
    try {
      $agency_id = $this->repo->one("SELECT id FROM agencies WHERE uuid = '$_POST[agency_id]'");

      $data = [
          "agency_id" => $agency_id["id"],
          "name" => trim($_POST["name"]),
          "username" => trim(strtolower($_POST["username"])),
          "password" => md5(trim($_POST["password"])),
          "email" => trim(strtolower($_POST["email"])),
          "gender" => trim($_POST["gender"]),
          "phone" => trim($_POST["phone"]),
          "ip" => trim($_POST["ip"]),
          "uuid" => GenUuid::uuid6()
      ];

      $this
        ->repo
        ->insert(
          "users",
          "agency_id, name, username, password, email, gender, phone, ip, uuid",
          "$data[agency_id], '$data[name]', '$data[username]', '$data[password]', '$data[email]', '$data[gender]', '$data[phone]', '$data[ip]', '$data[uuid]'"
        );

      header("location: /auth");
    } catch (PDOException $e) {
      echo "Error: " . $e->getMessage();
    }
  }

  public function logout()
  {
    if (isset($_SESSION["current_user"])) {
      session_destroy();
      header("location: /auth");
    } else {
      header("location: /home");
    }
  }

  public function reset()
  {
    if (!empty($_POST['token'])) {
      if (hash_equals($_SESSION['token'], $_POST['token'])) {
      } else {
        header("location: /notfound");
      }
    }
  }
}