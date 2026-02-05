document.addEventListener('alpine:init', () => {
    Alpine.data('imageGallery', () => ({
        open: false,
        currentIndex: 0,
        images: [],

        get currentImage() {
            return this.images[this.currentIndex];
        },

        openLightbox(index) {
            this.currentIndex = index;
            this.open = true;
            document.body.style.overflow = 'hidden';
        },

        closeLightbox() {
            this.open = false;
            document.body.style.overflow = '';
        },

        next() {
            this.currentIndex = (this.currentIndex + 1) % this.images.length;
        },

        prev() {
            this.currentIndex = (this.currentIndex - 1 + this.images.length) % this.images.length;
        }
    }));
});
