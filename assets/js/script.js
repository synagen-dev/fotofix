class FotoFixApp {
    constructor() {
        this.uploadedFiles = [];
        this.enhancedImages = [];
        this.selectedImages = new Set();
        this.enhancementOptions = {
            exterior: {},
            interior: {}
        };
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadEnhancementOptions();
    }

    setupEventListeners() {
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('fileInput');
        const uploadBtn = document.getElementById('uploadBtn');
        const processBtn = document.getElementById('processBtn');
        const resetBtn = document.getElementById('resetBtn');
        const checkoutBtn = document.getElementById('checkoutBtn');

        // Upload area events
        uploadArea.addEventListener('click', () => fileInput.click());
        uploadBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            fileInput.click();
        });

        // Drag and drop events
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            this.handleFiles(e.dataTransfer.files);
        });

        // File input change
        fileInput.addEventListener('change', (e) => {
            this.handleFiles(e.target.files);
        });

        // Process button
        processBtn.addEventListener('click', () => this.processImages());

        // Reset button
        resetBtn.addEventListener('click', () => this.resetApp());

        // Checkout button
        checkoutBtn.addEventListener('click', () => this.proceedToCheckout());

        // Photo type change
        document.querySelectorAll('input[name="photoType"]').forEach(radio => {
            radio.addEventListener('change', () => this.updateEnhancementOptions());
        });
    }

    handleFiles(files) {
        const validFiles = Array.from(files).filter(file => {
            if (!file.type.startsWith('image/')) {
                this.showError('Please select only image files.');
                return false;
            }
            if (file.size > 10 * 1024 * 1024) {
                this.showError('File size must be less than 10MB.');
                return false;
            }
            return true;
        });

        if (this.uploadedFiles.length + validFiles.length > 10) {
            this.showError('Maximum 10 images allowed.');
            return;
        }

        this.uploadedFiles = [...this.uploadedFiles, ...validFiles];
        this.displayUploadedImages();
        this.showPreviewSection();
    }

    displayUploadedImages() {
        const imagesGrid = document.getElementById('imagesGrid');
        imagesGrid.innerHTML = '';

        this.uploadedFiles.forEach((file, index) => {
            const imageItem = document.createElement('div');
            imageItem.className = 'image-item';
            
            const img = document.createElement('img');
            img.className = 'image-preview';
            img.src = URL.createObjectURL(file);
            
            const imageInfo = document.createElement('div');
            imageInfo.className = 'image-info';
            imageInfo.textContent = `${file.name} (${this.formatFileSize(file.size)})`;
            
            imageItem.appendChild(img);
            imageItem.appendChild(imageInfo);
            imagesGrid.appendChild(imageItem);
        });
    }

    showPreviewSection() {
        document.getElementById('previewSection').style.display = 'block';
    }

    async processImages() {
        if (this.uploadedFiles.length === 0) {
            this.showError('Please upload at least one image.');
            return;
        }

        this.showLoadingModal();

        try {
            const formData = new FormData();
            this.uploadedFiles.forEach(file => {
                formData.append('images[]', file);
            });
            
            // Get enhancement options
            const enhancementOptions = this.getSelectedEnhancementOptions();
            formData.append('enhancement_options', JSON.stringify(enhancementOptions));

            const response = await fetch('api/process_images.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.enhancedImages = result.enhanced_images;
                this.displayEnhancedImages();
                this.showCheckoutSection();
            } else {
                this.showError(result.message || 'Failed to process images.');
            }
        } catch (error) {
            console.error('Error processing images:', error);
            this.showError('An error occurred while processing your images.');
        } finally {
            this.hideLoadingModal();
        }
    }

    displayEnhancedImages() {
        const enhancedImagesContainer = document.getElementById('enhancedImages');
        enhancedImagesContainer.innerHTML = '';

        this.enhancedImages.forEach((imageData, index) => {
            const enhancedItem = document.createElement('div');
            enhancedItem.className = 'enhanced-item';
            
            const img = document.createElement('img');
            img.className = 'enhanced-preview';
            img.src = imageData.preview_url;
            img.addEventListener('click', () => this.showFullImage(imageData.preview_url));
            
            const buyCheckbox = document.createElement('div');
            buyCheckbox.className = 'buy-checkbox';
            buyCheckbox.innerHTML = `
                <label>
                    <input type="checkbox" data-index="${index}" onchange="app.toggleImageSelection(${index})">
                    Buy this image ($20)
                </label>
            `;
            
            const redoBtn = document.createElement('button');
            redoBtn.className = 'redo-btn';
            redoBtn.innerHTML = '<i class="fas fa-redo"></i> Redo';
            redoBtn.addEventListener('click', () => this.redoImage(index));
            
            enhancedItem.appendChild(img);
            enhancedItem.appendChild(buyCheckbox);
            enhancedItem.appendChild(redoBtn);
            enhancedImagesContainer.appendChild(enhancedItem);
        });
    }

    toggleImageSelection(index) {
        if (this.selectedImages.has(index)) {
            this.selectedImages.delete(index);
        } else {
            this.selectedImages.add(index);
        }
        this.updateCheckoutButton();
    }

    updateCheckoutButton() {
        const checkoutBtn = document.getElementById('checkoutBtn');
        const totalPrice = document.getElementById('totalPrice');
        const total = this.selectedImages.size * 20;
        
        totalPrice.textContent = total;
        checkoutBtn.disabled = this.selectedImages.size === 0;
    }

    showCheckoutSection() {
        document.getElementById('checkoutSection').style.display = 'block';
        this.updateCheckoutButton();
    }

    async proceedToCheckout() {
        if (this.selectedImages.size === 0) {
            this.showError('Please select at least one image to purchase.');
            return;
        }

        try {
            const response = await fetch('api/create_checkout.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    selected_images: Array.from(this.selectedImages),
                    enhanced_images: this.enhancedImages
                })
            });

            const result = await response.json();

            if (result.success) {
                window.location.href = result.checkout_url;
            } else {
                this.showError(result.message || 'Failed to create checkout session.');
            }
        } catch (error) {
            console.error('Error creating checkout:', error);
            this.showError('An error occurred while creating checkout session.');
        }
    }

    async redoImage(index) {
        try {
            const response = await fetch('api/redo_image.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    image_index: index,
                    enhanced_images: this.enhancedImages
                })
            });

            const result = await response.json();

            if (result.success) {
                this.enhancedImages[index] = result.enhanced_image;
                this.displayEnhancedImages();
            } else {
                this.showError(result.message || 'Failed to redo image.');
            }
        } catch (error) {
            console.error('Error redoing image:', error);
            this.showError('An error occurred while redoing the image.');
        }
    }

    showFullImage(imageUrl) {
        // Create a modal to show full-size image
        const modal = document.createElement('div');
        modal.className = 'image-modal';
        modal.style.display = 'flex';
        modal.innerHTML = `
            <div class="image-modal-backdrop"></div>
            <div class="image-modal-content">
                <button class="image-modal-close" onclick="this.closest('.image-modal').remove()">
                    <i class="fas fa-times"></i>
                </button>
                <img src="${imageUrl}" class="image-modal-image" alt="Enhanced image">
            </div>
        `;
        document.body.appendChild(modal);
        
        // Close modal when clicking on backdrop
        modal.addEventListener('click', (e) => {
            if (e.target === modal || e.target.classList.contains('image-modal-backdrop')) {
                modal.remove();
            }
        });
        
        // Close modal with Escape key
        const handleKeyPress = (e) => {
            if (e.key === 'Escape') {
                modal.remove();
                document.removeEventListener('keydown', handleKeyPress);
            }
        };
        document.addEventListener('keydown', handleKeyPress);
    }

    resetApp() {
        this.uploadedFiles = [];
        this.enhancedImages = [];
        this.selectedImages.clear();
        
        document.getElementById('fileInput').value = '';
        document.getElementById('customInstructions').value = '';
        document.getElementById('previewSection').style.display = 'none';
        document.getElementById('checkoutSection').style.display = 'none';
        document.getElementById('imagesGrid').innerHTML = '';
        document.getElementById('enhancedImages').innerHTML = '';
    }

    showLoadingModal() {
        document.getElementById('loadingModal').style.display = 'block';
    }

    hideLoadingModal() {
        document.getElementById('loadingModal').style.display = 'none';
    }

    showError(message) {
        document.getElementById('errorMessage').textContent = message;
        document.getElementById('errorModal').style.display = 'block';
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    async loadEnhancementOptions() {
        try {
            const response = await fetch('api/get_enhancement_options.php');
            const data = await response.json();
            
            if (data.success) {
                this.enhancementOptions = data.options;
                this.updateEnhancementOptions();
            }
        } catch (error) {
            console.error('Error loading enhancement options:', error);
            // Use default options if API fails
            this.enhancementOptions = {
                exterior: {
                    'landscaping': { name: 'Landscaping Improvements', description: 'Enhance grass, plants, and outdoor features' },
                    'sky_weather': { name: 'Sky & Weather Enhancement', description: 'Improve sky appearance and weather conditions' },
                    'exterior_cleaning': { name: 'Exterior Cleaning', description: 'Clean and brighten exterior surfaces' },
                    'outdoor_furniture': { name: 'Outdoor Furniture', description: 'Add or improve outdoor furniture' },
                    'lighting': { name: 'Exterior Lighting', description: 'Enhance outdoor lighting' }
                },
                interior: {
                    'furniture_modernization': { name: 'Furniture Modernization', description: 'Replace old furniture with modern pieces' },
                    'cleaning_decluttering': { name: 'Cleaning & Decluttering', description: 'Remove clutter and clean surfaces' },
                    'lighting_enhancement': { name: 'Lighting Enhancement', description: 'Improve interior lighting' },
                    'color_scheme': { name: 'Color Scheme Update', description: 'Modernize color schemes' },
                    'decorative_touches': { name: 'Decorative Touches', description: 'Add tasteful decorative elements' }
                }
            };
            this.updateEnhancementOptions();
        }
    }

    updateEnhancementOptions() {
        const photoType = document.querySelector('input[name="photoType"]:checked').value;
        const container = document.getElementById('enhancementCheckboxes');
        
        container.innerHTML = '';
        
        if (photoType === 'mixed') {
            // Show both interior and exterior options
            this.renderEnhancementCategory('Exterior Enhancements', this.enhancementOptions.exterior, container);
            this.renderEnhancementCategory('Interior Enhancements', this.enhancementOptions.interior, container);
        } else {
            // Show options for selected type
            const options = this.enhancementOptions[photoType] || {};
            this.renderEnhancementCategory(`${photoType.charAt(0).toUpperCase() + photoType.slice(1)} Enhancements`, options, container);
        }
    }

    renderEnhancementCategory(title, options, container) {
        const categoryDiv = document.createElement('div');
        categoryDiv.className = 'enhancement-category';
        
        const titleElement = document.createElement('h4');
        titleElement.textContent = title;
        categoryDiv.appendChild(titleElement);
        
        Object.entries(options).forEach(([key, option]) => {
            const optionDiv = document.createElement('div');
            optionDiv.className = 'enhancement-option';
            
            optionDiv.innerHTML = `
                <input type="checkbox" id="${key}" value="${key}" checked>
                <div class="enhancement-option-content">
                    <div class="enhancement-option-name">${option.name}</div>
                    <div class="enhancement-option-description">${option.description}</div>
                </div>
            `;
            
            categoryDiv.appendChild(optionDiv);
        });
        
        container.appendChild(categoryDiv);
    }

    getSelectedEnhancementOptions() {
        const photoType = document.querySelector('input[name="photoType"]:checked').value;
        const checkboxes = document.querySelectorAll('#enhancementCheckboxes input[type="checkbox"]:checked');
        const selectedOptions = Array.from(checkboxes).map(cb => cb.value);
        
        return {
            photoType: photoType,
            options: selectedOptions,
            customInstructions: document.getElementById('customInstructions').value
        };
    }
}

// Global functions
function closeErrorModal() {
    document.getElementById('errorModal').style.display = 'none';
}

// Initialize app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.app = new FotoFixApp();
});
