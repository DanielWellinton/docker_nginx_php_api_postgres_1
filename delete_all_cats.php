<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new BancoDados();
    $database->deleteAllCats();
    header('Location: index.php');
    exit;
}