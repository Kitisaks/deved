<?php
class Notfound extends Plug{

  private $main;
  
  function __construct(){
    parent::__construct();
    $this->main = strtolower(get_class($this));
    $this->view->render($this->main,"index");
  }

  public function index(){
    $this->view->render($this->main,"index");
  }
  
}