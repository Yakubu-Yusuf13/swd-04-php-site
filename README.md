# Prime Cleaners Ltd

A responsive, multi-page website for a fictional cleaning business, built as a hands-on PHP 8 learning project. It includes shared PHP layout files, data-driven service and team pages, and a validated contact form that stores submissions locally.

## Features

- Shared header, footer, site configuration, and navigation across pages
- Responsive home, about, services, service-detail, and contact pages
- Service cards and team profiles rendered from PHP arrays
- Escaped dynamic output and formatted service prices
- Server-side contact form validation with sticky values and field-level errors
- Session-based CSRF tokens, JSONL message storage, and Post/Redirect/Get after a successful submission
- Local-only password-protected message viewer with newest-first sorting
- Apache access rule to deny direct web requests for stored messages

## Tech stack

- PHP 8.2 (strict types, sessions, arrays, helper functions, and includes)
- Apache through XAMPP
- HTML5 and responsive CSS
- JSON Lines (`.jsonl`) for local contact-form submissions

## Run with XAMPP

1. Install and start **Apache** in the XAMPP Control Panel.
2. Put the project folder at:

   ```text
   C:\xampp\htdocs\swd\04-php-site
   ```

3. Open the home page:

   ```text
   http://localhost/swd/04-php-site/
   ```

4. Open the message viewer locally at:

   ```text
   http://localhost/swd/04-php-site/messages.php
   ```

   The sample local-only password is `change-this-local-password`. Change `MESSAGES_ADMIN_PASSWORD` in `includes/config.php` for your own local practice.

The contact form creates `storage/messages.jsonl` on the first successful submission. The file is ignored by Git and the storage folder includes an Apache rule that denies direct web access.

> **Learning-only security note:** The message viewer password is hard-coded in a PHP source file. This is not production-grade authentication: source code can be copied, committed, exposed in backups, or disclosed in a source leak, and this example has no user accounts, password hashing, rate limiting, or robust access-control system. Do not expose it on a public server. Production applications should use securely configured credentials, hashed passwords, HTTPS, and a proper authentication and authorization system.

## Deploy a read-only demo with Vercel

This project includes a Vercel configuration using the community-maintained `vercel-php` runtime; PHP is not an official Vercel runtime. Review the runtime and its limits before deploying.

1. Import `Yakubu-Yusuf13/swd-04-php-site` in the Vercel dashboard.
2. Keep the project root as the repository root and use the default framework/build settings.
3. Deploy. The runtime configured in `vercel.json` handles the PHP pages.

The Vercel deployment is intentionally a demo: the contact form is replaced with an email link because Vercel function filesystems and sessions are not durable storage; `messages.php` returns 404 there. Contact submissions are not saved. Continue using XAMPP for the file-backed form and local inbox, or add a database and production-grade authentication before enabling those features on a hosted site.

## Project structure

```text
04-php-site/
├── about.php
├── contact.php
├── index.php
├── messages.php
├── service.php
├── services.php
├── README.md
├── .gitignore
├── vercel.json
├── api/
│   └── index.php
├── assets/
│   └── css/
│       └── style.css
├── includes/
│   ├── config.php
│   ├── data.php
│   ├── footer.php
│   ├── functions.php
│   └── header.php
└── storage/
    └── .htaccess
```

`storage/messages.jsonl` is created at runtime when the first contact form submission is saved; it is intentionally excluded from version control.

## What I learned

Fill these in as you work through the project:

- **PHP includes and shared layouts:** [What did you learn?]
- **Arrays, loops, and data-driven pages:** [What did you learn?]
- **Form validation and sticky values:** [What did you learn?]
- **Sessions, CSRF tokens, and redirects:** [What did you learn?]
- **Escaping output and protecting stored data:** [What did you learn?]
- **One thing I want to improve next:** [Your idea]
