/**
 * Featured Events Banner
 * Handles rotating banner display for featured events
 */
class FeaturedEventsBanner {
    constructor(containerId, options = {}) {
        this.containerId = containerId;
        this.container = document.getElementById(containerId);
        
        if (!this.container) {
            console.error('Featured events container not found:', containerId);
            return;
        }

        this.options = {
            autoRotate: true,
            rotationInterval: 5000,
            showControls: true,
            showIndicators: true,
            fadeEffect: true,
            apiEndpoint: '/api/featured-events/rotation',
            viewEndpoint: '/api/featured-events/{id}/view',
            clickEndpoint: '/api/featured-events/{id}/click',
            limit: 5,
            ...options
        };

        this.carousel = null;
        this.featuredEvents = [];
        this.settings = {};
        this.viewedEvents = new Set();
        this.currentIndex = 0;
        this.autoRotateTimer = null;
    }

    /**
     * Initialize the banner
     */
    async init() {
        try {
            await this.loadFeaturedEvents();
            
            if (this.featuredEvents.length === 0) {
                this.showNoEventsMessage();
                return;
            }

            this.renderCarousel();
            this.initializeCarousel();
            this.attachEventListeners();
            this.startAutoRotation();

            console.log(`Featured events banner initialized with ${this.featuredEvents.length} events`);
        } catch (error) {
            console.error('Failed to initialize featured events banner:', error);
            this.showNoEventsMessage();
        }
    }

    /**
     * Load featured events from API
     */
    async loadFeaturedEvents() {
        const response = await fetch(`${this.options.apiEndpoint}?limit=${this.options.limit}`);
        
        if (!response.ok) {
            throw new Error('Failed to fetch featured events');
        }

        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Failed to load featured events');
        }

        this.featuredEvents = data.data || [];
        this.settings = { ...this.options, ...data.settings };
    }

    /**
     * Show message when no featured events are available
     */
    showNoEventsMessage() {
        const loadingElement = this.container.querySelector('.carousel-loading');
        const noEventsElement = this.container.querySelector('.no-featured-events');
        
        if (loadingElement) {
            loadingElement.style.display = 'none';
        }
        
        if (noEventsElement) {
            noEventsElement.style.display = 'block';
        }
    }

    /**
     * Render the carousel with featured events
     */
    renderCarousel() {
        const carouselInner = this.container.querySelector('.carousel-inner');
        const indicators = this.container.querySelector('.carousel-indicators');
        const slideTemplate = document.getElementById('featured-event-slide-template');
        
        if (!slideTemplate) {
            console.error('Featured event slide template not found');
            return;
        }

        carouselInner.innerHTML = '';
        indicators.innerHTML = '';

        this.featuredEvents.forEach((event, index) => {
            // Create slide
            const slideClone = slideTemplate.content.cloneNode(true);
            const slideElement = slideClone.querySelector('.carousel-item');
            
            if (index === 0) {
                slideElement.classList.add('active');
            }

            // Populate slide content
            this.populateSlide(slideClone, event);
            
            // Add click handler
            const linkButton = slideClone.querySelector('.featured-event-link');
            if (linkButton) {
                linkButton.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.handleEventClick(event);
                });
            }

            carouselInner.appendChild(slideClone);

            // Create indicator
            if (this.settings.showIndicators && this.featuredEvents.length > 1) {
                const indicator = document.createElement('button');
                indicator.type = 'button';
                indicator.setAttribute('data-bs-target', '#featuredEventsCarousel');
                indicator.setAttribute('data-bs-slide-to', index);
                indicator.className = index === 0 ? 'active' : '';
                indicator.setAttribute('aria-label', `Slide ${index + 1}`);
                
                if (index === 0) {
                    indicator.setAttribute('aria-current', 'true');
                }
                
                indicators.appendChild(indicator);
            }
        });
    }

    /**
     * Populate slide with event data
     */
    populateSlide(slideElement, event) {
        const titleElement = slideElement.querySelector('.featured-event-title');
        const descriptionElement = slideElement.querySelector('.featured-event-description');
        const imageElement = slideElement.querySelector('.featured-event-image img');
        const linkTextElement = slideElement.querySelector('.link-text');

        if (titleElement) {
            titleElement.textContent = event.title || 'Featured Event';
        }

        if (descriptionElement) {
            descriptionElement.textContent = event.description || '';
            descriptionElement.style.display = event.description ? 'block' : 'none';
        }

        if (imageElement && event.imageUrl) {
            imageElement.src = event.imageUrl;
            imageElement.alt = event.title || 'Featured Event';
        } else if (imageElement) {
            // Use placeholder image if no image provided
            imageElement.src = 'data:image/svg+xml;base64,' + btoa(`
                <svg width="1200" height="400" xmlns="http://www.w3.org/2000/svg">
                    <rect width="100%" height="100%" fill="#007bff"/>
                    <text x="50%" y="50%" font-family="Arial, sans-serif" font-size="48" fill="white" text-anchor="middle" dy=".3em">
                        ${event.title || 'Featured Event'}
                    </text>
                </svg>
            `);
            imageElement.alt = event.title || 'Featured Event';
        }

        if (linkTextElement) {
            linkTextElement.textContent = event.linkText || 'Learn More';
        }
    }

    /**
     * Initialize Bootstrap carousel
     */
    initializeCarousel() {
        const loadingElement = this.container.querySelector('.carousel-loading');
        const carouselInner = this.container.querySelector('.carousel-inner');
        const controls = this.container.querySelectorAll('.carousel-control-prev, .carousel-control-next');
        const indicators = this.container.querySelector('.carousel-indicators');

        // Hide loading, show carousel
        if (loadingElement) {
            loadingElement.style.display = 'none';
        }
        
        if (carouselInner) {
            carouselInner.style.display = 'block';
        }

        // Show/hide controls based on settings and event count
        if (this.settings.showControls && this.featuredEvents.length > 1) {
            controls.forEach(control => control.style.display = 'block');
        }

        // Show/hide indicators based on settings and event count
        if (this.settings.showIndicators && this.featuredEvents.length > 1) {
            indicators.style.display = 'block';
        }

        // Initialize Bootstrap carousel
        const carouselElement = this.container.querySelector('#featuredEventsCarousel');
        if (carouselElement && window.bootstrap) {
            this.carousel = new bootstrap.Carousel(carouselElement, {
                interval: this.settings.autoRotate ? this.settings.rotationInterval : false,
                ride: this.settings.autoRotate ? 'carousel' : false,
                pause: 'hover',
                wrap: true
            });
        }
    }

    /**
     * Attach event listeners
     */
    attachEventListeners() {
        const carouselElement = this.container.querySelector('#featuredEventsCarousel');
        
        if (carouselElement) {
            // Listen for slide events
            carouselElement.addEventListener('slide.bs.carousel', (e) => {
                this.currentIndex = e.to;
                this.recordView(this.featuredEvents[this.currentIndex]);
            });

            // Pause on hover
            carouselElement.addEventListener('mouseenter', () => {
                this.pauseAutoRotation();
            });

            carouselElement.addEventListener('mouseleave', () => {
                this.resumeAutoRotation();
            });
        }

        // Record initial view
        if (this.featuredEvents.length > 0) {
            this.recordView(this.featuredEvents[0]);
        }
    }

    /**
     * Start auto rotation
     */
    startAutoRotation() {
        if (!this.settings.autoRotate || this.featuredEvents.length <= 1) {
            return;
        }

        this.autoRotateTimer = setInterval(() => {
            if (this.carousel) {
                this.carousel.next();
            }
        }, this.settings.rotationInterval);
    }

    /**
     * Pause auto rotation
     */
    pauseAutoRotation() {
        if (this.autoRotateTimer) {
            clearInterval(this.autoRotateTimer);
            this.autoRotateTimer = null;
        }
    }

    /**
     * Resume auto rotation
     */
    resumeAutoRotation() {
        if (this.settings.autoRotate && !this.autoRotateTimer) {
            this.startAutoRotation();
        }
    }

    /**
     * Handle event click
     */
    async handleEventClick(event) {
        try {
            const response = await fetch(this.options.clickEndpoint.replace('{id}', event.id), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
            });

            const data = await response.json();

            if (data.success && data.redirect_url) {
                // Open link in same or new window based on URL type
                if (data.redirect_url.startsWith('http') && !data.redirect_url.includes(window.location.hostname)) {
                    window.open(data.redirect_url, '_blank', 'noopener,noreferrer');
                } else {
                    window.location.href = data.redirect_url;
                }
            } else if (event.linkUrl) {
                // Fallback to direct link
                if (event.linkUrl.startsWith('http') && !event.linkUrl.includes(window.location.hostname)) {
                    window.open(event.linkUrl, '_blank', 'noopener,noreferrer');
                } else {
                    window.location.href = event.linkUrl;
                }
            }
        } catch (error) {
            console.error('Failed to record click:', error);
            
            // Fallback to direct link
            if (event.linkUrl) {
                if (event.linkUrl.startsWith('http') && !event.linkUrl.includes(window.location.hostname)) {
                    window.open(event.linkUrl, '_blank', 'noopener,noreferrer');
                } else {
                    window.location.href = event.linkUrl;
                }
            }
        }
    }

    /**
     * Record view for analytics
     */
    async recordView(event) {
        if (!event || this.viewedEvents.has(event.id)) {
            return;
        }

        this.viewedEvents.add(event.id);

        try {
            await fetch(this.options.viewEndpoint.replace('{id}', event.id), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
            });
        } catch (error) {
            console.error('Failed to record view:', error);
        }
    }

    /**
     * Destroy the banner and clean up
     */
    destroy() {
        this.pauseAutoRotation();
        
        if (this.carousel) {
            this.carousel.dispose();
        }

        this.viewedEvents.clear();
        this.featuredEvents = [];
    }

    /**
     * Refresh the banner with new data
     */
    async refresh() {
        this.destroy();
        await this.init();
    }
}

// Global utility function for easy initialization
window.initFeaturedEventsBanner = function(containerId, options) {
    return new FeaturedEventsBanner(containerId, options);
};