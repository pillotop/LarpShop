# LarpShop

LarpShop is an educational e-commerce web application built with Laravel and PHP. The project focuses on learning modern web development, application architecture, database interaction, and security-conscious design.

## Tech Stack

* **Backend:** PHP 8.4, Laravel 13
* **Database:** SQLite
* **ORM:** Laravel Eloquent
* **Frontend:** Blade, HTML, CSS, JavaScript
* **UI:** Bootstrap
* **Testing:** PHPUnit / Pest
* **Development:** Docker
* **Deployment:** Render

The project intentionally avoids Node.js and frontend build tools in accordance with the course requirements.

## Architecture

LarpShop uses Laravel's server-rendered architecture:

```text
Browser
   │
   ▼
Laravel Routes
   │
   ▼
Controllers
   │
   ▼
Application Logic
   │
   ▼
Eloquent ORM
   │
   ▼
SQLite
```

Blade views are rendered on the server and delivered as HTML to the browser. JavaScript is used where client-side interaction is required.

## Project Structure

The project follows Laravel's standard conventions, with application features organized within the framework structure.

```text
app/
├── Http/
│   └── Controllers/
└── Models/

resources/
└── views/
    ├── layouts/
    ├── components/
    └── features/

public/
├── css/
├── js/
└── images/

database/
└── database.sqlite

routes/
└── web.php
```

## Frontend

The frontend uses Blade layouts and components to avoid duplicating common page structure.

Bootstrap provides the base responsive layout and UI components, while custom CSS handles project-specific styling.

JavaScript is used for interactive components such as:

* Dropdown menus
* Shared dropdown portals
* Carousels
* Client-side UI state
* Interactive SVG graphics

The application uses semantic HTML elements such as `main`, `aside`, `header`, and `section` where appropriate.

## Routing

Laravel routes handle page navigation and dynamic resources.

Example:

```php
Route::get('/categories/{category}', [CategoryController::class, 'show']);
```

Laravel's route model binding can also be used where appropriate.

## Database

LarpShop uses SQLite for development and Laravel Eloquent for database access.

Eloquent provides the application's ORM layer, allowing database records to be represented and manipulated through Laravel models.

## Interactive UI

The frontend includes reusable JavaScript components built around DOM elements and `data-*` attributes.

For example, reusable components can be initialized from markup without tightly coupling the JavaScript to individual pages.

This approach keeps interactive behavior modular while allowing Blade to generate the initial HTML.

## Graphics

The project can use browser-native graphics technologies such as:

* SVG for scalable vector graphics
* Canvas for programmatic graphics
* WebGL/WebGPU where GPU-based rendering is required

These graphics are rendered by the browser rather than by Laravel itself.

## Development

The application can be run through Laravel's development environment or Docker.

Typical Laravel commands include:

```bash
php artisan serve
php artisan migrate
php artisan test
```

Docker is used to provide a reproducible application environment and to support deployment.

## Goals

The main goals of LarpShop are to:

* Practice ORM-based database development
* Build reusable server-rendered UI components
* Develop interactive frontend components with vanilla JavaScript
* Apply security-conscious development practices
* Understand the separation between server-side application logic and client-side behavior
* Produce a deployable web application
