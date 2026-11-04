<?php
// app/Views/auth/register.php
?>
<div style="max-width: 480px; margin: 30px auto;">
    <div class="card" style="padding: 30px;">
        <h1 style="font-size: 24px; font-weight: 700; margin-bottom: 8px;">Create Account</h1>
        <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 24px;">Join Amazon Mini to place orders and trace your cargo in real-time.</p>

        <form action="<?= url('auth/register-post') ?>" method="POST">
            <?= csrf_field() ?>

            <div class="form-group">
                <label class="form-label" for="reg-name">Your Full Name</label>
                <input type="text" id="reg-name" name="name" class="form-control" required placeholder="First and last name">
            </div>

            <div class="form-group">
                <label class="form-label" for="reg-email">Email Address</label>
                <input type="email" id="reg-email" name="email" class="form-control" required placeholder="name@example.com">
            </div>

            <div class="form-group">
                <label class="form-label" for="reg-phone">Phone Number (Optional)</label>
                <input type="tel" id="reg-phone" name="phone" class="form-control" placeholder="+1 555-0100">
            </div>

            <div class="form-group">
                <label class="form-label" for="reg-pass">Password</label>
                <input type="password" id="reg-pass" name="password" class="form-control" required placeholder="At least 6 characters">
            </div>

            <div class="form-group">
                <label class="form-label" for="reg-pass-confirm">Confirm Password</label>
                <input type="password" id="reg-pass-confirm" name="password_confirm" class="form-control" required placeholder="Re-enter password">
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="margin-top: 10px;">
                Create Account
            </button>
        </form>

        <div style="text-align: center; margin-top: 20px; font-size: 13px; color: var(--text-secondary);">
            Already have an account? <a href="<?= url('auth/login') ?>">Sign In</a>
        </div>
    </div>
</div>
