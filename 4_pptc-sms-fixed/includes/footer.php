<?php
// ============================================================
// includes/footer.php — Professional footer with credits
// ============================================================
?>
</main>

<footer class="sms-footer">
    <div class="sms-footer-inner">

        <!-- Left: branding -->
        <div class="sms-footer-brand">
            <img src="<?= $baseUrl ?? '' ?>assets/img/pptc_logo.png" alt="PPTC" class="sms-footer-logo">
            <div>
                <div class="sms-footer-college">Pentium Point Group of Institutions</div>
                <div class="sms-footer-sub">Student Management System &bull; Rewa, M.P.</div>
            </div>
        </div>

        <!-- Right: credits -->
        <div class="sms-footer-credits">
            <div class="sms-credit-row">
                <span class="sms-credit-label">Guided By</span>
                <span class="sms-credit-value">Sunil Tiwari &mdash; Dept. of Computer Science</span>
            </div>
            <div class="sms-credit-row">
                <span class="sms-credit-label">Developed By</span>
                <span class="sms-credit-value">Nitish Sen </span>
            </div>
            <div class="sms-credit-batch">BCA 2nd Year &nbsp;&mdash;&nbsp; &copy; <?= date('Y') ?> All Rights Reserved</div>
        </div>

    </div>
</footer>

<style>
.sms-footer {
    background: linear-gradient(135deg, var(--crimson-dk) 0%, #3a0000 100%);
    border-top: 3px solid var(--gold);
    margin-top: 3rem;
    padding: 1.5rem;
}
.sms-footer-inner {
    max-width: 1100px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1.5rem;
    flex-wrap: wrap;
}
.sms-footer-brand {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-shrink: 0;
}
.sms-footer-logo {
    width: 40px;
    height: 40px;
    object-fit: contain;
    filter: drop-shadow(0 2px 6px rgba(201,168,76,0.5));
}
.sms-footer-college {
    font-family: 'Cinzel', serif;
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--gold-lt);
    letter-spacing: 0.04em;
}
.sms-footer-sub {
    font-size: 0.68rem;
    color: rgba(255,255,255,0.45);
    margin-top: 0.1rem;
    letter-spacing: 0.04em;
}
.sms-footer-credits {
    text-align: right;
}
.sms-credit-row {
    display: flex;
    align-items: baseline;
    justify-content: flex-end;
    gap: 0.5rem;
    margin-bottom: 0.25rem;
    flex-wrap: wrap;
}
.sms-credit-label {
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--gold);
    font-weight: 700;
    white-space: nowrap;
    flex-shrink: 0;
}
.sms-credit-value {
    font-size: 0.75rem;
    color: rgba(255,255,255,0.8);
    font-weight: 600;
}
.sms-credit-batch {
    font-size: 0.65rem;
    color: rgba(255,255,255,0.35);
    margin-top: 0.35rem;
    letter-spacing: 0.05em;
}
@media (max-width: 640px) {
    .sms-footer-inner  { flex-direction: column; align-items: flex-start; }
    .sms-footer-credits { text-align: left; }
    .sms-credit-row { justify-content: flex-start; }
}
</style>

<script src="<?= $baseUrl ?? '' ?>assets/js/script.js"></script>
</body>
</html>
