<?php
$pageTitle = "Contact Us - My Library";
require_once 'includes/sessions.php';
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';
require_once 'includes/functions.php';

use App\Auth;

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        // In production, send email here
        // For now, just log it
        error_log("Contact Form Submission - Name: $name, Email: $email, Subject: $subject");
        $success = 'Thank you for contacting us! We will get back to you soon.';
        
        // Clear form
        $name = $email = $subject = $message = '';
    }
}

include 'views/header.php';
include 'views/navbar.php';
?>
</div> <!-- Close default container from header.php -->

<style>
.contact-hero {
    background: linear-gradient(135deg, rgba(46, 138, 64, 0.95), rgba(52, 73, 94, 0.95)),
                url('https://images.unsplash.com/photo-1497366216548-37526070297c?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') center/cover;
    color: white;
    padding: 100px 0 80px;
    position: relative;
    overflow: hidden;
}

.contact-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="25" cy="25" r="2" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="2" fill="white" opacity="0.1"/></svg>') repeat;
    opacity: 0.3;
}

.contact-hero-content {
    position: relative;
    z-index: 1;
}

.contact-section {
    padding: 80px 0;
    background: #f8f9fa;
}

[data-bs-theme="dark"] .contact-section {
    background: #0f172a;
}

.contact-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    transition: transform 0.3s ease;
}

[data-bs-theme="dark"] .contact-card {
    background: #1e293b;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}

.contact-card:hover {
    transform: translateY(-5px);
}

.contact-info-card {
    background: linear-gradient(135deg, var(--primary-color), #34495e);
    color: white;
    padding: 40px;
    border-radius: 20px;
    height: 100%;
}

.contact-info-item {
    display: flex;
    align-items-start;
    margin-bottom: 30px;
    padding: 20px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

.contact-info-item:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateX(10px);
}

.contact-info-icon {
    width: 50px;
    height: 50px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 20px;
    flex-shrink: 0;
}

.contact-info-icon i {
    font-size: 1.5rem;
}

.contact-form {
    padding: 40px;
}

.form-group {
    margin-bottom: 25px;
}

.form-label {
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 10px;
    display: block;
}

[data-bs-theme="dark"] .form-label {
    color: #e2e8f0;
}

.form-control {
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 12px 20px;
    transition: all 0.3s ease;
    font-size: 1rem;
}

.form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(46, 138, 64, 0.15);
}

[data-bs-theme="dark"] .form-control {
    background: #0f172a;
    border-color: #334155;
    color: #e2e8f0;
}

[data-bs-theme="dark"] .form-control:focus {
    background: #0f172a;
    border-color: var(--accent-color);
}

textarea.form-control {
    min-height: 150px;
    resize: vertical;
}

.btn-submit {
    background: linear-gradient(135deg, var(--primary-color), #34495e);
    color: white;
    border: none;
    padding: 15px 40px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    width: 100%;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(46, 138, 64, 0.3);
}

.social-links {
    display: flex;
    gap: 15px;
    margin-top: 30px;
}

.social-link {
    width: 45px;
    height: 45px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    text-decoration: none;
    transition: all 0.3s ease;
}

.social-link:hover {
    background: white;
    color: var(--primary-color);
    transform: translateY(-3px);
}

.map-container {
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    height: 400px;
}

.map-container iframe {
    width: 100%;
    height: 100%;
    border: none;
}

.faq-section {
    padding: 80px 0;
}

.faq-item {
    background: white;
    border-radius: 12px;
    margin-bottom: 15px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

[data-bs-theme="dark"] .faq-item {
    background: #1e293b;
}

.faq-question {
    padding: 20px 25px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 600;
    color: var(--text-dark);
    transition: all 0.3s ease;
}

[data-bs-theme="dark"] .faq-question {
    color: #e2e8f0;
}

.faq-question:hover {
    background: #f8f9fa;
}

[data-bs-theme="dark"] .faq-question:hover {
    background: #0f172a;
}

.faq-answer {
    padding: 0 25px;
    max-height: 0;
    overflow: hidden;
    transition: all 0.3s ease;
    color: #6c757d;
}

[data-bs-theme="dark"] .faq-answer {
    color: #94a3b8;
}

.faq-item.active .faq-answer {
    padding: 0 25px 20px;
    max-height: 500px;
}

.faq-item.active .faq-icon {
    transform: rotate(180deg);
}

.faq-icon {
    transition: transform 0.3s ease;
}

@media (max-width: 768px) {
    .contact-hero {
        padding: 60px 0 40px;
    }
    
    .contact-section {
        padding: 40px 0;
    }
    
    .contact-form {
        padding: 30px 20px;
    }
}
</style>

<!-- Hero Section -->
<div class="contact-hero">
    <div class="contact-hero-content">
        <div class="container text-center">
            <h1 class="display-3 fw-bold mb-3 animate__animated animate__fadeInDown">Get In Touch</h1>
            <p class="lead mb-0 animate__animated animate__fadeInUp">We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
        </div>
    </div>
</div>

<!-- Contact Section -->
<div class="contact-section">
    <div class="container">
        <div class="row g-4">
            <!-- Contact Information -->
            <div class="col-lg-5">
                <div class="contact-info-card animate__animated animate__fadeInLeft">
                    <h3 class="mb-4">Contact Information</h3>
                    <p class="mb-4 opacity-75">Fill out the form and our team will get back to you within 24 hours.</p>
                    
                    <div class="contact-info-item">
                        <div class="contact-info-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Phone</h6>
                            <p class="mb-0 opacity-75">+1 (555) 123-4567</p>
                        </div>
                    </div>
                    
                    <div class="contact-info-item">
                        <div class="contact-info-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Email</h6>
                            <p class="mb-0 opacity-75">contact@mylibrary.com</p>
                        </div>
                    </div>
                    
                    <div class="contact-info-item">
                        <div class="contact-info-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Address</h6>
                            <p class="mb-0 opacity-75">123 Library Street<br>Book City, BC 12345</p>
                        </div>
                    </div>
                    
                    <div class="contact-info-item">
                        <div class="contact-info-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Working Hours</h6>
                            <p class="mb-0 opacity-75">Mon - Fri: 9:00 AM - 6:00 PM<br>Sat - Sun: 10:00 AM - 4:00 PM</p>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <h6 class="mb-3">Follow Us</h6>
                        <div class="social-links">
                            <a href="#" class="social-link" title="Facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" class="social-link" title="Twitter">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="#" class="social-link" title="Instagram">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="#" class="social-link" title="LinkedIn">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Contact Form -->
            <div class="col-lg-7">
                <div class="contact-card animate__animated animate__fadeInRight">
                    <div class="contact-form">
                        <h3 class="mb-4">Send Us a Message</h3>
                        
                        <form method="POST" id="contactForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name" class="form-label">Your Name *</label>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               placeholder="John Doe" required 
                                               value="<?= htmlspecialchars($name ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email" class="form-label">Your Email *</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               placeholder="john@example.com" required
                                               value="<?= htmlspecialchars($email ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="subject" class="form-label">Subject *</label>
                                <input type="text" class="form-control" id="subject" name="subject" 
                                       placeholder="How can we help you?" required
                                       value="<?= htmlspecialchars($subject ?? '') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="message" class="form-label">Message *</label>
                                <textarea class="form-control" id="message" name="message" 
                                          placeholder="Tell us more about your inquiry..." required><?= htmlspecialchars($message ?? '') ?></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-submit">
                                <i class="fas fa-paper-plane me-2"></i>Send Message
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Map Section -->
<div class="container mb-5">
    <div class="map-container animate__animated animate__fadeInUp">
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3022.2412648750455!2d-73.98784368459395!3d40.74844097932847!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c259a9b3117469%3A0xd134e199a405a163!2sEmpire%20State%20Building!5e0!3m2!1sen!2sus!4v1234567890123!5m2!1sen!2sus" 
                allowfullscreen="" loading="lazy"></iframe>
    </div>
</div>

<!-- FAQ Section -->
<div class="faq-section">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold mb-3">Frequently Asked Questions</h2>
            <p class="lead text-muted">Find answers to common questions about our library</p>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <span>How do I borrow a book?</span>
                        <i class="fas fa-chevron-down faq-icon"></i>
                    </div>
                    <div class="faq-answer">
                        <p>To borrow a book, simply create an account, browse our collection, and click the "Borrow" button on any available book. You can keep the book for up to 14 days.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <span>Can I renew a borrowed book?</span>
                        <i class="fas fa-chevron-down faq-icon"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Yes! You can renew a book up to 2 times if no one else has reserved it. Just go to your borrowing history and click the "Renew" button.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <span>How many books can I borrow at once?</span>
                        <i class="fas fa-chevron-down faq-icon"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Regular members can borrow up to 5 books at a time. Premium members can borrow up to 10 books simultaneously.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <span>What if I lose or damage a book?</span>
                        <i class="fas fa-chevron-down faq-icon"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Please contact us immediately if a book is lost or damaged. Replacement fees may apply depending on the condition and value of the book.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <span>Do you have e-books available?</span>
                        <i class="fas fa-chevron-down faq-icon"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Yes! We have a growing collection of e-books that you can download and read on your device. Check out our E-Books section for more information.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'views/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Show PHP messages with SweetAlert2
<?php if ($error): ?>
Swal.fire({
    icon: 'error',
    title: 'Error',
    text: <?= json_encode($error) ?>,
    confirmButtonColor: '#2e8a40',
    showClass: {
        popup: 'animate__animated animate__shakeX'
    }
});
<?php endif; ?>

<?php if ($success): ?>
Swal.fire({
    icon: 'success',
    title: 'Message Sent!',
    text: <?= json_encode($success) ?>,
    confirmButtonColor: '#2e8a40',
    showClass: {
        popup: 'animate__animated animate__fadeInDown'
    }
});
<?php endif; ?>

// Form validation
document.getElementById('contactForm').addEventListener('submit', function(e) {
    const name = document.getElementById('name').value.trim();
    const email = document.getElementById('email').value.trim();
    const subject = document.getElementById('subject').value.trim();
    const message = document.getElementById('message').value.trim();
    
    if (!name || !email || !subject || !message) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Missing Information',
            text: 'Please fill in all required fields',
            confirmButtonColor: '#2e8a40'
        });
        return false;
    }
    
    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Invalid Email',
            text: 'Please enter a valid email address',
            confirmButtonColor: '#2e8a40'
        });
        return false;
    }
    
    // Show loading
    Swal.fire({
        title: 'Sending...',
        text: 'Please wait while we send your message',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
});

// FAQ Toggle
function toggleFaq(element) {
    const faqItem = element.parentElement;
    const isActive = faqItem.classList.contains('active');
    
    // Close all FAQs
    document.querySelectorAll('.faq-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // Open clicked FAQ if it wasn't active
    if (!isActive) {
        faqItem.classList.add('active');
    }
}
</script>
