# Laravel 12 Development Guide

This document contains comprehensive information about Laravel 12 features, changes, and development standards.

## Quick Reference Links
- [Laravel 12 Release Notes](https://laravel.com/docs/12.x/releases)
- [Laravel 12 Upgrade Guide](https://laravel.com/docs/12.x/upgrade)
- [Laravel 12 Contribution Guidelines](https://laravel.com/docs/12.x/contributions)
- [Laravel 12 Starter Kits](https://laravel.com/docs/12.x/starter-kits)
- [Laravel 12 Frontend Documentation](https://laravel.com/docs/12.x/frontend)
- [Carbon 3 Documentation](https://carbon.nesbot.com/docs/)

## Content

### Laravel 12 Overview

Laravel 12 represents a significant milestone in Laravel's evolution, focusing on **minimal breaking changes** while introducing powerful new development tools. Released as part of Laravel's annual major release cycle (around Q1 each year), Laravel 12 emphasizes quality-of-life improvements without significant code disruption.

#### Key Support Information
- **Bug fixes**: Provided for 18 months
- **Security fixes**: Provided for 2 years  
- **PHP Support**: Versions 8.2 - 8.4

#### Major New Features

**New Application Starter Kits**
Laravel 12 introduces comprehensive starter kits for three major frontend technologies:

1. **React Starter Kit**
   - Inertia 2 integration
   - TypeScript support
   - shadcn/ui components
   - Tailwind CSS styling

2. **Vue Starter Kit**  
   - Inertia 2 integration
   - TypeScript support
   - shadcn/ui components
   - Tailwind CSS styling

3. **Livewire Starter Kit**
   - Flux UI component library
   - Laravel Volt integration
   - Server-side rendering capabilities

**Authentication Enhancements**
- Built-in authentication system across all starter kits
- **WorkOS AuthKit** variants offering:
  - Social authentication (Google, Microsoft, GitHub, Apple)
  - Passkeys support
  - Single Sign-On (SSO) capabilities
  - Free authentication for up to 1 million monthly active users

#### Important Deprecations
- **Laravel Breeze** and **Laravel Jetstream** will no longer receive updates
- Developers should migrate to the new starter kits for continued support

### Upgrade Guide: Laravel 11 to Laravel 12

#### Upgrade Overview
- **Estimated upgrade time**: 5 minutes
- **Recommended tool**: Laravel Shift for automated upgrades
- **Difficulty**: Minimal breaking changes focus

#### Key Dependencies to Update

```bash
"laravel/framework": "^12.0"
"phpunit/phpunit": "^11.0" 
"pestphp/pest": "^3.0"
```


#### Breaking Changes by Impact Level

**High Impact Changes**

1. **Laravel Installer Update**
   ```bash
   # Update existing installer
   composer global update laravel/installer
   
   # Or reinstall completely
   curl -s "https://laravel.build/my-app" | bash
   ```

2. **Models and UUIDs**
   - UUIDs now use **version 7** of the UUID specification
   - For backward compatibility, use `HasVersion4Uuids` trait:
   ```php
   use Laravel\Framework\Concerns\HasVersion4Uuids;
   
   class User extends Model {
       use HasVersion4Uuids;
   }
   ```

**Medium Impact Changes**

1. **Authentication Changes**
   - `DatabaseTokenRepository` constructor now expects `$expires` parameter in **seconds** (not minutes)

2. **Concurrency Updates**
   - Result indexing behavior changed for associative arrays

3. **Container Dependency Injection**
   - Container now respects default values of class properties
   ```php
   // Laravel 12 behavior change
   class Example {
       public function __construct(public ?Carbon $date = null) {}
   }
   
   $example = resolve(Example::class);
   // $example->date will now be null instead of Carbon instance
   ```

**Low Impact Changes**

1. **Carbon 3.x Requirement**
   - Support for Carbon 2.x **removed**
   - All applications must use Carbon 3.x
   - Main change: `createFromTimestamp()` now defaults to UTC timezone

2. **Image Validation**
   - SVG files now **excluded by default** from `image` validation rule
   - Explicitly allow SVGs if needed

3. **Database Schema Methods**
   - Multi-schema inspection behavior updated
   - `Schema::getTables()`, `Schema::getViews()`, `Schema::getTypes()` now include all schemas by default

4. **Request Merging**
   - `$request->mergeIfMissing()` now supports nested array merging with dot notation

#### Migration Steps

1. **Update Dependencies**
   ```bash
   composer update
   ```

2. **Run Database Migrations**
   ```bash
   php artisan migrate
   ```

3. **Clear Application Caches**
   ```bash
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```

4. **Test Thoroughly**
   - Review authentication flows
   - Check UUID generation
   - Verify Carbon date/time handling
   - Test image upload functionality

### Development Standards and Contribution Guidelines

#### Core Development Principles
Laravel 12 maintains strict standards for development and contributions, ensuring high-quality, maintainable code across all projects.

#### Coding Standards

**PSR Compliance**
- **PSR-2**: Coding standard compliance required
- **PSR-4**: Autoloading standard implementation
- **Automated Styling**: StyleCI automatically handles code formatting

**Documentation Standards**
- Use precise **PHPDoc annotations**
- Provide clear parameter and return type documentation
- Include examples for complex functionality

#### Contribution Workflow

**Branch Strategy**
- **Bug fixes**: Target latest supported version (currently 12.x)
- **Minor features**: Submit to stable branch (backward-compatible)
- **Major features**: Submit to master branch (may include breaking changes)

**Quality Requirements**
- Clear, detailed issue descriptions
- Include relevant code samples
- Comprehensive testing coverage
- Follow existing project patterns

#### Communication Channels

**Preferred Discussion Platforms**
- **GitHub Discussions**: Project-specific discussions
- **Laracasts Forums**: Community help and tutorials
- **Laravel.io Forums**: General Laravel discussions
- **StackOverflow**: Technical Q&A
- **Discord**: Real-time community chat

#### Security Best Practices

**Vulnerability Reporting**
- Report security issues directly to Taylor Otwell via email
- **Do not** post security vulnerabilities publicly
- Expect prompt addressing of security concerns

**Development Security**
- Never commit secrets or API keys
- Use environment variables for sensitive data
- Follow Laravel's built-in security features
- Validate and sanitize all user inputs

#### Code of Conduct

**Professional Standards**
- Be tolerant of opposing viewpoints
- Avoid personal attacks or inflammatory language
- Assume good intentions from all contributors
- Create an inclusive, harassment-free environment

#### Best Practices for Laravel 12 Development

1. **Follow Laravel Conventions**
   - Use Eloquent ORM patterns
   - Implement proper service container usage
   - Follow MVC architecture principles

2. **Testing Requirements**
   - Write comprehensive unit tests
   - Include feature tests for user-facing functionality
   - Maintain high test coverage

3. **Performance Considerations**
   - Optimize database queries
   - Use appropriate caching strategies
   - Follow Laravel's performance best practices

### Laravel 12 Starter Kits

Laravel 12 introduces three comprehensive starter kits designed to jumpstart modern web application development with different frontend technologies.

#### Available Starter Kits

**1. React Starter Kit**
- **Framework**: React 19 with TypeScript
- **Routing**: Inertia.js for seamless SPA experience
- **Styling**: Tailwind CSS for utility-first styling
- **Components**: shadcn/ui component library
- **Layouts**: Sidebar and header layout variants
- **SSR**: Server-side rendering support

**2. Vue Starter Kit**
- **Framework**: Vue 3 with Composition API and TypeScript
- **Routing**: Inertia.js integration
- **Styling**: Tailwind CSS
- **Components**: shadcn-vue component library
- **Layouts**: Multiple layout customization options
- **SSR**: Server-side rendering capable

**3. Livewire Starter Kit**
- **Framework**: Laravel Livewire 3
- **Styling**: Tailwind CSS
- **Components**: Flux UI component library
- **Architecture**: Traditional Livewire component system
- **Layouts**: Sidebar and header layout options
- **Performance**: Optimized for server-side reactivity

#### Installation Process

**1. Install Laravel CLI**
```bash
composer global require laravel/installer
```

**2. Create New Application**
```bash
laravel new my-app
```

**3. Install Dependencies**
```bash
cd my-app
npm install && npm run build
```

**4. Start Development**
```bash
composer run dev
```

#### Authentication Options

**Default Laravel Authentication**
All starter kits include:
- User registration
- Login/logout functionality
- Password reset
- Email verification
- Profile management

**WorkOS AuthKit Integration**
Enhanced authentication option providing:
- **Social Authentication**: Google, Microsoft, GitHub, Apple
- **Passkey Authentication**: Modern passwordless authentication
- **Magic Auth**: Email-based authentication links
- **Single Sign-On (SSO)**: Enterprise SSO integration
- **Free Tier**: Up to 1 million monthly active users

#### Customization and Development

**Full Code Access**
- All frontend and backend code included in your application
- Complete customization capability
- No vendor lock-in

**Layout Variants**
- **Sidebar Layout**: Navigation sidebar with collapsible options
- **Header Layout**: Top navigation bar configuration
- **Custom Layouts**: Build your own layout patterns

**Frontend Asset Management**
- **React/Vue**: Located in `resources/js/` directory
- **Livewire**: Blade templates in `resources/views/`
- **Asset Compilation**: Vite for fast development builds
- **Production**: Optimized builds for deployment

#### Development Workflow

**React/Vue Development**
```bash
# Start development server
npm run dev

# Build for production
npm run build

# Run tests
npm test
```

**Livewire Development**
```bash
# Start Laravel development server
php artisan serve

# Watch for asset changes
npm run dev

# Run Laravel tests
php artisan test
```

#### Key Advantages

1. **Rapid Development**: Skip boilerplate setup
2. **Modern Stack**: Latest versions of all technologies
3. **Authentication Ready**: Built-in user management
4. **Production Ready**: Optimized for deployment
5. **Flexible**: Full customization capabilities
6. **Community Support**: Laravel ecosystem integration

### Performance Optimization for Laravel 12

Laravel 12 applications can achieve significant performance improvements through strategic optimization techniques. Implementing these practices can reduce response time by 60-70% and cut database load by 50%.

#### Caching Strategies

**Application-Level Caching**
```bash
# Route caching for production
php artisan route:cache

# Configuration caching
php artisan config:cache

# View template caching
php artisan view:cache

# Complete optimization command
php artisan optimize
```

**Cache Driver Optimization**
- **Redis**: Recommended for cache and session storage
- **Memcached**: Alternative high-performance option
- **File Cache**: Only for development environments

**Application Cache Usage**
```php
// Cache expensive operations
$users = Cache::remember('active_users', 3600, function () {
    return User::where('active', true)->get();
});

// Cache database queries
$posts = Cache::tags(['posts'])->remember('recent_posts', 1800, function () {
    return Post::with('author')->latest()->take(10)->get();
});
```

#### Database Optimization

**Query Optimization**
```php
// Prevent N+1 query problems
$posts = Post::with('author', 'comments')->get();

// Use specific columns
$users = User::select('id', 'name', 'email')->get();

// Paginate large datasets
$posts = Post::paginate(20);
```

**Database Indexing**
```sql
-- Index frequently queried columns
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_posts_created_at ON posts(created_at);
CREATE INDEX idx_posts_user_id ON posts(user_id);
```

**Advanced Database Techniques**
```php
// Use database transactions for multiple operations
DB::transaction(function () {
    User::create($userData);
    Profile::create($profileData);
});

// Implement read/write database connections
// Configure in config/database.php
'mysql' => [
    'read' => [
        'host' => ['read-host1', 'read-host2'],
    ],
    'write' => [
        'host' => ['write-host'],
    ],
]
```

#### Composer and Autoloader Optimization

**Production Optimizations**
```bash
# Optimize autoloader for production
composer dump-autoload -o

# Install production dependencies only
composer install --no-dev --optimize-autoloader

# Remove unused packages
composer remove package-name
```

#### Frontend Asset Optimization

**Vite Configuration**
```javascript
// vite.config.js
export default defineConfig({
    build: {
        minify: 'terser',
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['react', 'vue'],
                }
            }
        }
    }
});
```

**Asset Delivery**
- **CDN Integration**: Serve static assets via CDN
- **Image Optimization**: Implement lazy loading and WebP format
- **Code Splitting**: Split JavaScript bundles for faster loading

#### Server-Level Optimizations

**PHP Configuration**
```ini
; Enable OPcache
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000

; JIT Compiler (PHP 8.2+)
opcache.jit_buffer_size=256M
opcache.jit=1255
```

**Web Server Configuration**
```nginx
# Nginx optimization
gzip on;
gzip_types text/css application/javascript application/json;

# Enable HTTP/2
listen 443 ssl http2;

# Asset caching
location ~* \.(js|css|png|jpg|jpeg|gif|svg)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

#### Advanced Performance Techniques

**Queue System Implementation**
```php
// Queue heavy operations
dispatch(new ProcessLargeDataset($data));

// Use horizon for queue monitoring
php artisan horizon
```

**Lazy Loading and Eager Loading**
```php
// Lazy load relationships when needed
$user->posts()->where('published', true)->get();

// Eager load to prevent N+1 queries
$users = User::with(['posts' => function ($query) {
    $query->where('published', true);
}])->get();
```

#### Performance Monitoring

**Laravel Debugging Tools**
```bash
# Install Laravel Telescope for development
composer require laravel/telescope

# Install Laravel Horizon for queue monitoring
composer require laravel/horizon

# Profiling with Laravel Debugbar
composer require barryvdh/laravel-debugbar
```

**External Monitoring**
- **Blackfire.io**: Professional PHP profiler
- **New Relic**: Application performance monitoring
- **Sentry**: Error tracking and performance monitoring

#### Production Deployment Checklist

**Pre-Deployment Commands**
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Asset compilation
npm run build
```

**Performance Validation**
- Load testing with tools like Apache Bench or Artillery
- Database query analysis with Laravel Telescope
- Memory usage monitoring with Xdebug
- Response time measurement with browser dev tools

### Carbon 3 Integration in Laravel 12

Laravel 12 requires Carbon 3.x, dropping support for Carbon 2.x. This upgrade brings enhanced localization, improved timezone handling, and better testing capabilities.

#### Key Carbon 3 Features

**Enhanced Localization Support**
```php
// 750+ supported locales
$date = Carbon::parse('2024-01-01');
$date->locale('fr'); // French
$date->locale('ar'); // Arabic
$date->locale('zh_CN'); // Chinese Simplified

// Embedded locale formats
$date->translatedFormat('F Y'); // Janvier 2024 (in French)
$date->isoFormat('MMMM YYYY'); // Full month name in current locale
```

**Improved Timezone Handling**
```php
// setTimezone() vs shiftTimezone()
$utc = Carbon::parse('2024-01-01 12:00:00', 'UTC');

// setTimezone() - changes timezone without changing the actual moment
$est = $utc->copy()->setTimezone('America/New_York'); // 07:00:00 EST

// shiftTimezone() - shifts the time value to maintain clock time
$shifted = $utc->copy()->shiftTimezone('America/New_York'); // 12:00:00 EST

// Default timezone behavior change in Laravel 12
$timestamp = Carbon::createFromTimestamp(1640995200); // Now defaults to UTC
```

**Factory Pattern Implementation**
```php
// Create a factory with default settings
$factory = new Factory([
    'locale' => 'fr',
    'timezone' => 'Europe/Paris',
]);

// Use factory to create consistent date instances
$date = $factory->now();
$birthday = $factory->parse('1990-05-15');
```

#### Migration from Carbon 2 to Carbon 3

**Breaking Changes**
```php
// Carbon 2 behavior
$timestamp = Carbon::createFromTimestamp(1640995200); 
// Used date_default_timezone_get()

// Carbon 3 behavior  
$timestamp = Carbon::createFromTimestamp(1640995200);
// Now defaults to UTC timezone

// To maintain Carbon 2 behavior in Carbon 3
$timestamp = Carbon::createFromTimestamp(1640995200, date_default_timezone_get());
```

**Updated API Usage**
```php
// Enhanced localization methods
$date = Carbon::now();

// Dynamic translation methods
$date->monthName; // Full month name in current locale
$date->shortMonthName; // Abbreviated month name
$date->dayName; // Full day name
$date->shortDayName; // Abbreviated day name

// Ordinal suffix support
$date->format('jS F Y'); // "1st January 2024"
```

#### Testing Improvements

**Enhanced Testing Aids**
```php
// More granular control over time mocking
Carbon::setTestNow('2024-01-01 12:00:00');

// Test specific scenarios
Carbon::setTestNowAndTimezone('2024-01-01 12:00:00', 'America/New_York');

// Clear test time
Carbon::setTestNow();
```

#### Laravel 12 Carbon Integration

**Service Container Changes**
```php
// Laravel 12 dependency injection respects default values
class EventService {
    public function __construct(public ?Carbon $scheduledAt = null) {}
}

// In Laravel 12, if no Carbon instance provided:
$service = app(EventService::class);
$service->scheduledAt; // Will be null, not auto-resolved Carbon instance
```

**Recommended Patterns**
```php
// Use Carbon facades for consistency
use Carbon\Carbon;

// Create dates with explicit timezone
$userTimezone = 'America/Los_Angeles';
$appointmentTime = Carbon::createFromFormat(
    'Y-m-d H:i:s',
    '2024-01-01 15:30:00',
    $userTimezone
);

// Convert to UTC for database storage
$utcTime = $appointmentTime->utc();

// Convert back to user timezone for display
$displayTime = $utcTime->setTimezone($userTimezone);
```

#### Best Practices for Laravel 12

**Database Storage**
```php
// Always store dates in UTC
$event = new Event();
$event->scheduled_at = $userInputDate->utc();
$event->save();

// Apply user timezone when retrieving
$event = Event::find(1);
$localTime = $event->scheduled_at->setTimezone($user->timezone);
```

**Localization in Views**
```php
// Blade template example
{{ $event->created_at->locale(app()->getLocale())->translatedFormat('l, F j, Y') }}

// Multi-language date formatting
@if(app()->getLocale() === 'ar')
    {{ $date->locale('ar')->translatedFormat('l، j F Y') }}
@else
    {{ $date->locale('en')->translatedFormat('l, F j, Y') }}
@endif
```

**API Response Formatting**
```php
// Consistent API date formatting
class EventResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'scheduled_at' => $this->scheduled_at->toISOString(),
            'scheduled_at_human' => $this->scheduled_at
                ->setTimezone($request->user()->timezone ?? 'UTC')
                ->translatedFormat('F j, Y \a\t g:i A'),
        ];
    }
}
```

### Frontend Development in Laravel 12

Laravel 12 provides flexible frontend development options, supporting both PHP-driven and JavaScript framework approaches with modern tooling and optimal developer experience.

#### Frontend Development Approaches

**1. PHP-Based Frontend Development**
```php
// Traditional Blade templates
<!-- resources/views/welcome.blade.php -->
@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Welcome to Laravel 12</h1>
        <p>{{ $message }}</p>
    </div>
@endsection

// Laravel Livewire for dynamic interfaces
class Counter extends Component
{
    public $count = 0;

    public function increment()
    {
        $this->count++;
    }

    public function render()
    {
        return view('livewire.counter');
    }
}
```

**2. JavaScript Framework Integration**
```javascript
// React with Inertia.js
import { Head } from '@inertiajs/react'

export default function Welcome({ message }) {
    return (
        <>
            <Head title="Welcome" />
            <div className="container">
                <h1>Welcome to Laravel 12</h1>
                <p>{message}</p>
            </div>
        </>
    )
}

// Vue with Inertia.js
<template>
    <Head title="Welcome" />
    <div class="container">
        <h1>Welcome to Laravel 12</h1>
        <p>{{ message }}</p>
    </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'

defineProps({
    message: String
})
</script>
```

#### Asset Bundling with Vite

**Vite Configuration**
```javascript
// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
        react(),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['react', 'react-dom'],
                },
            },
        },
    },
});
```

**Asset Loading in Blade**
```blade
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Laravel 12</title>
        
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        @yield('content')
    </body>
</html>
```

#### Modern Development Features

**Hot Module Replacement (HMR)**
```bash
# Development server with HMR
npm run dev

# Watch for changes
npm run watch

# Production build
npm run build
```

**CSS Handling**
```css
/* resources/css/app.css */
@import 'tailwindcss/base';
@import 'tailwindcss/components';
@import 'tailwindcss/utilities';

/* Custom styles */
.btn-primary {
    @apply bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded;
}
```

#### Inertia.js Integration

**Server-Side Setup**
```php
// Handle Inertia requests
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'message' => 'Hello from Laravel 12!',
        'user' => auth()->user(),
    ]);
});

// Middleware for Inertia
class HandleInertiaRequests extends Middleware
{
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user(),
            ],
            'flash' => [
                'message' => fn () => $request->session()->get('message')
            ],
        ]);
    }
}
```

**Client-Side Setup**
```javascript
// app.js for React
import { createRoot } from 'react-dom/client'
import { createInertiaApp } from '@inertiajs/react'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'

createInertiaApp({
    title: (title) => `${title} - Laravel 12`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.jsx`, import.meta.glob('./Pages/**/*.jsx')),
    setup({ el, App, props }) {
        const root = createRoot(el)
        root.render(<App {...props} />)
    },
    progress: {
        color: '#4B5563',
    },
})
```

#### Alpine.js for Lightweight Interactivity

```html
<!-- Simple Alpine.js component -->
<div x-data="{ open: false }">
    <button @click="open = !open" class="btn">
        Toggle Menu
    </button>
    
    <div x-show="open" x-transition>
        <ul class="menu">
            <li><a href="/dashboard">Dashboard</a></li>
            <li><a href="/profile">Profile</a></li>
        </ul>
    </div>
</div>

<!-- Advanced Alpine component -->
<div x-data="searchComponent()">
    <input x-model="query" @input.debounce.300ms="search()" placeholder="Search...">
    
    <div x-show="results.length > 0">
        <template x-for="result in results" :key="result.id">
            <div x-text="result.title" @click="selectResult(result)"></div>
        </template>
    </div>
</div>

<script>
function searchComponent() {
    return {
        query: '',
        results: [],
        async search() {
            if (this.query.length > 2) {
                const response = await fetch(`/api/search?q=${this.query}`);
                this.results = await response.json();
            }
        },
        selectResult(result) {
            window.location.href = `/items/${result.id}`;
        }
    }
}
</script>
```

#### CSS Frameworks Integration

**Tailwind CSS Setup**
```javascript
// tailwind.config.js
/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    "./resources/**/*.jsx",
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
```

**Bootstrap Integration**
```javascript
// Bootstrap with Vite
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap';

// Custom SCSS
import './app.scss';
```

#### Best Practices for Frontend Development

**1. Asset Organization**
```
resources/
├── css/
│   ├── app.css
│   └── components/
├── js/
│   ├── app.js
│   ├── components/
│   └── Pages/
└── views/
    ├── layouts/
    └── components/
```

**2. Component Reusability**
```jsx
// Reusable React components
export const Button = ({ variant = 'primary', children, ...props }) => {
    const baseClasses = 'px-4 py-2 rounded font-medium';
    const variantClasses = {
        primary: 'bg-blue-500 text-white hover:bg-blue-600',
        secondary: 'bg-gray-500 text-white hover:bg-gray-600',
    };
    
    return (
        <button 
            className={`${baseClasses} ${variantClasses[variant]}`}
            {...props}
        >
            {children}
        </button>
    );
};
```

**3. Performance Optimization**
```javascript
// Code splitting with React.lazy
const Dashboard = React.lazy(() => import('./Pages/Dashboard'));
const Profile = React.lazy(() => import('./Pages/Profile'));

// Lazy loading with Suspense
<Suspense fallback={<div>Loading...</div>}>
    <Routes>
        <Route path="/dashboard" element={<Dashboard />} />
        <Route path="/profile" element={<Profile />} />
    </Routes>
</Suspense>
```

---

## Summary

Laravel 12 represents a mature, stability-focused release that prioritizes developer experience and modern web development practices. With minimal breaking changes, comprehensive starter kits, and enhanced frontend integration, Laravel 12 continues to be the PHP framework of choice for building robust, scalable web applications.

Key takeaways for developers:
- **Easy Upgrade Path**: Most applications can upgrade with minimal code changes
- **Modern Frontend Options**: Choose between PHP-driven (Livewire) or JavaScript frameworks (React/Vue)
- **Enhanced Authentication**: WorkOS integration provides enterprise-level auth features
- **Performance Ready**: Built-in optimization tools and best practices
- **Carbon 3**: Improved date/time handling with better localization

This guide provides the foundation for developing with Laravel 12 while following current best practices and coding standards.