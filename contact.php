<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

session_start();

$pageTitle = 'Contact Us';
$pageStylesheets = [
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css',
];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $number = trim($_POST['number'] ?? '');
    $comment = trim($_POST['msg'] ?? '');

    if ($name === '' || mb_strlen($name) > 200) {
        $errors[] = 'Please enter a valid name.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 200) {
        $errors[] = 'Please enter a valid email.';
    }
    if ($comment === '' || mb_strlen($comment) > 600) {
        $errors[] = 'Please enter a message.';
    }
    if ($number !== '') {
        if (!ctype_digit($number)) {
            $errors[] = 'Phone number must contain digits only.';
        } elseif ((int) $number > 2147483647) {
            $errors[] = 'Phone number is too large.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('INSERT INTO contact_us (name, email, number, comment) VALUES (?, ?, ?, ?)');
            $stmt->execute([$name, $email, $number !== '' ? (int) $number : null, $comment]);
            flash('success', 'Your message has been sent successfully!');
            header('Location: ' . url('contact.php'));
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Could not save your message. Import database/schema.sql (contact_us table) if this is a new database.';
        }
    }
}

$flash = getFlash();

require_once __DIR__ . '/includes/header.php';

$nameVal = e($_POST['name'] ?? '');
$emailVal = e($_POST['email'] ?? '');
$numberVal = e($_POST['number'] ?? '');
$msgVal = e($_POST['msg'] ?? '');
?>

<style>
    .contact-wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 80vh;
        padding: 4rem 5%;
    }
    .contact-container {
        display: grid;
        grid-template-columns: 1fr 1.5fr;
        background: white;
        border-radius: 30px;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0,0,0,0.1);
        max-width: 1100px;
        width: 100%;
    }
    .contact-info-side {
        background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);
        padding: 50px;
        color: white;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .contact-info-side h3 { font-size: 2.2rem; font-weight: 800; color: #ffb400; margin-bottom: 1rem; }
    .contact-info-side p { opacity: 0.8; line-height: 1.6; margin-bottom: 2rem; }
    
    .info-list { list-style: none; padding: 0; }
    .info-list li { display: flex; align-items: center; gap: 20px; margin-bottom: 25px; }
    .info-list i { 
        width: 45px; height: 45px; background: rgba(255,255,255,0.1); 
        border-radius: 12px; display: flex; align-items: center; 
        justify-content: center; color: #ffb400; font-size: 1.2rem;
    }
    .info-list a { color: white; text-decoration: none; transition: 0.3s; }
    .info-list a:hover { color: #ffb400; }

    .contact-form-side {
        padding: 50px;
        background: white;
    }
    .contact-form-side h3 { font-size: 2rem; color: #2c5364; font-weight: 700; margin-bottom: 1.5rem; }
    
    .form-group { margin-bottom: 1.2rem; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; }
    
    @media (max-width: 992px) {
        .contact-container { grid-template-columns: 1fr; }
        .contact-info-side { padding: 40px; text-align: center; }
        .info-list li { justify-content: center; }
    }
</style>

<section class="contact">
    <div class="contact-wrapper">
        <div class="contact-container">
            <!-- Left Side: Information -->
            <div class="contact-info-side">
                <div>
                    <img src="<?= url('assets/img/AFAKLOGO.jpg') ?>" alt="AFAK Logo" style="width: 80px; border-radius: 50%; margin-bottom: 2rem;">
                    <h3>Let's Connect</h3>
                    <p>Have questions about our courses or need technical support? We're here to help you expand your horizons.</p>
                    
                    <ul class="info-list">
                        <li>
                            <i class="fas fa-phone"></i>
                            <div>
                                <small style="display:block; opacity: 0.6;">Call Us</small>
                                <a href="tel:1234567890">123-456-7890</a>
                            </div>
                        </li>
                        <li>
                            <i class="fas fa-envelope"></i>
                            <div>
                                <small style="display:block; opacity: 0.6;">Email Us</small>
                                <a href="mailto:AFAK@afak.edu.om">AFAK@afak.edu.om</a>
                            </div>
                        </li>
                    </ul>
                </div>
                <div style="font-size: 0.85rem; opacity: 0.5;">UTAS-Ibri Smart Education Project</div>
            </div>

            <!-- Right Side: Form -->
            <div class="contact-form-side">
                <h3>Send a Message</h3>
                <?php if ($flash): ?><div class="alert alert-success"><?= e($flash['message']) ?></div><?php endif ?>
                <?php if ($errors): ?><div class="alert alert-danger"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>

                <form action="<?= url('contact.php') ?>" method="post">
                    <input type="text" placeholder="Full Name" name="name" required maxlength="200" class="form-control mb-3" value="<?= $nameVal ?>">
                    <input type="email" placeholder="Email Address" name="email" required maxlength="200" class="form-control mb-3" value="<?= $emailVal ?>">
                    <input type="number" placeholder="Phone Number (Optional)" name="number" maxlength="50" class="form-control mb-3" value="<?= $numberVal ?>">
                    <textarea name="msg" class="form-control mb-3" placeholder="How can we help you?" required maxlength="600" rows="6"><?= $msgVal ?></textarea>
                    <button type="submit" name="submit" class="btn btn-primary" style="width: 100%; padding: 15px; font-weight: 700;">Submit Message</button>
                </form>
            </div>
        </div>
    </div>
</section>

<script src="<?= url('assets/js/script.js') ?>"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
