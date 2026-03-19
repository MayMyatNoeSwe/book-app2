// Global animation system
window.BookLibraryAnimations = (function() {
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };

    let observer;

    function createObserver() {
        return new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target); // Only animate once
                }
            });
        }, observerOptions);
    }

    function initializeElements(container = document) {
        const animatedElements = container.querySelectorAll('.animate-on-scroll:not(.is-visible)');
        animatedElements.forEach((el, index) => {
            // Add staggered delay for grid items
            if (el.classList.contains('stagger-item')) {
                // Calculate delay based on position in grid
                const allItems = document.querySelectorAll('.stagger-item');
                const itemIndex = Array.from(allItems).indexOf(el);
                el.style.transitionDelay = `${(itemIndex % 4) * 0.1}s`;
            }
            observer.observe(el);
        });
    }

    function init() {
        observer = createObserver();
        initializeElements();
    }

    // Public API
    return {
        init: init,
        observeNewElements: function(elements) {
            if (!observer) {
                observer = createObserver();
            }
            
            elements.forEach((el, index) => {
                if (el.classList.contains('stagger-item')) {
                    const allItems = document.querySelectorAll('.stagger-item');
                    const itemIndex = Array.from(allItems).indexOf(el);
                    el.style.transitionDelay = `${(itemIndex % 4) * 0.1}s`;
                }
                observer.observe(el);
            });
        }
    };
})();

document.addEventListener('DOMContentLoaded', () => {
    BookLibraryAnimations.init();
});
