<?php 
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Home';

// Fetch categories for the new categories section
$catStmt = $pdo->query("SELECT * FROM categories LIMIT 6");
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

/* display aproved courses */
$stmt = $pdo->query("
    SELECT 
        c.*, 
        u.first_name, 
        u.last_name, 
        cat.name AS category_name
    FROM courses c 
    JOIN users u ON c.instructor_id = u.id
    LEFT JOIN categories cat ON c.category_id = cat.id
    WHERE c.status = 'published'
    ORDER BY c.created_at DESC
    LIMIT 12
");

$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/includes/header.php';
?>

<style>
    :root {
        --main-gradient: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);
        --accent-gold: #ffb400;
    }

    /* Hero Section Modern Style */
    .hero-creative {
        background: var(--main-gradient);
        min-height: 85vh;
        display: flex;
        align-items: center;
        position: relative;
        padding: 60px 5%;
        color: white;
        overflow: hidden;
        border-radius: 0 0 80px 80px;
    }
    .hero-creative::before {
        content: '';
        position: absolute;
        top: -10%; right: -5%;
        width: 400px; height: 400px;
        background: rgba(255,255,255,0.03);
        border-radius: 50%;
    }
    .hero-text { flex: 1; z-index: 2; }
    .hero-text h1 { font-size: 4rem; font-weight: 800; line-height: 1.1; margin-bottom: 20px; }
    .hero-text h1 span { color: var(--accent-gold); }
    .hero-text p { font-size: 1.25rem; opacity: 0.8; margin-bottom: 35px; max-width: 550px; }
    
    .hero-visual { flex: 1; display: flex; justify-content: center; align-items: center; z-index: 2; }
    .floating-card {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(15px);
        padding: 20px;
        border-radius: 30px;
        border: 1px solid rgba(255,255,255,0.2);
        box-shadow: 0 25px 50px rgba(0,0,0,0.3);
        animation: float 6s ease-in-out infinite;
    }
    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-25px); }
    }

    /* Stats Row */
    .stats-container {
        display: flex;
        justify-content: space-around;
        margin-top: -60px;
        padding: 0 10%;
        z-index: 10;
        position: relative;
    }
    .stat-item {
        background: white;
        padding: 30px;
        border-radius: 25px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        text-align: center;
        min-width: 180px;
    }
    .stat-item h3 { font-size: 2.5rem; margin-bottom: 5px; color: #2c5364; }

    /* Featured Section Styling */
    .featured-section { padding: 100px 5%; }
    .section-nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 50px; }
    
    .modern-course-card {
        background: white;
        border-radius: 25px;
        overflow: hidden;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        border: 1px solid #eee;
        height: 100%;
        display: block;
        text-decoration: none;
        color: inherit;
    }
    .modern-course-card:hover {
        transform: translateY(-15px) scale(1.02);
        box-shadow: 0 30px 60px rgba(0,0,0,0.12);
    }
    .card-img-wrap { height: 200px; overflow: hidden; position: relative; }
    .card-category {
        position: absolute; top: 15px; left: 15px;
        background: var(--accent-gold); color: #000;
        padding: 5px 15px; border-radius: 50px; font-weight: 700; font-size: 0.75rem;
    }

    @media (max-width: 768px) {
        .hero-creative { flex-direction: column; text-align: center; padding-top: 100px; border-radius: 0 0 40px 40px; }
        .hero-text h1 { font-size: 2.5rem; }
        .hero-visual { display: none; }
        .stats-container { flex-direction: column; gap: 15px; margin-top: -30px; }
    }
</style>

<!-- MODERN HERO -->
<section class="hero-creative">
    <div class="hero-text">
        <h1>Shape Your Future with <span>AFAK</span> Learning Platform</h1>
        <p>Embark on an interactive learning journey with top experts. Video lessons, H5P educational games, and certified success to support your career.</p>
        <div style="display: flex; gap: 15px;">
            <?php if (!isLoggedIn()): ?>
                <a href="<?= url('register.php') ?>" class="btn btn-primary" style="padding: 15px 35px; background: var(--accent-gold); color: #000; border: none; font-weight: 700;">Start for Free Now</a>
            <?php else: ?>
                <a href="<?= url('courses.php') ?>" class="btn btn-primary" style="padding: 15px 35px;">Explore Courses</a>
            <?php endif; ?>
            <a href="<?= url('about.php') ?>" class="btn btn-outline-light" style="padding: 15px 35px; border-radius: 50px;">About Us</a>
        </div>
    </div>
    <div class="hero-visual">
        <div class="floating-card">
            <img src="<?= url('assets/img/AFAKLOGO.jpg') ?>" alt="AFAK" style="width: 320px; border-radius: 20px;">
            <div style="margin-top: 15px; text-align: center;">
                <span style="color: var(--accent-gold); font-weight: 700;">+40k Active Students</span>
            </div>
        </div>
    </div>
</section>

<!-- 
<div class="stats-container">
    <div class="stat-item">
        <h3>+10k</h3>
        <p class="text-muted">Learning Materials</p>
    </div>
    <div class="stat-item">
        <h3>+2k</h3>
        <p class="text-muted">Expert Tutors</p>
    </div>
    <div class="stat-item">
        <h3>100%</h3>
        <p class="text-muted">Interactive Learning</p>
    </div>
</div>
-->

<!-- FEATURED COURSES -->
<section class="featured-section">
    <div class="section-nav">
        <h2 style="font-weight: 800; margin: 0;">Latest Courses</h2>
        <a href="<?= url('courses.php') ?>" style="color: #2c5364; font-weight: 700;">View All &rarr;</a>
    </div>

    <?php if (empty($courses)): ?>
        <div class="empty-state">
            <p class="text-muted">No courses available yet. Stay tuned!</p>
        </div>
    <?php else: ?>
        <div class="course-grid">
            <?php foreach ($courses as $course): ?>
                <?php
                    $desc = mb_strlen($course['short_description'] ?? $course['description']) > 90 
                        ? mb_substr($course['short_description'] ?? $course['description'], 0, 90) . '...'
                        : ($course['short_description'] ?? $course['description']);
                ?>
                <a href="<?= url('course-detail.php?id=' . $course['id']) ?>" class="modern-course-card">
                    <div class="card-img-wrap">
                        <span class="card-category"><?= e($course['category_name'] ?? 'General') ?></span>
                        <?php if (!empty($course['thumbnail_url']) && $course['thumbnail_url'] !== 'NULL'): ?>
                            <img src="<?= e(url($course['thumbnail_url'])) ?>" alt="<?= e($course['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <img src="<?= url('assets/img/cover1.png') ?>" alt="No Image" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php endif; ?>
                    </div>
                    <div style="padding: 25px;">
                        <h3 style="font-size: 1.25rem; margin-bottom: 12px; height: 3rem; overflow: hidden;"><?= e($course['title']) ?></h3>
                        <p class="text-muted" style="font-size: 0.9rem; margin-bottom: 20px; line-height: 1.6;"><?= e($desc) ?></p>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f0f0f0; padding-top: 15px;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="width: 30px; height: 30px; background: #eee; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.7rem;">👤</div>
                                <span style="font-size: 0.85rem;"><?= e($course['first_name']) ?></span>
                            </div>
                            <span style="font-weight: 800; color: #2c5364;">
                                <?= $course['is_free'] ? 'FREE' : number_format($course['price'] ?? 0, 1) . ' OMR' ?>
                            </span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- CALL TO ACTION -->
<section style="margin: 0 5% 80px; background: #f8faff; border-radius: 40px; padding: 80px; text-align: center;">
    <h2 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 20px;">Ready to Start Your Journey?</h2>
    <p class="text-muted" style="font-size: 1.1rem; margin-bottom: 40px; max-width: 600px; margin-left: auto; margin-right: auto;">Join the AFAK learning community today and get unlimited access to the best educational content in Oman.</p>
    <a href="<?= url('register.php') ?>" class="btn btn-primary" style="padding: 18px 50px; font-weight: 700; font-size: 1.1rem; border-radius: 50px; box-shadow: 0 10px 20px rgba(0,0,0,0.1);">Create New Account</a>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>