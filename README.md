# Transport Management System (TMS PRO) - Ethiopia 🇪🇹

A modern, high-performance Transport Management System built for the Ethiopian logistics industry. This system features a premium glassmorphic UI and a full integration with the **Chapa Payment Gateway** for automated real-time transactions.

## 🌟 Key Features
- **Real-Time Payments**: Integrated with **Chapa** (Telebirr, CBE Birr, Amole, etc.).
- **Automatic Ticket Generation**: Printable boarding passes generated instantly after payment.
- **Admin Command Center**:
  - Full User Management (Promote/Demote/Remove).
  - Fleet & Driver Management.
  - Route Optimization with Fare Calculation.
  - Revenue Analytics (Total ETB collected).
- **User Dashboard**:
  - Instant Ride Booking.
  - Secure Payment Portal.
  - Profile & Security Settings.
- **Modern Aesthetics**: Sleek dark-mode design with fluid animations.

## 🛠️ Installation & Setup

### 1. Database Configuration
1. Create a database named `tms_db` in your MySQL server.
2. Import the `sql/setup.sql` file.

### 2. API Integration
1. Register at [Chapa Dashboard](https://dashboard.chapa.co/).
2. Open `includes/config.php`.
3. Enter your `CHAPA_SECRET_KEY` and `CHAPA_PUBLIC_KEY`.

### 3. App Deployment
1. Move the project folder to your server's root (e.g., `htdocs`).
2. Update the `BASE_URL` in `includes/config.php` to match your domain.
3. Update `includes/db.php` with your database credentials.

## 📁 Repository Structure
- `assets/`: UI design system (CSS/Images).
- `includes/`: Core logic and configurations.
- `sql/`: Database schema and demo seeds.
- `process_payment.php`: Gateway bridge.
- `payment_verify.php`: Secure transaction auditor.
- `ticket.php`: Ticket rendering engine.

## 🔒 Security
- Password hashing using `BCRYPT`.
- Role-Based Access Control (RBAC).
- Secure API verification callbacks.

---
*Developed for the future of Ethiopian Transport.*
