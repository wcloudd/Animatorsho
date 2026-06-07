# Animatorsho MVP Plan

## 1. Project Summary

Animatorsho is a Persian RTL educational product website for selling and delivering the main animation course. The current MVP is intentionally small and conversion-focused.

The website is not a full educational portal yet. It is a clean mobile-first product landing and purchase system for the Animatorsho course.

Main goal:

> A user enters the site, understands the Animatorsho course, requests consultation or buys the course, then receives their SpotPlayer license in their profile.

---

## 2. Tech Stack

- Laravel
- React
- Inertia
- Tailwind
- SQLite (local development) / PostgreSQL (production) — see [PostgreSQL Production Deployment](postgresql-deploy.md)
- Laravel authentication starter
- Future admin panel can be built with a Laravel-friendly admin approach such as Filament.

Main design target:

- Persian RTL
- Mobile-first
- Primary width: 390px
- Clean product landing style similar in clarity to Duolingo and Brilliant

---

## 3. MVP Scope

### In Scope

- Product landing page for Animatorsho
- Buy course flow
- Zarinpal payment
- Manual card-to-card payment with admin approval
- Manual installment purchase request and staged access
- Register / Login
- User profile
- SpotPlayer license display
- Support tickets
- Free consultation form
- Admin management for orders, users, payments, consultation requests, tickets, and licenses

### Out of Scope for MVP

- Articles
- Forum
- Exercises and challenges
- Student community
- Gamification
- Full learning dashboard
- Full content library
- Advanced notifications
- Automatic installment gateway

These can be added later without changing the MVP direction.

---

## 4. Main Navigation

The bottom navigation currently has only three buttons:

1. **انیماتورشو**
2. **پشتیبان**
3. **پروفایل**

### Animatorsho

This is the main product landing page. It introduces the comprehensive Animatorsho course and guides users toward buying or requesting free consultation.

### Support

Users can submit tickets, see ticket history, and read replies.

### Profile

Users can see their purchases, SpotPlayer licenses, access status, personal information, and support-related links.

---

## 5. Main User Flow

### Purchase Flow

```text
Open Animatorsho landing page
↓
Understand course value
↓
Click buy CTA
↓
Login / Register if needed
↓
Choose package and payment method
↓
Pay with Zarinpal or submit card-to-card payment
↓
Payment verification / admin approval
↓
Access becomes active
↓
SpotPlayer license appears in Profile
```

### Consultation Flow

```text
Open Animatorsho landing page
↓
Click free consultation CTA
↓
Fill consultation form
↓
Request is stored for admin
↓
Admin follows up
↓
User may buy full course, one season, or installment plan
```

### Support Flow

```text
Login
↓
Open Support tab
↓
Submit ticket
↓
Admin replies
↓
User sees reply in Support tab
```

---

## 6. Product Structure

### Main Course

```text
Course: انیماتورشو
```

### Packages

```text
Package 1: دوره جامع انیماتورشو
Package 2: فصل اول — ساخت ساده انیمیشن با سیستم
Package 3: فصل دوم — آموزش طراحی
Package 4: فصل سوم — ساخت انیمیشن مثل نیم‌وجبی
Package 5: فصل چهارم — ساخت انیمیشن با گوشی
```

### Payment Option

```text
خرید اقساطی دوره جامع
```

Installment purchase is not a separate educational package. It is a payment option for the comprehensive course.

Correct user-facing profile example:

```text
دوره من: دوره جامع انیماتورشو
وضعیت پرداخت: اقساطی
دسترسی فعلی: فصل اول فعال
```

---

## 7. Product Landing Page Structure

Route recommendation:

```text
/
```

The first page of the website should be the Animatorsho product landing page, not a generic home dashboard.

### Recommended Sections

1. **Hero with video**
   - Full-width video or optimized loop
   - Main CTA: `خرید دوره جامع انیماتورشو`
   - Secondary CTA: `دریافت مشاوره رایگان`

2. **Problem and promise**
   - Explain that beginners often do not know where to start
   - Present Animatorsho as a clear step-by-step learning path

3. **Learning path**
   - Learn
   - Practice
   - Build

4. **Comprehensive course introduction**
   - Who it is for
   - What the learner will build
   - Why it is beginner-friendly

5. **Course sections**
   - Season 1: simple animation with computer
   - Season 2: drawing
   - Season 3: making animations like Nimvajabee
   - Season 4: animation with phone

6. **Student work preview**
   - A few selected examples only
   - Avoid making the page heavy

7. **Purchase plans**
   - Comprehensive course as the primary plan
   - Separate seasons as secondary options
   - Installment request option

8. **Free consultation form**
   - Can be a section or modal

9. **FAQ**
   - SpotPlayer access
   - Beginner level
   - Payment methods
   - Installments
   - Support

10. **Final CTA**
   - Encourage the user to start learning or request consultation

---

## 8. Checkout Page

The checkout page should be short and focused. It does not need to repeat the full course landing content.

Required content:

- Selected package name
- Price
- Included access
- Payment method selection
- Zarinpal payment
- Card-to-card instructions / receipt submission
- Installment request path
- Short explanation that SpotPlayer license will appear in profile after successful payment
- Login/Register requirement if user is not authenticated

Important rule:

> The backend must read package price and access rules from the database. Never trust price or package details sent from the frontend.

---

## 9. Payment Methods

### Zarinpal

- Online payment
- Backend creates pending order
- User is redirected to payment gateway
- Callback must be verified on backend
- Only after successful verification should access be activated

### Card-to-Card

- User uploads receipt or enters tracking details
- Order status becomes manual review
- Admin approves or rejects
- Access is activated only after admin approval

### Installment Purchase

MVP installment flow should be manual and admin-controlled.

Recommended flow:

```text
User submits installment request
↓
Admin reviews request
↓
Admin defines installment plan
↓
User pays first installment
↓
Admin approves payment
↓
Staged access is activated
```

Recommended staged access:

```text
Installment 1 → Season 1 active
Installment 2 → Season 2 active
Installment 3 → Season 3 active
Installment 4 → Season 4 active
```

---

## 10. Profile Page

The Profile tab should stay simple and useful.

Required sections:

- User information
- My purchases
- My licenses
- Access status
- Support shortcuts
- Consultation request status if useful

### License Display

For each license:

- Course/package name
- SpotPlayer license key
- Access status
- Copy button
- Short SpotPlayer usage guide

---

## 11. Support Page

Required sections:

- Submit new ticket
- Ticket categories:
  - Payment issue
  - SpotPlayer license issue
  - Educational question
  - Consultation follow-up
  - Other
- Ticket list
- Ticket status:
  - Open
  - Answered
  - Closed
- Ticket detail and replies

Users must only see their own tickets.

---

## 12. Consultation Form

Recommended fields:

- Full name
- Mobile number
- Age
- Current level:
  - Complete beginner
  - Know a little
  - Have made animation before
- Interested in:
  - Comprehensive course
  - Season 1
  - Installment purchase
  - Summer class
  - Free consultation
- Optional note

Admin statuses:

- New
- Contacted
- Needs follow-up
- Converted to purchase
- Closed

---

## 13. Admin Requirements

Admin should be able to manage:

- Users
- Courses and packages
- Orders
- Payments
- Zarinpal successful/failed payments
- Card-to-card manual approvals
- Installment requests and installment payments
- SpotPlayer licenses
- Consultation requests
- Support tickets

Admin pages do not need custom front-end design in the first MVP unless required.

---

## 14. Security Rules

Security is critical.

- User pages must require authentication.
- Users can only view their own orders, tickets, licenses, and profile data.
- Payment status must only be trusted after backend verification.
- Zarinpal callback must be verified server-side.
- Card-to-card and installment payments must not activate access before admin approval.
- Package price must be read from backend/database.
- Do not expose API keys or secrets in React/frontend code.
- SpotPlayer, payment, and SMS credentials must be stored in environment variables.
- Validate all forms on backend.
- Add file type and size restrictions for receipt uploads.
- Add rate limiting for consultation form, login, and support ticket submission.
- Avoid hardcoded localhost, IP addresses, Windows paths, or absolute storage URLs.

---

## 15. Design System Direction

### Visual Style

- Clean
- Warm
- Friendly
- Product-focused
- Mobile-first
- Trustworthy
- Slightly playful and animated

### Colors

```css
--color-purple: #6037A8;
--color-purple-soft: #F6EEFB;
--color-gold: #EBA239;
--color-gold-soft: #FEF7EB;
--color-green: #2DA160;
--color-green-soft: #F2F9F4;
--color-red: #ED5276;
--color-blue: #3AB5EC;
--color-bg: #FFF9F1;
--color-surface: #FFFFFF;
--color-text: #2E2442;
--color-muted: #7E728E;
```

### Fonts

- Main UI/body: IRANYekan
- Display/accent: Liana

Rules:

- IRANYekan for body, forms, checkout, support, profile, and readable text.
- Liana for hero titles, section titles, short badges, and playful labels.
- Do not use Liana for long paragraphs or form-heavy sections.

### Video and Animation

- Hero can use optimized MP4/WebM loop.
- Prefer WebM + MP4 fallback.
- Always include a poster image.
- Keep animation lightweight.
- Avoid heavy GIFs.

---

## 16. Build Priority

Recommended order:

1. Project rules and documentation
2. Fonts and design tokens
3. App shell with 3-button bottom navigation
4. Animatorsho product landing page
5. Authentication polishing
6. Consultation form
7. Checkout page
8. Payment result page
9. Profile page
10. License display
11. Support tickets
12. Admin management
13. Zarinpal integration
14. Manual card-to-card approval
15. Installment request and staged access
16. SpotPlayer integration

Always finish the current slice before starting the next one.

---

## 17. Verification Commands

After meaningful slices:

```powershell
php artisan optimize:clear
npm run build
php artisan test
```

Git checklist:

```powershell
git status
git add .
git commit -m "Short clear message"
```

Vite chunk warning rule:

- During early UI slices, do not prematurely optimize chunk size warnings.
- Use lazy loading / dynamic imports for heavy page-level components when the project grows.
- Do not optimize chunks prematurely during the first UI slices.
