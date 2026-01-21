document.addEventListener('alpine:init', () => {
    Alpine.data('headerNav', (menuData) => ({
        // State Management
        isMobileMenuOpen: false,
        activeMobileDrawerId: null,
        isDesktopMenuOpen: false,
        activeDesktopCategory: null,
        isSearchModalOpen: false,
        searchQuery: '',
        results: [],
        isLoading: false,
        error: '',
        // Keyboard navigation state
        focusedCategoryIndex: 0,
        focusedChildIndex: 0,
        focusContext: 'categories',
        // Menu data from Twig
        menuData: menuData,

        init() {
            window.addEventListener('keydown', (event) => {
                if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
                    event.preventDefault();
                    this.openSearchModal();
                }
                if (event.key === 'Escape' && this.isSearchModalOpen) {
                    this.closeSearchModal();
                }
            });
        },

        get activeMobileCategoryData() {
            if (!this.activeMobileDrawerId || !this.menuData) return null;
            return this.menuData.find(item => item.blogid === this.activeMobileDrawerId);
        },

        toggleMobileMenu() {
            this.isMobileMenuOpen = !this.isMobileMenuOpen;
            if (!this.isMobileMenuOpen) {
                setTimeout(() => {
                    this.activeMobileDrawerId = null;
                }, 300);
            }
        },

        toggleDesktopMenu() {
            this.isDesktopMenuOpen = !this.isDesktopMenuOpen;
            if (this.isDesktopMenuOpen) {
                this.resetDesktopMenuFocus();
                if (this.menuData.length > 0) {
                    this.selectDesktopCategory(this.menuData[0]);
                }
                this.$nextTick(() => {
                    this.focusCategoryButton(0);
                });
            }
        },

        openMobileDrawer(id) {
            this.activeMobileDrawerId = id;
        },

        closeMobileDrawer() {
            this.activeMobileDrawerId = null;
        },

        selectDesktopCategory(category) {
            this.activeDesktopCategory = category;
        },

        openSearchModal() {
            this.isSearchModalOpen = true;
        },

        closeSearchModal() {
            this.isSearchModalOpen = false;
            this.searchQuery = '';
            this.results = [];
            this.error = '';
        },

        closeOnClickOutside(event) {
            if (!this.isDesktopMenuOpen) return;

            const button = this.$refs.desktopMenuButton;
            const panel = this.$refs.desktopMenuPanel;

            const isClickOnButton = button && button.contains(event.target);
            const isClickInsidePanel = panel && panel.contains(event.target);

            if (!isClickOnButton && !isClickInsidePanel) {
                this.isDesktopMenuOpen = false;
            }
        },

        handleDesktopMenuKeydown(event) {
            if (!this.isDesktopMenuOpen) return;

            const key = event.key;

            if (key === 'Escape') {
                event.preventDefault();
                this.isDesktopMenuOpen = false;
                this.$refs.desktopMenuButton?.focus();
                return;
            }

            if (this.focusContext === 'categories') {
                if (key === 'ArrowDown') {
                    event.preventDefault();
                    this.navigateCategories(1);
                } else if (key === 'ArrowUp') {
                    event.preventDefault();
                    this.navigateCategories(-1);
                } else if (key === 'ArrowRight' && this.activeDesktopCategory?.items?.length > 0) {
                    event.preventDefault();
                    this.moveToChildren();
                } else if (key === 'Enter' || key === ' ') {
                    event.preventDefault();
                    this.activateFocusedCategory();
                }
            } else if (this.focusContext === 'children') {
                if (key === 'ArrowDown') {
                    event.preventDefault();
                    this.navigateChildren(1);
                } else if (key === 'ArrowUp') {
                    event.preventDefault();
                    this.navigateChildren(-1);
                } else if (key === 'ArrowLeft') {
                    event.preventDefault();
                    this.moveToCategories();
                }
            }
        },

        navigateCategories(direction) {
            const maxIndex = this.menuData.length - 1;
            this.focusedCategoryIndex = Math.max(0, Math.min(maxIndex, this.focusedCategoryIndex + direction));
            this.focusCategoryButton(this.focusedCategoryIndex);
            this.selectDesktopCategory(this.menuData[this.focusedCategoryIndex]);
        },

        navigateChildren(direction) {
            if (!this.activeDesktopCategory?.items) return;
            const maxIndex = this.activeDesktopCategory.items.length - 1;
            this.focusedChildIndex = Math.max(0, Math.min(maxIndex, this.focusedChildIndex + direction));
            this.focusChildLink(this.focusedChildIndex);
        },

        moveToChildren() {
            if (!this.activeDesktopCategory?.items?.length) return;
            this.focusContext = 'children';
            this.focusedChildIndex = 0;
            this.$nextTick(() => {
                this.focusChildLink(0);
            });
        },

        moveToCategories() {
            this.focusContext = 'categories';
            this.$nextTick(() => {
                this.focusCategoryButton(this.focusedCategoryIndex);
            });
        },

        focusCategoryButton(index) {
            const buttons = this.$refs.desktopMenuPanel?.querySelectorAll('[data-category-index]');
            if (buttons && buttons[index]) {
                buttons[index].focus();
            }
        },

        focusChildLink(index) {
            const links = this.$refs.desktopMenuPanel?.querySelectorAll('[data-child-index]');
            if (links && links[index]) {
                links[index].focus();
            }
        },

        activateFocusedCategory() {
            if (this.menuData[this.focusedCategoryIndex]) {
                this.selectDesktopCategory(this.menuData[this.focusedCategoryIndex]);
            }
        },

        resetDesktopMenuFocus() {
            this.focusedCategoryIndex = 0;
            this.focusedChildIndex = 0;
            this.focusContext = 'categories';
        }
    }));
});
