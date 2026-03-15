/* ============================================================
   assets/js/script.js  v2
   Pentium Point SMS — PPTC Rewa
   ============================================================ */

'use strict';

/* ============================================================
   UTILITY
   ============================================================ */
function qs(sel, ctx) { return (ctx || document).querySelector(sel); }
function qsa(sel, ctx) { return [...(ctx || document).querySelectorAll(sel)]; }

/* ============================================================
   1. LANDING PAGE — Floating Particles
   ============================================================ */
(function spawnParticles() {
    const container = qs('.particles');
    if (!container) return;
    const colors = ['#C9A84C','#E8C76A','rgba(255,255,255,0.6)','#8B0000'];
    for (let i = 0; i < 30; i++) {
        const p = document.createElement('div');
        p.className = 'particle';
        const size = Math.random() * 5 + 2;
        p.style.cssText = `
            width:${size}px;height:${size}px;
            left:${Math.random()*100}%;
            top:${55+Math.random()*45}%;
            background:${colors[Math.floor(Math.random()*colors.length)]};
            --dur:${3+Math.random()*5}s;
            --delay:${Math.random()*7}s;
        `;
        container.appendChild(p);
    }
})();

/* ============================================================
   2. LANDING PAGE — Carousel (swipe + auto-play)
   ============================================================ */
(function initCarousel() {
    const track    = qs('.carousel-track');
    if (!track) return;
    const slides   = qsa('.carousel-slide', track);
    const dotsWrap = qs('.carousel-dots');
    const total    = slides.length;
    let current    = 0;
    let autoTimer  = null;

    // Build dots
    slides.forEach((_, i) => {
        const d = document.createElement('button');
        d.className = 'dot' + (i === 0 ? ' active' : '');
        d.addEventListener('click', () => { goTo(i); resetTimer(); });
        dotsWrap.appendChild(d);
    });

    function updateDots() {
        qsa('.dot', dotsWrap).forEach((d, i) => d.classList.toggle('active', i === current));
    }

    function goTo(idx) {
        current = ((idx % total) + total) % total;
        track.style.transform = `translateX(-${current * 100}%)`;
        updateDots();
    }

    qs('.carousel-btn.next')?.addEventListener('click', () => { goTo(current + 1); resetTimer(); });
    qs('.carousel-btn.prev')?.addEventListener('click', () => { goTo(current - 1); resetTimer(); });

    // Touch/swipe
    let startX = 0;
    track.addEventListener('touchstart', e => { startX = e.touches[0].clientX; }, { passive: true });
    track.addEventListener('touchend',   e => {
        const diff = startX - e.changedTouches[0].clientX;
        if (Math.abs(diff) > 40) { diff > 0 ? goTo(current+1) : goTo(current-1); resetTimer(); }
    });

    function startTimer() { autoTimer = setInterval(() => goTo(current + 1), 3800); }
    function resetTimer() { clearInterval(autoTimer); startTimer(); }
    startTimer();
})();

/* ============================================================
   3. LANDING PAGE — Animated Counters
   ============================================================ */
(function animateCounters() {
    const nums = qsa('.strip-num[data-target]');
    if (!nums.length) return;
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            const el     = entry.target;
            const target = parseInt(el.dataset.target);
            const suffix = el.dataset.suffix || '';
            let count    = 0;
            const step   = Math.max(1, Math.ceil(target / 70));
            const timer  = setInterval(() => {
                count = Math.min(count + step, target);
                el.textContent = count + suffix;
                if (count >= target) clearInterval(timer);
            }, 22);
            observer.unobserve(el);
        });
    }, { threshold: 0.5 });
    nums.forEach(n => observer.observe(n));
})();

/* ============================================================
   4. LANDING PAGE — Course Category Tabs
   ============================================================ */
(function initCourseTabs() {
    const tabs   = qsa('.course-tab');
    const panels = qsa('.course-panel');
    if (!tabs.length) return;

    tabs.forEach(tab => {
        tab.addEventListener('click', function () {
            const cat = this.dataset.cat;

            // Update active tab
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            // Show matching panel
            panels.forEach(p => {
                const isTarget = p.id === `panel-${cat}`;
                p.classList.toggle('active', isTarget);
                // Re-trigger card animation
                if (isTarget) {
                    qsa('.course-card', p).forEach((card, i) => {
                        card.style.animation = 'none';
                        card.offsetHeight; // reflow
                        card.style.animation = `cardFadeIn 0.4s ease ${i * 0.04}s both`;
                    });
                }
            });
        });
    });
})();

/* ============================================================
   5. AJAX — Dashboard Student Search
      Replaces full-page reload with smooth inline result
   ============================================================ */
(function initDashboardSearch() {
    const form        = qs('#dashSearchForm');
    const resultBox   = qs('#dashSearchResult');
    const input       = qs('#dashRollInput');
    if (!form || !resultBox) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const roll = input.value.trim();
        if (!roll) return;

        resultBox.innerHTML = '<div class="ajax-loader">🔍 Searching…</div>';

        fetch(`ajax_search.php?roll_no=${encodeURIComponent(roll)}`)
            .then(r => r.json())
            .then(data => {
                if (data.found) {
                    const s = data.student;
                    const badgeClass = s.is_active == 1 ? 'badge-active' : 'badge-inactive';
                    const badgeText  = s.is_active == 1 ? 'Active' : 'Inactive';
                    resultBox.innerHTML = `
                    <div class="detail-card" style="margin-top:1rem;animation:cardFadeIn 0.4s ease both;">
                        <div class="detail-card-header">
                            <div class="detail-avatar">${escHtml(s.name.charAt(0).toUpperCase())}</div>
                            <div class="detail-name">${escHtml(s.name)}</div>
                            <span class="badge ${badgeClass}">${badgeText}</span>
                        </div>
                        <div class="detail-body">
                            <div class="detail-row"><span class="detail-key">Roll No</span><span class="detail-val">${escHtml(s.roll_no)}</span></div>
                            <div class="detail-row"><span class="detail-key">Course</span><span class="detail-val"><span class="course-badge">${escHtml(s.course)}</span></span></div>
                            <div class="detail-row"><span class="detail-key">Admission Date</span><span class="detail-val">${escHtml(s.admission_date_fmt)}</span></div>
                        </div>
                    </div>
                    <div class="btn-group" style="margin-top:1rem;">
                        <a href="view.php?roll_no=${encodeURIComponent(s.roll_no)}" class="btn btn-gold">👁️ Full Profile</a>
                        <a href="update.php?roll_no=${encodeURIComponent(s.roll_no)}" class="btn btn-primary">✏️ Update</a>
                        ${s.is_active == 1 ? `<a href="delete.php?roll_no=${encodeURIComponent(s.roll_no)}" class="btn btn-danger confirm-delete-ajax">🗑️ Deactivate</a>` : ''}
                    </div>`;
                    initDeleteConfirmOnce(resultBox);
                } else {
                    resultBox.innerHTML = `<div class="alert alert-error">❌ No student found with Roll No: <strong>${escHtml(roll)}</strong></div>`;
                }
            })
            .catch(() => {
                resultBox.innerHTML = '<div class="alert alert-error">❌ Search failed. Please check your connection.</div>';
            });
    });

    // Clear result when input is cleared
    input?.addEventListener('input', function () {
        if (!this.value.trim()) resultBox.innerHTML = '';
    });
})();

/* ============================================================
   6. AJAX — view_all.php filter (no page reload)
   ============================================================ */
(function initFilterAjax() {
    const filterForm  = qs('#filterForm');
    const tableTarget = qs('#studentsTableBody');
    const countBadge  = qs('#resultCount');
    if (!filterForm || !tableTarget) return;

    let debounceTimer = null;

    function doFilter() {
        const params = new FormData(filterForm);
        const qs_str = new URLSearchParams(params).toString();

        tableTarget.closest('.table-wrapper')?.classList.add('loading');

        fetch(`ajax_filter.php?${qs_str}`)
            .then(r => r.json())
            .then(data => {
                tableTarget.closest('.table-wrapper')?.classList.remove('loading');
                if (countBadge) countBadge.textContent = data.total;
                tableTarget.innerHTML = data.html;

                // Attach delete confirms to newly injected buttons
                initDeleteConfirmOnce(tableTarget);

                // Animate rows
                qsa('tr', tableTarget).forEach((tr, i) => {
                    tr.style.animation = 'none';
                    tr.offsetHeight;
                    tr.style.animation = `cardFadeIn 0.3s ease ${i * 0.03}s both`;
                });
            })
            .catch(() => {
                tableTarget.closest('.table-wrapper')?.classList.remove('loading');
            });
    }

    // Debounce on text input
    qs('#filterNameInput', filterForm)?.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(doFilter, 350);
    });

    // Immediate on selects/date
    qsa('select, input[type="date"]', filterForm).forEach(el => {
        el.addEventListener('change', doFilter);
    });

    // Prevent normal form submit
    filterForm.addEventListener('submit', e => { e.preventDefault(); doFilter(); });

    // Clear button
    qs('#clearFilters')?.addEventListener('click', () => {
        filterForm.reset();
        doFilter();
    });
})();

/* ============================================================
   7. Form Validation — Insert / Update
   ============================================================ */
(function initFormValidation() {
    const form = qs('#studentForm');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        let valid = true;
        qsa('.field-error').forEach(el => el.remove());
        qsa('.form-control', form).forEach(el => el.classList.remove('input-error'));

        qsa('[required]', form).forEach(field => {
            if (!field.value.trim()) {
                valid = false;
                markError(field, 'This field is required.');
            }
        });

        const rollField = qs('#roll_no', form);
        if (rollField && rollField.value.trim() && !/^[A-Za-z0-9_]{3,30}$/.test(rollField.value.trim())) {
            valid = false;
            markError(rollField, 'Roll No: 3-30 alphanumeric characters only.');
        }

        if (!valid) {
            e.preventDefault();
            qs('.input-error', form)?.focus();
        }
    });

    function markError(field, msg) {
        field.classList.add('input-error');
        const err = document.createElement('span');
        err.className = 'field-error';
        err.textContent = msg;
        field.parentNode.appendChild(err);
    }
})();

/* ============================================================
   8. Soft-Delete Confirm (works on static + AJAX-injected)
   ============================================================ */
function initDeleteConfirmOnce(ctx) {
    qsa('.confirm-delete, .confirm-delete-ajax', ctx).forEach(btn => {
        btn.replaceWith(btn.cloneNode(true)); // remove old listeners
    });
    qsa('.confirm-delete, .confirm-delete-ajax', ctx).forEach(btn => {
        btn.addEventListener('click', function (e) {
            if (!confirm('Mark this student as Inactive (Soft Delete)?\nThe record will NOT be permanently deleted.')) {
                e.preventDefault();
            }
        });
    });
}

// Initial pass on page load
initDeleteConfirmOnce(document);

/* ============================================================
   9. Auto-dismiss alerts (4 s)
   ============================================================ */
(function autoDismissAlerts() {
    qsa('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.6s ease';
            alert.style.opacity    = '0';
            setTimeout(() => alert.remove(), 700);
        }, 4000);
    });
})();

/* ============================================================
   10. Inject extra inline styles (error field, loader)
   ============================================================ */
(function injectStyles() {
    const style = document.createElement('style');
    style.textContent = `
        .form-control.input-error { border-color: #B22222 !important; box-shadow: 0 0 0 3px rgba(178,34,34,0.12) !important; }
        .field-error  { display:block; font-size:0.76rem; color:#B22222; margin-top:4px; font-weight:600; }
        .ajax-loader  { text-align:center; padding:1.5rem; color:var(--text-light); font-size:0.9rem; font-weight:600; }
        .table-wrapper.loading { opacity:0.5; pointer-events:none; transition:opacity 0.3s; }
    `;
    document.head.appendChild(style);
})();

/* ============================================================
   11. Utility — HTML escape for AJAX-injected content
   ============================================================ */
function escHtml(str) {
    return String(str)
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;')
        .replace(/'/g,'&#039;');
}

/* ============================================================
   12. PHOTO UPLOAD — Live preview in insert.php / update.php
   ============================================================ */
(function initPhotoPreview() {
    const input      = document.getElementById('photo');
    const previewBox = document.getElementById('previewBox');
    if (!input || !previewBox) return;

    input.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;

        // Basic size check client-side (2MB)
        if (file.size > 2 * 1024 * 1024) {
            alert('Photo is too large! Please choose a file under 2 MB.');
            this.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function (e) {
            // Replace whatever is in previewBox with the image
            previewBox.innerHTML = `<img src="${e.target.result}" alt="Preview" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`;
        };
        reader.readAsDataURL(file);
    });
})();
