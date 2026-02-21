document.addEventListener('DOMContentLoaded', () => {
  // Reveal on scroll
  const revealEls = Array.from(document.querySelectorAll('.reveal'));
  const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        revealObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.15 });

  revealEls.forEach((el) => revealObserver.observe(el));

  // Card tilt effect
  const cards = Array.from(document.querySelectorAll('.event-card'));
  cards.forEach((card) => {
    let rafId = null;
    const reset = () => {
      card.style.transform = 'translateY(0) scale(1.01) rotateX(0) rotateY(0)';
    };
    const onMove = (e) => {
      const rect = card.getBoundingClientRect();
      const x = e.clientX - rect.left;
      const y = e.clientY - rect.top;
      const rotateY = ((x / rect.width) - 0.5) * 6;  // tilt left/right
      const rotateX = ((y / rect.height) - 0.5) * -6; // tilt up/down
      if (rafId) cancelAnimationFrame(rafId);
      rafId = requestAnimationFrame(() => {
        card.style.transform = `translateY(-4px) scale(1.01) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
      });
    };
    card.addEventListener('mousemove', onMove);
    card.addEventListener('mouseleave', () => {
      reset();
    });
  });

  // Parallax hero
  const hero = document.querySelector('.hero');
  const heroContent = document.querySelector('.hero .hero-content');
  if (hero && heroContent) {
    window.addEventListener('scroll', () => {
      const scrolled = window.scrollY;
      const offset = Math.min(scrolled * 0.15, 40);
      heroContent.style.transform = `translateY(${offset}px)`;
      heroContent.style.willChange = 'transform';
    }, { passive: true });
  }

  // Scroll to top
  const scrollBtn = document.getElementById('scrollToTop');
  if (scrollBtn) {
    const onScroll = () => {
      if (window.scrollY > 300) {
        scrollBtn.classList.add('visible');
      } else {
        scrollBtn.classList.remove('visible');
      }
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    scrollBtn.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
    onScroll();
  }

  // Mobile navigation toggle
  const navToggle = document.querySelector('.nav-toggle');
  const navMenu = document.querySelector('.nav-menu');
  if (navToggle && navMenu) {
    navToggle.addEventListener('click', () => {
      const isOpen = navMenu.classList.toggle('open');
      navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
    // Close menu on link click (mobile)
    navMenu.querySelectorAll('a').forEach((link) => {
      link.addEventListener('click', () => {
        if (navMenu.classList.contains('open')) {
          navMenu.classList.remove('open');
          navToggle.setAttribute('aria-expanded', 'false');
        }
      });
    });
  }
});