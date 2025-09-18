# Laravel Calendar Management System

A modern calendar management system built with Laravel 12, Vue.js, and Inertia.js. Features booking management, availability scheduling, and planned Google Calendar integration.

## Features

- 🗓️ **Calendar Management**: Create and manage availabilities, unavailabilities, and bookings
- 👤 **Admin Dashboard**: Comprehensive admin interface for appointment management
- 🔄 **Google Calendar Integration**: Two-way sync with Google Calendar (planned)
- 📱 **Responsive Design**: Built with Tailwind CSS and shadcn-vue components
- ⚡ **Modern Stack**: Laravel 12, Vue 3, Inertia.js, TypeScript

## Tech Stack

- **Backend**: Laravel 12, Zap for Laravel (calendar package)
- **Frontend**: Vue.js 3 (Composition API), TypeScript, Inertia.js
- **Styling**: Tailwind CSS, shadcn-vue components
- **Database**: MySQL/PostgreSQL

## Installation

```bash
# Clone the repository
git clone https://github.com/timosz/calendar-management-system.git
cd laravel-calendar-management-system

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Build assets
npm run build

# Start development servers
composer run dev
```

## Development Status

🚧 **Work in Progress** - This project is currently under active development.

## License

MIT License
