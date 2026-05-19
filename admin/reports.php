<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();
requireAdmin();

$pageTitle = 'Reports';

// Course report
$courseReport = $pdo->query("
    SELECT c.title, c.status, COUNT(e.id) as enrollments,
           SUM(CASE WHEN e.status = 'completed' THEN 1 ELSE 0 END) as completed
    FROM courses c
    LEFT JOIN enrollments e ON c.id = e.course_id
    GROUP BY c.id
    ORDER BY enrollments DESC
")->fetchAll();

// Student activity
$studentReport = $pdo->query("
    SELECT u.username, u.first_name, u.last_name, COUNT(e.id) as courses_enrolled,
           SUM(CASE WHEN e.status = 'completed' THEN 1 ELSE 0 END) as courses_completed,
           SUM(e.time_spent_seconds) as total_time
    FROM users u
    LEFT JOIN enrollments e ON u.id = e.student_id AND u.role = 'student'
    WHERE u.role = 'student'
    GROUP BY u.id
    ORDER BY courses_enrolled DESC
    LIMIT 50
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div id="full-report-area">
    <h2 class="section-title">Reports</h2>

    <div class="dashboard-grid">
        <aside class="sidebar">
            <h3 style="margin-top: 0;">Admin Menu</h3>
            <nav class="sidebar-nav">
                <a href="<?= url('admin/index.php') ?>">Dashboard</a>
                <a href="<?= url('admin/users.php') ?>">Manage Users</a>
                <a href="<?= url('admin/courses.php') ?>">Manage Courses</a>
                <a href="<?= url('admin/reports.php') ?>" class="active">Reports</a>
                <a href="<?= url('admin/contact-messages.php') ?>">Contact Messages</a>
            </nav>
        </aside>
        <div id="report-content">
            <h3>Course Report</h3>
            <table style="width: 100%; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.06); margin-bottom: 2rem;">
                <thead>
                    <tr style="background: var(--light);">
                        <th style="padding: 1rem; text-align: left;">Course</th>
                        <th style="padding: 1rem;">Status</th>
                        <th style="padding: 1rem;">Enrollments</th>
                        <th style="padding: 1rem;">Completed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($courseReport as $r): ?>
                    <tr style="border-top: 1px solid var(--border);">
                        <td style="padding: 1rem;"><?= e($r['title']) ?></td>
                        <td style="padding: 1rem;"><?= e($r['status']) ?></td>
                        <td style="padding: 1rem;"><?= $r['enrollments'] ?></td>
                        <td style="padding: 1rem;"><?= $r['completed'] ?></td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>

            <h3>Student Activity</h3>
            <table style="width: 100%; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.06);">
                <thead>
                    <tr style="background: var(--light);">
                        <th style="padding: 1rem; text-align: left;">Student</th>
                        <th style="padding: 1rem;">Courses Enrolled</th>
                        <th style="padding: 1rem;">Completed</th>
                        <th style="padding: 1rem;">Time Spent</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($studentReport as $r): ?>
                    <tr style="border-top: 1px solid var(--border);">
                        <td style="padding: 1rem;"><?= e($r['first_name'] . ' ' . $r['last_name']) ?> (<?= e($r['username']) ?>)</td>
                        <td style="padding: 1rem;"><?= $r['courses_enrolled'] ?></td>
                        <td style="padding: 1rem;"><?= $r['courses_completed'] ?></td>
                        <td style="padding: 1rem;"><?= formatDuration((int)($r['total_time'] ?? 0)) ?></td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>

       
        <div id="pre-preview-zone" style="margin-top: 2rem; text-align: center;">
            <button type="button" class="btn btn-primary" onclick="enterPreviewMode()">Print or Save as PDF</button>
        </div>

     
        <div id="preview-toolbar" class="report-actions" style="display: none; margin-top: 2rem; text-align: center; justify-content: center; gap: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 8px; border: 1px solid #ddd;">
            <button type="button" class="btn btn-primary" onclick="window.print()">Print Now</button>
            <button type="button" class="btn btn-secondary" onclick="downloadPDF()" style="background-color: #e74c3c; color: white; border: none;">Save as PDF</button>
            <button type="button" class="btn btn-outline-light" onclick="exitPreviewMode()" style="color: var(--text); border: 1px solid #ccc;">Cancel Preview</button>
        </div>
        </div>
    </div>
</div>

<style>

.preview-active .site-header, 
.preview-active .sidebar, 
.preview-active .site-footer,
.preview-active #pre-preview-zone {
    display: none !important;
}

.preview-active .main-content {
    padding-top: 20px !important;
}

.preview-active .dashboard-grid {
    display: block !important;
}


.preview-active table {
    width: 100% !important;
    table-layout: auto !important;
    word-wrap: break-word;
    border-collapse: collapse;
}


@media print {
    .site-header, 
    .sidebar, 
    .btn, 
    .report-actions,
    #preview-toolbar,
    #pre-preview-zone,
    .nav-toggle, 
    .site-footer,
    h2.section-title {
        display: none !important;
    }

    .main-content {
        padding-top: 0 !important;
    }

    .dashboard-grid {
        display: block !important;
    }
    
    table { page-break-inside: auto; }
    tr { page-break-inside: avoid; page-break-after: auto; }
}
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
function enterPreviewMode() {
    document.body.classList.add('preview-active');
    document.getElementById('preview-toolbar').style.display = 'flex';
}

function exitPreviewMode() {
    document.body.classList.remove('preview-active');
    document.getElementById('preview-toolbar').style.display = 'none';
}

function downloadPDF() {
    const element = document.getElementById('full-report-area');
    const toolbar = document.getElementById('preview-toolbar');
    
   
    if (toolbar) toolbar.style.display = 'none';

    const opt = {
        margin:       [10, 5, 10, 5], 
        filename:     'AFAK_Full_Report.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { 
            scale: 2, 
            useCORS: true, 
            letterRendering: true,
            
            ignoreElements: (el) => {
                return el.classList.contains('sidebar') || 
                       el.id === 'pre-preview-zone' || 
                       el.id === 'preview-toolbar';
            }
        },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };

    html2pdf().set(opt).from(element).save().then(() => {
        if (toolbar) toolbar.style.display = 'flex';
    }).catch(error => {
        console.error("PDF Export Error:", err);
        if (toolbar) toolbar.style.display = 'flex';
        alert("An error occurred while generating the PDF.");
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
