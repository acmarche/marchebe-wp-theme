document.addEventListener('alpine:init', () => {
    Alpine.data('categoryShow', (config) => ({
        currentSite: config.currentSite,
        currentCategory: config.currentCategory,
        categorySelected: config.categorySelected,
        loading: false,
        categoryName: config.categoryName,
        allPosts: config.posts,
        filteredPosts: config.posts,

        handleCategorySelected(category) {
            this.categorySelected = category.cat_ID;
            this.categoryName = category.name;
        },

        async selectCategory(categoryId, categoryName) {
            this.categorySelected = categoryId;
            this.categoryName = categoryName;
            await this.fetchData();
            await this.$nextTick();
            const element = document.getElementById('posts');
            if (element) {
                element.scrollIntoView({behavior: 'smooth', block: 'start'});
            }
        },

        isSelected(categoryId) {
            return this.categorySelected === categoryId;
        },

        async fetchData() {
            this.loading = true;

            const formData = new FormData();
            formData.append('action', 'set_category_action');
            formData.append('nonce', wpData.categoryNonce);
            formData.append('categorySelected', this.categorySelected);
            formData.append('currentSite', this.currentSite);

            try {
                const response = await fetch(wpData.ajaxUrl, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success && result.data.posts) {
                    this.filteredPosts = result.data.posts;
                } else {
                    console.error('Error fetching posts:', result.data?.message || 'Unknown error');
                    this.filteredPosts = [];
                }
            } catch (error) {
                console.error('Error:', error);
                this.filteredPosts = [];
            } finally {
                this.loading = false;
            }
        }
    }));
});
