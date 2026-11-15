    </div><!-- /.container -->
</main><!-- /.main-content -->

<!-- IDLE SESSION TIMEOUT WARNING MODAL (Handout Section 8.4: 9 min timeout, warn 1 min before) -->
<?php if (isset($_SESSION['user_id'])): ?>
<div id="idle-warning-modal" class="modal-overlay" style="display: none;">
    <div class="modal-box">
        <h3 class="modal-title">&#9888; Session Idle Warning</h3>
        <p>You have been inactive. For your security, your session will automatically terminate in <strong id="idle-countdown">60</strong> seconds.</p>
        <div class="modal-actions">
            <button type="button" id="stay-logged-in-btn" class="btn btn-primary">Stay Logged In</button>
            <a href="logout.php" class="btn btn-secondary">Log Out Now</a>
        </div>
    </div>
</div>
<script src="js/idle_timer.js"></script>
<?php endif; ?>

<!-- MAIN FOOTER (Handout Section 3.1: Footer holds copyright statement) -->
<footer class="main-footer">
    <div class="container footer-inner">
        <p class="copyright">&copy; 2026 Efren Castillano. All rights reserved.</p>
        <p class="footer-links">
            <a href="privacy_notice.php">Privacy Notice (RA 10173)</a> &middot;
            <span>Caraga State University &middot; BS Information Technology</span>
        </p>
    </div>
</footer>

<script src="js/validate.js"></script>
<?php if (isset($extraJs)): ?>
    <script src="<?= e($extraJs) ?>"></script>
<?php endif; ?>

</body>
</html>
