# Changelog

All notable changes to `filament-advanced-export` will be documented in this file.

## [Unreleased]

## [1.1.0] - 2025-12-19

### Added
- **Background Export with Database Notifications** - New `getBackgroundExportAction()` method for async exports
- Database notifications when background export completes with download link
- Failure notifications when background export fails
- Configurable user model for notifications (`advanced-export.user_model`)

### Fixed
- **Relationship Filter Bug** - Fixed `ProcessExportJob` not applying relationship filters correctly
  - Added `resolveColumnName()` method to convert filter names to actual column names
  - Now correctly handles `insurer` → `insurer_id` type conversions
- Filter application now checks if column exists before applying

### Changed
- `ProcessExportJob` now logs warnings for unknown filter columns instead of failing silently
- Improved queue configuration - uses `QUEUE_CONNECTION` from `.env` when connection is 'default'

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
  - `export:resource {resource}` - Complete setup for a Filament resource
  - `export:views {model}` - Generate export views
  - `export:model {model}` - Add export methods to model
  - `export:publish` - Publish assets
- Background job processing for large exports (`ProcessExportJob`)
- Configurable limits (max records, chunk size, queue threshold)
- Bilingual support (English and Portuguese)
- View stubs for code generation
