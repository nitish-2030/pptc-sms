<?php
// ============================================================
// insert.php — Add Student Form (v4 — Production UI)
// ============================================================
require_once 'config/db.php';
require_once 'config/courses_helper.php';
require_once 'config/csrf_helper.php';
$pageTitle       = 'Add Student';
$baseUrl         = '';
$grouped_courses = get_all_courses($conn);

$success = $error = $photo_warn = '';
if (isset($_GET['status'])) {
    $success    = $_GET['status'] === 'success' ? 'Student added successfully!' : '';
    $error      = $_GET['status'] === 'error'   ? htmlspecialchars($_GET['msg'] ?? 'Something went wrong.') : '';
    $photo_warn = htmlspecialchars($_GET['photo_warn'] ?? '');
}

include 'includes/header.php';
?>

<style>
/* ═══════════════════════════════════════
   INSERT FORM v4 — Clean Production UI
   ═══════════════════════════════════════ */

/* Wider container */
.ins-container{
    max-width:860px;
    margin:0 auto;
}

/* Page header */
.ins-page-header{
    display:flex;align-items:center;gap:.75rem;
    margin-bottom:1.5rem;
    padding-bottom:1rem;
    border-bottom:1.5px solid #f0e5e0;
}
.ins-back-btn{
    display:inline-flex;align-items:center;justify-content:center;
    width:32px;height:32px;border-radius:8px;
    background:#f5f0eb;color:var(--crimson-dk);
    text-decoration:none;font-size:1rem;
    border:1px solid rgba(139,0,0,.1);
    transition:all .15s;flex-shrink:0;
}
.ins-back-btn:hover{background:var(--crimson);color:#fff;border-color:var(--crimson);}
.ins-page-title{
    font-size:1.2rem;font-weight:800;color:var(--crimson-dk);
    margin:0;letter-spacing:-.01em;
}
.ins-page-sub{font-size:.7rem;color:var(--text-light);margin:.1rem 0 0;font-weight:600;}
[data-theme="dark"] .ins-page-title{color:#f5c07a;}
[data-theme="dark"] .ins-page-header{border-color:rgba(255,255,255,.07);}

/* Section cards */
.ins-section{
    background:#fff;
    border-radius:12px;
    box-shadow:0 2px 14px rgba(0,0,0,.065);
    padding:1.3rem 1.5rem;
    margin-bottom:1rem;
    transition:box-shadow .2s;
}
.ins-section:focus-within{box-shadow:0 4px 22px rgba(139,0,0,.1);}
[data-theme="dark"] .ins-section{background:#1e1e28;box-shadow:0 4px 24px rgba(0,0,0,.42);}

/* Section heading */
.ins-section-head{
    display:flex;align-items:center;gap:.6rem;
    font-size:.72rem;font-weight:800;
    color:var(--crimson-dk);
    text-transform:uppercase;letter-spacing:.1em;
    padding-bottom:.6rem;
    border-bottom:1.5px solid #f0e5e0;
    margin-bottom:1.1rem;
}
.ins-section-icon{
    width:28px;height:28px;border-radius:7px;
    background:linear-gradient(135deg,var(--crimson-dk),var(--crimson));
    color:var(--gold-lt);font-size:.85rem;
    display:flex;align-items:center;justify-content:center;
    flex-shrink:0;
}
[data-theme="dark"] .ins-section-head{color:var(--gold-lt);border-color:rgba(255,255,255,.07);}

/* Photo upload — redesigned */
.ins-photo-row{
    display:flex;align-items:center;gap:1.25rem;
    padding:1rem;
    background:#faf6f0;
    border-radius:10px;
    border:1.5px dashed #ddd0c8;
    margin-bottom:1.1rem;
    transition:border-color .2s;
}
.ins-photo-row:focus-within{border-color:var(--crimson);}
[data-theme="dark"] .ins-photo-row{background:#252530;border-color:rgba(255,255,255,.1);}
.ins-photo-preview{
    width:68px;height:68px;border-radius:50%;
    background:var(--cream-dk);border:3px solid var(--gold);
    display:flex;align-items:center;justify-content:center;
    font-size:1.65rem;flex-shrink:0;overflow:hidden;
    box-shadow:0 2px 10px rgba(0,0,0,.12);
}
.ins-photo-preview img{width:100%;height:100%;object-fit:cover;border-radius:50%;}
.ins-photo-details{flex:1;}
.ins-photo-details label{
    display:inline-flex;align-items:center;gap:.4rem;
    padding:.38rem 1rem;
    background:linear-gradient(135deg,var(--crimson),var(--crimson-lt));
    color:#fff;border-radius:6px;font-size:.76rem;font-weight:700;
    cursor:pointer;transition:all .15s;margin-bottom:.35rem;
}
.ins-photo-details label:hover{background:linear-gradient(135deg,var(--crimson-dk),var(--crimson));transform:translateY(-1px);}
.ins-photo-details input[type="file"]{display:none;}
.ins-photo-hint{font-size:.65rem;color:var(--text-light);display:block;line-height:1.5;}
.ins-photo-label-main{font-size:.8rem;font-weight:700;color:var(--text-dark);display:block;margin-bottom:.2rem;}
[data-theme="dark"] .ins-photo-label-main{color:#e8ddd0;}

/* Form grids */
.ins-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:1rem;}
.ins-grid-4{display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:1rem;}
.ins-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;}
@media(max-width:700px){
    .ins-grid-2,.ins-grid-4,.ins-grid-3{grid-template-columns:1fr 1fr;}
}
@media(max-width:480px){
    .ins-grid-2,.ins-grid-4,.ins-grid-3{grid-template-columns:1fr;}
}

/* Form group */
.ins-group{display:flex;flex-direction:column;}
.ins-label{
    font-size:.67rem;font-weight:800;color:var(--text-mid);
    text-transform:uppercase;letter-spacing:.08em;
    margin-bottom:.38rem;
}
[data-theme="dark"] .ins-label{color:#c8a898;}
.ins-label .req{color:var(--crimson);margin-left:.1rem;}
.ins-label .opt{color:var(--text-light);font-weight:400;font-size:.65rem;text-transform:none;letter-spacing:0;}

/* Inputs */
.ins-input,.ins-select,.ins-textarea{
    width:100%;
    padding:.6rem .85rem;
    border:1.5px solid #ddd0ca;
    border-radius:8px;
    font-size:.88rem;
    color:var(--text-dark);
    background:#fdfcfb;
    outline:none;
    transition:border-color .2s,box-shadow .2s,background .2s;
    font-family:inherit;
}
.ins-input:focus,.ins-select:focus,.ins-textarea:focus{
    border-color:var(--crimson);
    box-shadow:0 0 0 3px rgba(139,0,0,.09);
    background:#fff;
}
.ins-input::placeholder,.ins-textarea::placeholder{color:#c8b0ac;font-size:.83rem;}
[data-theme="dark"] .ins-input,[data-theme="dark"] .ins-select,[data-theme="dark"] .ins-textarea{
    background:#252530;border-color:rgba(255,255,255,.1);color:#f0eae0;
}
[data-theme="dark"] .ins-input:focus,[data-theme="dark"] .ins-select:focus{background:#2d2d3a;border-color:var(--gold);}
.ins-textarea{resize:vertical;min-height:72px;line-height:1.55;}

/* Submit bar */
.ins-submit-bar{
    display:flex;gap:.75rem;align-items:center;
    padding:1.1rem 1.5rem;
    background:#fff;border-radius:12px;
    box-shadow:0 2px 14px rgba(0,0,0,.065);
    flex-wrap:wrap;
}
[data-theme="dark"] .ins-submit-bar{background:#1e1e28;box-shadow:0 4px 24px rgba(0,0,0,.42);}
.ins-submit-note{font-size:.68rem;color:var(--text-light);margin-left:auto;}

/* Ripple */
.ripple{position:relative;overflow:hidden;}
.ripple-wave{position:absolute;border-radius:50%;pointer-events:none;background:rgba(255,255,255,.32);transform:scale(0);animation:rippleOut .55s ease-out forwards;}
@keyframes rippleOut{to{transform:scale(4);opacity:0;}}

/* Section entry animation */
.ins-section,.ins-submit-bar{animation:sectionIn .28s ease both;}
.ins-section:nth-child(2){animation-delay:.05s;}
.ins-section:nth-child(3){animation-delay:.1s;}
.ins-section:nth-child(4){animation-delay:.15s;}
.ins-section:nth-child(5){animation-delay:.2s;}
.ins-submit-bar{animation-delay:.25s;}
@keyframes sectionIn{from{opacity:0;transform:translateY(8px);}to{opacity:1;transform:translateY(0);}}
</style>

<div class="ins-container">

    <!-- Page header with back button -->
    <div class="ins-page-header">
        <a href="dashboard.php" class="ins-back-btn" title="Back to Dashboard">&#8592;</a>
        <div>
            <h1 class="ins-page-title">Add New Student</h1>
            <p class="ins-page-sub">Fill in the details below to register a new student</p>
        </div>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success" style="margin-bottom:1rem;">&#10003; <?= $success ?><?= $photo_warn ? ' <em style="font-size:0.85rem;opacity:.8;">(Photo: '.$photo_warn.')</em>' : '' ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-error" style="margin-bottom:1rem;">&#10007; <?= $error ?></div>
    <?php endif; ?>

    <form id="studentForm" action="process_insert.php" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <!-- SECTION 1: Photo & Identity -->
        <div class="ins-section">
            <div class="ins-section-head">
                <span class="ins-section-icon">&#128247;</span>
                Photo &amp; Identity
            </div>

            <!-- Photo upload — properly integrated -->
            <div class="ins-photo-row">
                <div class="ins-photo-preview" id="previewBox">&#127891;</div>
                <div class="ins-photo-details">
                    <span class="ins-photo-label-main">Profile Photo <span style="color:var(--text-light);font-weight:400;font-size:.75rem;">(optional)</span></span>
                    <label for="photo">&#128247; Choose Photo</label>
                    <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp">
                    <span class="ins-photo-hint">JPG, PNG or WEBP &middot; Max 2 MB &middot; Square recommended</span>
                </div>
            </div>

            <div class="ins-grid-2" style="margin-bottom:1rem;">
                <div class="ins-group">
                    <label class="ins-label" for="roll_no">Roll Number<span class="req">*</span></label>
                    <input type="text" id="roll_no" name="roll_no" class="ins-input"
                           placeholder="e.g. BCA2024001" required maxlength="30">
                </div>
                <div class="ins-group">
                    <label class="ins-label" for="name">Full Name<span class="req">*</span></label>
                    <input type="text" id="name" name="name" class="ins-input"
                           placeholder="Student's full name" required maxlength="100">
                </div>
            </div>

            <div class="ins-grid-4">
                <div class="ins-group">
                    <label class="ins-label" for="gender">Gender<span class="req">*</span></label>
                    <select id="gender" name="gender" class="ins-select" required>
                        <option value="">Select</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="ins-group">
                    <label class="ins-label" for="dob">Date of Birth <span class="opt">(optional)</span></label>
                    <input type="date" id="dob" name="dob" class="ins-input"
                           max="<?= date('Y-m-d', strtotime('-14 years')) ?>">
                </div>
                <div class="ins-group">
                    <label class="ins-label" for="blood_group">Blood Group <span class="opt">(optional)</span></label>
                    <select id="blood_group" name="blood_group" class="ins-select">
                        <option value="">Select</option>
                        <?php foreach(['A+','A-','B+','B-','O+','O-','AB+','AB-'] as $bg): ?>
                        <option value="<?= $bg ?>"><?= $bg ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ins-group">
                    <label class="ins-label" for="category">Category<span class="req">*</span></label>
                    <select id="category" name="category" class="ins-select" required>
                        <option value="General">General</option>
                        <option value="OBC">OBC</option>
                        <option value="SC">SC</option>
                        <option value="ST">ST</option>
                        <option value="EWS">EWS</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- SECTION 2: Contact Info -->
        <div class="ins-section">
            <div class="ins-section-head">
                <span class="ins-section-icon">&#128222;</span>
                Contact Information
            </div>
            <div class="ins-grid-2" style="margin-bottom:1rem;">
                <div class="ins-group">
                    <label class="ins-label" for="phone">Phone Number <span class="opt">(optional)</span></label>
                    <input type="tel" id="phone" name="phone" class="ins-input"
                           placeholder="e.g. 9876543210" maxlength="15" pattern="[0-9]{10,15}">
                </div>
                <div class="ins-group">
                    <label class="ins-label" for="email">Email Address <span class="opt">(optional)</span></label>
                    <input type="email" id="email" name="email" class="ins-input"
                           placeholder="student@email.com" maxlength="120">
                </div>
            </div>
            <div class="ins-group">
                <label class="ins-label" for="address">Address <span class="opt">(optional)</span></label>
                <textarea id="address" name="address" class="ins-textarea"
                          rows="2" placeholder="Full residential address..." maxlength="300"></textarea>
            </div>
        </div>

        <!-- SECTION 3: Guardian Info -->
        <div class="ins-section">
            <div class="ins-section-head">
                <span class="ins-section-icon">&#128106;</span>
                Guardian / Parent Details
            </div>
            <div class="ins-grid-2">
                <div class="ins-group">
                    <label class="ins-label" for="guardian_name">Guardian Name <span class="opt">(optional)</span></label>
                    <input type="text" id="guardian_name" name="guardian_name" class="ins-input"
                           placeholder="Father / Mother / Guardian" maxlength="100">
                </div>
                <div class="ins-group">
                    <label class="ins-label" for="guardian_phone">Guardian Phone <span class="opt">(optional)</span></label>
                    <input type="tel" id="guardian_phone" name="guardian_phone" class="ins-input"
                           placeholder="e.g. 9876543210" maxlength="15" pattern="[0-9]{10,15}">
                </div>
            </div>
        </div>

        <!-- SECTION 4: Academic Info -->
        <div class="ins-section">
            <div class="ins-section-head">
                <span class="ins-section-icon">&#127891;</span>
                Academic Details
            </div>
            <div class="ins-grid-4">
                <div class="ins-group" style="grid-column:span 2;">
                    <label class="ins-label" for="course">Course<span class="req">*</span></label>
                    <select id="course" name="course" class="ins-select" required>
                        <option value="">-- Select Course --</option>
                        <?php foreach ($grouped_courses as $cat => $list): ?>
                        <optgroup label="— <?= $cat ?> Programmes —">
                            <?php foreach ($list as $c): ?>
                            <option value="<?= htmlspecialchars($c['code']) ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ins-group">
                    <label class="ins-label" for="admission_date">Admission Date<span class="req">*</span></label>
                    <input type="date" id="admission_date" name="admission_date" class="ins-input"
                           required max="<?= date('Y-m-d') ?>">
                </div>
                <div class="ins-group">
                    <label class="ins-label" for="is_active">Status</label>
                    <select id="is_active" name="is_active" class="ins-select">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>
            <!-- <div class="ins-group" style="margin-top:1rem;">
                <label class="ins-label" for="total_fee">Total Course Fee (&#8377;) <span class="opt">(optional — auto-filled from course)</span></label>
                <input type="number" id="total_fee" name="total_fee" class="ins-input"
                       placeholder="e.g. 48000" min="0" step="0.01"
                       style="max-width:260px;">
            </div> -->
        </div>

        <!-- Submit bar -->
        <div class="ins-submit-bar">
            <button type="submit" class="btn btn-primary ripple">&#128190; Save Student</button>
            <a href="dashboard.php" class="btn btn-outline">Cancel</a>
            <span class="ins-submit-note">&#42; Required fields must be filled</span>
        </div>
    </form>
</div>

<script>
// Photo preview
(function(){
    const input = document.getElementById('photo');
    const box   = document.getElementById('previewBox');
    if(!input||!box) return;
    input.addEventListener('change',function(){
        const f = this.files[0];
        if(!f) return;
        const r = new FileReader();
        r.onload = e => { box.innerHTML = `<img src="${e.target.result}" alt="preview">`; };
        r.readAsDataURL(f);
    });
})();

// Ripple
document.addEventListener('click',function(e){
    const el=e.target.closest('.ripple');
    if(!el) return;
    const r=el.getBoundingClientRect();
    const w=document.createElement('span');
    w.className='ripple-wave';
    const sz=Math.max(r.width,r.height)*2;
    w.style.cssText=`width:${sz}px;height:${sz}px;top:${e.clientY-r.top-sz/2}px;left:${e.clientX-r.left-sz/2}px`;
    el.appendChild(w);
    w.addEventListener('animationend',()=>w.remove());
});
</script>

<?php include 'includes/footer.php'; ?>
