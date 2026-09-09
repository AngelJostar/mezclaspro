const privacyDocument = document.getElementById('privacy-document');
if (privacyDocument) {
    const index = document.querySelector('.privacy-index');
    const compactLayout = window.matchMedia('(max-width: 899px)');
    const updateIndexLayout = () => { index.open = !compactLayout.matches; };
    compactLayout.addEventListener('change', updateIndexLayout);
    updateIndexLayout();
    const links = [...document.querySelectorAll('.privacy-index nav a')];
    const sections = links.map(link => document.getElementById(link.hash.slice(1)));
    let scheduled = false;

    const updateCurrentSection = () => {
        scheduled = false;
        let current = sections[0];
        for (const section of sections) {
            if (section.getBoundingClientRect().top <= 120) current = section;
        }
        if (window.scrollY > 0 && Math.ceil(window.scrollY + window.innerHeight) >= document.documentElement.scrollHeight - 2) {
            current = sections.at(-1);
        }
        for (const link of links) {
            if (link.hash === `#${current.id}`) link.setAttribute('aria-current', 'location');
            else link.removeAttribute('aria-current');
        }
    };

    const scheduleUpdate = () => {
        if (!scheduled) {
            scheduled = true;
            requestAnimationFrame(updateCurrentSection);
        }
    };
    window.addEventListener('scroll', scheduleUpdate, { passive: true });
    window.addEventListener('resize', scheduleUpdate);
    window.addEventListener('hashchange', scheduleUpdate);
    document.fonts.ready.then(scheduleUpdate);

    for (const link of document.querySelectorAll('.privacy-page a[href^="#"]')) {
        link.addEventListener('click', () => {
            const target = document.getElementById(link.hash.slice(1));
            (target?.querySelector('h2') || target)?.focus({ preventScroll: true });
        });
    }
    updateCurrentSection();
}
