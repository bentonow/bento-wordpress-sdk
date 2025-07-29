# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

### Building and Development
- `npm run dev` - Start Vite development server for React components
- `npm run build` - Build React components for production using Vite
- `npm run preview` - Preview the built application

### Testing
- `composer test` or `vendor/bin/pest` - Run PHP unit tests using Pest framework
- `vendor/bin/phpunit` - Alternative test runner using PHPUnit directly

## Architecture Overview

This is a WordPress plugin that integrates Bento email marketing services with WordPress and WooCommerce. The codebase has a hybrid architecture combining PHP backend logic with React frontend components.

### Core Components

**Main Plugin Structure:**
- `bento-helper.php` - Main plugin file, handles initialization and loading of core classes
- `inc/` - Contains all PHP classes organized by functionality
- `assets/js/src/` - React components and frontend code

**Key PHP Classes:**
- `Bento_Events_Controller` (inc/class-bento-events-controller.php) - Central event handling system that queues and sends events to Bento API
- `Bento_Settings_Controller` (inc/class-bento-settings-controller.php) - Handles AJAX requests for plugin settings management
- `Bento_Mail_Handler` (inc/class-bento-mail-handler.php) - Manages transactional email routing through Bento
- `Bento_Logger` (inc/class-bento-logger.php) - Logging system for debugging and monitoring

**Event System Architecture:**
The plugin uses an event-driven architecture where different e-commerce platforms trigger standardized events:
- Event controllers in `inc/events-controllers/` handle platform-specific integrations (WooCommerce, LearnDash, EDD, SureCart)
- Events are queued in `Bento_Events_Controller` and sent to Bento API endpoint in batches
- Each event controller extends the base event handling pattern and registers WordPress hooks for their respective platforms

**Frontend Architecture:**
- React components built with Vite bundler
- Entry point: `assets/js/src/bento-app.jsx`
- Components use Radix UI primitives and Tailwind CSS
- Settings UI and mail logs are separate React apps mounted to different DOM containers

### Form Integrations
The plugin supports multiple form builders through dedicated handlers in `inc/forms/`:
- Elementor Forms (`class-bento-elementor-form-handler.php`)
- WPForms (`class-wp-forms-form-handler.php`) 
- Bricks Forms (`class-bento-bricks-form-handler.php`)
- ThriveThemes (`class-bento-thrive-themes-events.php`)

### Configuration Management
- Settings stored in WordPress options table via `Configuration_Interface`
- WordPress adapter pattern used for configuration abstraction
- AJAX endpoints for real-time settings validation and updates

### Development Patterns
- Dependency injection used in controllers (see `Bento_Settings_Controller` constructor)
- Interface-based design for mail handling (`inc/interfaces/mail-interfaces.php`)
- WordPress adapter pattern for configuration management
- Event-driven architecture for e-commerce platform integrations
- Singleton pattern for main plugin class

### Testing Setup
- Pest framework for PHP testing with PHPUnit compatibility
- Test bootstrap in `tests/bootstrap.php`
- Mockery for mocking dependencies
- Test coverage includes mail handling, event deduplication, and logging