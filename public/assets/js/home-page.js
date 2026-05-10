'use strict';

/** Utiliser toujours window.* — en mode strict, showCaModal seul peut être undefined. */
function caShowModal(opts) {
  if (typeof window.showCaModal === 'function') {
    window.showCaModal(opts);
    return;
  }
  console.warn('[CleanAvis] Modal indisponible — vérifiez que ca-ui-modal.js est chargé.', opts);
}

function _packPriceEuro(formSlug, fallback) {
  const p = window.CLEANAVIS_PACKS && window.CLEANAVIS_PACKS[formSlug];
  const v = p && typeof p.priceEuro === 'number' ? p.priceEuro : null;
  return v !== null && !Number.isNaN(v) ? v : fallback;
}

function packPricesEuro() {
  return {
    standard: _packPriceEuro('standard', 49),
    fiche: _packPriceEuro('fiche', 99),
    avocat: _packPriceEuro('avocat', 299),
  };
}

let currentNote = null;
let manualNote = null;
/** Instance Embedded Checkout (destroy au changement d’étape). */
let stripeEmbeddedCheckoutInstance = null;
/** Évite les doubles appels API si Turnstile renvoie plusieurs fois le jeton. */
let checkoutInitInFlight = false;

function setPaymentFetchLoading(on) {
  const el = document.getElementById('contact-payment-loading');
  if (el) el.hidden = !on;
}

function resetStripeEmbeddedCheckout() {
  checkoutInitInFlight = false;
  setPaymentFetchLoading(false);
  if (stripeEmbeddedCheckoutInstance) {
    try { stripeEmbeddedCheckoutInstance.destroy(); } catch (e) { /* ignore */ }
    stripeEmbeddedCheckoutInstance = null;
  }
  const pre = document.getElementById('contact-payment-preembed');
  const wrap = document.getElementById('stripe-checkout-embed-wrap');
  const mount = document.getElementById('stripe-checkout-embed');
  if (pre) pre.hidden = false;
  if (wrap) wrap.hidden = true;
  if (mount) mount.innerHTML = '';
}

/**
 * Cloudflare Turnstile : après succès, création de commande + montage Embedded Checkout (sans bouton intermédiaire).
 */
function isContactWizardPaymentStepActive() {
  const root = document.getElementById('contact-wizard');
  if (!root) return false;
  const step = parseInt(root.dataset.currentStep || '1', 10);
  if (step !== 6) return false;
  const panel = root.querySelector('.contact-wizard-panel[data-wizard-step="6"]');
  return !!(panel && panel.classList.contains('is-active'));
}

window.cleanavisTurnstileExpired = function cleanavisTurnstileExpired() {
  if (!isContactWizardPaymentStepActive()) return;
  checkoutInitInFlight = false;
  setPaymentFetchLoading(false);
};

window.cleanavisTurnstilePaymentReady = async function cleanavisTurnstilePaymentReady(token) {
  /* Turnstile vit dans l’étape 6 mais le panneau peut être masqué (visibility) : le challenge peut quand même se terminer et déclencher ce callback sans que l’utilisateur soit à l’étape paiement. */
  if (!isContactWizardPaymentStepActive()) return;
  if (stripeEmbeddedCheckoutInstance || checkoutInitInFlight) return;
  checkoutInitInFlight = true;
  try {
    const ts = typeof token === 'string' && token ? token : document.querySelector('[name="cf-turnstile-response"]')?.value || '';
    await cleanavisStartEmbeddedCheckout(ts);
  } finally {
    checkoutInitInFlight = false;
  }
};

function displayPlaceResult(place) {
  const rating = place.rating || null;
  const total  = place.user_ratings_total || 0;
  const neg = estimateNegativeReviews(rating, total);
  const pct = total > 0 ? Math.round((neg / total) * 100) : 0;
  const iconEl = document.getElementById('res-icon');
  if (place.icon) { iconEl.innerHTML = `<img src="${place.icon}" alt="${place.name}">`; } else { iconEl.textContent = '📍'; }
  document.getElementById('res-name').textContent  = place.name || '—';
  document.getElementById('res-addr').textContent  = place.formatted_address || '—';
  document.getElementById('res-note').textContent  = rating ? rating.toFixed(1) + ' ★' : 'N/A';
  document.getElementById('res-total').textContent = total.toLocaleString('fr-FR');
  document.getElementById('res-neg').textContent   = neg.toLocaleString('fr-FR');
  document.getElementById('res-pct').textContent   = pct + '%';
  renderAlert(rating, pct, total, neg);
  currentNote = rating;
  document.getElementById('note-manual-wrap').style.display = 'none';
  showResultPanel();
  calcLoss();
  const formInput = document.getElementById('f-entreprise');
  if (formInput && place.name) { formInput.value = place.name; displayFormInsight(place, true); }
}

function estimateNegativeReviews(rating, total) {
  if (!rating || !total) return 0;
  let negRate;
  if      (rating <= 1.5) negRate = 0.75;
  else if (rating <= 2.0) negRate = 0.60;
  else if (rating <= 2.5) negRate = 0.45;
  else if (rating <= 3.0) negRate = 0.32;
  else if (rating <= 3.5) negRate = 0.20;
  else if (rating <= 4.0) negRate = 0.12;
  else if (rating <= 4.3) negRate = 0.07;
  else if (rating <= 4.7) negRate = 0.04;
  else                    negRate = 0.02;
  return Math.round(total * negRate);
}

function renderAlert(rating, pct, total, neg) {
  const el = document.getElementById('res-alert');
  if (!rating) { el.innerHTML = ''; return; }
  if (rating < 3.0) {
    el.innerHTML = `<div class="a-box a-danger"><span class="a-icon">🚨</span><div><div class="a-title" style="color:var(--g-red)">Note critique — Situation d'urgence</div><div class="a-body">Avec <strong>${rating.toFixed(1)}★</strong>, votre établissement subit un préjudice réputationnel majeur. <strong>${pct}%</strong> de vos avis sont négatifs (${neg} sur ${total.toLocaleString('fr-FR')}). Une mise en demeure juridique est fortement conseillée.</div></div></div>`;
  } else if (rating < 4.2) {
    el.innerHTML = `<div class="a-box a-warning"><span class="a-icon">⚠️</span><div><div class="a-title" style="color:#8A5E00">Note insuffisante — Action recommandée</div><div class="a-body">Avec <strong>${rating.toFixed(1)}★</strong>, vous êtes en dessous du seuil de confiance de 4,2★. <strong>${pct}%</strong> de vos avis sont négatifs. Un signalement renforcé auprès de Google peut rapidement améliorer votre situation.</div></div></div>`;
  } else {
    el.innerHTML = `<div class="a-box a-ok"><span class="a-icon">✅</span><div><div class="a-title" style="color:var(--g-green)">Note correcte — Surveillance recommandée</div><div class="a-body">Votre note de <strong>${rating.toFixed(1)}★</strong> est au-dessus du seuil critique. Cependant, <strong>${pct}%</strong> d'avis négatifs peuvent encore impacter votre réputation.</div></div></div>`;
  }
}

function calcLoss() {
  const ca   = parseFloat(document.getElementById('ca-input').value) || 0;
  const note = currentNote !== null ? currentNote : manualNote;
  const disp = document.getElementById('calc-result-display');
  if (!ca || ca <= 0 || note === null || Number.isNaN(note)) { disp.style.display = 'none'; return; }
  let coeff, severity;
  if      (note <= 1.5) { coeff = 0.28; severity = 'danger'; }
  else if (note <= 2.0) { coeff = 0.22; severity = 'danger'; }
  else if (note <= 2.5) { coeff = 0.17; severity = 'danger'; }
  else if (note <= 3.0) { coeff = 0.13; severity = 'danger'; }
  else if (note <= 3.5) { coeff = 0.09; severity = 'warning'; }
  else if (note <= 4.0) { coeff = 0.06; severity = 'warning'; }
  else if (note <= 4.3) { coeff = 0.03; severity = 'ok'; }
  else                  { coeff = 0.01; severity = 'ok'; }
  const annualLoss  = Math.round(ca * coeff);
  const monthlyLoss = Math.round(annualLoss / 12);
  const pe = packPricesEuro();
  const roiStd = Math.round(annualLoss / pe.standard);
  const roiAvo = Math.round(annualLoss / pe.avocat);
  const colorMap = { danger:'danger-txt', warning:'warning-txt', ok:'ok-txt' };
  const c = colorMap[severity];
  disp.className = `calc-result-display ${severity}`;
  disp.style.display = 'flex';
  disp.innerHTML = `<div class="crd-left"><div class="crd-amount ${c}">${monthlyLoss.toLocaleString('fr-FR')} €</div><div class="crd-period">par mois</div></div><div class="crd-right"><div class="crd-message">Avec une note de <strong>${note.toFixed(1)}★</strong>, votre manque à gagner estimé est de <strong class="${c}">${monthlyLoss.toLocaleString('fr-FR')} €/mois</strong> soit <strong class="${c}">${annualLoss.toLocaleString('fr-FR')} €/an</strong>.</div><div class="crd-annual ${c}">${severity !== 'ok' ? `ROI Package Standard : ×${roiStd} → ROI Package Avocats : ×${roiAvo}` : 'Votre note est correcte — surveillez vos nouveaux avis régulièrement.'}</div></div>`;
}

function setManualNote(n, btn) {
  manualNote = n; currentNote = n;
  document.querySelectorAll('.star-btn').forEach((b, i) => {
    b.classList.toggle('active', i < n);
    b.setAttribute('aria-pressed', i < n ? 'true' : 'false');
  });
  calcLoss();
}

function displayFormInsight(place, noScroll) {
  const rating = place.rating || null;
  const total  = place.user_ratings_total || 0;
  const name   = place.name || '';
  const addr   = place.formatted_address || '';
  const addrWrap  = document.getElementById('f-adresse-wrap');
  const addrInput = document.getElementById('f-adresse');
  if (addrInput) { addrInput.value = addr; addrWrap.style.display = 'block'; }
  const panel = document.getElementById('form-insight-panel');
  if (!panel) return;
  if (!rating) { panel.style.display = 'none'; return; }
  const gaugeW    = Math.min(Math.round((rating / 5) * 100), 100);
  const targetPct = Math.round((4.2 / 5) * 100);
  const neg = estimateNegativeReviews(rating, total);
  const pct = total > 0 ? Math.round((neg / total) * 100) : 0;
  let gaugeColor, urgencyClass, noteColor, severity;
  if      (rating < 3.0) { gaugeColor='var(--g-red)';  urgencyClass='a-danger';  noteColor='var(--g-red)';  severity='danger';  }
  else if (rating < 4.2) { gaugeColor='#F9AB00';        urgencyClass='a-warning'; noteColor='#F9AB00';       severity='warning'; }
  else                   { gaugeColor='var(--g-green)'; urgencyClass='a-ok';      noteColor='var(--g-green)';severity='ok';      }
  function starsHtml(r) {
    const full = Math.floor(r), half = (r - full) >= 0.5 ? 1 : 0, empty = 5 - full - half;
    return '★'.repeat(full) + (half ? '½' : '') + '☆'.repeat(empty);
  }
  const coeffMap = [[1.5,0.28],[2.0,0.22],[2.5,0.17],[3.0,0.13],[3.5,0.09],[4.0,0.06],[4.3,0.03]];
  let coeff = 0.01;
  for (const [seuil, c] of coeffMap) { if (rating <= seuil) { coeff = c; break; } }
  const caEstim    = 400000;
  const annualLoss = Math.round(caEstim * coeff);
  const monthlyLoss= Math.round(annualLoss / 12);
  const pe = packPricesEuro();
  const pStd = window.CLEANAVIS_PACKS && window.CLEANAVIS_PACKS.standard;
  const pAvo = window.CLEANAVIS_PACKS && window.CLEANAVIS_PACKS.avocat;
  const pkgPrice = rating < 3.5 ? pe.avocat : pe.standard;
  const pkgLabel = rating < 3.5 ? (pAvo && pAvo.cardTitle ? pAvo.cardTitle : 'Package Avocats') : (pStd && pStd.cardTitle ? pStd.cardTitle : 'Package Standard');
  const pkgVal   = rating < 3.5 ? 'avocat' : 'standard';
  const roi      = Math.round(annualLoss / pkgPrice);
  const urgencyMsgs = {
    danger:  { t:'🚨 Situation critique — chaque jour aggrave le préjudice', b:`Avec <strong>${rating.toFixed(1)}★</strong>, votre établissement est sous le seuil critique de 4,2★. <strong>${pct}% de vos avis sont négatifs</strong>.` },
    warning: { t:'⚠️ Note insuffisante — vous perdez des clients chaque semaine', b:`Avec <strong>${rating.toFixed(1)}★</strong>, vous êtes sous le seuil de confiance. <strong>${neg} avis négatifs</strong> sur ${total} freinent activement vos conversions.` },
    ok:      { t:'✅ Bonne note — protégez-la avant qu\'un avis ne la fasse chuter', b:`Votre note de <strong>${rating.toFixed(1)}★</strong> est bonne. Un seul avis diffamatoire non traité peut faire basculer votre réputation.` }
  };
  const { t: urgencyTitle, b: urgencyBody } = urgencyMsgs[severity];
  const sel = document.getElementById('f-package');
  if (sel) sel.value = pkgVal;
  const iconHtml = place.icon ? `<img src="${place.icon}" alt="${name}">` : '🏢';
  panel.innerHTML = `<div class="fi-header"><div class="fi-biz-icon">${iconHtml}</div><div><div class="fi-biz-name">${name}</div><div class="fi-biz-addr">${addr}</div></div></div><div class="fi-body"><div class="fi-gauge-wrap"><div class="fi-gauge-label"><div><span class="fi-gauge-note" style="color:${noteColor}">${rating.toFixed(1)}</span> <span class="fi-gauge-stars">${starsHtml(rating)}</span></div><div class="fi-gauge-seuil">Seuil de confiance : 4.2★</div></div><div class="fi-gauge-track"><div class="fi-gauge-fill" style="width:${gaugeW}%;background:${gaugeColor}"></div><div class="fi-gauge-target" style="left:${targetPct}%"></div></div></div><div class="fi-stats"><div class="fi-stat"><div class="fi-stat-val" style="color:${noteColor}">${rating.toFixed(1)}★</div><div class="fi-stat-lbl">Note Google</div></div><div class="fi-stat"><div class="fi-stat-val" style="color:var(--g-red)">${neg}</div><div class="fi-stat-lbl">Avis négatifs estimés</div></div><div class="fi-stat"><div class="fi-stat-val" style="color:var(--g-red)">~${monthlyLoss.toLocaleString('fr-FR')}€</div><div class="fi-stat-lbl">Perte mensuelle estimée</div></div></div><div class="fi-urgency ${urgencyClass}"><span class="fi-urgency-icon">${severity==='danger'?'🚨':severity==='warning'?'⚠️':'✅'}</span><div><div class="fi-urgency-title">${urgencyTitle}</div><div class="fi-urgency-body">${urgencyBody}</div></div></div><div class="fi-recommendation"><div class="fi-rec-left"><div class="fi-rec-tag">★ Recommandé pour vous</div><div class="fi-rec-title">${pkgLabel}</div><div class="fi-rec-roi">ROI estimé ×${roi} — soit ${annualLoss.toLocaleString('fr-FR')} €/an récupérés</div></div><div class="fi-rec-price"><sup>€</sup>${pkgPrice}</div></div></div>`;
  panel.style.display = 'block';
  if (!noScroll) setTimeout(() => panel.scrollIntoView({ behavior:'smooth', block:'nearest' }), 150);
}

function showResultPanel() {
  const p = document.getElementById('result-panel');
  p.classList.add('visible');
  setTimeout(() => p.scrollIntoView({ behavior:'smooth', block:'nearest' }), 100);
}

function selectPackAndScroll(pack) {
  const sel = document.getElementById('f-package');
  if (sel) sel.value = pack;
  const section = document.getElementById('contact');
  if (section) window.scrollTo({top: section.getBoundingClientRect().top + window.scrollY - 72, behavior: 'smooth'});
  if (document.getElementById('contact-wizard')) {
    wizardSetStep(5);
  }
}

function extractUrl(input) {
  const match = input.value.match(/https?:\/\/\S+/);
  if (match) input.value = match[0];
}

let _rpDebounce = null;
function validateUrl(input) {
  const val = input.value.trim();
  const ok  = document.getElementById('url-ok');
  const err = document.getElementById('url-err');
  if (!val) { ok.style.display='none'; input.style.borderColor=''; err.style.display='none'; hideReviewPreview(); return; }
  try {
    const u = new URL(val);
    if (u.protocol === 'https:') {
      ok.textContent='✅'; ok.style.display='block'; input.style.borderColor='var(--g-green)'; err.style.display='none';
      clearTimeout(_rpDebounce); _rpDebounce = setTimeout(() => fetchReviewPreview(val), 600);
    } else { ok.textContent='❌'; ok.style.display='block'; input.style.borderColor='var(--g-red)'; err.style.display='block'; hideReviewPreview(); }
  } catch { if (val.length > 6) { ok.textContent='❌'; ok.style.display='block'; input.style.borderColor='var(--g-red)'; err.style.display='block'; } hideReviewPreview(); }
}

function hideReviewPreview() { const el=document.getElementById('review-preview'); if(el){el.style.display='none';el.innerHTML='';} }

function parseGoogleMapsUrl(url) {
  try {
    const u = new URL(url);
    const host = u.hostname;
    if (host==='maps.app.goo.gl'||host==='goo.gl'||host==='g.page') return {type:'short'};
    const isGMaps = (host.includes('google.')&&url.includes('maps'))||host==='maps.google.com';
    if (!isGMaps) return null;
    const placeIdMatch = url.match(/!1s(ChIJ[^!&%]+)/);
    if (placeIdMatch) return {type:'place_id',value:decodeURIComponent(placeIdMatch[1])};
    const cid = u.searchParams.get('cid');
    if (cid) return {type:'name',value:u.searchParams.get('q')||cid};
    const nameMatch = url.match(/maps\/place\/([^/@?#]+)/);
    if (nameMatch) return {type:'name',value:decodeURIComponent(nameMatch[1]).replace(/\+/g,' ')};
    return {type:'short'};
  } catch { return null; }
}

function starsHtmlSm(rating) { const full=Math.floor(rating),half=rating%1>=0.5?1:0,empty=5-full-half; return '★'.repeat(full)+(half?'½':'')+'☆'.repeat(empty); }

function rpRenderLoading() { const el=document.getElementById('review-preview'); el.style.display='block'; el.innerHTML=`<div class="rp-card"><div class="rp-loading"><span class="rp-loading-spinner"></span>Récupération des avis en cours…</div></div>`; }
function rpRenderShort()   { const el=document.getElementById('review-preview'); el.style.display='block'; el.innerHTML=`<div class="rp-card"><div class="rp-short-msg"><span class="rp-ic">✅</span><div><strong>Lien enregistré</strong><br>Vérifiez que ce lien correspond bien à l'avis que vous souhaitez signaler.</div></div></div>`; }
function rpRenderError()   { const el=document.getElementById('review-preview'); el.style.display='block'; el.innerHTML=`<div class="rp-card"><div class="rp-short-msg"><span class="rp-ic">⚠️</span><div><strong>Lien non reconnu</strong><br>Vérifiez que le lien pointe bien vers une fiche Google Maps.</div></div></div>`; }

function rpRenderPlace(place) {
  const el=document.getElementById('review-preview');
  const name=place.name||'Établissement',rating=place.rating||0,total=place.user_ratings_total||0,reviews=place.reviews||[];
  const iconHtml=place.icon?`<img src="${place.icon}" alt="${name}" loading="lazy">`:'🏢';
  let reviewsHtml='';
  if(reviews.length===0){ reviewsHtml=`<div class="rp-empty">Aucun avis disponible via l'API pour cet établissement.</div>`; }
  else { reviewsHtml=reviews.map(r=>{ const initial=(r.author_name||'?')[0].toUpperCase(); const avatarHtml=r.profile_photo_url?`<img src="${r.profile_photo_url}" alt="${r.author_name}" loading="lazy">`:initial; const stars='★'.repeat(r.rating)+'☆'.repeat(5-r.rating); const text=r.text?r.text.replace(/</g,'&lt;').replace(/>/g,'&gt;'):'<em>Aucun texte</em>'; return `<div class="rp-review-item"><div class="rp-review-top"><div class="rp-avatar">${avatarHtml}</div><div class="rp-author">${r.author_name||'Anonyme'}</div><div class="rp-date">${r.relative_time_description||''}</div></div><div class="rp-stars">${stars}</div><div class="rp-text">${text}</div></div>`; }).join(''); }
  el.style.display='block';
  el.innerHTML=`<div class="rp-card"><div class="rp-head"><div class="rp-biz-icon">${iconHtml}</div><div><div class="rp-biz-name">${name}</div><div class="rp-biz-meta"><span class="rp-biz-stars">${starsHtmlSm(rating)}</span> ${rating.toFixed(1)} · ${total.toLocaleString('fr-FR')} avis</div></div></div><div class="rp-reviews">${reviewsHtml}</div><div class="rp-footer"><span class="rp-footer-icon">ℹ️</span>Identifiez l'avis concerné ci-dessus et mentionnez-le dans votre justification</div></div>`;
}

function fetchReviewPreview(url) {
  const info = parseGoogleMapsUrl(url);
  if (!info) { rpRenderShort(); return; }
  if (info.type==='short') { rpRenderShort(); return; }
  if (typeof google==='undefined'||!google.maps||!google.maps.places) { rpRenderShort(); return; }
  rpRenderLoading();
  const svcDiv=document.createElement('div'); svcDiv.style.display='none'; document.body.appendChild(svcDiv);
  const svc=new google.maps.places.PlacesService(svcDiv);
  const fields=['name','rating','user_ratings_total','reviews','icon'];
  if (info.type==='place_id') {
    svc.getDetails({placeId:info.value,fields},(place,status)=>{ document.body.removeChild(svcDiv); if(status===google.maps.places.PlacesServiceStatus.OK&&place){rpRenderPlace(place);}else{rpRenderError();} });
  } else if (info.type==='name') {
    svc.findPlaceFromText({query:info.value,fields:['place_id']},(results,status)=>{ if(status===google.maps.places.PlacesServiceStatus.OK&&results?.[0]){ svc.getDetails({placeId:results[0].place_id,fields},(place,st)=>{ document.body.removeChild(svcDiv); if(st===google.maps.places.PlacesServiceStatus.OK&&place){rpRenderPlace(place);}else{rpRenderError();} }); }else{ document.body.removeChild(svcDiv); rpRenderError(); } });
  }
}

function showFiles(input) { const list=document.getElementById('files-list'); if(!list)return; list.innerHTML=Array.from(input.files).map(f=>`<div style="font-size:12px;color:var(--g-green);margin-top:4px">✅ ${f.name} (${(f.size/1024).toFixed(0)} Ko)</div>`).join(''); }

const WIZARD_TOTAL_STEPS = 6;
const WIZARD_FIELD_STEP = { 'f-nom': 1, 'f-prenom': 1, 'f-email': 2, 'f-tel': 2, 'f-entreprise': 3, 'f-url': 4, 'f-justif': 5 };

function wizardRoot() {
  return document.getElementById('contact-wizard');
}

function wizardSetStep(n) {
  const root = wizardRoot();
  if (!root) return;
  const prev = parseInt(root.dataset.currentStep || '1', 10);
  const step = Math.max(1, Math.min(WIZARD_TOTAL_STEPS, n));
  if (prev === 6 && step !== 6) {
    resetStripeEmbeddedCheckout();
  }
  root.dataset.currentStep = String(step);
  root.querySelectorAll('.contact-wizard-panel').forEach((panel) => {
    const ps = parseInt(panel.getAttribute('data-wizard-step'), 10);
    const active = ps === step;
    panel.classList.toggle('is-active', active);
    panel.setAttribute('aria-hidden', active ? 'false' : 'true');
  });
  root.querySelectorAll('.contact-wizard__prog-item').forEach((item, i) => {
    const idx = i + 1;
    item.classList.toggle('is-active', idx === step);
    item.classList.toggle('is-done', idx < step);
  });
  const activePanel = root.querySelector('.contact-wizard-panel.is-active');
  const focusEl = activePanel && activePanel.querySelector('input:not([readonly]),select,textarea');
  if (focusEl) setTimeout(() => focusEl.focus(), 100);
  if (step === 6) {
    /* Nouveau passage à l’étape paiement : on réinitialise Turnstile pour annuler toute validation faite pendant que le panneau était masqué (sinon callback ignoré + widget déjà « vert »). */
    if (prev !== 6) {
      setTimeout(() => {
        try { window.turnstile?.reset(); } catch (e) { /* ignore */ }
      }, 150);
    }
    setTimeout(() => window.dispatchEvent(new Event('resize')), 250);
  }
}

function wizardValidateStep(step) {
  const markErr = (id) => { const el = document.getElementById(id); if (el) el.classList.add('err'); };
  const clearErr = (ids) => { ids.forEach((id) => document.getElementById(id)?.classList.remove('err')); };
  if (step === 1) {
    clearErr(['f-nom', 'f-prenom']);
    if (!document.getElementById('f-nom')?.value.trim()) { markErr('f-nom'); return false; }
    if (!document.getElementById('f-prenom')?.value.trim()) { markErr('f-prenom'); return false; }
    return true;
  }
  if (step === 2) {
    clearErr(['f-email', 'f-tel']);
    const em = document.getElementById('f-email')?.value.trim() || '';
    const tel = document.getElementById('f-tel')?.value.trim() || '';
    if (!em) { markErr('f-email'); return false; }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em)) {
      markErr('f-email');
      caShowModal({ variant: 'warning', title: 'Email invalide', message: 'Indiquez une adresse e-mail valide.' });
      return false;
    }
    if (!tel) { markErr('f-tel'); return false; }
    return true;
  }
  if (step === 3) {
    clearErr(['f-entreprise']);
    if (!document.getElementById('f-entreprise')?.value.trim()) { markErr('f-entreprise'); return false; }
    return true;
  }
  if (step === 4) {
    const input = document.getElementById('f-url');
    clearErr(['f-url']);
    extractUrl(input);
    const val = input.value.trim();
    if (!val) { markErr('f-url'); return false; }
    try {
      const u = new URL(val);
      if (u.protocol !== 'https:') throw new Error();
    } catch {
      markErr('f-url');
      validateUrl(input);
      caShowModal({
        variant: 'warning',
        title: 'Lien Google invalide',
        message: 'Collez une adresse complète commençant par https://.',
      });
      return false;
    }
    validateUrl(input);
    return true;
  }
  if (step === 5) {
    clearErr(['f-justif']);
    if (!document.getElementById('f-justif')?.value.trim()) { markErr('f-justif'); return false; }
    return true;
  }
  return true;
}

function wizardGoNext() {
  const root = wizardRoot();
  if (!root) return;
  const cur = parseInt(root.dataset.currentStep || '1', 10);
  if (!wizardValidateStep(cur)) return;
  wizardSetStep(cur + 1);
}

function wizardGoPrev() {
  const root = wizardRoot();
  if (!root) return;
  const cur = parseInt(root.dataset.currentStep || '1', 10);
  wizardSetStep(cur - 1);
}

document.addEventListener('click', (e) => {
  const t = e.target;
  if (!(t instanceof Element)) return;
  if (t.closest('.js-wizard-next')) {
    e.preventDefault();
    wizardGoNext();
  }
  if (t.closest('.js-wizard-prev')) {
    e.preventDefault();
    wizardGoPrev();
  }
});

async function cleanavisStartEmbeddedCheckout(turnstileToken) {
  const required = [{id:'f-nom',label:'Nom'},{id:'f-prenom',label:'Prénom'},{id:'f-email',label:'Email'},{id:'f-tel',label:'Téléphone'},{id:'f-entreprise',label:"Nom de l'entreprise"},{id:'f-url',label:"Lien de l'avis Google"},{id:'f-justif',label:'Justification'}];
  const missing = [];
  required.forEach(({id,label})=>{ const el=document.getElementById(id); if(!el.value.trim()){el.classList.add('err');missing.push(label);}else el.classList.remove('err'); });
  if (missing.length) {
    caShowModal({
      variant: 'warning',
      title: 'Champs obligatoires',
      message: 'Complétez les informations ci-dessous pour poursuivre.',
      listItems: missing,
      hint: 'Les champs concernés sont surlignés en rouge.'
    });
    const miss = required.find(r=>!document.getElementById(r.id).value.trim());
    if (miss && WIZARD_FIELD_STEP[miss.id]) {
      wizardSetStep(WIZARD_FIELD_STEP[miss.id]);
    }
    if (miss) document.getElementById(miss.id).focus();
    try { window.turnstile?.reset(); } catch (e) { /* ignore */ }
    return;
  }
  const urlInput = document.getElementById('f-url');
  extractUrl(urlInput);
  const urlVal = urlInput.value.trim();
  try { const u=new URL(urlVal); if(u.protocol!=='https:') throw new Error(); } catch {
    caShowModal({
      variant: 'warning',
      title: 'Lien Google invalide',
      message: 'Collez une adresse complète commençant par https:// (lien vers votre fiche ou avis Google).',
    });
    wizardSetStep(4);
    try { window.turnstile?.reset(); } catch (e) { /* ignore */ }
    return;
  }
  if (!turnstileToken) {
    caShowModal({
      variant: 'info',
      title: 'Vérification anti-robot',
      message: 'Jeton Cloudflare manquant. Rechargez la page ou complétez à nouveau la vérification.',
    });
    return;
  }
  const pk = window.CLEANAVIS_STRIPE_PK || '';
  if (!pk || pk.indexOf('pk_') !== 0) {
    caShowModal({
      variant: 'error',
      title: 'Paiement indisponible',
      message: 'Clé publique Stripe absente. Ajoutez STRIPE_PUBLISHABLE_KEY (pk_…) dans le fichier .env côté serveur, puis videz le cache Symfony.',
    });
    try { window.turnstile?.reset(); } catch (e) { /* ignore */ }
    return;
  }
  if (typeof window.Stripe !== 'function') {
    caShowModal({
      variant: 'error',
      title: 'Stripe non chargé',
      message: 'Le script Stripe (js.stripe.com) est introuvable. Vérifiez votre connexion ou désactivez un bloqueur de publicités.',
    });
    try { window.turnstile?.reset(); } catch (e) { /* ignore */ }
    return;
  }
  const apiUrl = window.CLEANAVIS_API_ORDER_INIT || '/api/order/init';
  setPaymentFetchLoading(true);
  const packVal = document.getElementById('f-package').value || 'standard';
  const payload = {
    firstName: document.getElementById('f-prenom').value.trim(),
    lastName: document.getElementById('f-nom').value.trim(),
    email: document.getElementById('f-email').value.trim(),
    phone: document.getElementById('f-tel').value.trim(),
    company: document.getElementById('f-entreprise').value.trim(),
    address: document.getElementById('f-adresse').value.trim(),
    reviewUrl: document.getElementById('f-url').value.trim(),
    package: packVal,
    justification: document.getElementById('f-justif').value.trim(),
    turnstileToken,
  };
  try {
    const res = await fetch(apiUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify(payload)
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.error || data.message || `Erreur ${res.status}`);
    const clientSecret = data.clientSecret;
    if (!clientSecret || typeof clientSecret !== 'string') {
      throw new Error('Réponse serveur invalide (clientSecret manquant).');
    }
    const stripe = window.Stripe(pk);
    const checkout = await stripe.initEmbeddedCheckout({ clientSecret });
    stripeEmbeddedCheckoutInstance = checkout;
    const pre = document.getElementById('contact-payment-preembed');
    const wrap = document.getElementById('stripe-checkout-embed-wrap');
    if (pre) pre.hidden = true;
    if (wrap) wrap.hidden = false;
    checkout.mount('#stripe-checkout-embed');
  } catch(err) {
    const msg = err && err.message ? String(err.message) : 'Une erreur est survenue.';
    caShowModal({
      variant: 'error',
      title: 'Impossible de lancer le paiement',
      message: msg,
      hint: 'Réessayez dans un instant ou écrivez-nous à contact@cleanavis.fr.',
    });
    try { window.turnstile?.reset(); } catch (e) { /* ignore */ }
  } finally {
    setPaymentFetchLoading(false);
  }
}

function toggleFaq(btn) {
  const item=btn.parentElement, isOpen=item.classList.contains('open');
  document.querySelectorAll('.faq-item.open').forEach(i=>{ i.classList.remove('open'); i.querySelector('.faq-q').setAttribute('aria-expanded','false'); });
  if (!isOpen) { item.classList.add('open'); btn.setAttribute('aria-expanded','true'); }
}

function toggleMenu() {
  const hb=document.getElementById('hamburger'), mm=document.getElementById('mobile-menu');
  const scrim=document.getElementById('mobile-menu-scrim');
  const open=mm.classList.toggle('open');
  hb.classList.toggle('open',open); hb.setAttribute('aria-expanded',open);
  document.body.classList.toggle('menu-open', open);
  if (scrim) scrim.setAttribute('aria-hidden', open ? 'false' : 'true');
}
function closeMenu() {
  document.getElementById('hamburger').classList.remove('open');
  document.getElementById('mobile-menu').classList.remove('open');
  document.getElementById('hamburger').setAttribute('aria-expanded','false');
  document.body.classList.remove('menu-open');
  const scrim=document.getElementById('mobile-menu-scrim');
  if (scrim) scrim.setAttribute('aria-hidden','true');
}
document.addEventListener('click',e=>{ if(!e.target.closest('nav')&&!e.target.closest('.mobile-menu')) closeMenu(); });
document.addEventListener('keydown',e=>{ if(e.key==='Escape') closeMenu(); });

document.querySelectorAll('a[href^="#"]').forEach(a=>{
  a.addEventListener('click',e=>{
    const id=a.getAttribute('href'), target=document.querySelector(id);
    if(target){
      e.preventDefault();
      window.scrollTo({top:target.getBoundingClientRect().top+window.scrollY-72,behavior:'smooth'});
    }
  });
});

const observer=new IntersectionObserver(entries=>{ entries.forEach(e=>{ if(e.isIntersecting){e.target.classList.add('visible');observer.unobserve(e.target);} }); },{threshold:0.05,rootMargin:'0px 0px -30px 0px'});
document.querySelectorAll('.reveal').forEach((el,i)=>{ el.dataset.revealIdx=i%4; observer.observe(el); });

function toggleTt(btn) {
  const box=btn.nextElementSibling, isOpen=box.classList.contains('open');
  document.querySelectorAll('.tt-box.open').forEach(b=>{ b.classList.remove('open'); b.setAttribute('aria-hidden','true'); b.previousElementSibling.setAttribute('aria-expanded','false'); });
  if (!isOpen) { box.classList.add('open'); box.setAttribute('aria-hidden','false'); btn.setAttribute('aria-expanded','true'); }
}
document.addEventListener('click',e=>{ if(!e.target.closest('.tt-wrap')) document.querySelectorAll('.tt-box.open').forEach(b=>{ b.classList.remove('open'); b.setAttribute('aria-hidden','true'); b.previousElementSibling.setAttribute('aria-expanded','false'); }); });

document.head.insertAdjacentHTML('beforeend',`<style>.star-btn{flex:1;padding:10px 6px;background:var(--bg);border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-weight:600;color:var(--text3);cursor:pointer;transition:all .15s;font-family:'Inter',sans-serif;text-align:center;-webkit-appearance:none;appearance:none;box-sizing:border-box;}.star-btn:hover{border-color:var(--g-yellow);background:var(--g-yel-lt);color:#F9AB00;}.star-btn.active{background:var(--g-yel-lt);border-color:var(--g-yellow);color:#F9AB00;}</style>`);

/** Mobile : accordéons fermés par défaut ; desktop (≥901px) : toujours ouverts. */
(function initCaMobileCollapses() {
  const mq = window.matchMedia('(min-width:901px)');
  const nodes = () => document.querySelectorAll('details[data-ca-mcoll]');
  const apply = function () {
    const open = mq.matches;
    nodes().forEach(function (d) {
      d.open = open;
    });
  };
  if (typeof mq.addEventListener === 'function') {
    mq.addEventListener('change', apply);
  } else {
    mq.addListener(apply);
  }
  apply();
})();
