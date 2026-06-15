# Changelog

## About
A WordPress plugin to create events with registration forms, replay forms, QR e-tickets, and submission management.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.1.7] - 2026-06-15
### Added
- Add schema.org (JSON-LD) metadata support for events
- Redesign single event page UI layout

## [2.1.6] - 2026-03-05
### Added
- add Logo Plugin


## [2.1.5] - 2026-03-04
### Added
- add Feature Block Email
- After the registration form is submitted, the form will be hidden immediately, then a successful response will appear.

## [2.1.4] - 2026-02-26
### Added
- Fixing share social media


## [2.1.3] - 2026-02-25
### Added
- **Add  Link**:  Add Short Description for share social media
- **Add Social Media**: Add Link Social media Profile Mentor and Speaker
- optimized css and javascript
- add field short description social media
- fixing bug copy link and other

## [2.1.2] - 2026-02-24
### Added
- **Add  Link **:  Add link zoom or meet automatic after register event
## [2.1.1] - 2026-02-24
### Added
- **Add  Shortcode Compact**:  Feature form Mega menu or grid list
-  **Paginition**: Add feature paginition list for limit page
- fixing limit shorcode

**Full Changelog**: https://github.com/Gioidstar/simple-event/compare/v2.1.0...v2.1.1

## [2.1.0] - 2026-02-24
### Added
- **Until Finished**: Checkbox option next to End Date that automatically closes the event on the start date. Frontend displays "Finished" badge instead of end date/time.
- **GitHub Auto-Updater**: Automatic plugin updates from GitHub releases. Sites using this plugin will receive update notifications directly in the WordPress admin.
- **Elementor Event Grid Widget**: Elementor widget to display events in a customizable grid layout with category filter support.
- **Add Fetaure Feedback Video Event

### Changed
- **Full English Translation**: All plugin UI text, admin labels, email content, form messages, and code comments translated from Indonesian to English.

## [2.0.0] - 2026-02-22
### Added
- **Replay Form**: "Watch the Replay" form for ended events with embedded YouTube video.
- **Email Notification**: Automatic confirmation emails with site logo, event details, and QR e-ticket for registration & replay submissions.
- **QR E-Ticket System**: QR code ticket generation for each registrant, with a ticket verification page when QR is scanned.
- **Speakers & Moderators**: Repeatable meta box to add speakers/moderators per event with photo, name, title, and role (Speaker, Moderator, or custom role).
- **Target Audience**: Repeatable meta box to add target audience per event, displayed as pill badges on the frontend.
- **Custom Form Fields**: Configurable form fields from WP Admin per event — add/remove/change label/type. Name & Email fields are required (locked). Supported types: Text, Email, Phone, Number, Textarea, Dropdown, Checkbox, Radio Button.
- **Custom Form Title & Subtitle**: Form title and subtitle can be customized per event from WP Admin.
- **Google Form Embed**: Option to use embedded Google Form for registration (replay forms still use the built-in form).
- **Phone Country Code**: Phone input with country code selector (flag + dial code), default Indonesia (+62), using intl-tel-input library.
- **Form Placeholders**: All form fields have automatic placeholder text.
- **Share Buttons**: Share event buttons for Facebook, LinkedIn, WhatsApp, X (Twitter), and copy link — using round SVG icons with brand colors.
- **CSV Export**: Export registrant data to CSV from the event edit page.
- **Pagination**: Pagination on the admin registrant table (15 per page).
- **Delete Submission**: Delete individual registrants from the admin table with confirmation.

### Changed
- Registration and replay forms are displayed inline below the event page (no modal/popup), with smooth scroll from the button.
- Form is full-width with border, border-radius, and box shadow.
- Admin registrant table displays dynamic columns based on the event's field configuration.
- Ticket page displays dynamic fields based on the configuration.
- Share buttons redesigned from dashicons to round SVG icons with each platform's brand color.

### Database
- Column `form_type` (VARCHAR) added to `wp_event_submissions` table — differentiates registration vs replay submissions.
- Column `custom_fields` (TEXT, JSON) added to `wp_event_submissions` table — stores custom field data.
- Auto-migration on `init` hook for backward compatibility.

## [1.0.1] - 2025-04-17
### Fixed
- Bug fixes on the event registration form.
- Improved email validation for the event page.

### Changed
- Updated UI for the event page.
