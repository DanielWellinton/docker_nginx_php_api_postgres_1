<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $catId = $_POST['cat_id'] ?? null;
    if ($catId) {
        $database = new BancoDados();
        $database->deleteCat($catId);
        header('Location: index.php');
        exit;
    } else {
        echo "ID do gato não foi fornecido.";
    }
}