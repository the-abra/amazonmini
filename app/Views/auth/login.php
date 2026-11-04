<?php
// app/Views/auth/login.php
?>
<div style="max-width: 480px; margin: 40px auto;">
    <div class="card" style="padding: 40px; text-align: center;">
        <div style="margin-bottom: 30px;">
            <div style="width: 80px; height: 80px; margin: 0 auto 20px; background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: var(--shadow-lg);">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </div>
            <h1 style="font-size: 28px; font-weight: 800; margin-bottom: 8px; color: var(--text-primary);">Welcome Back!</h1>
            <p style="color: var(--text-muted); font-size: 15px;">Choose your role to continue shopping</p>
        </div>

        <!-- Demo Login Buttons -->
        <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 30px;">
            <!-- Customer Button -->
            <a href="<?= url('auth/demo-login', ['role' => 'customer']) ?>" 
               style="display: flex; align-items: center; gap: 14px; padding: 18px 24px; background: white; border: 2px solid var(--border-color); border-radius: var(--radius-lg); text-decoration: none; transition: all 0.2s; box-shadow: var(--shadow-xs);"
               onmouseover="this.style.borderColor='var(--primary)'; this.style.boxShadow='var(--shadow-md)'; this.style.transform='translateY(-2px)';"
               onmouseout="this.style.borderColor='var(--border-color)'; this.style.boxShadow='var(--shadow-xs)'; this.style.transform='translateY(0)';">
                <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                </div>
                <div style="text-align: left; flex: 1;">
                    <div style="font-size: 17px; font-weight: 700; color: var(--text-primary); margin-bottom: 2px;">Continue as Customer</div>
                    <div style="font-size: 13px; color: var(--text-muted);">Browse products & place orders</div>
                </div>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </a>

            <!-- Admin Button -->
            <a href="<?= url('auth/demo-login', ['role' => 'admin']) ?>" 
               style="display: flex; align-items: center; gap: 14px; padding: 18px 24px; background: white; border: 2px solid var(--border-color); border-radius: var(--radius-lg); text-decoration: none; transition: all 0.2s; box-shadow: var(--shadow-xs);"
               onmouseover="this.style.borderColor='var(--primary)'; this.style.boxShadow='var(--shadow-md)'; this.style.transform='translateY(-2px)';"
               onmouseout="this.style.borderColor='var(--border-color)'; this.style.boxShadow='var(--shadow-xs)'; this.style.transform='translateY(0)';">
                <div style="width: 48px; height: 48px; background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                        <path d="M2 17l10 5 10-5M2 12l10 5 10-5"></path>
                    </svg>
                </div>
                <div style="text-align: left; flex: 1;">
                    <div style="font-size: 17px; font-weight: 700; color: var(--text-primary); margin-bottom: 2px;">Continue as Admin</div>
                    <div style="font-size: 13px; color: var(--text-muted);">Manage products & orders</div>
                </div>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </a>

            <!-- Courier Button -->
            <a href="<?= url('auth/demo-login', ['role' => 'courier']) ?>" 
               style="display: flex; align-items: center; gap: 14px; padding: 18px 24px; background: white; border: 2px solid var(--border-color); border-radius: var(--radius-lg); text-decoration: none; transition: all 0.2s; box-shadow: var(--shadow-xs);"
               onmouseover="this.style.borderColor='var(--primary)'; this.style.boxShadow='var(--shadow-md)'; this.style.transform='translateY(-2px)';"
               onmouseout="this.style.borderColor='var(--border-color)'; this.style.boxShadow='var(--shadow-xs)'; this.style.transform='translateY(0)';">
                <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5">
                        <rect x="1" y="3" width="15" height="13"></rect>
                        <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                        <circle cx="5.5" cy="18.5" r="2.5"></circle>
                        <circle cx="18.5" cy="18.5" r="2.5"></circle>
                    </svg>
                </div>
                <div style="text-align: left; flex: 1;">
                    <div style="font-size: 17px; font-weight: 700; color: var(--text-primary); margin-bottom: 2px;">Continue as Courier</div>
                    <div style="font-size: 13px; color: var(--text-muted);">Update delivery status</div>
                </div>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </a>
        </div>

        <!-- Divider -->
        <div style="position: relative; margin: 30px 0;">
            <div style="position: absolute; top: 50%; left: 0; right: 0; height: 1px; background: var(--border-color);"></div>
            <div style="position: relative; text-align: center; background: var(--bg-surface); display: inline-block; padding: 0 16px;">
                <span style="font-size: 13px; color: var(--text-muted); font-weight: 600;">OR</span>
            </div>
        </div>

        <!-- Register Link -->
        <div style="margin-top: 24px;">
            <p style="font-size: 14px; color: var(--text-secondary); margin-bottom: 12px;">Need a new account?</p>
            <a href="<?= url('auth/register') ?>" class="btn btn-secondary btn-block" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 24px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="8.5" cy="7" r="4"></circle>
                    <line x1="20" y1="8" x2="20" y2="14"></line>
                    <line x1="23" y1="11" x2="17" y2="11"></line>
                </svg>
                Create Account
            </a>
        </div>

        <!-- Info Box -->
        <div style="margin-top: 30px; padding: 16px; background: var(--bg-subtle); border-radius: var(--radius-md); text-align: left;">
            <div style="display: flex; align-items: start; gap: 12px;">
                <div style="width: 24px; height: 24px; flex-shrink: 0;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                </div>
                <div>
                    <div style="font-size: 13px; font-weight: 600; color: var(--text-primary); margin-bottom: 4px;">Demo Platform</div>
                    <div style="font-size: 12px; color: var(--text-muted); line-height: 1.5;">
                        This is a demo e-commerce platform. Click any role above to explore the system instantly - no password required!
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
