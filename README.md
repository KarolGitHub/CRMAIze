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

```bash
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
```

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

### Deployment

CRMAIze is production-ready and can be deployed to various platforms:

#### **Render.com (Recommended)**

- Automatic deployments from Git
- Free tier available
- Built-in SSL certificates
- Easy environment variable management

#### **Heroku**

- Add-on support for databases
- Automatic scaling
- Git-based deployments

#### **VPS/Dedicated Server**

- Full control over the environment
- Custom domain and SSL setup
- Database optimization options

### Testing & Quality Assurance

#### **Accessibility Testing**

```bash
# Run the accessibility audit
php scripts/accessibility_audit.php

# Manual testing recommendations:
# 1. Test with screen readers (NVDA, JAWS, VoiceOver)
# 2. Test keyboard-only navigation
# 3. Test with high contrast mode enabled
# 4. Validate with WAVE or axe-core tools
```

#### **Performance Testing**

```bash
# Run performance tests
php scripts/performance_test.php

# Check database performance
php scripts/db_optimization.php
```

#### **Security Testing**

```bash
# Run security audit
php scripts/security_audit.php

# Test authentication and authorization
php scripts/auth_test.php
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

#### ✅ **Completed Features**

- **User Authentication & Roles:** ✅ Completed - Login system and role-based access implemented
- **Real Email Integration:** ✅ Completed - SMTP integration with PHPMailer implemented
- **Enhanced Campaign Features:** ✅ Completed - Scheduling, A/B testing, and advanced AI implemented
- **Data Import/Export:** ✅ Completed - CSV import/export for customer data and campaigns implemented
- **Advanced Analytics:** ✅ Completed - PDF reports and data visualization charts implemented
- **Mobile UI & PWA:** ✅ Completed - Mobile-responsive design and progressive web app functionality implemented
- **Accessibility (A11y):** ✅ Completed - WCAG 2.1 AA compliance with color contrast, keyboard navigation, and screen reader support

#### 🎯 **Next Priority Features**

1. **Advanced AI & Machine Learning**

   - [ ] **Predictive Churn Modeling:** Implement more sophisticated ML algorithms for churn prediction
   - [ ] **Customer Lifetime Value (CLV) Calculation:** Add CLV prediction and segmentation
   - [ ] **Personalized Content Recommendations:** AI-powered content suggestions for campaigns
   - [ ] **Sentiment Analysis:** Analyze customer feedback and social media mentions

2. **Enhanced Campaign Management**

   - [ ] **Multi-Channel Campaigns:** Support for SMS, push notifications, and social media
   - [ ] **Advanced A/B Testing:** Statistical significance testing and automated optimization
   - [ ] **Campaign Templates Library:** Pre-built templates for common scenarios
   - [ ] **Dynamic Content Blocks:** Real-time content personalization

3. **Advanced Analytics & Reporting**

   - [ ] **Real-time Dashboard:** Live metrics and alerts
   - [ ] **Custom Report Builder:** Drag-and-drop report creation
   - [ ] **Predictive Analytics:** Revenue forecasting and trend analysis
   - [ ] **ROI Tracking:** Campaign performance and revenue attribution

4. **Integration & API Enhancements**

   - [ ] **Third-party Integrations:** Shopify, WooCommerce, Magento connectors
   - [ ] **Webhook System:** Real-time data synchronization
   - [ ] **API Rate Limiting:** Production-ready API with authentication
   - [ ] **GraphQL API:** Modern API with flexible data queries

5. **Security & Performance**

   - [ ] **Advanced Security:** Two-factor authentication, audit logs
   - [ ] **Performance Optimization:** Caching, database optimization
   - [ ] **Scalability:** Microservices architecture preparation
   - [ ] **Backup & Recovery:** Automated backup system

6. **User Experience Enhancements**
   - [ ] **Onboarding Flow:** Interactive tutorial for new users
   - [ ] **Advanced Search:** Full-text search across all data
   - [ ] **Bulk Operations:** Mass campaign management
   - [ ] **Notification System:** Real-time alerts and updates

#### 🔮 **Future Vision**

- **AI-Powered Automation:** Fully automated campaign optimization
- **Omnichannel Marketing:** Unified customer experience across all channels
- **Advanced Personalization:** Hyper-personalized customer journeys
- **Predictive Customer Service:** Proactive customer support automation
