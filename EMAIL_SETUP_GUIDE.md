# Email Setup Guide for Password Reset

## Overview
The password reset feature sends emails using PHPMailer. You can use either **Mailtrap.io** (recommended for testing) or **Gmail SMTP** (for production).

---

## Option 1: Mailtrap.io (Recommended for Testing) ⭐

Mailtrap is a safe email testing service that captures emails without sending them to real users. Perfect for development!

### Quick Setup:

1. **Create Account**: Go to [https://mailtrap.io](https://mailtrap.io) and sign up (free)
2. **Get Credentials**: Click on your inbox → SMTP Settings → Select "PHP"
3. **Update .env**:
   ```env
   MAIL_HOST=sandbox.smtp.mailtrap.io
   MAIL_PORT=2525
   MAIL_USERNAME=your_mailtrap_username
   MAIL_PASSWORD=your_mailtrap_password
   MAIL_FROM_ADDRESS=noreply@mylibrary.com
   MAIL_FROM_NAME=My Library
   ```
4. **Test**: Go to `forgot_password.php`, enter any registered email, and check your Mailtrap inbox!

✅ **Benefits**: 
- Safe testing (no real emails sent)
- Spam analysis
- HTML preview
- No configuration hassles
- Free tier: 500 emails/month

📖 **Detailed Guide**: See `MAILTRAP_SETUP_GUIDE.md` for complete instructions

---

## Option 2: Gmail SMTP (For Production)

### Step 1: Enable 2-Factor Authentication on Gmail
1. Go to your Google Account: https://myaccount.google.com/
2. Click on "Security" in the left sidebar
3. Under "Signing in to Google", enable "2-Step Verification"
4. Follow the prompts to set it up

### Step 2: Generate App Password
1. After enabling 2FA, go back to Security settings
2. Under "Signing in to Google", click "App passwords"
3. Select "Mail" as the app
4. Select "Other (Custom name)" as the device
5. Enter "My Library App" as the name
6. Click "Generate"
7. **Copy the 16-character password** (you'll need this for .env file)

### Step 3: Update .env File
Open your `.env` file and update these values:

```env
# Email Configuration (Gmail SMTP)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_actual_email@gmail.com
MAIL_PASSWORD=your_16_char_app_password
MAIL_FROM_ADDRESS=your_actual_email@gmail.com
MAIL_FROM_NAME=My Library
```

**Replace:**
- `your_actual_email@gmail.com` with your Gmail address
- `your_16_char_app_password` with the app password from Step 2

### Step 4: Test the Feature
1. Go to `http://localhost:3400/book-app/forgot_password.php`
2. Enter an email address that exists in your users table
3. Click "Send Reset Link"
4. Check your email inbox (and spam folder)
5. Click the reset link in the email
6. Enter your new password

## Alternative: Using Other Email Services

### Using Outlook/Hotmail
```env
MAIL_HOST=smtp-mail.outlook.com
MAIL_PORT=587
MAIL_USERNAME=your_email@outlook.com
MAIL_PASSWORD=your_password
```

### Using Yahoo Mail
```env
MAIL_HOST=smtp.mail.yahoo.com
MAIL_PORT=587
MAIL_USERNAME=your_email@yahoo.com
MAIL_PASSWORD=your_app_password
```

### Using Custom SMTP Server
```env
MAIL_HOST=your.smtp.server.com
MAIL_PORT=587
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
```

## Troubleshooting

### Email Not Sending
1. **Check .env file**: Make sure all email settings are correct
2. **Check spam folder**: Reset emails might go to spam
3. **Check PHP error log**: Look for PHPMailer errors
4. **Verify app password**: Make sure you're using the app password, not your regular Gmail password
5. **Check firewall**: Make sure port 587 is not blocked

### "SMTP connect() failed" Error
- Make sure 2FA is enabled on Gmail
- Verify you're using an app password, not your regular password
- Check if your hosting/network allows SMTP connections

### Email Goes to Spam
- This is normal for localhost development
- In production, configure SPF, DKIM, and DMARC records for your domain

## Testing Without Email

If you can't configure email right now, you can still test the feature:

1. Go to `forgot_password.php` and enter an email
2. Check the PHP error log for the reset link
3. Copy the token from the log
4. Visit: `reset_password.php?token=YOUR_TOKEN`
5. Reset your password

The token is also logged even when email sending fails, so you can always test the functionality.

## Security Notes

1. **Never commit .env file**: Make sure `.env` is in your `.gitignore`
2. **Use app passwords**: Never use your main Gmail password
3. **Token expiry**: Reset tokens expire after 1 hour
4. **One-time use**: Tokens are deleted after successful password reset
5. **No email disclosure**: System doesn't reveal if an email exists in the database

## Production Recommendations

For production environments:

1. Use a dedicated email service like:
   - SendGrid
   - Mailgun
   - Amazon SES
   - Postmark

2. Configure proper DNS records (SPF, DKIM, DMARC)
3. Use environment-specific .env files
4. Monitor email delivery rates
5. Implement rate limiting to prevent abuse

## Files Modified

- `forgot_password.php` - Now sends actual emails
- `composer.json` - Added PHPMailer dependency
- `.env` - Added email configuration
- `EMAIL_SETUP_GUIDE.md` - This guide

## Need Help?

If you're having trouble:
1. Check the test page: `test_forgot_password.php`
2. Review PHP error logs
3. Verify all .env settings
4. Make sure PHPMailer is installed: `composer install`
