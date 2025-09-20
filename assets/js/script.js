class FotoFixApp {
    constructor() {
        this.uploadedFiles = [];
        this.enhancedImages = [];
        this.selectedImages = new Set();
        this.init();
    }

    init() {
        this.setupEventListeners();
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
            
            const customInstructions = document.getElementById('customInstructions').value;
            formData.append('custom_instructions', customInstructions);

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
        modal.className = 'modal';
        modal.style.display = 'block';
        modal.innerHTML = `
            <div class="modal-content" style="max-width: 90%; max-height: 90%;">
                <img src="${imageUrl}" style="width: 100%; height: auto; border-radius: 8px;">
                <button class="btn btn-primary" onclick="this.parentElement.parentElement.remove()" style="margin-top: 20px;">Close</button>
            </div>
        `;
        document.body.appendChild(modal);
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
}

// Global functions
function closeErrorModal() {
    document.getElementById('errorModal').style.display = 'none';
}

// Initialize app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.app = new FotoFixApp();
});
