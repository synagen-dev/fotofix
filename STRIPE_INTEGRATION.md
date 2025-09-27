# Stripe Integration Documentation

## Overview

The FotoFix application now includes full Stripe integration for processing payments for enhanced images. The integration supports 
quantity-based pricing where customers pay $20 per image they select for purchase.

## Configuration

### Stripe Keys
The Stripe configuration is managed in `api/config.php` with the following settings:

- **Test Mode**: Currently using test keys (set `$stripe_live = false`)
- **Live Mode**: Set `$stripe_live = true` for production
- **API Keys**: Retrieved from Google Cloud Secret Manager
- **Price per Image**: $20.00 (2000 cents)

### Environment Variables Required
- `STRIPE_KEY_FOTOFIX_TEST` - Test secret key
- `STRIPE_PUBLIC_FOTOFIX_TEST` - Test publishable key
- `STRIPE_SIGNING_SECRET_TEST` - Test webhook signing secret
- `STRIPE_API_KEY_FOTOFIX` - Live secret key (for production)
- `STRIPE_PUBLIC_FOTOFIX_LIVE` - Live publishable key (for production)
- `STRIPE_SIGNING_SECRET_LIVE` - Live webhook signing secret (for production)

## Integration Flow

### 1. Image Processing
1. User uploads images
2. Images are processed with AI enhancement
3. User sees previews and selects images to purchase
4. User clicks "Proceed to Checkout"

### 2. Checkout Process
1. Frontend calls `api/create_checkout.php` with selected images
2. Server creates Stripe checkout session with line items
3. User is redirected to Stripe checkout page
4. User completes payment on Stripe

### 3. Payment Confirmation
1. Stripe sends webhook to `api/stripe_webhook.php`
2. Webhook processes payment and marks images as paid
3. User is redirected to `checkout_success.php`
4. User can download enhanced images

## API Endpoints

### `api/create_checkout.php`
Creates a Stripe checkout session for selected images.

**Request:**
```json
{
  "selected_images": [0, 1, 2],
  "enhanced_images": [...]
}
```

**Response:**
```json
{
  "success": true,
  "checkout_url": "https://checkout.stripe.com/...",
  "session_id": "cs_test_..."
}
```

### `api/stripe_webhook.php`
Handles Stripe webhook events for payment confirmation.

**Events Handled:**
- `checkout.session.completed` - Payment successful
- `payment_intent.succeeded` - Payment processed

### `api/download_file.php`
Secure download endpoint for enhanced images.

**Parameters:**
- `type` - File type (enhanced/preview)
- `id` - Unique image ID
- `name` - Download filename

### `checkout_success.php`
Success page that shows download links for purchased images.

## File Structure

```
api/
├── create_checkout.php      # Creates Stripe checkout sessions
├── stripe_webhook.php      # Handles Stripe webhooks
├── download_file.php       # Secure file downloads
└── config.php              # Stripe configuration

checkout_success.php        # Payment success page
test_stripe_integration.php # Integration test script
```

## Security Features

### Payment Verification
- Enhanced images require payment verification before download
- Webhook validates payment status
- Session data is securely stored and cleaned up

### File Access Control
- Download endpoint checks payment status
- Only paid images can be downloaded
- Temporary files are cleaned up after use

## Testing

### Test Cards
- **Success**: 4242 4242 4242 4242
- **Decline**: 4000 0000 0000 0002
- **3D Secure**: 4000 0025 0000 3155

### Test Script
Run `test_stripe_integration.php` to verify:
- Stripe configuration
- API key validity
- Directory permissions
- Endpoint availability

## Webhook Configuration

### Stripe Dashboard:
1. Go to Developers > Webhooks
2. Add endpoint: `https://yourdomain.com/api/stripe_webhook.php`
3. Select events: `checkout.session.completed`, `payment_intent.succeeded`
4. Copy signing secret to environment variable

## Production Deployment

### Environment Setup
1. Set `$stripe_live = true` in config.php
2. Update environment variables with live keys
3. Configure webhook endpoint in Stripe Dashboard
4. Test with live payment methods

### Monitoring
- Check Stripe Dashboard for payment status
- Monitor webhook delivery in Stripe Dashboard
- Review error logs for failed payments

## Troubleshooting

### Common Issues
1. **Webhook not receiving events**: Check endpoint URL and signing secret
2. **Payment not processing**: Verify API keys and product configuration
3. **Download not working**: Check file permissions and payment status

### Debug Mode
Enable debug mode in `config.php` to see detailed logs:
```php
$debugMode = true;
$debugLevel = 3;
```

## Support

For Stripe-related issues:
- Check Stripe Dashboard for payment status
- Review webhook logs in Stripe Dashboard
- Test with Stripe test cards
- Verify API key configuration
