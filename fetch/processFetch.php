<?php

$requestFetch = true;

require_once "../config/APP.php";

session_name(NAMESESSION);
session_start();

if (isset($_SESSION["token"])) {
  require_once "./../Controllers/ProcessController.php";
  $process = new ProcessController();

  // echo print_r($_POST);
  if (isset($_POST["tx_title"]) && isset($_POST["tx_type"]) && isset($_POST["tx_authors"]) && isset($_POST["tx_descri"]) && isset($_FILES["file"])) {
    echo $process->generateProcessController();
  }
} else {
  session_unset();
  session_destroy();
  header("Location:" . SERVER_URL . "/login");
  exit();
}
