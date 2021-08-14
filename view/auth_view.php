<?php

class Auth extends Plug
{

  function __construct()
  {
    parent::__construct();
    $this->main = strtolower(__CLASS__);
  }

  public function index()
  {
    $this
      ->view
      ->render($this->main, "index", 1);
  }

  public function login()
  {
    $this
      ->controller
      ->login();
  }

  public function reset()
  {
    $this
      ->controller
      ->reset();
    $this
      ->view
      ->render($this->main, "reset", 1);
  }

  public function signup()
  {
    $this
      ->controller
      ->signup();
    $this
      ->view
      ->render($this->main, "signup", 1);
  }

  public function add()
  {
    $this
      ->controller
      ->create();
  }

  public function logout()
  {
    $this
      ->controller
      ->logout();
  }
}
