<?php

class BancoDados {

    private String $dsn;
    private String $username;
    private String $password;
    private PDO $pdo;
    private Memcached $memcached;

    public function __construct()
    {
        $this->dsn = 'pgsql:host=postgres;port=5432;dbname=banco_dados_1;';
        $this->username = 'root';
        $this->password = 'root';
        $this->connect();
        $this->createTable();
        $this->initMemcached();
    }

    private function connect(): void
    {
        try {
            $this->pdo = new PDO($this->dsn, $this->username, $this->password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo "Erro na conexão: " . $e->getMessage();
            exit;
        }
    }

    private function createTable(): void
    {
        $stmt = "CREATE TABLE IF NOT EXISTS cats (
            id SERIAL PRIMARY KEY,
            cat_id VARCHAR(255) NOT NULL,
            image_url TEXT NOT NULL,
            width INT,
            height INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );";
        $this->pdo->exec($stmt);
    }

    private function initMemcached(): void
    {
        $this->memcached = new Memcached();
        $this->memcached->addServer('memcached', 11211);

        if (!$this->memcached->getVersion()) {
            die("Erro ao conectar ao Memcached.");
        }
    }

    public function insertCat(array $cat): void
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM cats WHERE cat_id = :cat_id");
        $stmt->bindParam(':cat_id', $cat['id']);
        $stmt->execute();
        $count = $stmt->fetchColumn();

        if ($count == 0) {
            $stmt = $this->pdo->prepare("INSERT INTO cats (cat_id, image_url, width, height) VALUES (:cat_id, :image_url, :width, :height)");
            $stmt->bindParam(':cat_id', $cat['id']);
            $stmt->bindParam(':image_url', $cat['url']);
            $stmt->bindParam(':width', $cat['width']);
            $stmt->bindParam(':height', $cat['height']);
            $stmt->execute();

            $cachedCats = $this->memcached->get('all_cats');
            if ($cachedCats === false) {
                $cachedCats = [];
            }
            $cachedCats[] = [
                'cat_id' => $cat['id'],
                'image_url' => $cat['url'],
                'width' => $cat['width'],
                'height' => $cat['height'],
            ];
            $this->memcached->set('all_cats', $cachedCats, 60);
        }
    }

    public function getAllCats(): array
    {
        $cachedCats = $this->memcached->get('all_cats');
        if ($cachedCats !== false) {
            return $cachedCats;
        }

        $stmt = $this->pdo->query("SELECT * FROM cats ORDER BY cat_id DESC;");
        $cats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->memcached->set('all_cats', $cats, 60);
        return $cats;
    }

    public function getAllCatsPaginated(int $limit, int $offset): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM cats ORDER BY cat_id DESC LIMIT :limit OFFSET :offset");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalCats(): int
    {
        $allCats = $this->memcached->get('all_cats');
        $countCached = is_array($allCats) ? count($allCats) : 0;
        if ($countCached > 0) {
            return $countCached;
        }
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM cats");
        return (int) $stmt->fetchColumn();
    }

    public function deleteAllCats(): void
    {
        $this->pdo->exec("DELETE FROM cats;");
        $this->memcached->delete('all_cats');
    }

    public function deleteCat(string $catId): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM cats WHERE cat_id = :cat_id");
        $stmt->bindParam(':cat_id', $catId);
        $stmt->execute();

        $cachedCats = $this->getAllCats();
        $updateCats = array_filter($cachedCats, function ($cat) use ($catId) {
            return $cat['cat_id'] !== $catId;
        });
        $this->memcached->set('all_cats', $updateCats, 60);
    }
}
