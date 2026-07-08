<?php

declare(strict_types=1);

namespace App\Modules\Review\Presentation\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Session;
use PDO;

class AdminReviewController
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = db();
    }

    public function index(Request $request): Response
    {
        $page    = max(1, (int) $request->query('page', 1));
        $perPage = 20;
        $rating  = $request->query('rating', '');
        $search  = (string) $request->query('search', '');
        $offset  = ($page - 1) * $perPage;

        $where  = ['1=1'];
        $params = [];

        if ($rating !== '') {
            $where[]         = 'r.rating = :rating';
            $params['rating'] = (int) $rating;
        }

        if ($search !== '') {
            $where[]          = '(p.name LIKE :search OR u.name LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        $whereSql = implode(' AND ', $where);

        $countStmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM reviews r
             JOIN products p ON p.id = r.product_id
             JOIN users u ON u.id = r.user_id
             WHERE {$whereSql}"
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->pdo->prepare(
            "SELECT r.*, p.name AS product_name, u.name AS user_name,
                (SELECT pi.path FROM product_images pi WHERE pi.product_id = r.product_id AND pi.is_primary = 1 LIMIT 1) AS product_image
             FROM reviews r
             JOIN products p ON p.id = r.product_id
             JOIN users u ON u.id = r.user_id
             WHERE {$whereSql}
             ORDER BY r.created_at DESC
             LIMIT :limit OFFSET :offset"
        );

        foreach ($params as $key => $val) {
            $stmt->bindValue(':' . $key, $val);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $reviews = $stmt->fetchAll();

        // Ambil gambar per review
        foreach ($reviews as &$review) {
            $imgStmt = $this->pdo->prepare('SELECT path FROM review_images WHERE review_id = :review_id');
            $imgStmt->execute(['review_id' => $review['id']]);
            $review['images'] = array_column($imgStmt->fetchAll(), 'path');
        }

        return Response::make(view('Review::admin.index', [
            'title'        => 'Ulasan Produk',
            'reviews'      => $reviews,
            'total'        => $total,
            'page'         => $page,
            'perPage'      => $perPage,
            'search'       => $search,
            'ratingFilter' => $rating,
        ]));
    }

    public function destroy(Request $request, string $id): Response
    {
        // Hapus gambar dulu
        $imgs = $this->pdo->prepare('SELECT path FROM review_images WHERE review_id = :id');
        $imgs->execute(['id' => $id]);
        foreach ($imgs->fetchAll() as $img) {
            $path = base_path('storage/uploads/' . $img['path']);
            if (file_exists($path)) {
                unlink($path);
            }
        }

        $this->pdo->prepare('DELETE FROM reviews WHERE id = :id')->execute(['id' => $id]);

        Session::flash('success', 'Ulasan berhasil dihapus.');

        return Response::redirect('/admin/reviews');
    }
}