<?php
// Dynamic Pop-up & Form Rendering Engine
// Supports 4 distinct luxury layouts: Center Modal, Bottom Sheet, Side Drawer, and Fullscreen Takeover
// Respects granular element visibility toggles and page targeting

require_once __DIR__ . '/../engine/JsonStorage.php';
use CoreStore\Engine\JsonStorage;

$currentPage = basename($_SERVER['PHP_SELF'] ?? 'menu.php');
$popupsStorage = new JsonStorage(__DIR__ . '/../data/popups.json');
$popupsList = $popupsStorage->read() ?: [];

$activePopup = null;
foreach ($popupsList as $p) {
    if (!empty($p['active']) && in_array($p['target_page'], [$currentPage, 'all'])) {
        $activePopup = $p;
        break;
    }
}

if (!$activePopup) {
    return; // No active popup for this page
}

$layout = $activePopup['layout'] ?? 'center_modal';
$elements = $activePopup['elements'] ?? [];
$showBadge = $elements['show_badge'] ?? true;
$showTitle = $elements['show_title'] ?? true;
$showSubtitle = $elements['show_subtitle'] ?? true;
$showImage = $elements['show_image'] ?? true;
$showGuests = $elements['show_guests'] ?? true;
$showDateTime = $elements['show_datetime'] ?? true;
$showNotes = $elements['show_notes'] ?? true;
$showClose = $elements['show_close'] ?? true;

$triggerType = $activePopup['trigger_type'] ?? 'both';
$delaySec = intval($activePopup['trigger_delay_sec'] ?? 6);
$floatingBtnText = $activePopup['floating_btn_text'] ?? '✦ Reserve Table';
$badgeText = $activePopup['badge_text'] ?? 'EXCLUSIVE DINING';
$title = $activePopup['title'] ?? 'Reserve Your Dining Experience';
$subtitle = $activePopup['subtitle'] ?? 'Experience our slow-fermented hearth kitchen and architectural ambiance at Orah House.';
$btnText = $activePopup['button_text'] ?? 'Confirm Reservation Request';
$image = $activePopup['image'] ?? 'uploads/mozzarella_flatbread.jpg';
$resolvedImg = str_starts_with($image, 'http') ? $image : $image;
?>

<!-- Pop-up & Form Stylesheet -->
<style>
/* Pop-up Overlay Backdrop */
.orah-popup-overlay {
    position: fixed;
    inset: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(18, 7, 8, 0.65);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    z-index: 9999;
    display: flex;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.38s cubic-bezier(0.22, 1, 0.36, 1), visibility 0.38s ease;
}

.orah-popup-overlay.active {
    opacity: 1;
    visibility: visible;
}

/* =========================================================================
   LAYOUT OPTION 1: CENTER FLOATING MODAL
   ========================================================================= */
.orah-popup-overlay.layout-center_modal {
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.layout-center_modal .orah-popup-card {
    background: #FAF6EE;
    border-radius: 24px;
    max-width: 520px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 25px 60px rgba(45, 10, 15, 0.35);
    border: 1px solid rgba(104, 20, 24, 0.12);
    position: relative;
    transform: translateY(24px) scale(0.96);
    transition: transform 0.38s cubic-bezier(0.22, 1, 0.36, 1);
}

.orah-popup-overlay.active.layout-center_modal .orah-popup-card {
    transform: translateY(0) scale(1);
}

/* =========================================================================
   LAYOUT OPTION 2: BOTTOM SHEET (Slide-up Drawer)
   ========================================================================= */
.orah-popup-overlay.layout-bottom_sheet {
    align-items: flex-end;
    justify-content: center;
    padding: 0;
}

.layout-bottom_sheet .orah-popup-card {
    background: #FAF6EE;
    border-radius: 28px 28px 0 0;
    max-width: 580px;
    width: 100%;
    max-height: 85vh;
    overflow-y: auto;
    box-shadow: 0 -10px 40px rgba(45, 10, 15, 0.3);
    border: 1px solid rgba(104, 20, 24, 0.12);
    border-bottom: none;
    position: relative;
    transform: translateY(100%);
    transition: transform 0.42s cubic-bezier(0.22, 1, 0.36, 1);
}

.orah-popup-overlay.active.layout-bottom_sheet .orah-popup-card {
    transform: translateY(0);
}

/* Drag Pill Handle on Bottom Sheet */
.layout-bottom_sheet .sheet-drag-pill {
    width: 44px;
    height: 4px;
    background: rgba(104, 20, 24, 0.2);
    border-radius: 3px;
    margin: 12px auto 6px;
}

/* =========================================================================
   LAYOUT OPTION 3: SIDE DRAWER (Slide-in from Right)
   ========================================================================= */
.orah-popup-overlay.layout-side_drawer {
    align-items: stretch;
    justify-content: flex-end;
    padding: 0;
}

.layout-side_drawer .orah-popup-card {
    background: #FAF6EE;
    width: 100%;
    max-width: 460px;
    height: 100vh;
    overflow-y: auto;
    box-shadow: -15px 0 50px rgba(45, 10, 15, 0.28);
    border-left: 1px solid rgba(104, 20, 24, 0.12);
    position: relative;
    transform: translateX(100%);
    transition: transform 0.42s cubic-bezier(0.22, 1, 0.36, 1);
}

.orah-popup-overlay.active.layout-side_drawer .orah-popup-card {
    transform: translateX(0);
}

/* =========================================================================
   LAYOUT OPTION 4: FULLSCREEN EDITORIAL TAKEOVER
   ========================================================================= */
.orah-popup-overlay.layout-fullscreen {
    align-items: center;
    justify-content: center;
    padding: 0;
    background: rgba(251, 246, 238, 0.98);
}

.layout-fullscreen .orah-popup-card {
    background: transparent;
    width: 100%;
    max-width: 680px;
    height: 100vh;
    overflow-y: auto;
    padding: 40px 24px;
    box-shadow: none;
    border: none;
    position: relative;
    transform: scale(0.97);
    opacity: 0;
    transition: transform 0.4s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.4s ease;
}

.orah-popup-overlay.active.layout-fullscreen .orah-popup-card {
    transform: scale(1);
    opacity: 1;
}

/* Close Button */
.popup-close-btn {
    position: absolute;
    top: 14px;
    right: 14px;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.85);
    border: 1px solid rgba(104, 20, 24, 0.12);
    color: #681418;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 10;
    transition: all 0.2s ease;
}

.popup-close-btn:hover {
    background: #681418;
    color: #ffffff;
}

/* Card Hero Media */
.popup-media {
    position: relative;
    height: 150px;
    width: 100%;
    background: #1a0a0c;
    overflow: hidden;
}

.popup-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    opacity: 0.9;
}

.popup-img-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(0,0,0,0.15) 0%, rgba(104,20,24,0.7) 100%);
}

/* Body Content */
.popup-body {
    padding: 24px 26px 28px;
}

.popup-badge {
    display: inline-block;
    background: rgba(104, 20, 24, 0.08);
    color: #681418;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 1.8px;
    text-transform: uppercase;
    padding: 4px 10px;
    border-radius: 14px;
    border: 1px solid rgba(104, 20, 24, 0.12);
    margin-bottom: 10px;
}

.popup-title {
    font-family: 'Dream Avenue', 'Cormorant Garamond', Georgia, serif;
    font-size: clamp(1.6rem, 3.2vw, 2.1rem);
    color: #681418;
    line-height: 1.1;
    font-weight: 400;
    margin-bottom: 6px;
}

.popup-subtitle {
    font-size: 0.85rem;
    color: #6c6054;
    line-height: 1.45;
    margin-bottom: 20px;
}

/* Form Controls */
.popup-form {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.popup-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.popup-input-wrap {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.popup-label {
    font-size: 0.72rem;
    font-weight: 700;
    color: #4a3e35;
    text-transform: uppercase;
    letter-spacing: 0.6px;
}

.popup-input, .popup-select, .popup-textarea {
    width: 100%;
    padding: 10px 12px;
    font-size: 0.88rem;
    font-family: 'Manrope', sans-serif;
    color: #1e1e1e;
    background: #ffffff;
    border: 1px solid rgba(104, 20, 24, 0.16);
    border-radius: 10px;
    outline: none;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.popup-input:focus, .popup-select:focus, .popup-textarea:focus {
    border-color: #681418;
    box-shadow: 0 0 0 3px rgba(104, 20, 24, 0.08);
}

.popup-submit-btn {
    background: #681418;
    color: #ffffff;
    border: none;
    border-radius: 24px;
    padding: 13px 22px;
    font-size: 0.88rem;
    font-weight: 700;
    letter-spacing: 0.6px;
    cursor: pointer;
    margin-top: 8px;
    box-shadow: 0 6px 18px rgba(104, 20, 24, 0.28);
    transition: all 0.25s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.popup-submit-btn:hover {
    background: #4a0c0f;
    transform: translateY(-1px);
    box-shadow: 0 10px 24px rgba(104, 20, 24, 0.36);
}

/* Floating Trigger Button (Bottom Right) */
.orah-floating-trigger-btn {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 800;
    background: #681418;
    color: #ffffff;
    border: 1.5px solid rgba(255, 255, 255, 0.3);
    padding: 11px 20px;
    border-radius: 30px;
    font-size: 0.82rem;
    font-weight: 700;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    box-shadow: 0 8px 26px rgba(45, 10, 15, 0.35);
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.25s ease;
}

.orah-floating-trigger-btn:hover {
    background: #4a0c0f;
    transform: translateY(-2px);
    box-shadow: 0 12px 32px rgba(45, 10, 15, 0.45);
}

@media (max-width: 768px) {
    .orah-floating-trigger-btn {
        bottom: 84px; /* avoid dock */
        right: 16px;
        padding: 9px 16px;
        font-size: 0.75rem;
    }
}
</style>

<!-- Floating Trigger Pill Button -->
<?php if ($triggerType === 'button' || $triggerType === 'both'): ?>
    <button type="button" class="orah-floating-trigger-btn" id="orahFloatingTriggerBtn">
        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
        <span><?= htmlspecialchars($floatingBtnText) ?></span>
    </button>
<?php endif; ?>

<!-- Pop-up Overlay Dialog -->
<div class="orah-popup-overlay layout-<?= htmlspecialchars($layout) ?>" id="orahPopupOverlay" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="orah-popup-card">
        <?php if ($layout === 'bottom_sheet'): ?>
            <div class="sheet-drag-pill"></div>
        <?php endif; ?>

        <?php if ($showClose): ?>
            <button type="button" class="popup-close-btn" id="orahPopupCloseBtn" aria-label="Close dialog">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
        <?php endif; ?>

        <?php if ($showImage): ?>
            <div class="popup-media">
                <img src="<?= htmlspecialchars($resolvedImg) ?>" alt="<?= htmlspecialchars($title) ?>" class="popup-img" onerror="this.src='https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600&q=80'">
                <div class="popup-img-overlay"></div>
            </div>
        <?php endif; ?>

        <div class="popup-body">
            <?php if ($showBadge): ?>
                <span class="popup-badge"><?= htmlspecialchars($badgeText) ?></span>
            <?php endif; ?>

            <?php if ($showTitle): ?>
                <h3 class="popup-title"><?= htmlspecialchars($title) ?></h3>
            <?php endif; ?>

            <?php if ($showSubtitle): ?>
                <p class="popup-subtitle"><?= htmlspecialchars($subtitle) ?></p>
            <?php endif; ?>

            <form class="popup-form" id="orahLeadForm">
                <input type="hidden" name="form_name" value="<?= htmlspecialchars($activePopup['name'] ?? 'Table Reservation') ?>">
                <input type="hidden" name="page" value="<?= htmlspecialchars($currentPage) ?>">

                <div class="popup-form-row">
                    <div class="popup-input-wrap">
                        <label class="popup-label">Full Name *</label>
                        <input type="text" name="name" class="popup-input" placeholder="e.g. Karan Sharma" required>
                    </div>

                    <div class="popup-input-wrap">
                        <label class="popup-label">Phone / WhatsApp *</label>
                        <input type="tel" name="phone" class="popup-input" placeholder="e.g. +91 98200 12345" required>
                    </div>
                </div>

                <?php if ($showGuests || $showDateTime): ?>
                    <div class="popup-form-row">
                        <?php if ($showGuests): ?>
                            <div class="popup-input-wrap">
                                <label class="popup-label">Guests</label>
                                <select name="guests" class="popup-select">
                                    <option value="1 Guest">1 Guest</option>
                                    <option value="2 Guests" selected>2 Guests (Table for Two)</option>
                                    <option value="3-4 Guests">3-4 Guests (Small Group)</option>
                                    <option value="5-8 Guests">5-8 Guests (Celebration)</option>
                                    <option value="8+ Guests">8+ Guests (Private Tasting)</option>
                                </select>
                            </div>
                        <?php endif; ?>

                        <?php if ($showDateTime): ?>
                            <div class="popup-input-wrap">
                                <label class="popup-label">Preferred Date</label>
                                <input type="date" name="date" class="popup-input" value="<?= date('Y-m-d') ?>">
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($showDateTime): ?>
                    <div class="popup-input-wrap">
                        <label class="popup-label">Meal Slot / Time</label>
                        <select name="time" class="popup-select">
                            <option value="Lunch (12:30 PM - 3:00 PM)">Lunch (12:30 PM - 3:00 PM)</option>
                            <option value="Sunset Specialty Coffee (4:30 PM - 6:30 PM)">Sunset Coffee & Bakes (4:30 PM - 6:30 PM)</option>
                            <option value="Dinner (7:30 PM - 9:30 PM)" selected>Dinner (7:30 PM - 9:30 PM)</option>
                            <option value="Late Dining (9:30 PM onwards)">Late Dining (9:30 PM onwards)</option>
                        </select>
                    </div>
                <?php endif; ?>

                <?php if ($showNotes): ?>
                    <div class="popup-input-wrap">
                        <label class="popup-label">Special Notes / Occasion (Optional)</label>
                        <textarea name="notes" class="popup-textarea" rows="2" placeholder="e.g. Hearthside seating, birthday, dietary preferences..."></textarea>
                    </div>
                <?php endif; ?>

                <button type="submit" class="popup-submit-btn" id="orahLeadSubmitBtn">
                    <span><?= htmlspecialchars($btnText) ?></span>
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </button>

                <div id="orahLeadStatusMsg" style="display:none; font-size:0.85rem; font-weight:600; text-align:center; padding:10px; border-radius:10px; margin-top:8px;"></div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const overlay = document.getElementById('orahPopupOverlay');
    const closeBtn = document.getElementById('orahPopupCloseBtn');
    const floatingBtn = document.getElementById('orahFloatingTriggerBtn');
    const form = document.getElementById('orahLeadForm');
    const submitBtn = document.getElementById('orahLeadSubmitBtn');
    const statusMsg = document.getElementById('orahLeadStatusMsg');

    const triggerType = <?= json_encode($triggerType) ?>;
    const delaySec = <?= json_encode($delaySec) ?>;

    function openPopup() {
        if (!overlay) return;
        overlay.classList.add('active');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closePopup() {
        if (!overlay) return;
        overlay.classList.remove('active');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    floatingBtn?.addEventListener('click', openPopup);
    closeBtn?.addEventListener('click', closePopup);
    overlay?.addEventListener('click', (e) => {
        if (e.target === overlay) closePopup();
    });

    // Auto-trigger delay (with session debounce)
    if (triggerType === 'delay' || triggerType === 'both') {
        const hasTriggered = sessionStorage.getItem('orah_popup_triggered_' + <?= json_encode($activePopup['id']) ?>);
        if (!hasTriggered) {
            setTimeout(() => {
                openPopup();
                sessionStorage.setItem('orah_popup_triggered_' + <?= json_encode($activePopup['id']) ?>, 'true');
            }, Math.max(1, delaySec) * 1000);
        }
    }

    // AJAX Form Submission
    form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        submitBtn.disabled = true;
        submitBtn.innerHTML = `<span>Submitting...</span>`;

        const formData = new FormData(form);
        const payload = Object.fromEntries(formData.entries());

        try {
            const resp = await fetch('lead_submit.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await resp.json();

            if (data.success) {
                statusMsg.style.display = 'block';
                statusMsg.style.background = '#e6f4ea';
                statusMsg.style.color = '#137333';
                statusMsg.style.border = '1px solid #ceead6';
                statusMsg.textContent = '✓ ' + data.message;
                form.reset();
                submitBtn.innerHTML = `<span>✓ Request Sent</span>`;

                setTimeout(() => {
                    closePopup();
                }, 2800);
            } else {
                throw new Error(data.error || 'Submission failed');
            }
        } catch (err) {
            statusMsg.style.display = 'block';
            statusMsg.style.background = '#fce8e6';
            statusMsg.style.color = '#c5221f';
            statusMsg.style.border = '1px solid #fad2cf';
            statusMsg.textContent = '✕ ' + (err.message || 'Error sending request. Please try again.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = `<span><?= htmlspecialchars($btnText) ?></span>`;
        }
    });
});
</script>
