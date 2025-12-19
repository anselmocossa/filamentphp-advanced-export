# Changelog

All notable changes to `filament-advanced-export` will be documented in this file.

## [Unreleased]

## [1.0.0] - 2025-12-19

### Added
- Initial release
- `HasAdvancedExport` trait for Filament ListRecords pages
- Dynamic column selection with custom titles
- Configurable ordering (column and direction)
- Automatic filter extraction from Filament tables
- Support for simple and advanced export views
- `Exportable` interface for models
- Artisan commands:
  - `export:install` - Initial setup
  - `export:views {model}` - Generate export views
  - `export:model {model}` - Add export methods to model
  - `export:publish` - Publish assets
- Background job processing for large exports
- Configurable limits (max records, chunk size)
- Bilingual support (English and Portuguese)
- View stubs for code generation
