 /* --- Hamburger menu --- */
    const hamburger = document.getElementById('hamburger');
    const mobileNav = document.getElementById('mobileNav');

    hamburger.addEventListener('click', () => {
      hamburger.classList.toggle('open');
      mobileNav.classList.toggle('open');
    });

    // Close mobile nav on link click
    mobileNav.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', () => {
        hamburger.classList.remove('open');
        mobileNav.classList.remove('open');
      });
    });

    /* --- Active nav on scroll --- */
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.nav-links a, .mobile-nav a');

    window.addEventListener('scroll', () => {
      let current = '';
      sections.forEach(section => {
        if (window.scrollY >= section.offsetTop - 120) {
          current = section.getAttribute('id');
        }
      });
      navLinks.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === '#' + current || link.getAttribute('href') === current + '.html') {
          link.classList.add('active');
        }
      });
    });

    /* --- Scroll reveal --- */
    const revealObserver = new IntersectionObserver((entries) => {
      entries.forEach((entry, i) => {
        if (entry.isIntersecting) {
          setTimeout(() => entry.target.classList.add('visible'), i * 80);
          revealObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12 });

    document.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));

    /* --- Testimonials carousel --- */
    (function () {
      const track  = document.querySelector('#testimonialCarousel .carousel-track');
      if (!track) return; // only run on pages that have the carousel
      const slides = Array.from(track.children);
      const next   = document.querySelector('#testimonialCarousel .next');
      const prev   = document.querySelector('#testimonialCarousel .prev');
      const dots   = Array.from(document.querySelectorAll('#testimonialDots .dot'));
      let current  = 0;

      function go(index) {
        if (index < 0) index = slides.length - 1;
        if (index >= slides.length) index = 0;
        current = index;
        track.style.transform = `translateX(-${index * 100}%)`;
        slides.forEach((s, i) => {
          s.classList.toggle('active', i === index);
          s.classList.toggle('prev',   i === (index - 1 + slides.length) % slides.length);
          s.classList.toggle('next',   i === (index + 1) % slides.length);
        });
        dots.forEach((d, i) => d.classList.toggle('active', i === index));
      }

      next.addEventListener('click', () => go(current + 1));
      prev.addEventListener('click', () => go(current - 1));
      dots.forEach(d => d.addEventListener('click', () => go(+d.dataset.index)));

      let timer = setInterval(() => go(current + 1), 6000);
      track.addEventListener('mouseenter', () => clearInterval(timer));
      track.addEventListener('mouseleave', () => { timer = setInterval(() => go(current + 1), 6000); });

      go(0);
    })();

    /* --- FAQ accordion --- */
    document.querySelectorAll('.faq-question').forEach(btn => {
      btn.addEventListener('click', () => {
        const item = btn.closest('.faq-item');
        const isOpen = item.classList.contains('open');

        // Close all
        document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('open'));

        // Toggle clicked
        if (!isOpen) item.classList.add('open');
      });
    });