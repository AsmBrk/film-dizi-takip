<?php
session_start();
require_once 'config.php';

function check_user_session() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
    return $_SESSION['user_id'];
}

$user_id = check_user_session();
$theme = $_COOKIE['theme'] ?? 'light';

$query = "SELECT 
    c.id AS content_id,
    c.title,
    c.type,
    w.id AS watchlist_id,
    w.rating,
    w.comment
FROM watched_list w
JOIN content c ON w.content_id = c.id
WHERE w.user_id = ?
ORDER BY w.id DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$movies = [];
$series = [];

while ($row = $result->fetch_assoc()) {
    if ($row['type'] === 'movie') {
        $movies[] = $row;
    } else {
        $series[] = $row;
    }
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="tr" data-bs-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <title>İzlediklerim</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container mt-4">
    <h2>İzlediklerim
        <button type="button" class="btn btn-primary float-end" data-bs-toggle="modal" data-bs-target="#addContentModal" onclick="resetForm()">
            <i class="fas fa-plus"></i> İçerik Ekle
        </button>
    </h2>

    <!-- Modal -->
    <div class="modal fade" id="addContentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="formModalTitle">İzlediklerime Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addContentForm" action="add_watched_content.php" method="POST">
                    <div class="modal-body">
                        <div id="formErrorMessages" class="alert alert-danger d-none"></div>

                        <input type="hidden" id="watchlist_id" name="watchlist_id">

                        <div class="mb-3">
                            <label for="title" class="form-label">Başlık</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>

                        <div class="mb-3">
                            <label for="type" class="form-label">Tür</label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="">Seçiniz</option>
                                <option value="movie">Film</option>
                                <option value="series">Dizi</option>
                            </select>
                        </div>
                    
                        <div class="mb-3">
                            <label for="comment" class="form-label">Yorum</label>
                            <textarea class="form-control" id="comment" name="comment" rows="3"></textarea>
                        </div>


                        <div class="mb-3">
                            <label for="rating" class="form-label">Puan (1-10)</label>
                            <select class="form-select" id="rating" name="rating" required>
                                <option value="">Seçiniz</option>
                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                    <option value="<?= $i ?>"><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="mb-3 form-check" id="approvalCheck">
                            <input type="checkbox" class="form-check-input" id="approval" name="approval" required>
                            <label class="form-check-label" for="approval">Valla izledim</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary">Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <h2>İzlediğiniz İçerikler</h2>
    <ul class="nav nav-tabs" id="tabList" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="movies-tab" data-bs-toggle="tab" data-bs-target="#movies" type="button">
                Filmler (<?php echo count($movies); ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="series-tab" data-bs-toggle="tab" data-bs-target="#series" type="button">
                Diziler (<?php echo count($series); ?>)
            </button>
        </li>
    </ul>

    <div class="tab-content mt-3">
        <div class="tab-pane fade show active" id="movies" role="tabpanel">
            <div class="row">
                <?php foreach ($movies as $movie): ?>
                    <div class="col-md-4">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($movie['title']) ?></h5>
                                <?php if($movie['rating']) : ?>
                                <p class="card-text">Puan: <?= $movie['rating'] ?>/10</p>
                                <?php endif; ?>
                                <?php if($movie['comment']) : ?>
                                <p class="card-text">Yorum: <?= htmlspecialchars($movie['comment']) ?></p>
                                <?php endif; ?>
                                <div class="btn-group mt-2" role="group">
                                    <button class="btn btn-sm btn-outline-secondary" 
                                            onclick='openEditModal(<?= json_encode($movie) ?>)'>
                                        <i class="fas fa-edit"></i> Düzenle
                                    </button>
                                    <a href="delete_content.php?id=<?= $movie['watchlist_id'] ?>&list=watched"
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Bu içeriği silmek istediğinizden emin misiniz?')">
                                        <i class="fas fa-trash"></i> Sil
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="tab-pane fade" id="series" role="tabpanel">
            <div class="row">
                <?php foreach ($series as $serie): ?>
                    <div class="col-md-4">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($serie['title']) ?></h5>
                                <?php if($serie['rating']) : ?>
                                <p class="card-text">Puan: <?= $serie['rating'] ?>/10</p>
                                <?php endif; ?>
                                <?php if($serie['comment']) : ?>
                                <p class="card-text">Yorum: <?= htmlspecialchars($serie['comment']) ?></p>
                                <?php endif; ?>
                                <div class="btn-group mt-2" role="group">
                                    <button class="btn btn-sm btn-outline-secondary" 
                                            onclick='openEditModal(<?= json_encode($serie) ?>)'>
                                        <i class="fas fa-edit"></i> Düzenle
                                    </button>
                                    <a href="delete_content.php?id=<?= $serie['watchlist_id'] ?>&list=watched"
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Bu içeriği silmek istediğinizden emin misiniz?')">
                                        <i class="fas fa-trash"></i> Sil
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
function resetForm() {
    const form = document.getElementById("addContentForm");
    form.reset();
    document.getElementById("formModalTitle").innerText = "İzlediklerime Ekle";
    document.getElementById("watchlist_id").value = "";

    const approval = document.getElementById("approval");
    const approvalGroup = document.getElementById("approvalCheck");
    approvalGroup.classList.remove("d-none");
    approval.required = true;
}

function openEditModal(data) {
    document.getElementById("formModalTitle").innerText = "İçeriği Düzenle";
    document.getElementById("title").value = data.title;
    document.getElementById("type").value = data.type;
    document.getElementById("comment").value = data.comment;
    document.getElementById("rating").value = data.rating;
    document.getElementById("watchlist_id").value = data.watchlist_id;

    const approval = document.getElementById("approval");
    const approvalGroup = document.getElementById("approvalCheck");
    approvalGroup.classList.add("d-none");
    approval.required = false;

    new bootstrap.Modal(document.getElementById("addContentModal")).show();
}

document.getElementById('addContentForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const isUpdate = document.getElementById("watchlist_id").value !== "";
    const actionUrl = isUpdate ? "update_watched_content.php" : "add_watched_content.php";

    try {
        const response = await fetch(actionUrl, {
            method: 'POST',
            body: new FormData(this)
        });

        const result = await response.text();
        let data;

        try {
            data = JSON.parse(result);
        } catch (e) {
            console.error('Server response:', result);
            throw new Error('Sunucudan geçersiz yanıt alındı');
        }

        if (data.success) {
            window.location.reload();
        } else {
            const errorDiv = document.getElementById('formErrorMessages');
            errorDiv.textContent = data.message;
            errorDiv.classList.remove('d-none');
        }
    } catch (error) {
        document.getElementById('formErrorMessages').textContent = error.message;
        document.getElementById('formErrorMessages').classList.remove('d-none');
    }
});
</script>
<?php include 'footer.php'; ?>
