# FotoFix - Real Estate Photo Enhancement

An AI-powered web application that helps real estate agents enhance their property photos to make them more attractive to potential buyers.

## Features

- **Image Upload**: Upload up to 10 high-resolution images (max 10MB each)
- **AI Enhancement**: Uses Google's NanoBanana AI to enhance photos
- **Custom Instructions**: Add specific requirements for photo enhancement
- **Preview System**: View low-resolution previews before purchase
- **Payment Processing**: Secure checkout with Stripe
- **Instant Download**: Download high-resolution enhanced images after payment

## Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Backend**: PHP 7.4+
- **AI Integration**: Google NanoBanana API
- **Payment**: Stripe
- **Server**: Apache/Nginx on Debian Linux

## Installation

### Prerequisites

- PHP 7.4 or higher
- Apache or Nginx web server
- Composer (for PHP dependencies)
- GD extension for image processing
- cURL extension for API calls

### Setup Instructions

1. **Clone the repository**:
   ```bash
   git clone <repository-url>
   cd fotofix
   ```

2. **Install PHP dependencies**:
   ```bash
   composer install
   ```

3. **Configure the application**:
   - Edit `api/config.php`
   - Set your Google AI API key
   - Set your Stripe API keys
   - Update file paths if needed

4. **Set up directories**:
   ```bash
   sudo mkdir -p /var/www/fotofix/images/{temp,enhanced,preview}
   sudo chown -R www-data:www-data /var/www/fotofix/images
   sudo chmod -R 755 /var/www/fotofix/images
   ```

5. **Configure web server**:
   - Point document root to the project directory
   - Ensure `.htaccess` is enabled (for Apache)
   - Set up SSL certificate for secure payments

## Configuration

### API Keys

1. **Google AI API Key**:
   - Get your API key from Google AI Studio
   - Update `GOOGLE_AI_API_KEY` in `api/config.php`

2. **Stripe Keys**:
   - Get your keys from Stripe Dashboard
   - Configure webhook endpoint: `https://yourdomain.com/api/stripe_webhook.php`
   - Set environment variables for API keys (see `STRIPE_INTEGRATION.md`)
   - Test with Stripe test cards: 4242 4242 4242 4242

### File Upload Settings

- Maximum file size: 10MB per image
- Maximum files: 10 images per session
- Allowed formats: JPG, JPEG, PNG, WebP

## Usage

1. **Upload Images**: Users drag and drop or select up to 10 property photos
2. **Add Instructions**: Optional custom enhancement instructions
3. **Process**: AI enhances all images with default + custom instructions
4. **Preview**: Users see low-resolution previews of enhanced images
5. **Select & Pay**: Choose images to purchase and proceed to Stripe checkout
6. **Download**: After payment, download high-resolution enhanced images

## Security Features

- File type validation
- File size limits
- Secure file storage
- Input sanitization
- CSRF protection
- Rate limiting (recommended)

## File Structure

```
fotofix/
├── index.html              # Main landing page
├── checkout_success.php    # Payment success page
├── assets/
│   ├── css/
│   │   └── style.css       # Stylesheet
│   └── js/
│       └── script.js       # Frontend JavaScript
├── api/
│   ├── config.php          # Configuration
│   ├── process_images.php  # Image processing endpoint
│   ├── get_image.php       # Image serving endpoint
│   ├── create_checkout.php # Stripe checkout creation
│   ├── download_file.php   # File download endpoint
│   └── redo_image.php      # Image redo endpoint
├── .htaccess              # Apache configuration
├── composer.json          # PHP dependencies
└── README.md             # This file
```

## API Endpoints

- `POST /api/process_images.php` - Process uploaded images
- `GET /api/get_image.php` - Serve image files
- `POST /api/create_checkout.php` - Create Stripe checkout session
- `POST /api/stripe_webhook.php` - Handle Stripe payment webhooks
- `GET /api/download_file.php` - Download enhanced images (payment required)
- `POST /api/redo_image.php` - Reprocess a specific image

## Customization

### Default AI Instructions

Modify `DEFAULT_INSTRUCTIONS` in `api/config.php` to change the default enhancement instructions.

### Pricing

Update `PRICE_PER_IMAGE` in `api/config.php` to change the price per enhanced image.

### Styling

Customize the appearance by modifying `assets/css/style.css`.

## Troubleshooting

### Common Issues

1. **File Upload Errors**: Check PHP upload limits and directory permissions
2. **Image Processing Fails**: Ensure GD extension is installed
3. **Payment Issues**: Verify Stripe API keys and webhook configuration
4. **Permission Errors**: Check file/directory ownership and permissions

### Logs

Check PHP error logs and web server logs for debugging information.

## License

MIT License - see LICENSE file for details.

## Support

For support and questions, please contact the development team.
