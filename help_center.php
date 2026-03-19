<?php
$pageTitle = "Help Center - My Library";
require_once 'includes/sessions.php';
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';
require_once 'includes/functions.php';

use App\Auth;

include 'views/header.php';
include 'views/navbar.php';
?>
</div> <!-- Close default container from header.php -->

<style>
.help-hero {
    background: linear-gradient(135deg, rgba(46, 138, 64, 0.95), rgba(52, 73, 94, 0.95)),
                url('https://images.unsplash.com/photo-1521587760476-6c12a4b040da?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') center/cover;
    color: white;
    padding: 100px 0 80px;
    position: relative;
    overflow: hidden;
}

.help-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="25" cy="25" r="2" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="2" fill="white" opacity="0.1"/></svg>') repeat;
    opacity: 0.3;
}

.help-hero-content {
    position: relative;
    z-index: 1;
}

.help-search-box {
    max-width: 700px;
    margin: 30px auto 0;
}

.help-search-box .input-group {
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.help-search-box input {
    padding: 18px 25px;
    font-size: 1.1rem;
    border: none;
    border-radius: 50px 0 0 50px;
}

.help-search-box button {
    padding: 18px 35px;
    border-radius: 0 50px 50px 0;
    font-weight: 600;
}

.help-categories {
    padding: 80px 0;
    background: #f8f9fa;
}

[data-bs-theme="dark"] .help-categories {
    background: #0f172a;
}

.category-card {
    background: white;
    border-radius: 20px;
    padding: 40px 30px;
    text-align: center;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    height: 100%;
    border: 2px solid transparent;
}

.category-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
    border-color: var(--primary-color);
}

[data-bs-theme="dark"] .category-card {
    background: #1e293b;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
}

.category-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--primary-color), #34495e);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 2rem;
    color: white;
    transition: all 0.3s ease;
}

.category-card:hover .category-icon {
    transform: scale(1.1) rotate(5deg);
}

.category-title {
    font-weight: 700;
    margin-bottom: 15px;
    color: var(--text-dark);
}

[data-bs-theme="dark"] .category-title {
    color: #e2e8f0;
}

.category-description {
    color: #6c757d;
    margin-bottom: 20px;
    line-height: 1.6;
}

[data-bs-theme="dark"] .category-description {
    color: #94a3b8;
}

.faq-section {
    padding: 80px 0;
}

.faq-category {
    margin-bottom: 50px;
}

.faq-category-title {
    font-weight: 700;
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 3px solid var(--primary-color);
    display: flex;
    align-items: center;
    gap: 15px;
}

.faq-category-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, var(--primary-color), #34495e);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.faq-item {
    background: white;
    border-radius: 15px;
    margin-bottom: 15px;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    transition: all 0.3s ease;
}

.faq-item:hover {
    box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
}

[data-bs-theme="dark"] .faq-item {
    background: #1e293b;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.2);
}

.faq-question {
    padding: 25px 30px;
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
    color: var(--primary-color);
}

[data-bs-theme="dark"] .faq-question:hover {
    background: #0f172a;
}

.faq-answer {
    padding: 0 30px;
    max-height: 0;
    overflow: hidden;
    transition: all 0.4s ease;
    color: #6c757d;
}

[data-bs-theme="dark"] .faq-answer {
    color: #94a3b8;
}

.faq-item.active .faq-answer {
    padding: 0 30px 25px;
    max-height: 1000px;
}

.faq-item.active .faq-icon {
    transform: rotate(180deg);
}

.faq-icon {
    width: 35px;
    height: 35px;
    background: var(--primary-color);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.contact-cta {
    background: linear-gradient(135deg, var(--primary-color), #34495e);
    color: white;
    padding: 60px 0;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.contact-cta::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="25" cy="25" r="2" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="2" fill="white" opacity="0.1"/></svg>') repeat;
    opacity: 0.3;
}

.contact-cta-content {
    position: relative;
    z-index: 1;
}

.quick-links-section {
    padding: 60px 0;
    background: white;
}

[data-bs-theme="dark"] .quick-links-section {
    background: #1e293b;
}

.quick-link-card {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 25px;
    text-align: center;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.quick-link-card:hover {
    background: white;
    border-color: var(--primary-color);
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

[data-bs-theme="dark"] .quick-link-card {
    background: #0f172a;
}

[data-bs-theme="dark"] .quick-link-card:hover {
    background: #1e293b;
}

.quick-link-icon {
    font-size: 2.5rem;
    color: var(--primary-color);
    margin-bottom: 15px;
}

@media (max-width: 768px) {
    .help-hero {
        padding: 60px 0 40px;
    }
    
    .help-categories,
    .faq-section {
        padding: 40px 0;
    }
    
    .category-card {
        margin-bottom: 20px;
    }
}
</style>

<!-- Hero Section -->
<div class="help-hero">
    <div class="help-hero-content">
        <div class="container text-center">
            <h1 class="display-3 fw-bold mb-3 animate__animated animate__fadeInDown">
                <i class="fas fa-question-circle me-3"></i>How Can We Help?
            </h1>
            <p class="lead mb-4 animate__animated animate__fadeInUp">
                Search our knowledge base or browse categories below
            </p>
            
            <div class="help-search-box animate__animated animate__fadeInUp">
                <form action="help_center.php" method="GET">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search for help articles, guides, tutorials..." 
                               value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                        <button class="btn btn-light" type="submit">
                            <i class="fas fa-search me-2"></i>Search
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Help Categories -->
<div class="help-categories">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold mb-3">Browse by Category</h2>
            <p class="lead text-muted">Find answers organized by topic</p>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <a href="#getting-started" class="text-decoration-none">
                    <div class="category-card">
                        <div class="category-icon">
                            <i class="fas fa-rocket"></i>
                        </div>
                        <h4 class="category-title">Getting Started</h4>
                        <p class="category-description">
                            Learn the basics of using our library system, creating an account, and borrowing your first book.
                        </p>
                        <span class="badge bg-primary">12 Articles</span>
                    </div>
                </a>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <a href="#borrowing" class="text-decoration-none">
                    <div class="category-card">
                        <div class="category-icon">
                            <i class="fas fa-book-reader"></i>
                        </div>
                        <h4 class="category-title">Borrowing & Returns</h4>
                        <p class="category-description">
                            Everything about borrowing books, due dates, renewals, and returning items.
                        </p>
                        <span class="badge bg-primary">15 Articles</span>
                    </div>
                </a>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <a href="#account" class="text-decoration-none">
                    <div class="category-card">
                        <div class="category-icon">
                            <i class="fas fa-user-cog"></i>
                        </div>
                        <h4 class="category-title">Account Management</h4>
                        <p class="category-description">
                            Manage your profile, update preferences, reset password, and more.
                        </p>
                        <span class="badge bg-primary">10 Articles</span>
                    </div>
                </a>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <a href="#ebooks" class="text-decoration-none">
                    <div class="category-card">
                        <div class="category-icon">
                            <i class="fas fa-tablet-alt"></i>
                        </div>
                        <h4 class="category-title">E-Books & Digital</h4>
                        <p class="category-description">
                            Download and read e-books, compatible devices, and digital formats.
                        </p>
                        <span class="badge bg-primary">8 Articles</span>
                    </div>
                </a>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <a href="#fees" class="text-decoration-none">
                    <div class="category-card">
                        <div class="category-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <h4 class="category-title">Fees & Payments</h4>
                        <p class="category-description">
                            Information about late fees, lost items, payment methods, and refunds.
                        </p>
                        <span class="badge bg-primary">6 Articles</span>
                    </div>
                </a>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <a href="#technical" class="text-decoration-none">
                    <div class="category-card">
                        <div class="category-icon">
                            <i class="fas fa-tools"></i>
                        </div>
                        <h4 class="category-title">Technical Support</h4>
                        <p class="category-description">
                            Troubleshooting login issues, browser compatibility, and technical problems.
                        </p>
                        <span class="badge bg-primary">9 Articles</span>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- FAQ Section -->
<div class="faq-section">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold mb-3">Frequently Asked Questions</h2>
            <p class="lead text-muted">Quick answers to common questions</p>
        </div>
        
        <!-- Getting Started FAQs -->
        <div class="faq-category" id="getting-started">
            <h3 class="faq-category-title">
                <div class="faq-category-icon">
                    <i class="fas fa-rocket"></i>
                </div>
                Getting Started
            </h3>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>How do I create an account?</span>
                    <div class="faq-icon">
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
                <div class="faq-answer">
                    <p>Creating an account is easy! Click the "Sign Up" button in the top right corner, fill in your details (name, email, password), and verify your email address. You can also sign up using your Google or Facebook account for faster registration.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>Is there a membership fee?</span>
                    <div class="faq-icon">
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
                <div class="faq-answer">
                    <p>Basic membership is completely free! You can borrow up to 5 books at a time with a standard 14-day borrowing period. We also offer a Premium membership ($9.99/month) that allows you to borrow up to 10 books simultaneously and provides early access to new releases.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>What types of books are available?</span>
                    <div class="faq-icon">
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
                <div class="faq-answer">
                    <p>Our library offers a diverse collection including Fiction, Non-Fiction, Science, History, Technology, Biography, Mystery, Romance, and more. We have both physical books and e-books available. You can browse by category, author, or use our search feature to find specific titles.</p>
                </div>
            </div>
        </div>
        
        <!-- Borrowing & Returns FAQs -->
        <div class="faq-category" id="borrowing">
            <h3 class="faq-category-title">
                <div class="faq-category-icon">
                    <i class="fas fa-book-reader"></i>
                </div>
                Borrowing & Returns
            </h3>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>How do I borrow a book?</span>
                    <div class="faq-icon">
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
                <div class="faq-answer">
                    <p>Browse our collection and click on any book to view its details. If the book is available, click the "Borrow Now" button. The book will be added to your account and you'll have 14 days to read it. You'll receive email reminders before the due date.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>Can I renew a borrowed book?</span>
                    <div class="faq-icon">
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
                <div class="faq-answer">
                    <p>Yes! You can renew a book up to 2 times if no one else has reserved it. Go to your borrowing history and click the "Renew" button next to the book. Each renewal extends your borrowing period by another 14 days.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>What happens if I return a book late?</span>
                    <div class="faq-icon">
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
                <div class="faq-answer">
                    <p>Late returns incur a small fee of $0.25 per day for physical books. However, we understand that life happens! If you're going to be late, you can request an extension through your account. E-books automatically return on the due date, so there are no late fees for digital items.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>How many books can I borrow at once?</span>
                    <div class="faq-icon">
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
                <div class="faq-answer">
                    <p>Basic members can borrow up to 5 books at a time. Premium members can borrow up to 10 books simultaneously. This limit includes both physical books and e-books combined.</p>
                </div>
            </div>
        </div>
        
        <!-- Account Management FAQs -->
        <div class="faq-category" id="account">
            <h3 class="faq-category-title">
                <div class="faq-category-icon">
                    <i class="fas fa-user-cog"></i>
                </div>
                Account Management
            </h3>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>How do I reset my password?</span>
                    <div class="faq-icon">
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
                <div class="faq-answer">
                    <p>Click "Forgot Password" on the login page, enter your email address, and we'll send you a password reset link. The link is valid for 1 hour. If you don't receive the email, check your spam folder or contact support.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>Can I change my email address?</span>
                    <div class="faq-icon">
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
                <div class="faq-answer">
                    <p>Yes! Go to your Profile Settings, click "Edit Profile", and update your email address. You'll need to verify the new email address before the change takes effect.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>How do I delete my account?</span>
                    <div class="faq-icon">
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
                <div class="faq-answer">
                    <p>We're sorry to see you go! To delete your account, go to Settings > Account > Delete Account. Please note that you must return all borrowed books and clear any outstanding fees before deletion. This action is permanent and cannot be undone.</p>
                </div>
            </div>
        </div>
        
        <!-- E-Books & Digital FAQs -->
        <div class="faq-category" id="ebooks">
            <h3 class="faq-category-title">
                <div class="faq-category-icon">
                    <i class="fas fa-tablet-alt"></i>
                </div>
                E-Books & Digital
            </h3>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>What devices can I read e-books on?</span>
                    <div class="faq-icon">
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
                <div class="faq-answer">
                    <p>Our e-books are compatible with most devices including computers, tablets, smartphones, and e-readers. Supported formats include PDF, EPUB, and MOBI. You can download the book file and read it using your preferred e-reader app.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>Do e-books have due dates?</span>
                    <div class="faq-icon">
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
                <div class="faq-answer">
                    <p>Yes, e-books have the same 14-day borrowing period as physical books. However, the file will automatically become inaccessible after the due date, so there are no late fees. You can download the same e-book again if you need more time.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Links -->
<div class="quick-links-section">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-6 fw-bold mb-3">Quick Links</h2>
            <p class="text-muted">Access important resources and tools</p>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <a href="index.php" class="text-decoration-none">
                    <div class="quick-link-card">
                        <div class="quick-link-icon">
                            <i class="fas fa-home"></i>
                        </div>
                        <h5 class="mb-2">Browse Books</h5>
                        <p class="text-muted small mb-0">Explore our collection</p>
                    </div>
                </a>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <a href="<?= Auth::check() ? 'profile.php' : 'login.php' ?>" class="text-decoration-none">
                    <div class="quick-link-card">
                        <div class="quick-link-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        <h5 class="mb-2">My Account</h5>
                        <p class="text-muted small mb-0">Manage your profile</p>
                    </div>
                </a>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <a href="contact_us.php" class="text-decoration-none">
                    <div class="quick-link-card">
                        <div class="quick-link-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h5 class="mb-2">Contact Us</h5>
                        <p class="text-muted small mb-0">Get in touch</p>
                    </div>
                </a>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <a href="book-list.php" class="text-decoration-none">
                    <div class="quick-link-card">
                        <div class="quick-link-icon">
                            <i class="fas fa-list"></i>
                        </div>
                        <h5 class="mb-2">All Books</h5>
                        <p class="text-muted small mb-0">Complete catalog</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Contact CTA -->
<div class="contact-cta">
    <div class="contact-cta-content">
        <div class="container">
            <h2 class="display-5 fw-bold mb-3">Still Need Help?</h2>
            <p class="lead mb-4">Our support team is here to assist you</p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="contact_us.php" class="btn btn-light btn-lg">
                    <i class="fas fa-envelope me-2"></i>Contact Support
                </a>
                <a href="mailto:support@mylibrary.com" class="btn btn-outline-light btn-lg">
                    <i class="fas fa-paper-plane me-2"></i>Email Us
                </a>
            </div>
            <div class="mt-4">
                <p class="mb-1"><i class="fas fa-clock me-2"></i>Support Hours: Mon-Fri 9AM-6PM</p>
                <p class="mb-0"><i class="fas fa-reply me-2"></i>Average Response Time: 2-4 hours</p>
            </div>
        </div>
    </div>
</div>

<?php include 'views/footer.php'; ?>

<script>
// FAQ Toggle
function toggleFaq(element) {
    const faqItem = element.parentElement;
    const isActive = faqItem.classList.contains('active');
    
    // Close all FAQs in the same category
    const category = faqItem.closest('.faq-category');
    category.querySelectorAll('.faq-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // Open clicked FAQ if it wasn't active
    if (!isActive) {
        faqItem.classList.add('active');
    }
}

// Smooth scroll to category
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const href = this.getAttribute('href');
        if (href !== '#' && href.length > 1) {
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }
    });
});
</script>
