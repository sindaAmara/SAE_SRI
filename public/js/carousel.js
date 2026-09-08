/**
 * Class representing the statistics carousel.
 * Handles automatic sliding, manual navigation, and hover pauses.
 */
class Carousel {
    /**
     * @param {string} carouselSelector - CSS selector for the carousel container.
     */
    constructor(carouselSelector = '.stats-carousel') {
        this.carouselElement = document.querySelector(carouselSelector);
        this.slides = document.querySelectorAll('.stat-slide');
        this.dots = document.querySelectorAll('.dot');
        this.currentSlide = 0;
        this.autoSlideInterval = null;

        if (this.carouselElement && this.slides.length > 0) {
            this.init();
        }
    }

    /**
     * Initializes the carousel events and starts the auto-slide.
     */
    init() {
        this.startAutoSlide();

        // Pause auto-slide on hover
        this.carouselElement.addEventListener('mouseenter', () => {
            this.stopAutoSlide();
        });

        // Resume auto-slide on mouse leave
        this.carouselElement.addEventListener('mouseleave', () => {
            this.startAutoSlide();
        });
    }

    /**
     * Displays a specific slide.
     * @param {number} n - The index of the slide to display.
     */
    showSlide(n) {
        if (n >= this.slides.length) this.currentSlide = 0;
        else if (n < 0) this.currentSlide = this.slides.length - 1;
        else this.currentSlide = n;

        this.slides.forEach(slide => slide.classList.remove('active'));
        this.dots.forEach(dot => dot.classList.remove('active'));

        this.slides[this.currentSlide].classList.add('active');
        this.dots[this.currentSlide].classList.add('active');
    }

    /**
     * Changes the slide relative to the current position.
     * @param {number} direction - Direction step (e.g., 1 for next, -1 for previous).
     */
    changeSlide(direction) {
        this.showSlide(this.currentSlide + direction);
        this.restartAutoSlide();
    }

    /**
     * Jumps directly to a specific slide index.
     * @param {number} n - The target slide index.
     */
    goToSlide(n) {
        this.showSlide(n);
        this.restartAutoSlide();
    }

    /**
     * Advances to the next slide.
     */
    nextSlide() {
        this.showSlide(this.currentSlide + 1);
    }

    /**
     * Starts the automatic slide timer.
     */
    startAutoSlide() {
        this.stopAutoSlide();
        this.autoSlideInterval = setInterval(() => this.nextSlide(), 5000);
    }

    /**
     * Stops the automatic slide timer.
     */
    stopAutoSlide() {
        clearInterval(this.autoSlideInterval);
    }

    /**
     * Restarts the automatic slide timer.
     */
    restartAutoSlide() {
        this.startAutoSlide();
    }


}

window.addEventListener('beforeunload', () => {
    sessionStorage.setItem('scrollY', window.scrollY);
});

document.addEventListener('DOMContentLoaded', () => {
    const savedScroll = sessionStorage.getItem('scrollY');
    if (savedScroll !== null) {
        window.scrollTo(0, parseInt(savedScroll));
        sessionStorage.removeItem('scrollY');
    }
});
document.addEventListener('DOMContentLoaded', () => {
    // We always create the instance so window.carousel exists, even if empty.
    window.carousel = new Carousel();
});