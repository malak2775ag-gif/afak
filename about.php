<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

session_start();

$pageTitle = 'About Us';
$pageStylesheets = [
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css',
    url('assets/css/style_ab_con.css'),
];

require_once __DIR__ . '/includes/header.php';
?>

<section class="about">

    <div class="row">
        <div class="image">
            <img src="<?= url('assets/img/AFAKLOGO.jpg') ?>" alt="AFAK Logo" style="width: 100%;">
        </div>

        <div class="content">
            <h3>Why choose us?</h3>
            <p>
                AFAK: online learning platform is a smart learning platform that enhances accessibility,
                flexibility, and engagement. AFAK is an Arabic word which means “horizons”, “vast spaces”, or “far reaching views.”
                It symbolizes openness, ambition, limitless possibilities, and a broad outlook on life.
            </p>
            <p>
                AFAK presents an online learning platform aimed to support self-directed learners by providing
                interactive courses, assessments, and progress tracking features. The platform integrates multimedia
                learning materials, real-time feedback systems, AI-powered chatbot, and certification upon course
                completion to promote active learning and motivation.
            </p>
            <p>
                Developed using Scrum methodology, AFAK platform ensures continuous improvement
                based on user feedback, aligning with Oman Vision 2040’s goals of psychological well-being and
                digital transformation.
            </p>
            <a href="<?= url('courses.php') ?>" class="inline-btn">Our Courses</a>
        </div>
    </div>

    <div class="box-container">
        <div class="box">
            <i class="fas fa-graduation-cap"></i>
            <div>
                <h3>+10k</h3>
                <p>Online Courses</p>
            </div>
        </div>

        <div class="box">
            <i class="fas fa-user-graduate"></i>
            <div>
                <h3>+40k</h3>
                <p>Brilliant Students</p>
            </div>
        </div>

        <div class="box">
            <i class="fas fa-chalkboard-user"></i>
            <div>
                <h3>+2k</h3>
                <p>Expert Tutors</p>
            </div>
        </div>

        <div class="box">
            <i class="fas fa-briefcase"></i>
            <div>
                <h3>100%</h3>
                <p>Job Placement</p>
            </div>
        </div>
    </div>

</section>

<script src="<?= url('assets/js/script.js') ?>"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
