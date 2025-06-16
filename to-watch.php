<?php
session_start();

// Kullanıcı giriş kontrol fonksiyonu
function check_user_session() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
    return $_SESSION['user_id'];
}

require_once 'config.php';

// Session kontrolü
$user_id = check_user_session();

// Tema tercihi
$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';

// İzlenecek içerikleri getir
$query = "SELECT c.*, w.id as watchlist_id
          FROM to_watch_list w
          JOIN content c ON w.content_id = c.id
          WHERE w.user_id = ?
          ORDER BY w.id DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$movies = [];
$series = [];

while ($row = $result->fetch_assoc()) {
    if ($row['type'] === 'movie') {
        $movies[] = $row;
    } else if ($row['type'] === 'series') {
        $series[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="tr" data-bs-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İzleyeceklerim</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container mt-4">
    <h2>İzleyeceklerim 
        <button type="button" class="btn btn-primary float-end" data-bs-toggle="modal" data-bs-target="#addContentModal">
            <i class="fas fa-plus"></i> İçerik Ekle
        </button>
    </h2>

    <!-- İçerik Ekleme Modal -->
    <div class="modal fade" id="addContentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">İzleyeceklerime Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addContentForm" action="add_content.php" method="POST">
                    <div class="modal-body">
                        <div id="formErrorMessages" class="alert alert-danger d-none"></div>                        <div class="mb-3">
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
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary">Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs" id="contentTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#movies" type="button">
                Filmler (<?php echo count($movies); ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#series" type="button">
                Diziler (<?php echo count($series); ?>)
            </button>
        </li>
    </ul>

    <div class="tab-content mt-3">
        <div class="tab-pane fade show active" id="movies">
            <div class="row">
                <?php foreach ($movies as $movie): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-body">                                <h5 class="card-title"><?php echo htmlspecialchars($movie['title']); ?></h5>
                                <div class="mt-auto">
                                    <div class="btn-group w-100">
                                        <button class="btn btn-sm btn-outline-success" onclick="markAsWatched(<?php echo $movie['watchlist_id']; ?>)">
                                            <i class="fas fa-check"></i> İzledim
                                        </button>                                        <a href="delete_content.php?id=<?php echo $movie['watchlist_id']; ?>&list=to-watch" 
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Bu içeriği silmek istediğinizden emin misiniz?')">
                                            <i class="fas fa-trash"></i> Sil
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="tab-pane fade" id="series">
            <div class="row">
                <?php foreach ($series as $serie): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-body">                                <h5 class="card-title"><?php echo htmlspecialchars($serie['title']); ?></h5>
                                <div class="mt-auto">
                                    <div class="btn-group w-100">
                                        <button class="btn btn-sm btn-outline-success" onclick="markAsWatched(<?php echo $serie['watchlist_id']; ?>)">
                                            <i class="fas fa-check"></i> İzledim
                                        </button>                                        <a href="delete_content.php?id=<?php echo $serie['watchlist_id']; ?>&list=to-watch" 
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Bu içeriği silmek istediğinizden emin misiniz?')">
                                            <i class="fas fa-trash"></i> Sil
                                        </a>
                                    </div>
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
document.getElementById('addContentForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    try {
        const response = await fetch('add_content.php', {
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
        console.error('Error:', error);
        document.getElementById('formErrorMessages').textContent = error.message;
        document.getElementById('formErrorMessages').classList.remove('d-none');
    }
});

function markAsWatched(id) {
    if (confirm('Bu içeriği izlendi olarak işaretlemek istiyor musunuz?')) {
        const formData = new FormData();
        formData.append('watchlist_id', id);

        fetch('update_status.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Bir hata oluştu');
        });
    }
}
</script>

<?php include 'footer.php'; ?>
