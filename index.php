<?php

require_once "db.php";
require_once "api.php";

$database = new BancoDados();

$limit = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$allCats = $database->getAllCatsPaginated($limit, $offset);
unset($offset);

$totalCats = $database->getTotalCats();
unset($database);

$totalPages = ceil($totalCats / $limit);
unset($totalCats);
unset($limit);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API do Gato</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header-container">
            <h1>Lista de gatos</h1>
            <button id="loadMore">Carregar +</button>
            <form method="POST" action="delete_all_cats.php">
                <button type="submit">Deletar todos</button>
            </form>
        </div>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>" class="<?= $i === $page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
        <table>
            <thead>
                <tr>
                    <th>ID API</th>
                    <th>URL</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($allCats as $cat): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($cat['cat_id']) ?></td>
                        <td><img src="<?php echo htmlspecialchars($cat['image_url']); ?>" alt="Imagem do gato" width="100"></td>
                        <td>
                            <form method="POST" action="delete_cat.php">
                                <input type="hidden" name="cat_id" value="<?= htmlspecialchars($cat['cat_id']) ?>">
                                <button type="submit">Deletar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <script>
        $(document).ready(function () {
            let currentPage = <?= $page ?>;

            function loadCatsFromAPI() {
                $('table tbody tr').remove();
                $.ajax({
                    url: 'load_more_cats.php',
                    method: 'POST',
                    data: { page: currentPage },
                    success: function (response) {
                        const resp = JSON.parse(response);
                        if (resp['error']) {
                            alert("Aviso: " + resp['error']);
                        } else  {
                            resp['cats'].forEach(cat => {
                            $('table tbody').append(`
                                <tr>
                                    <td>${cat.cat_id}</td>
                                    <td><img src="${cat.image_url}" alt="Imagem do gato" width="100"></td>
                                    <td>
                                        <form method="POST" action="delete_cat.php">
                                            <input type="hidden" name="cat_id" value="${cat.cat_id}">
                                            <button type="submit">Deletar</button>
                                        </form>
                                    </td>
                                `);
                            });
                            $('.pagination a').remove()
                            for (let i = 1; i <= resp['totalPages']; i++) {
                                $('.pagination').append(`
                                    <a href="?page=${ i }" class="${i === currentPage ? 'active' : ''}">
                                        ${ i }
                                    </a>
                                `);
                            }
                        }
                    }
                })
            }

            $('#loadMore').on('click', function () {
                loadCatsFromAPI();
            });
        })
    </script>
</body>
</html>