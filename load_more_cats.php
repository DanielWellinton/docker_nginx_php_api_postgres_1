<?php
require_once 'db.php';
require_once 'api.php';

$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
unset($page);

$database = new BancoDados();
$api = new API();
$newCats = $api->getApi();
$response['cats'] = [];
if (isset($newCats['error'])) {
    $response['error'] = $newCats['error'];
} else {
    foreach ($newCats as $cat) {
        $database->insertCat($cat);
    }
    unset($cat);
}

$cats = $database->getAllCatsPaginated($limit, $offset);
$response['cats'] = $cats;
unset($cats);
unset($offset);

$totalCats = $database->getTotalCats();
$totalPages = ceil($totalCats / $limit);
$response['totalPages'] = $totalPages;
unset($totalCats);
unset($limit);
unset($totalPages);

echo json_encode($response);
