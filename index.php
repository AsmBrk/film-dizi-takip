<?php
// Index.php dosyanızın PHP kısmını bu şekilde güncelleyin

require_once 'config.php';

// Check if user is logged in
$logged_in = isset($_SESSION['user_id']);

// Get theme preference
$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';

if ($logged_in) {    // İzlenen içerikleri getir
    $watched_query = "SELECT 
        c.id AS content_id,
        c.title,
        c.type,
        w.id AS watchlist_id,
        w.rating,
        w.comment
    FROM watched_list w
    JOIN content c ON w.content_id = c.id
    WHERE w.user_id = ?
    ORDER BY w.id DESC
    ";
    
    $stmt = $conn->prepare($watched_query);
    if ($stmt) {
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $watched = $stmt->get_result();
    } else {
        $watched = false;
    }

    // İzlenecek içerikleri getir
    $to_watch_query = "SELECT 
        c.id, 
        c.title, 
        c.type
        FROM to_watch_list tw
        INNER JOIN content c ON tw.content_id = c.id
        WHERE tw.user_id = ?
        ORDER BY tw.id DESC
        LIMIT 6";
    
    $stmt = $conn->prepare($to_watch_query);
    if ($stmt) {
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $to_watch = $stmt->get_result();
    } else {
        $to_watch = false;
    }
}
?>
<!DOCTYPE html>
<html lang="tr" data-bs-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Film ve Dizi Takip Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
        }

        body {
            background-color: var(--bs-body-bg);
            transition: all 0.3s ease;
        }

        .loading-spinner {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }

        .loading-spinner.show {
            opacity: 1;
            visibility: visible;
        }

        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
            backdrop-filter: blur(10px);
        }

        .card {
            transition: all 0.3s ease-in-out;
            box-shadow: 0 2px 4px rgba(0,0,0,.05);
            border: none;
            background: var(--bs-body-bg);
        }

        .card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 8px 16px rgba(0,0,0,.1);
        }

        .jumbotron {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 4rem 2rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .jumbotron::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('https://source.unsplash.com/random/1920x1080/?cinema') center/cover;
            opacity: 0.1;
        }

        .rating {
            color: #ffc107;
        }

        .nav-link {
            position: relative;
            transition: all 0.3s ease;
        }

        .nav-link:after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            background: #fff;
            left: 0;
            bottom: 0;
            transition: width 0.3s ease;
        }

        .nav-link:hover:after {
            width: 100%;
        }

        .content-card {
            height: 100%;
            border-radius: 1rem;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .content-card .card-body {
            padding: 1.5rem;
        }

        .theme-switch {
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .theme-switch:hover {
            background: rgba(255,255,255,0.1);
        }

        @media (max-width: 768px) {
            .jumbotron {
                padding: 2rem 1rem;
            }
            
            .card {
                margin-bottom: 1rem;
            }
        }

        [data-bs-theme="dark"] {
            --bs-body-bg: #1a1a1a;
            --bs-body-color: #f8f9fa;
        }

        [data-bs-theme="dark"] .card {
            background: #2d2d2d;
        }

        [data-bs-theme="dark"] .navbar {
            background: #2d2d2d !important;
        }
    </style>
</head>
<body>
    <!-- Loading Spinner -->
    <div class="loading-spinner">
        <div class="spinner-border text-light" role="status">
            <span class="visually-hidden">Yükleniyor...</span>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-film me-2"></i>Film/Dizi Takip
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-home me-2"></i>Ana Sayfa
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="watched.php">
                            <i class="fas fa-check-circle me-2"></i>İzlediklerim
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="to-watch.php">
                            <i class="fas fa-clock me-2"></i>İzleyeceklerim
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav align-items-center">
                    <li class="nav-item me-3">
                        <div class="theme-switch" id="theme-switch">
                            <i class="fas fa-sun"></i>
                        </div>
                    </li>
                    <?php if ($logged_in): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">
                               <?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Çıkış Yap
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">
                                <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">
                                <i class="fas fa-user-plus me-2"></i>Kayıt Ol
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if ($logged_in): ?>
            <div class="jumbotron text-center">
                <h1 class="display-4"><i class="fas fa-film me-2"></i>Film ve Dizi Takip Sistemine Hoş Geldiniz!</h1>
                <p class="lead">Sevdiğiniz filmleri ve dizileri takip edin, değerlendirin ve yorumlayın.</p>
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2><i class="fas fa-film me-2"></i>Son İzlediğiniz Filmler</h2>
                        </div>
                        
                        <div class="row row-cols-1 row-cols-md-2 g-4">
                            <?php if ($watched && $watched->num_rows > 0): ?>
                                <?php while ($movie = $watched->fetch_assoc()): ?>
                                    <div class="col">
                                        <div class="card content-card h-100">
                                            <div class="card-body">                                                <h5 class="card-title text-primary">
                                                    <?php echo htmlspecialchars($movie['title'] ?? ''); ?>
                                                </h5>
                                                <p class="card-subtitle mb-2 text-muted">
                                                    <?php echo $movie['type'] === 'movie' ? 'Film' : 'Dizi'; ?>
                                                </p>
                                                <?php if($movie['comment']) : ?>
                                                <p class="card-subtitle mb-2 text-muted">
                                                    <?php echo htmlspecialchars($movie['comment'] ?? ''); ?>
                                                </p>
                                                <?php endif; ?>
                                                <?php if($movie['rating']) : ?>
                                                <p class="card-subtitle mb-2 text-muted">
                                                    <?php echo htmlspecialchars($movie['rating'] ?? ''); ?> / 10
                                                </p>
                                                <?php endif; ?>
                                                    </span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2><i class="fas fa-clock me-2"></i>İzleyecekleriniz</h2>
                        </div>
                        <div class="row row-cols-1 row-cols-md-2 g-4">
                            <?php if ($to_watch && $to_watch->num_rows > 0): ?>
                                <?php while ($serie = $to_watch->fetch_assoc()): ?>
                                    <div class="col">
                                        <div class="card content-card h-100">
                                            <div class="card-body">
                                                <h5 class="card-title text-primary">
                                                    <?php echo htmlspecialchars($serie['title'] ?? ''); ?>
                                                </h5>                                                <p class="card-subtitle mb-2 text-muted">
                                                    <?php echo $serie['type'] === 'movie' ? 'Film' : 'Dizi'; ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center">
                <h1>Film ve Dizi Takip Sistemine Hoş Geldiniz</h1>
                <p class="lead">Devam etmek için lütfen giriş yapın veya kayıt olun.</p>
                <div class="mt-4">
                    <a href="login.php" class="btn btn-primary me-2">Giriş Yap</a>
                    <a href="register.php" class="btn btn-success">Kayıt Ol</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>