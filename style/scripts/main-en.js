document.getElementById('logo-link')?.addEventListener('click', function (e) {
  e.preventDefault();
  window.scrollTo({ top: 0, behavior: 'smooth' });
});

function setupSmoothAnchorLinks() {
  const links = document.querySelectorAll(
    'nav a[href^="#"], .mobile-nav a[href^="#"], a.scroll-link[href^="#"]'
  );
  links.forEach(link => {
    link.addEventListener('click', function (e) {
      const targetId = this.getAttribute('href');
      if (!targetId || targetId === '#') return;
      const target = document.querySelector(targetId);
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });
}

function fillExperience() {
  const start = new Date(2010, 8, 20);
  const now = new Date();
  let years = now.getFullYear() - start.getFullYear();
  const hasNotPassedYet =
    now.getMonth() < start.getMonth() ||
    (now.getMonth() === start.getMonth() && now.getDate() < start.getDate());
  if (hasNotPassedYet) years--;

  const els = [
    document.getElementById('years-experience'),
    document.getElementById('years-experience-2')
  ];
  els.forEach(el => { if (el) el.textContent = years; });
}

function fillYear() {
  const yEl = document.getElementById('current-year');
  if (yEl) yEl.textContent = new Date().getFullYear();
}

function setupHeaderOffset() {
  const header = document.querySelector('header');
  if (!header) return;

  const setOffset = () => {
    document.documentElement.style.setProperty('--header-offset', `${header.offsetHeight}px`);
  };

  setOffset();
  window.addEventListener('resize', setOffset);
}

function setupBurger() {
  const burger = document.querySelector('.burger');
  const mobileMenu = document.getElementById('mobile-menu');
  if (!burger || !mobileMenu) return;

  const setExpanded = (isOpen) => {
    burger.setAttribute('aria-expanded', String(isOpen));
  };

  const toggle = () => {
    const isOpen = burger.classList.toggle('is-open');
    mobileMenu.classList.toggle('is-open');
    setExpanded(isOpen);
  };

  setExpanded(false);
  burger.addEventListener('click', toggle);

  mobileMenu.querySelectorAll('a[href^="#"]').forEach(link => {
    link.addEventListener('click', () => {
      burger.classList.remove('is-open');
      mobileMenu.classList.remove('is-open');
      setExpanded(false);
    });
  });
}

function setupPolicyModal() {
  const link = document.getElementById('policy-link');
  const modal = document.getElementById('policy-modal');
  if (!link || !modal) return;

  const closeBtn = modal.querySelector('.modal-close');
  const backdrop = modal.querySelector('.modal-backdrop');

  const open = () => {
    modal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
  };

  const close = () => {
    modal.classList.remove('is-open');
    document.body.style.overflow = '';
  };

  link.addEventListener('click', (e) => {
    e.preventDefault();
    open();
  });

  closeBtn.addEventListener('click', close);
  backdrop.addEventListener('click', close);
}

function setupRulesModal() {
  const link = document.getElementById('rules-link');
  const modal = document.getElementById('rules-modal');
  if (!link || !modal) return;

  const closeBtn = modal.querySelector('.modal-close');
  const backdrop = modal.querySelector('.modal-backdrop');

  const open = () => {
    modal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
  };

  const close = () => {
    modal.classList.remove('is-open');
    document.body.style.overflow = '';
  };

  link.addEventListener('click', (e) => {
    e.preventDefault();
    open();
  });

  closeBtn.addEventListener('click', close);
  backdrop.addEventListener('click', close);
}

function openSuccessModal() {
  const modal = document.getElementById('success-modal');
  if (!modal) return;

  const closeBtn = modal.querySelector('.modal-close');
  const backdrop = modal.querySelector('.modal-backdrop');

  const close = () => {
    modal.classList.remove('is-open');
    document.body.style.overflow = '';
  };

  modal.classList.add('is-open');
  document.body.style.overflow = 'hidden';

  closeBtn.addEventListener('click', close);
  backdrop.addEventListener('click', close);
}

const COOKIE_CONSENT_KEY = 'cookie_consent_v1';

function hasCookieConsent() {
  return localStorage.getItem(COOKIE_CONSENT_KEY);
}

function setCookieConsent(value) {
  localStorage.setItem(COOKIE_CONSENT_KEY, value);
}

function setupCookieBanner() {
  const banner = document.getElementById('cookie-banner');
  if (!banner) return;

  const btnAccept = document.getElementById('cookie-accept');
  const btnReject = document.getElementById('cookie-reject');

  if (!hasCookieConsent()) {
    banner.classList.add('is-visible');
    banner.setAttribute('aria-hidden', 'false');
  }

  const hide = () => {
    banner.classList.remove('is-visible');
    banner.setAttribute('aria-hidden', 'true');
  };

  btnAccept?.addEventListener('click', () => {
    setCookieConsent('accepted');
    hide();
    enableAnalytics();
  });

  btnReject?.addEventListener('click', () => {
    setCookieConsent('rejected');
    hide();
  });
}

function enableAnalytics() {
  if (document.getElementById('ga-script')) return;

  const script = document.createElement('script');
  script.async = true;
  script.src = 'https://www.googletagmanager.com/gtag/js?id=G-XXXXXXX';
  script.id = 'ga-script';
  document.head.appendChild(script);

  window.dataLayer = window.dataLayer || [];
  function gtag(){ dataLayer.push(arguments); }
  gtag('js', new Date());
  gtag('config', 'G-XXXXXXX');
}

function setupHeroCarousel() {
  const carousel = document.getElementById('hero-carousel');
  if (!carousel) return;

  const slides = Array.from(carousel.querySelectorAll('.hero-slide'));
  const dots = Array.from(carousel.querySelectorAll('.hero-dot'));
  const btnPrev = carousel.querySelector('.hero-carousel-prev');
  const btnNext = carousel.querySelector('.hero-carousel-next');
  if (!slides.length || !dots.length || !btnPrev || !btnNext) return;

  let current = 0;
  const AUTO_INTERVAL = 5000;
  let autoTimer = null;

  function showSlide(index) {
    slides.forEach((slide, i) => {
      slide.classList.toggle('is-active', i === index);
      dots[i]?.classList.toggle('is-active', i === index);
    });
  }

  function nextSlide() {
    const next = (current + 1) % slides.length;
    current = next;
    showSlide(current);
  }

  function prevSlide() {
    const prev = (current - 1 + slides.length) % slides.length;
    current = prev;
    showSlide(current);
  }

  function startAuto() {
    stopAuto();
    autoTimer = setInterval(nextSlide, AUTO_INTERVAL);
  }

  function stopAuto() {
    if (autoTimer) clearInterval(autoTimer);
  }

  btnNext.addEventListener('click', () => {
    nextSlide();
    startAuto();
  });

  btnPrev.addEventListener('click', () => {
    prevSlide();
    startAuto();
  });

  dots.forEach(dot => {
    dot.addEventListener('click', () => {
      const index = Number(dot.dataset.index || 0);
      current = index;
      showSlide(current);
      startAuto();
    });
  });

  carousel.addEventListener('mouseenter', stopAuto);
  carousel.addEventListener('mouseleave', startAuto);

  showSlide(current);
  startAuto();
}

function setupSectionReveal() {
  const sections = document.querySelectorAll('.section-animated');
  if (!sections.length) return;

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      }
    });
  }, {
    threshold: 0.12
  });

  sections.forEach(sec => observer.observe(sec));
}

function setupPhoneMask() {
  const input = document.getElementById('phone-input');
  if (!input) return;

  function cleanDigits(value) {
    return value.replace(/\D/g, '').slice(0, 14);
  }

  function formatPhone(value) {
    if (!value) return '+1 (___) ___‑____';

    const d = value.padEnd(11, '_');
    const p1 = d.slice(0, 3);
    const p2 = d.slice(3, 6);
    const p3 = d.slice(6, 10);

    return `+${p1} ${p2}‑${p3}`;
  }

  function onInput(e) {
    const digits = cleanDigits(e.target.value);
    e.target.value = formatPhone(digits);

    const len = digits.length;
    if (len < 10) {
      e.target.classList.add('input-error');
    } else {
      e.target.classList.remove('input-error');
    }
  }

  function onFocus(e) {
    if (!e.target.value) {
      e.target.value = formatPhone('');
    }
  }

  function onBlur(e) {
    const digits = cleanDigits(e.target.value);
    if (digits.length === 0) {
      e.target.value = '';
    }
  }

  input.addEventListener('input', onInput);
  input.addEventListener('focus', onFocus);
  input.addEventListener('blur', onBlur);
}

function setupMessengerChoice() {
  const checkbox = document.getElementById('contact-messenger');
  const selectBlock = document.getElementById('messenger-select');
  const hiddenInput = document.getElementById('messenger-input');
  const buttons = selectBlock ? selectBlock.querySelectorAll('.messenger-btn') : null;

  if (!checkbox || !selectBlock || !hiddenInput || !buttons?.length) return;

  checkbox.addEventListener('change', () => {
    if (!checkbox.checked) {
      selectBlock.classList.remove('is-visible');
      hiddenInput.value = '';
    } else {
      selectBlock.classList.add('is-visible');
      if (!hiddenInput.value) hiddenInput.value = 'telegram';
    }
  });

  buttons.forEach(btn => {
    btn.addEventListener('click', () => {
      buttons.forEach(b => b.classList.remove('is-active'));
      btn.classList.add('is-active');
      hiddenInput.value = btn.dataset.value || '';
      checkbox.checked = true;
      selectBlock.classList.add('is-visible');
    });
  });
}

function setFieldError(input, hint, message) {
  input.classList.add('input-error');
  if (hint) {
    hint.textContent = message;
    hint.classList.add('input-hint-error');
  }
}

function clearFieldError(input, hint) {
  input.classList.remove('input-error');
  if (hint) {
    hint.textContent = hint.dataset.default || '';
    hint.classList.remove('input-hint-error');
  }
}

function setFormStatus(form, message, type) {
  const statusEl = form.querySelector('.form-status');
  if (!statusEl) return;
  statusEl.textContent = message || '';
  statusEl.classList.toggle('is-error', type === 'error');
  statusEl.classList.toggle('is-success', type === 'success');
}

function setupBasicFormValidation() {
  const form = document.querySelector('#request form');
  if (!form) return;

  const requiredFields = [
    {
      input: form.querySelector('[name="from_city"]'),
      message: 'Please enter the origin city.'
    },
    {
      input: form.querySelector('[name="to_city"]'),
      message: 'Please enter the destination city.'
    },
    {
      input: form.querySelector('#phone-input'),
      message: 'Please enter a valid phone number.'
    }
  ];

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    setFormStatus(form, '', '');

    let hasError = false;

    requiredFields.forEach(({ input, message }) => {
      if (!input) return;
      const hint = input.parentElement?.querySelector('.input-hint');
      if (!input.value.trim()) {
        setFieldError(input, hint, message);
        hasError = true;
      } else {
        clearFieldError(input, hint);
      }
    });

    const phoneInput = form.querySelector('#phone-input');
    if (!phoneInput) return;

    const digits = phoneInput.value.replace(/\D/g, '');
    if (digits.length < 10) {
      const phoneHint = phoneInput.parentElement?.querySelector('.input-hint');
      setFieldError(phoneInput, phoneHint, 'Please enter a valid phone number.');
      setFormStatus(form, 'Please check the phone number format.', 'error');
      phoneInput.focus();
      return;
    }

    if (hasError) {
      const firstError = form.querySelector('.input-error');
      firstError?.focus();
      setFormStatus(form, 'Please fill in the required fields.', 'error');
      return;
    }

    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn ? submitBtn.textContent : '';
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = 'Sending...';
    }

    try {
      const formData = new FormData(form);

      const response = await fetch(form.action, {
        method: 'POST',
        body: formData
      });

      const data = await response.json().catch(() => null);

      if (response.ok && data && data.status === 'ok') {
        form.reset();
        openSuccessModal();
        setFormStatus(form, 'Request sent. We will contact you during business hours.', 'success');
      } else {
        setFormStatus(form, (data && data.message) || 'Failed to submit the request. Please try again.', 'error');
      }
    } catch (err) {
      setFormStatus(form, 'Network error while sending the request. Please try again.', 'error');
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
      }
    }
  });
}

function initPage() {
  setupSmoothAnchorLinks();
  fillExperience();
  fillYear();
  setupHeaderOffset();
  setupBurger();
  setupPolicyModal();
  setupRulesModal();
  setupCookieBanner();
  setupHeroCarousel();
  setupSectionReveal();
  setupPhoneMask();
  setupMessengerChoice();
  setupBasicFormValidation();

  if (hasCookieConsent() === 'accepted') {
    enableAnalytics();
  }
}

document.addEventListener('DOMContentLoaded', initPage);
