</main>

<footer class="site-footer">
    <div class="footer-inner">
        <p>
            &copy; <?= date('Y') ?> AFAK Learning Platform.
            Learn effectively anywhere, anytime.
        </p>
    </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const header = document.querySelector('.site-header');
    if (header) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
    }

    const navToggle = document.querySelector('.nav-toggle');
    const navLinks = document.querySelector('.nav-links');
    if (navToggle && navLinks) {
        navToggle.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            navToggle.classList.toggle('active');
        });
    }
});
</script>
</body>
</html>