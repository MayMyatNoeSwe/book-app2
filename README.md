# BookHouse - Premium Book Library Project Documentation

## 🚀 Technology Stack

The **BookHouse** application is a modern, full-stack PHP web application built with a focus on premium user experience and modular architecture.

### Backend (Core Logic)
- **Language**: PHP 8.1+
- **Database**: MySQL / MariaDB using **PDO (PHP Data Objects)** for secure, prepared-statement-driven queries.
- **Dependency Management**: **Composer** (PSR-4 Autoloading).
- **Environment Management**: Custom `.env` loader for secure configuration.

### Frontend (UI/UX)
- **Framework**: **Bootstrap 5.3** (Responsive, Utility-first layout).
- **Icons**: **Font Awesome 6.4** (Vector icons).
- **Aesthetics**: Custom Premium CSS System (Glassmorphism, CSS Variables, smooth transitions).
- **Animations**: **Animate.css** & Custom CSS Keyframe animations.
- **Interactive UI**: **SweetAlert2** (Premium popups and notifications).

### Key Libraries & Integrations
- **PDF Generation**: `TCPDF` (for invoices or reports).
- **Sample Data**: `FakerPHP` (for populating the library during development).
- **Email Services**: `PHPMailer` (for password resets and notifications).
- **OAuth**: Google & Facebook Login integration.
- **Client-side Features**:
    - **Infinite Scroll**: Custom JavaScript using `IntersectionObserver` for seamless book browsing.
    - **Dynamic Search**: Interactive modal-based search.
    - **Dark Mode**: Persistence-based theme toggle using `localStorage`.

---

## 📖 User Stories

### 1. As a Guest (Unauthenticated User)
- **Discovery**: I want to browse the library's latest arrivals on the home page so that I can see what's new.
- **Search**: I want to search for books by title, author, or keyword so that I can find a specific book quickly.
- **Filtering**: I want to filter books by category (e.g., Fiction, Sci-Fi) so that I can explore genres I'm interested in.
- **Details**: I want to view a book's cover, description, and reader reviews so that I can decide if it's worth reading.
- **Registration**: I want to create an account so that I can start borrowing and reviewing books.

### 2. As a Member (Authenticated User)
- **Social Login**: I want to log in using my Google or Facebook account so that I don't have to remember another password.
- **Reviews**: I want to rate books (1-5 stars) and write text reviews so that I can share my opinion with the community.
- **Ownership**: I want to edit or delete my own reviews if I change my mind or made a mistake.
- **Shopping Cart**: I want to add books to my cart and "checkout" so that I can borrow or purchase them in one go.
- **Dashboard**: I want to see my username and account pill in the navbar so that I know I am logged in.

### 3. As an Administrator
- **Catalog Management**: Implied functionality to add, update, or remove books from the library to keep the collection fresh.
- **System Health**: I want to use diagnostic tools (like `check_database.php`) to ensure the system is running correctly.

---

## 🏗️ Technical Highlights
- **Architecture**: Separated View-Logic structure (PHP logic in `src/`, UI in `views/`).
- **Security**: 
    - CSRF-resistant session handling (`Lax` cookies).
    - Environment secrets kept out of the codebase via `.env`.
    - Protected API endpoints for sensitive actions like deleting reviews.
- **Performance**:
    - Asynchronous book loading (API-driven infinite scroll).
    - Optimized CSS naming and variable usage for quick loading.
