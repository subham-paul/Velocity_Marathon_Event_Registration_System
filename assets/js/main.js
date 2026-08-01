/* Velocity Marathon — landing page + registration flow */
(() => {
  'use strict';

  /* ---------- Sticky navbar ---------- */
  const nav = document.getElementById('mainNav');
  const onScroll = () => nav.classList.toggle('scrolled', window.scrollY > 40);
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  /* Collapse mobile menu after clicking a link */
  document.querySelectorAll('#navMenu a').forEach(a =>
    a.addEventListener('click', () => {
      const menu = document.getElementById('navMenu');
      if (menu.classList.contains('show')) bootstrap.Collapse.getInstance(menu)?.hide();
    })
  );

  /* ---------- Countdown ---------- */
  const cd = document.getElementById('countdown');
  if (cd) {
    const target = new Date(cd.dataset.target).getTime();
    const pad = n => String(n).padStart(2, '0');
    const tick = () => {
      const diff = Math.max(0, target - Date.now());
      const d = Math.floor(diff / 864e5);
      const h = Math.floor(diff % 864e5 / 36e5);
      const m = Math.floor(diff % 36e5 / 6e4);
      const s = Math.floor(diff % 6e4 / 1e3);
      document.getElementById('cd-days').textContent = pad(d);
      document.getElementById('cd-hours').textContent = pad(h);
      document.getElementById('cd-mins').textContent = pad(m);
      document.getElementById('cd-secs').textContent = pad(s);
    };
    tick();
    setInterval(tick, 1000);
  }

  /* ---------- Category pre-select buttons + fee hint ---------- */
  const categorySelect = document.getElementById('category');
  const feeHint = document.getElementById('feeHint');
  const updateFeeHint = () => {
    const opt = categorySelect?.selectedOptions[0];
    if (feeHint && opt && opt.dataset.fee) {
      feeHint.innerHTML = `Registration fee: <strong class="text-accent">₹${Number(opt.dataset.fee).toLocaleString('en-IN')}</strong> — payable online after email verification.`;
    } else if (feeHint) {
      feeHint.textContent = 'Registration fee is payable online after email verification.';
    }
  };
  categorySelect?.addEventListener('change', updateFeeHint);
  document.querySelectorAll('[data-category]').forEach(btn =>
    btn.addEventListener('click', () => {
      categorySelect.value = btn.dataset.category;
      updateFeeHint();
    })
  );

  /* ---------- Registration flow ---------- */
  const form = document.getElementById('regForm');
  if (!form) return;

  const csrf = form.querySelector('[name="csrf_token"]').value;
  const submitBtn = document.getElementById('submitBtn');
  const formAlert = document.getElementById('formAlert');
  const otpModalEl = document.getElementById('otpModal');
  const otpModal = new bootstrap.Modal(otpModalEl);
  const successModal = new bootstrap.Modal(document.getElementById('successModal'));
  const otpAlert = document.getElementById('otpAlert');
  const verifyBtn = document.getElementById('verifyBtn');
  const resendBtn = document.getElementById('resendBtn');
  const digits = [...document.querySelectorAll('.otp-digit')];

  let verifyToken = null;
  let resendInterval = null;

  const setLoading = (btn, loading) => {
    btn.disabled = loading;
    btn.querySelector('.btn-text').classList.toggle('d-none', loading);
    btn.querySelector('.spinner-border').classList.toggle('d-none', !loading);
  };

  const showAlert = (el, msg) => {
    el.textContent = msg;
    el.classList.remove('d-none');
  };
  const hideAlert = el => el.classList.add('d-none');

  const clearFieldErrors = () => {
    form.querySelectorAll('.is-invalid').forEach(i => i.classList.remove('is-invalid'));
  };

  const applyFieldErrors = errors => {
    Object.entries(errors).forEach(([name, msg]) => {
      const input = form.querySelector(`[name="${name}"]`);
      if (!input) return;
      input.classList.add('is-invalid');
      const fb = input.closest('div').querySelector('.invalid-feedback');
      if (fb) fb.textContent = msg;
    });
    const firstBad = form.querySelector('.is-invalid');
    firstBad?.scrollIntoView({ behavior: 'smooth', block: 'center' });
  };

  const post = async (url, payload) => {
    const body = new FormData();
    Object.entries(payload).forEach(([k, v]) => body.append(k, v));
    body.append('csrf_token', csrf);
    const res = await fetch(url, { method: 'POST', body });
    return res.json();
  };

  /* --- resend cooldown --- */
  const startResendCooldown = (secs = 30) => {
    clearInterval(resendInterval);
    let left = secs;
    resendBtn.disabled = true;
    const timerSpan = document.getElementById('resendTimer');
    resendBtn.innerHTML = `Resend OTP (<span id="resendTimer">${left}</span>s)`;
    resendInterval = setInterval(() => {
      left -= 1;
      const span = document.getElementById('resendTimer');
      if (span) span.textContent = left;
      if (left <= 0) {
        clearInterval(resendInterval);
        resendBtn.disabled = false;
        resendBtn.textContent = 'Resend OTP';
      }
    }, 1000);
  };

  /* --- step 1: submit form, request OTP --- */
  form.addEventListener('submit', async e => {
    e.preventDefault();
    hideAlert(formAlert);
    clearFieldErrors();

    if (!form.checkValidity()) {
      form.classList.add('was-validated');
      return;
    }

    setLoading(submitBtn, true);
    try {
      const payload = Object.fromEntries(new FormData(form).entries());
      const data = await post('api/register.php', payload);

      if (data.success) {
        verifyToken = data.token;
        digits.forEach(d => (d.value = ''));
        hideAlert(otpAlert);
        document.getElementById('otpSentTo').innerHTML = data.message;
        otpModal.show();
        startResendCooldown();
        setTimeout(() => digits[0].focus(), 400);
      } else if (data.errors) {
        applyFieldErrors(data.errors);
        showAlert(formAlert, data.message || 'Please fix the highlighted fields.');
      } else {
        showAlert(formAlert, data.message || 'Something went wrong. Please try again.');
      }
    } catch {
      showAlert(formAlert, 'Network error — please check your connection and try again.');
    } finally {
      setLoading(submitBtn, false);
    }
  });

  /* --- OTP input UX: auto-advance, backspace, paste --- */
  digits.forEach((input, idx) => {
    input.addEventListener('input', () => {
      input.value = input.value.replace(/\D/g, '').slice(0, 1);
      if (input.value && idx < digits.length - 1) digits[idx + 1].focus();
    });
    input.addEventListener('keydown', e => {
      if (e.key === 'Backspace' && !input.value && idx > 0) digits[idx - 1].focus();
      if (e.key === 'Enter') verifyBtn.click();
    });
    input.addEventListener('paste', e => {
      e.preventDefault();
      const text = (e.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 6);
      text.split('').forEach((ch, i) => { if (digits[i]) digits[i].value = ch; });
      digits[Math.min(text.length, 5)].focus();
    });
  });

  /* --- step 2: verify OTP → payment step --- */
  const payModal = new bootstrap.Modal(document.getElementById('payModal'));
  const payBtn = document.getElementById('payBtn');
  const payAlert = document.getElementById('payAlert');
  let paymentInfo = null; // order details returned by verify_otp

  verifyBtn.addEventListener('click', async () => {
    const otp = digits.map(d => d.value).join('');
    hideAlert(otpAlert);
    if (otp.length !== 6) {
      showAlert(otpAlert, 'Enter all 6 digits of the OTP.');
      return;
    }
    setLoading(verifyBtn, true);
    try {
      const data = await post('api/verify_otp.php', { token: verifyToken, otp });
      if (data.success && data.payment_required) {
        paymentInfo = data;
        otpModal.hide();
        document.getElementById('payCategory').textContent = data.category.toUpperCase();
        document.getElementById('payAmount').textContent = data.amount_display;
        hideAlert(payAlert);
        payModal.show();
      } else {
        showAlert(otpAlert, data.message || 'Verification failed.');
      }
    } catch {
      showAlert(otpAlert, 'Network error — please try again.');
    } finally {
      setLoading(verifyBtn, false);
    }
  });

  /* --- step 3: pay via Razorpay, then finalize --- */
  const finalizeRegistration = async (rzpResponse) => {
    setLoading(payBtn, true);
    hideAlert(payAlert);
    try {
      const data = await post('api/payment_verify.php', {
        token: verifyToken,
        razorpay_order_id: rzpResponse.razorpay_order_id || paymentInfo.order_id,
        razorpay_payment_id: rzpResponse.razorpay_payment_id || '',
        razorpay_signature: rzpResponse.razorpay_signature || ''
      });
      if (data.success) {
        payModal.hide();
        document.getElementById('successName').textContent = data.name;
        document.getElementById('successRegId').textContent = data.reg_id;
        document.getElementById('successAmount').textContent = data.amount_paid;
        document.getElementById('successPayId').textContent = 'Payment ID: ' + data.payment_id;
        document.getElementById('successQr').src = data.qr_data || data.qr_url;
        document.getElementById('successEmailNote').textContent = data.email_sent
          ? 'A confirmation email with your QR entry pass and payment receipt has been sent to your inbox.'
          : 'Saved! But the confirmation email could not be sent — screenshot this QR code, or contact support.';
        successModal.show();
        form.reset();
        form.classList.remove('was-validated');
        updateFeeHint();
        paymentInfo = null;
      } else {
        showAlert(payAlert, data.message || 'Payment verification failed.');
      }
    } catch {
      showAlert(payAlert, 'Network error while confirming your payment — please try again. Do not re-pay; contact support if money was deducted.');
    } finally {
      setLoading(payBtn, false);
    }
  };

  payBtn.addEventListener('click', () => {
    if (!paymentInfo) return;
    hideAlert(payAlert);

    if (paymentInfo.dev_mode) {
      // No Razorpay keys configured — simulate the gateway locally.
      finalizeRegistration({});
      return;
    }

    const rzp = new Razorpay({
      key: paymentInfo.key_id,
      amount: paymentInfo.amount,
      currency: paymentInfo.currency,
      name: paymentInfo.event_name,
      description: paymentInfo.category + ' — Registration Fee',
      order_id: paymentInfo.order_id,
      prefill: paymentInfo.prefill,
      theme: { color: '#f97316' },
      modal: {
        ondismiss: () => showAlert(payAlert, 'Payment was cancelled. Click the button to try again.')
      },
      handler: finalizeRegistration
    });
    rzp.on('payment.failed', resp => {
      showAlert(payAlert, 'Payment failed: ' + (resp.error?.description || 'please try again.'));
    });
    rzp.open();
  });

  /* --- resend OTP --- */
  resendBtn.addEventListener('click', async () => {
    if (resendBtn.disabled || !verifyToken) return;
    resendBtn.disabled = true;
    hideAlert(otpAlert);
    try {
      const data = await post('api/resend_otp.php', { token: verifyToken });
      if (data.success) {
        startResendCooldown();
        otpAlert.classList.remove('alert-danger');
        otpAlert.classList.add('alert-success');
        showAlert(otpAlert, data.message);
        setTimeout(() => {
          hideAlert(otpAlert);
          otpAlert.classList.remove('alert-success');
          otpAlert.classList.add('alert-danger');
        }, 4000);
      } else {
        otpAlert.classList.add('alert-danger');
        showAlert(otpAlert, data.message || 'Could not resend the OTP.');
        resendBtn.disabled = false;
      }
    } catch {
      showAlert(otpAlert, 'Network error — please try again.');
      resendBtn.disabled = false;
    }
  });
})();
