<?php
class ProjectController
{
  function __construct()
  {
    $this->repo = new Repo();
  }

  public function index()
  {
    $agency_id = $GLOBALS["conn"]["agency"]["id"];
    $GLOBALS["unpublished"] =
      $this
      ->repo
      ->all(
        "SELECT p.id AS page_id, p.permalink AS page_permalink, p.uuid AS page_uuid, p.meta_title AS page_meta_title, p.meta_description AS page_meta_description, p.inserted_at AS page_inserted_at, u.name AS user_name, u.role AS user_role, a.name AS attachment_name, a.kind AS attachment_kind, a.title AS attachment_title
          FROM pages p
          LEFT JOIN users u ON p.user_id = u.id
          LEFT JOIN attachments a ON a.page_id = p.id
          WHERE p.agency_id = $agency_id AND a.kind = 'page' AND a.title = 'cover_image' AND p.status = 1 
          ORDER BY p.id DESC"
      );
    $GLOBALS["published"] =
      $this
      ->repo
      ->all(
        "SELECT p.id AS page_id, p.permalink AS page_permalink, p.uuid AS page_uuid, p.meta_title AS page_meta_title, p.meta_description AS page_meta_description, p.inserted_at AS page_inserted_at, u.name AS user_name, u.role AS user_role, a.name AS attachment_name, a.kind AS attachment_kind, a.title AS attachment_title
          FROM pages p
          LEFT JOIN users u ON p.user_id = u.id
          LEFT JOIN attachments a ON a.page_id = p.id
          WHERE p.agency_id = $agency_id AND a.kind = 'page' AND a.title = 'cover_image' AND p.status = 2 
          ORDER BY p.id DESC"
      );
    $count_unpub = 
      $this
      ->repo
      ->one(
        "SELECT DISTINCT count(p.id) AS num
        FROM pages p
        WHERE p.agency_id = $agency_id AND p.status = 1"
      );
    $count_pub = 
      $this
      ->repo
      ->one(
        "SELECT DISTINCT count(p.id) AS num
        FROM pages p
        WHERE p.agency_id = $agency_id AND p.status = 2"
      );
    $total = intval($count_unpub["num"]) + intval($count_pub["num"]);
    if ($total != 0) {
      $GLOBALS["statics"] = [
        "total" => $total,
        "percent_pub" => (intval($count_pub["num"])/$total)*100,
        "percent_unpub" => (intval($count_unpub["num"])/$total)*100
      ];
    } else {
      $GLOBALS["statics"] = [
        "total" => 0,
        "percent_pub" => 0,
        "percent_unpub" => 0
      ];
    }
  }

  public function create()
  {
    #- Validate phone number
    if (!Utils::validate_phone_number($_POST["user"]["phone"])) {
      $_SESSION["popup"] = ["status" => 0, "error" => "Your phone number is not valid."];
      header("location: /project/new");
      return false;
    }
    $agency_id = $GLOBALS["conn"]["agency"]["id"];
    $user = [
      "agency_id" => $agency_id,
      "uuid" => $_POST["user"]["uuid"],
      "username" => RandUsername::generate(),
      "name" => trim($_POST["user"]["name"]),
      "password" => md5(trim($_POST["user"]["password"])),
      "email" => trim(strtolower($_POST["user"]["email"])),
      "phone" => $_POST["user"]["phone"]
    ];
    #- Insert users table
    $resp_user =
      $this
      ->repo
      ->insert(
        "users",
        "agency_id, uuid, username, name, password, email, phone",
        "$user[agency_id], '$user[uuid]', '$user[username]', '$user[name]', '$user[password]', '$user[email]', '$user[phone]'"
      );
    if ($resp_user) {
      $data_user =
        $this
        ->repo
        ->get_by("users", "uuid", $user["uuid"]);
      #- Create directory for user
      $user_dir = FileHandler::create_dir("users/$user[uuid]");
      if (!$user_dir) {
        $_SESSION["popup"] = ["status" => 0, "error" => "Somethings went wrong."];
        header("location: /project/new");
        return false;
      }
    } else {
      $_SESSION["popup"] = ["status" => 0, "error" => "Somethings went wrong."];
      header("location: /project/new");
      return false;
    }
    $page = [
      "agency_id" => $agency_id,
      "user_id" => $data_user["id"],
      "uuid" => $_POST["page"]["uuid"],
      "permalink" => trim(strtolower($_POST["page"]["permalink"])),
      "meta_title" => trim($_POST["page"]["meta_title"]),
      "meta_description" => trim($_POST["page"]["meta_description"])
    ];
    #- Insert pages table
    $resp_page =
      $this
      ->repo
      ->insert(
        "pages",
        "agency_id, user_id, uuid, permalink, meta_title, meta_description",
        "$page[agency_id], $page[user_id], '$page[uuid]', '$page[permalink]', '$page[meta_title]', '$page[meta_description]'"
      );
    if ($resp_page) {
      $data_page =
        $this
        ->repo
        ->get_by("pages", "uuid", "$page[uuid]");
      #- Create page directory
      $page_dir = FileHandler::create_dir("pages/$page[uuid]");
      if (!$page_dir) {
        $_SESSION["popup"] = ["status" => 0, "error" => "Somethings went wrong."];
        header("location: /project/new");
        return false;
      }
    } else {
      $_SESSION["popup"] = ["status" => 0, "error" => "Somethings went wrong."];
      header("location: /project/new");
      return false;
    }
    $notification = [
      "agency_id" => $agency_id,
      "user_id" => $data_user["id"],
      "page_id" => $data_page["id"],
      "email" => ($_POST["notification"]["email"] == "on") ? 1 : 0,
      "line" => ($_POST["notification"]["line"] == "on") ? 1 : 0
    ];
    #- Insert notifications table
    $resp_notification =
      $this
      ->repo
      ->insert(
        "notifications",
        "agency_id, user_id, page_id, email, line",
        "$notification[agency_id], $notification[user_id], $notification[page_id], $notification[email], $notification[line]"
      );
    if (!$resp_notification) {
      $_SESSION["popup"] = ["status" => 0, "error" => "Somethings went wrong."];
      header("location: /project/new");
      return false;
    }
    $favicon = [
      "agency_id" => $agency_id,
      "user_id" => $data_user["id"],
      "page_id" => $data_page["id"],
      "name" => basename($_FILES["attachment"]["name"]["favicon"]),
      "kind" => "page",
      "title" => "favicon",
      "type" => FileHandler::mime_content_type($_FILES["attachment"]["tmp_name"]["favicon"]),
      "tmp_name" => $_FILES["attachment"]["tmp_name"]["favicon"]
    ];
    $cover_image = [
      "agency_id" => $agency_id,
      "user_id" => $data_user["id"],
      "page_id" => $data_page["id"],
      "name" => basename($_FILES["attachment"]["name"]["cover_image"]),
      "kind" => "page",
      "title" => "cover_image",
      "type" => FileHandler::mime_content_type($_FILES["attachment"]["tmp_name"]["cover_image"]),
      "tmp_name" => $_FILES["attachment"]["tmp_name"]["cover_image"]
    ];
    #- Insert favicon to attachments table
    $resp_favicon =
      $this
      ->repo
      ->insert(
        "attachments",
        "agency_id, user_id, page_id, name, kind, title, type",
        "$favicon[agency_id], $favicon[user_id], $favicon[page_id], '$favicon[name]', '$favicon[kind]', '$favicon[title]', '$favicon[type]'"
      );
    #- Insert cover image to attachments table
    $resp_cover_image =
      $this
      ->repo
      ->insert(
        "attachments",
        "agency_id, user_id, page_id, name, kind, title, type",
        "$cover_image[agency_id], $cover_image[user_id], $cover_image[page_id], '$cover_image[name]', '$cover_image[kind]', '$cover_image[title]', '$cover_image[type]'"
      );
    if (!$resp_favicon || !$resp_cover_image) {
      $_SESSION["popup"] = ["status" => 0, "error" => "Somethings went wrong."];
      header("location: /project/new");
      return false;
    } else {
      #- Upload all attachment to drive
      if (MODE == "DEV") {
        $uploads_dir = dirname($_SERVER["DOCUMENT_ROOT"]) . "/api/priv/drive/{$GLOBALS['conn']['agency']['cname']}";
      } elseif (MODE == "PRO") {
        $uploads_dir = dirname($_SERVER["DOCUMENT_ROOT"]) . "/db/priv/drive/{$GLOBALS['conn']['agency']['cname']}";
      }
      $files_upload = [$favicon, $cover_image];
      foreach ($files_upload as $file) {
        $tmp_name = $file["tmp_name"];
        $name = "{$file['kind']}-{$file['title']}-{$file['page_id']}-{$file['name']}";
        move_uploaded_file($tmp_name, "{$uploads_dir}/{$name}");
      }
      $_SESSION["popup"] = ["status" => 1, "info" => "Already created your page."];
      header("location: /project");
    }
  }
}
