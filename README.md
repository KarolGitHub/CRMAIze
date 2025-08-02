# CRMAIze

**CRMAIze** is a smart, AI-powered customer retention and engagement dashboard tailored for e-commerce businesses. It helps marketing teams identify at-risk customers, automate personalized discount campaigns, and analyze customer behavior using AI-driven segmentation and predictive analytics — all within a sleek, responsive interface built with Twig, PHP, and Foundation CSS.

---

## Key Features

- **AI-Based Customer Segmentation:** Automatically group customers by behavior and churn risk using built-in clustering and predictive models.
- **Campaign Builder:** Create rule-driven discount or email campaigns with dynamic Twig-based templates and AI-suggested subject lines.
- **Background Analysis with Web Workers:** Run customer data analysis and scoring asynchronously to keep the UI smooth.
- **RESTful JSON API:** Full backend API supporting customer data retrieval, campaign management, and analysis.
- **Dynamic Email Preview & Generation:** Generate personalized marketing emails with Twig templating and AI-powered content suggestions.
- **Smart Coupon Code Generator:** Create unique, limited-use discount codes tied to campaigns.
- **Campaign History & Logs:** Track sent campaigns, customer responses, and engagement metrics.

---

## Tech Stack

- **Backend:** PHP (vanilla or Symfony components), RESTful API returning JSON
- **Frontend:** Twig templating engine, Foundation CSS framework, Vanilla JavaScript (Ajax + Web Workers)
- **Database:** MySQL or SQLite for storing customers, campaigns, and logs
- **Other:** Web Workers for async data processing, Twig for email and UI templating

---

## Project Structure

CRMAIze/
├── public/
│ └── index.php # Front controller, routing entry point
├── templates/
│ ├── dashboard.twig
│ ├── campaign_form.twig
│ └── emails/
│ └── campaign_template.twig
├── src/
│ ├── Controller/ # REST API controllers
│ ├── Service/ # Business logic (AI segmentation, prediction)
│ ├── Repository/ # Data access layer
│ └── Model/ # Domain models (Customer, Campaign, etc.)
├── assets/
│ ├── js/
│ │ └── analysis.worker.js
│ └── css/
├── data/
│ └── customers.json # Sample or imported customer data
├── README.md
└── composer.json

---

## High-Level Architecture

1. **Frontend UI:** Twig templates styled with Foundation, enhanced with Ajax calls and Web Workers for smooth user experience.
2. **Backend API:** PHP RESTful endpoints serve JSON data for customers, campaigns, and analytics.
3. **AI Layer:** Embedded AI logic in backend services for customer segmentation, churn prediction, and campaign suggestions.
4. **Data Storage:** Relational database holding customer records, campaign details, and engagement logs.
5. **Async Processing:** Web Workers handle heavy analysis tasks without blocking UI rendering.

---

## How It Works

- Customers’ purchase data is imported or mocked and stored in the database.
- The AI module runs segmentation and churn-risk scoring asynchronously.
- Marketers build campaigns by selecting target segments and defining discounts or emails.
- The system generates personalized emails with Twig templates and optional AI-generated subject lines.
- Campaigns can be previewed, sent (simulated), and tracked.
- The dashboard visualizes KPIs, customer health, and campaign performance in real-time.

---

## Getting Started

### Prerequisites

- PHP 7.4+ with PDO and SQLite extensions
- Composer (PHP package manager)
- Web server (Apache/Nginx) or built-in PHP server for development

### Quick Installation

```bash
# Clone the repository
git clone https://github.com/yourusername/CRMAIze.git
cd CRMAIze

# Run the installation script
php install.php

# Install dependencies
composer install

# Import sample data
php scripts/import_data.php

# Start the development server
php -S localhost:8000 -t public

# Open http://localhost:8000 in your browser
```

### Manual Installation

If you prefer to set up manually:

1. **Install dependencies:**

   ```bash
   composer install
   ```

2. **Configure environment:**

   ```bash
   cp env.example .env
   # Edit .env file if needed
   ```

3. **Import sample data:**

   ```bash
   php scripts/import_data.php
   ```

4. **Start the server:**
   ```bash
   php -S localhost:8000 -t public
   ```

### Database Configuration

By default, CRMAIze uses SQLite for simplicity. The database file will be created automatically at `data/crmaize.db`.

For MySQL, update your `.env` file:

```
DB_DSN=mysql:host=localhost;dbname=crmaize;charset=utf8mb4
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

---

## Implementation Status

### ✅ Completed Features

- **AI-Based Customer Segmentation:** ✅ Implemented with automatic customer grouping and churn risk analysis
- **Campaign Builder:** ✅ Basic campaign creation with email and discount campaigns
- **Background Analysis with Web Workers:** ✅ Async customer data processing implemented
- **RESTful JSON API:** ✅ Full API endpoints for customers, campaigns, and analytics
- **Dynamic Email Preview & Generation:** ✅ Twig-based email templates with AI suggestions
- **Smart Coupon Code Generator:** ✅ Basic coupon generation and discount management
- **Campaign History & Logs:** ✅ Database structure and logging system in place

### 🚧 Roadmap (Next Priorities)

- **User Authentication & Roles:** ✅ Completed - Login system and role-based access implemented
- **Real Email Integration:** ✅ Completed - SMTP integration with PHPMailer implemented
- **Enhanced Campaign Features:** 🔄 In Progress - Adding scheduling, A/B testing, and advanced AI
- **Data Import/Export:** ⏳ Planned - CSV import/export for customer data and campaigns
- **Advanced Analytics:** ⏳ Planned - PDF reports and data visualization charts
- **Mobile UI & PWA:** ⏳ Planned - Mobile-friendly enhancements and progressive web app support
