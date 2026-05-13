# LegalFin

## Description

LegalFin is a full-stack digital banking platform built with PHP (MVC) and MySQL, developed as a 2nd-year integrated project at ESPRIT School of Engineering. It covers everything from account and card management to loans, investments, donations, and an AI-powered chatbot — all in a secure, responsive interface.

---

## Features

- 🔐 Multi-layer authentication (OTP, 2FA, Face ID, WhatsApp recovery)
- 🏦 Bank account creation with automatic IBAN generation and transfers
- 💳 Card management with spending limits and automatic tier upgrades
- 📊 Real-time transaction history and CSV export
- 🎯 Savings goals with progress tracking and email alerts
- 💸 Loan requests with AI credit scoring and electronic signature
- 📋 Chequebook requests and expiration reminders
- 🤝 Fundraising campaigns with Stripe payments and donation badges
- 📈 Investment marketplace with AI project recommendations
- 🤖 AI chatbot powered by Google Gemini
- 🛠️ Admin back office for full platform management

---

## Prerequisites

- PHP 7.4 or higher
- Apache web server (XAMPP recommended)
- MySQL database
- Composer

---

## Installation

1. Clone the repository:

```bash
git clone <repository-url> c:\xampp\htdocs\LegalFin
```

2. Install dependencies:

```bash
composer install
```

3. Configure your environment variables in `.env` (API keys for Gemini, Stripe, Twilio, DocuSeal, etc.).

4. Set up the database in `models/config.php` and import the schema:

```bash
mysql -u root -p legalfin < database/schema.sql
```

5. Start XAMPP and open:

```
http://localhost/LegalFin
```

---

## Usage

- **Clients** — manage accounts, cards, loans, goals, and chat with the AI assistant
- **Associations** — create fundraising campaigns and track donations
- **Investors** — browse and fund projects
- **Admins** — manage users, moderate content, and monitor the platform

---

## License

This project is under the MIT license. See the [LICENSE](LICENSE) file for more details.

---

## Authors

- Elyes Kalai
- Aymen Hamouda
- Youssef Kaouach
- Mariem Laabidi
- Sara Ben Aoueyenne
- Mouna Ncib

---

## Acknowledgments

- Thanks to our instructor and tutor **Ameni Hajri** for her guidance throughout this project.
- Thanks to the open source community for the tools and libraries used.
