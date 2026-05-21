/**
 * UNISUAM — Scroll Engine
 * Handles: progress bar, parallax, sticky features,
 *          counters, nav state, hero reveal
 */

class ScrollEngine {
  constructor() {
    this.raf = null;
    this.ticking = false;
    this.scrollY = window.scrollY;
    this._initProgressBar();
    this._initHero();
    this._initParallax();
    this._initCounters();
    this._initReveal();
    this._initStickyFeature();
    this._bindScroll();
  }

  // ── Progress Bar ─────────────────────────────
  _initProgressBar() {
    this.bar = document.getElementById('scroll-bar');
  }

  _updateProgressBar() {
    if (!this.bar) return;
    const max = document.documentElement.scrollHeight - window.innerHeight;
    const pct = max > 0 ? this.scrollY / max : 0;
    this.bar.style.transform = `scaleX(${pct})`;
  }

  // ── Nav ──────────────────────────────────────
  _updateNav() {
    const nav = document.getElementById('nav');
    if (!nav) return;
    nav.classList.toggle('scrolled', this.scrollY > 60);
  }

  // ── Hero split text reveal ────────────────────
  _initHero() {
    const h1 = document.querySelector('.hero-h1[data-split]');
    if (!h1) return;

    // Split into word spans
    const raw = h1.innerHTML;
    // preserve <em> tags
    const parts = raw.split(/(<em>.*?<\/em>|\s+)/g).filter(p => p && !/^\s+$/.test(p));
    h1.innerHTML = parts.map(part => {
      if (part.startsWith('<em>')) {
        return `<span class="word"><em><span>${part.replace(/<\/?em>/g, '')}</span></em></span>`;
      }
      return `<span class="word"><span>${part}</span></span>`;
    }).join(' ');

    // Stagger reveal on load
    requestAnimationFrame(() => {
      setTimeout(() => {
        h1.classList.add('revealed');
        const words = h1.querySelectorAll('.word > span');
        words.forEach((w, i) => {
          w.style.transitionDelay = `${0.3 + i * 0.08}s`;
        });
      }, 100);
    });
  }

  // ── Parallax ─────────────────────────────────
  _initParallax() {
    this.parallaxEls = document.querySelectorAll('[data-parallax]');
  }

  _updateParallax() {
    if (!this.parallaxEls.length) return;
    this.parallaxEls.forEach(el => {
      const speed = parseFloat(el.dataset.parallax) || 0.3;
      const rect = el.getBoundingClientRect();
      const center = rect.top + rect.height / 2 - window.innerHeight / 2;
      el.style.transform = `translateY(${center * speed}px)`;
    });
  }

  // ── Counters ─────────────────────────────────
  _initCounters() {
    this.counters = document.querySelectorAll('[data-count]');
  }

  _updateCounters() {
    if (!this.counters.length) return;
    this.counters.forEach(el => {
      if (el._counted) return;
      const rect = el.getBoundingClientRect();
      if (rect.top > window.innerHeight * 0.88) return;

      el._counted = true;
      const end = parseFloat(el.dataset.count);
      const suffix = el.dataset.suffix || '';
      const prefix = el.dataset.prefix || '';
      const decimals = el.dataset.decimals ? parseInt(el.dataset.decimals) : 0;
      const duration = 2400;
      const startTime = performance.now();

      const easeOutCubic = t => 1 - Math.pow(1 - t, 3);

      function tick(now) {
        const progress = Math.min((now - startTime) / duration, 1);
        const val = easeOutCubic(progress) * end;
        el.textContent = prefix + val.toFixed(decimals) + suffix;
        if (progress < 1) requestAnimationFrame(tick);
      }
      requestAnimationFrame(tick);
    });
  }

  // ── Intersection Reveal ───────────────────────
  _initReveal() {
    const io = new IntersectionObserver(entries => {
      entries.forEach(e => {
        if (e.isIntersecting) {
          e.target.classList.add('on');
          io.unobserve(e.target);
        }
      });
    }, { threshold: 0.1 });

    document.querySelectorAll('.rv').forEach(el => io.observe(el));
  }

  // ── Sticky Feature Showcase ───────────────────
  _initStickyFeature() {
    this.fsTrack = document.querySelector('.fs-track');
    this.fsItems = document.querySelectorAll('.fs-item');
    this.fsVisuals = document.querySelectorAll('.fs-visual');
    this.fsDots = document.querySelectorAll('.fs-dot');
    this._currentStep = -1;

    // Dot click
    this.fsDots.forEach((dot, i) => {
      dot.addEventListener('click', () => this._goToStep(i));
    });

    // Mobile: touch handling
    if (window.innerWidth < 768) {
      this._initMobileFeatureSwipe();
    }
  }

  _initMobileFeatureSwipe() {
    const sticky = document.querySelector('.fs-sticky');
    if (!sticky) return;
    let startX = 0;
    sticky.addEventListener('touchstart', e => { startX = e.touches[0].clientX; }, { passive: true });
    sticky.addEventListener('touchend', e => {
      const dx = e.changedTouches[0].clientX - startX;
      if (Math.abs(dx) > 50) {
        const n = this.fsItems.length;
        const next = ((this._currentStep === -1 ? 0 : this._currentStep) + (dx < 0 ? 1 : -1) + n) % n;
        this._goToStep(next);
      }
    }, { passive: true });

    // Init first step on mobile
    this._goToStep(0);
  }

  _goToStep(step) {
    if (step === this._currentStep) return;
    this._currentStep = step;
    this.fsItems.forEach((item, i) => item.classList.toggle('active', i === step));
    this.fsVisuals.forEach((v, i) => v.classList.toggle('active', i === step));
    this.fsDots.forEach((d, i) => d.classList.toggle('on', i === step));
  }

  _updateStickyFeature() {
    if (!this.fsTrack || window.innerWidth < 768) return;
    const rect = this.fsTrack.getBoundingClientRect();
    const trackH = this.fsTrack.offsetHeight - window.innerHeight;
    if (trackH <= 0) return;

    const progress = Math.max(0, Math.min(0.9999, -rect.top / trackH));
    const n = this.fsItems.length;
    const step = Math.min(Math.floor(progress * n), n - 1);

    if (step !== this._currentStep) {
      this._goToStep(step);
    }
  }

  // ── Bind Scroll ───────────────────────────────
  _bindScroll() {
    window.addEventListener('scroll', () => {
      this.scrollY = window.scrollY;
      if (!this.ticking) {
        requestAnimationFrame(() => {
          this._tick();
          this.ticking = false;
        });
        this.ticking = true;
      }
    }, { passive: true });

    // Initial tick
    this._tick();
  }

  _tick() {
    this._updateProgressBar();
    this._updateNav();
    this._updateParallax();
    this._updateCounters();
    this._updateStickyFeature();
  }
}

// ── Mouse tilt effect on t-card ──────────────────
function initCardTilt() {
  document.querySelectorAll('.t-card').forEach(card => {
    card.addEventListener('mousemove', e => {
      const rect = card.getBoundingClientRect();
      const cx = (e.clientX - rect.left) / rect.width - 0.5;
      const cy = (e.clientY - rect.top) / rect.height - 0.5;
      card.style.transform = `translateY(-4px) rotateX(${-cy * 8}deg) rotateY(${cx * 8}deg)`;
      card.style.transition = 'none';
    });
    card.addEventListener('mouseleave', () => {
      card.style.transform = '';
      card.style.transition = '';
    });
  });
}

// ── Smooth section background color transitions ──
function initSectionColorShift() {
  const sections = [
    { el: document.querySelector('.hero'), bg: 'var(--bg)' },
    { el: document.querySelector('.metrics'), bg: 'var(--t1)' },
    { el: document.querySelector('.features-wrap'), bg: 'var(--bg)' },
    { el: document.querySelector('.critique'), bg: 'var(--bg2)' },
    { el: document.querySelector('.courses'), bg: 'var(--bg)' },
    { el: document.querySelector('.testi'), bg: 'var(--bg)' },
    { el: document.querySelector('.blog-preview'), bg: 'var(--bg2)' },
  ].filter(s => s.el);

  const io = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        // subtle body bg update handled via CSS, just add class hooks
        e.target.dataset.visible = '1';
      } else {
        delete e.target.dataset.visible;
      }
    });
  }, { threshold: 0.4 });

  sections.forEach(s => io.observe(s.el));
}

// ── Horizontal course scroll indicator ───────────
function initCourseScrollHint() {
  const wrap = document.querySelector('.c-scroll-wrap');
  if (!wrap) return;
  const hint = document.querySelector('.scroll-hint-arrow');
  if (!hint) return;
  wrap.addEventListener('scroll', () => {
    const atEnd = wrap.scrollLeft + wrap.clientWidth >= wrap.scrollWidth - 10;
    hint.style.opacity = atEnd ? '0' : '1';
  }, { passive: true });
}

// Boot
document.addEventListener('DOMContentLoaded', () => {
  window._scrollEngine = new ScrollEngine();
  initCardTilt();
  initSectionColorShift();
  initCourseScrollHint();
});
