# Contributing

Thank you for considering contributing to the WhatsApp Bridge Settings Plugin.

## Development Setup

```bash
git clone https://github.com/islamV/whatsapp-bridge-settings-plugin.git
cd whatsapp-bridge-settings-plugin
composer install
```

## Running Tests

```bash
vendor/bin/phpunit
```

## Coding Standards

- Follow PSR-12 coding style
- Use Laravel and Filament conventions
- Keep methods focused and small
- Use type hints for all parameters and return types
- Write tests for all new functionality
- Keep backward compatibility where possible

## Pull Request Process

1. Fork the repository and create your branch from `main`
2. Add tests for any new functionality
3. Ensure all existing tests pass
4. Update documentation (README, CHANGELOG) if needed
5. Submit a pull request with a clear description of changes

## Reporting Issues

When reporting issues, please include:

- Laravel and Filament versions
- PHP version
- Steps to reproduce
- Expected vs actual behavior
- Relevant configuration (with credentials removed)

## License

By contributing, you agree that your contributions will be licensed under the MIT License.
