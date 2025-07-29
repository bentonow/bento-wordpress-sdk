# Agent Guidelines for Bento WordPress SDK

## Build/Test Commands
- **Build**: `npm run build` (Vite build for React components)
- **Dev**: `npm run dev` (Vite dev server)
- **PHP Tests**: `composer test` or `vendor/bin/pest` (uses Pest framework)
- **Single Test**: `vendor/bin/pest tests/Unit/SpecificTest.php`

## Code Style
- **Prettier**: 80 char width, single quotes, 2-space tabs (see .prettierrc)
- **React**: Use functional components, JSX imports from '@/' alias
- **PHP**: WordPress coding standards, snake_case methods, PascalCase classes
- **Types**: Use TypeScript where available, PHP type hints for methods
- **Imports**: Use '@/' alias for React components, relative imports for utils

## Architecture
- **Frontend**: React + Vite + Tailwind CSS + Radix UI components
- **Backend**: WordPress plugin with PHP classes in `inc/` directory
- **Forms**: Event controllers for WooCommerce, EDD, LearnDash integrations
- **Mail**: Custom mail handler with logging functionality

## Error Handling
- **PHP**: Use `wp_send_json_error()` for AJAX responses, sanitize all inputs
- **React**: Handle async operations with proper error states
- **Security**: Always check nonces and user capabilities in PHP AJAX handlers