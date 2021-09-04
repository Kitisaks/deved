<?php
class ProjectController
{
  function __construct()
  {
    Plug::permitted();
    $this->view = new View(__CLASS__);
    $this->repo = new Repo();
  }

  public function index($conn)
  {
    $agency_id = $conn["agency"]["id"];

    $pages =
      $this
      ->repo
      ->select([
        "p.id as page_id",
        "p.permalink as page_permalink",
        "p.uuid as page_uuid",
        "p.status as page_status",
        "p.meta_title as page_meta_title",
        "p.meta_description as page_meta_description",
        "p.inserted_at as page_inserted_at",
        "u.name as user_name",
        "u.role as user_role",
        "a.name as attachment_name",
        "a.kind as attachment_kind",
        "a.title as attachment_title"
      ])
      ->from("pages p")
      ->join("left", [
        "users u" => "p.user_id = u.id",
        "attachments a" => "a.page_id = p.id"
      ])
      ->where("p.agency_id = {$agency_id} and a.kind = 'page'")
      ->order_by(["desc" => "p.id"])
      ->limit(8)
      ->all();

    $attachments = AttachmentData::assigns($pages, "favicon");

    $count_unpub = count(
      array_filter(
        $pages,
        function ($page) {
          return $page["page_status"] === 1;
        }
      )
    );

    $count_pub = count(
      array_filter(
        $pages,
        function ($page) {
          return $page["page_status"] === 2;
        }
      )
    );

    $total = $count_unpub + $count_pub;

    if ($total > 0) {
      $statics = [
        "total" => $total,
        "percent_pub" => ($count_pub / $total) * 100,
        "percent_unpub" => ($count_unpub / $total) * 100
      ];
    } else {
      $statics = [
        "total" => 0,
        "percent_pub" => 0,
        "percent_unpub" => 0
      ];
    }

    $this
      ->view
      ->assign("attachments", $attachments)
      ->assign("pages", $pages)
      ->assign("statics", $statics)
      ->render("index.html");
  }

  public function show($conn, $params)
  {
    $page =
      $this
      ->repo
      ->select([
        "p.id as page_id",
        "p.permalink as page_permalink",
        "p.uuid as page_uuid",
        "p.title as page_title",
        "p.content as page_content",
        "p.description as page_description",
        "p.meta_title as page_meta_title",
        "p.meta_description as page_meta_description",
        "p.inserted_at as page_inserted_at",
        "u.id as user_id",
        "u.name as user_name",
        "u.email as user_email",
        "u.phone as user_phone",
        "u.role as user_role",
        "a.name as attachment_name",
        "a.kind as attachment_kind",
        "a.title as attachment_title"
      ])
      ->from("pages p")
      ->join("left", [
        "users u" => "p.user_id = u.id",
        "attachments a" => "a.page_id = p.id"
      ])
      ->where("p.agency_id = {$conn['agency']['id']} and p.uuid = '{$params['uuid']}'")
      ->one();

    $cover_image = AttachmentData::assign($page, "cover_image");
    $favicon = AttachmentData::assign($page, "favicon");

    // $cover_image = AttachmentData::default_image($cover_image, 47);
    // print_r($cover_image);
    // exit;
    $this
      ->view
      ->assign("page", $page)
      ->assign("cover_image", $cover_image)
      ->assign("favicon", $favicon)
      ->render("show.html");
  }

  public function new($conn, $params)
  {
    $this
      ->view
      ->render("new.html");
  }

  public function create($conn, $params)
  {
    #- Validate phone number
    if (!Utils::validate_phone_number($params["user"]["phone"])) {
      $this
        ->view
        ->put_flash(false, "Your phone number is not valid.")
        ->redirect("/project/new");
    }

    $agency_id = $conn["agency"]["id"];

    $user = [
      "agency_id" => $agency_id,
      "uuid" => $params["user"]["uuid"],
      "username" => RandUsername::generate(),
      "name" => trim($params["user"]["name"]),
      "password" => md5(trim($params["user"]["password"])),
      "email" => strtolower(trim($params["user"]["email"])),
      "phone" => $params["user"]["phone"]
    ];

    if ($this->repo->insert("users", $user)) {
      $data_user = $this->repo->get_by("users", "uuid='{$user['uuid']}'");
    } else {
      $this
        ->view
        ->put_flash(false, "Somethings went wrong.")
        ->redirect("/project/new");
    }

    $page = [
      "agency_id" => $agency_id,
      "user_id" => $data_user["id"],
      "uuid" => $params["page"]["uuid"],
      "permalink" => strtolower(trim($params["page"]["permalink"])),
      "meta_title" => trim($params["page"]["meta_title"]),
      "meta_description" => trim($params["page"]["meta_description"])
    ];

    if ($this->repo->insert("pages", $page)) {
      $data_page = $this->repo->get_by("pages", "uuid='{$page['uuid']}'");
    } else {
      $this
        ->view
        ->put_flash(false, "Somethings went wrong.")
        ->redirect("/project/new");
    }

    $notification = [
      "agency_id" => $agency_id,
      "user_id" => $data_user["id"],
      "page_id" => $data_page["id"]
    ];

    if (isset($params["notification"]["line"]))
      array_merge(
        $notification,
        ["line" => ($params["notification"]["line"] == "on") ? 1 : 0]
      );

    if (isset($_POST["notification"]["email"]))
      array_merge(
        $notification,
        ["email" => ($params["notification"]["email"] == "on") ? 1 : 0]
      );

    if (!$this->repo->insert("notifications", $notification)) {
      $this
        ->view
        ->put_flash(false, "Somethings went wrong.")
        ->redirect("/project/new");
    }

    if ($_FILES["attachment"]["name"]["favicon"] != "") {
      $favicon = [
        "agency_id" => $agency_id,
        "page_id" => $data_page["id"],
        "name" => basename($_FILES["attachment"]["name"]["favicon"]),
        "kind" => "page",
        "title" => "favicon",
        "type" => FileHandler::mime_content_type($_FILES["attachment"]["tmp_name"]["favicon"])
      ];

      if (!$this->repo->insert("attachments", $favicon)) {
        $this
          ->view
          ->put_flash(false, "Somethings went wrong.")
          ->redirect("/project/new");
      }

      $favicon =
        array_merge(
          $favicon,
          ["tmp_name" => $_FILES["attachment"]["tmp_name"]["favicon"]]
        );

      AttachmentData::upload_file_ftp($favicon);
    }

    if ($_FILES["attachment"]["name"]["cover_image"] != "") {
      $cover_image = [
        "agency_id" => $agency_id,
        "page_id" => $data_page["id"],
        "name" => basename($_FILES["attachment"]["name"]["cover_image"]),
        "kind" => "page",
        "title" => "cover_image",
        "type" => FileHandler::mime_content_type($_FILES["attachment"]["tmp_name"]["cover_image"])
      ];

      if (!$this->repo->insert("attachments", $cover_image)) {
        $this
          ->view
          ->put_flash(false, "Somethings went wrong.")
          ->redirect("/project/new");
      }

      $cover_image =
        array_merge(
          $cover_image,
          ["tmp_name" => $_FILES["attachment"]["tmp_name"]["cover_image"]]
        );

        AttachmentData::upload_file_ftp($cover_image);
    }

    $this
      ->view
      ->put_flash(true, "Already created your page.")
      ->redirect("/project/{$data_page['uuid']}");
  }
}
