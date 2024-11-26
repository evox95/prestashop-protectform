# BC Form Protection Module for PrestaShop

This module adds CSRF protection to various PrestaShop forms including signup, login forms. It is compatible with PrestaShop versions 1.6, 1.7, and 8.x.

## Features

- CSRF protection for signup forms
- CSRF protection for login forms
- Easy configuration through the PrestaShop admin panel
- Automatic token injection - no manual template modifications required
- Compatible with PrestaShop 1.6, 1.7, and 8.x

## Installation

1. Download the module
2. Upload the `bc_protectform` folder to your PrestaShop's `modules` directory
3. Go to the Modules page in your PrestaShop admin panel
4. Find "BC Form Protection" in the modules list and click "Install"

## Configuration

1. After installation, go to the module's configuration page
2. Enable or disable CSRF protection for each form type:
   - Signup Form
   - Login Form
3. Save your settings

## How It Works

The module automatically injects CSRF tokens into the configured forms. When a form is submitted, the module validates the token to ensure the request is legitimate. If the token is invalid or missing, the request will be rejected.

No manual template modifications are required - the module handles everything automatically through JavaScript.

## Security

The module generates unique CSRF tokens for each session and validates them on form submission. This helps protect your PrestaShop store against Cross-Site Request Forgery (CSRF) attacks.

## Support

If you encounter any issues or need assistance, please create an issue in the module's repository.
