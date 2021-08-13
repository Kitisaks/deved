<?php

class Project extends Plug
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
      ->render($this->main, "index"); 
  }

  public function new()
  {
    $this
      ->view
      ->render($this->main, "new");
  }

  public function create()
  {
    $this
      ->controller
      ->create();
  }
}
