# Mailtrap.io Setup Guide for Password Reset

This guide will help you configure Mailtrap.io for testing the password reset functionality in your Book Library application.

## What is Mailtrap?

Mailtrap is an email testing service that captures emails sent from your application in a safe testing environment. This prevents accidentally sending test emails to real users during development.

## Setup Steps

### 1. Create a Mailtrap Account

1. Go to [https://mailtrap.io](https://mailtrap.io)
2. Click "Sign Up" (it's free!)
3. You can sign up with:
   - Email and password
   - Google account
   - GitHub account

### 2. Get Your SMTP Credentials

1. After logging in, you'll see your inbox
2. Click on "My Inbox" (or create a new inbox)
3. Click on "SMTP Settings"
4. Select "PHP" from the dropdown
5. You'll see credentials like:
   ```
   Host: sandbox.smtp.mailtrap.io
   Port: 2525
   Username: [your_username]
   Password: [your_password]
   ```

### 3. Update Your .env File

Open your `.env` file and update the email configuration:

```env
# Email Configuration (Mailtrap.io for Testing)
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username_here
MAIL_PASSWORD=your_mailtrap_password_here
MAIL_FROM_ADDRESS=noreply@mylibrary.com
MAIL_FROM_NAME=My Library
```

**Important:** Replace `your_mailtrap_username_here` and `your_mailtrap_password_here` with your actual Mailtrap credentials.

### 4. Test the Password Reset

1. Go to `http://localhost:3400/book-app/forgot_password.php`
2. Enter a registered email address
3. Click "Send Reset Link"
4. Go to your Mailtrap inbox at [https://mailtrap.io/inboxes](https://mailtrap.io/inboxes)
5. You should see the password reset email!

## Features

✅ **Safe Testing** - Emails never reach real inboxes
✅ **HTML Preview** - See how your email looks
✅ **Spam Analysis** - Check if your email might be marked as spam
✅ **HTML/Text Check** - Verify both HTML and plain text versions
✅ **Validation** - Check for common email issues

## Viewing Test Emails

In your Mailtrap inbox, you can:

1. **View HTML** - See the styled email
2. **View Text** - See the plain text version
3. **View Raw** - See the raw email source
4. **Check Spam Score** - Ensure your email won't be marked as spam
5. **Forward to Real Email** - Test with a real inbox if needed

## Troubleshooting

### Email Not Appearing in Mailtrap?

1. **Check Credentials**: Make sure your username and password are correct in `.env`
2. **Check Error Logs**: Look at your PHP error logs for any SMTP errors
3. **Verify Email Exists**: The email address must exist in your database
4. **Check Inbox**: Make sure you're looking at the correct inbox in Mailtrap

### Common Errors

**"SMTP connect() failed"**
- Check your internet connection
- Verify MAIL_HOST is `sandbox.smtp.mailtrap.io`
- Verify MAIL_PORT is `2525`

**"Authentication failed"**
- Double-check your username and password
- Make sure there are no extra spaces in `.env`

**"Could not instantiate mail function"**
- Make sure PHPMailer is installed: `composer require phpmailer/phpmailer`

## Production Setup

⚠️ **Important**: Mailtrap is for testing only!

When you're ready to go to production:

1. Sign up for a real email service:
   - Gmail SMTP (free, limited)
   - SendGrid (free tier available)
   - Mailgun (free tier available)
   - Amazon SES (pay as you go)

2. Update your `.env` with production credentials

3. Remove the SSL verification bypass in `forgot_password.php`:
   ```php
   // Remove this section in production:
   $mail->SMTPOptions = array(
       'ssl' => array(
           'verify_peer' => false,
           'verify_peer_name' => false,
           'allow_self_signed' => true
       )
   );
   ```

## Email Template

The password reset email includes:

- **Professional Design** - Branded with your library colors
- **Clear CTA Button** - "Reset Password" button
- **Fallback Link** - Plain text link for email clients that don't support buttons
- **Expiration Notice** - Clear 1-hour expiration warning
- **Security Note** - Instructions to ignore if not requested
- **Plain Text Version** - For email clients that don't support HTML

## Testing Checklist

- [ ] Mailtrap account created
- [ ] SMTP credentials added to `.env`
- [ ] Test email sent successfully
- [ ] Email appears in Mailtrap inbox
- [ ] HTML version displays correctly
- [ ] Reset link works
- [ ] Token expires after 1 hour
- [ ] Plain text version is readable

## Support

- **Mailtrap Documentation**: [https://help.mailtrap.io](https://help.mailtrap.io)
- **PHPMailer Documentation**: [https://github.com/PHPMailer/PHPMailer](https://github.com/PHPMailer/PHPMailer)

## Free Tier Limits

Mailtrap Free Plan includes:
- 1 inbox
- 500 emails/month
- 2 months email history
- Perfect for development and testing!

---

**Happy Testing! 🚀**
