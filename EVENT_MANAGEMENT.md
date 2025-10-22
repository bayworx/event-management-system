# Event Management Module

This module provides comprehensive event management functionality for administrators in the event management system.

## Features

### Event Management
- **Create Events**: Full-featured event creation with all necessary details
- **Edit Events**: Update event information, dates, locations, and settings
- **View Event Details**: Comprehensive event overview with statistics
- **Clone Events**: Duplicate existing events for similar future events
- **Toggle Status**: Activate/deactivate events without deletion
- **Delete Events**: Remove events with proper confirmation
- **Filter and Search**: Find events by title, description, location, status, and date range

### Event Features
- **Event Information**: Title, description, start/end dates, location
- **Attendee Management**: Set maximum attendee limits, track registrations
- **Banner Images**: Upload event banner images using VichUploaderBundle
- **Administrator Assignment**: Assign multiple administrators to manage events
- **SEO-Friendly URLs**: Automatic slug generation from event titles
- **Status Management**: Active/inactive status control

### Access Control
- **Role-Based Access**: All administrators can create and manage events
- **Event-Specific Permissions**: Regular admins only see their assigned events
- **Super Admin Privileges**: Super admins can see and manage all events
- **Administrator Assignment**: Only super admins can assign other administrators to events

### User Interface
- **Responsive Design**: Mobile-friendly interface
- **Advanced Filtering**: Multiple filter options with clear/reset functionality
- **Pagination**: Efficient handling of large event lists
- **Visual Indicators**: Status badges, progress bars, and statistics
- **Bulk Actions**: Toggle status, clone, and delete operations

## URL Structure

- `/admin/events` - Event listing with filtering
- `/admin/events/new` - Create new event
- `/admin/events/{id}` - View event details
- `/admin/events/{id}/edit` - Edit event
- `/admin/events/{id}/toggle-status` - Toggle active/inactive (POST)
- `/admin/events/{id}/clone` - Clone event (POST)
- `/admin/events/{id}/delete` - Delete event (POST)

## Event Entity Fields

### Core Information
- `title` - Event name (required)
- `description` - Event description (optional, rich text)
- `slug` - URL-friendly identifier (auto-generated)
- `startDate` - Event start date and time (required)
- `endDate` - Event end date and time (optional)
- `location` - Event venue (optional)

### Settings
- `isActive` - Whether the event is publicly visible
- `maxAttendees` - Maximum number of attendees (optional)
- `bannerImage` - Event banner image (optional, via VichUploader)

### Metadata
- `createdAt` - When the event was created
- `updatedAt` - When the event was last modified

### Relationships
- `attendees` - Collection of registered attendees
- `files` - Collection of event files and documents
- `administrators` - Administrators who can manage this event

## Form Types

### AdminEventType
Main form for creating and editing events:
- Title (required)
- Description (rich textarea)
- Start date/time (datetime picker)
- End date/time (optional datetime picker)
- Location (text input)
- Maximum attendees (number input, optional)
- Banner image upload (VichUploader field)
- Active status (checkbox)
- Administrator assignment (multi-select, super admins only)

### AdminEventFilterType
Search and filter form:
- Text search (title, description, location)
- Status filter (active/inactive)
- Date range filter (upcoming/ongoing/past)

## Templates

- `templates/admin/event/index.html.twig` - Event listing with filters and pagination
- `templates/admin/event/new.html.twig` - Create new event form
- `templates/admin/event/edit.html.twig` - Edit event form
- `templates/admin/event/show.html.twig` - Event details view with statistics

## Key Features

### Automatic Slug Generation
- Slugs are automatically created from event titles
- Ensures uniqueness by appending numbers if needed
- Updates slugs when titles are changed

### Permission System
- Regular administrators: Only see events they're assigned to
- Super administrators: Can see and manage all events
- Administrator assignment: Only super admins can assign other administrators

### Event Cloning
- Duplicates event information for similar future events
- Sets new dates (1 month in the future by default)
- Preserves all settings and administrator assignments
- Starts as inactive for review

### Visual Statistics
- Attendee capacity progress bars
- Event status indicators (upcoming/ongoing/past)
- Quick statistics cards
- Attendee and file counts

### Integration with Public Site
- Events are displayed on the public event listing
- Public registration through event-specific URLs
- Attendee verification and file download systems

## Usage Examples

### Creating a New Event
1. Navigate to `/admin/events`
2. Click "Create Event"
3. Fill in event details
4. Set dates, location, and capacity
5. Upload banner image (optional)
6. Assign administrators (super admins only)
7. Set active status
8. Save to create the event

### Managing Events
1. Use filters to find specific events
2. View event details for comprehensive overview
3. Edit events to update information
4. Clone events for similar future events
5. Toggle status to activate/deactivate
6. Delete events with confirmation

### Event Access
- Regular admins automatically get assigned to events they create
- Super admins can assign multiple administrators to events
- Event-specific permissions ensure administrators only manage their events

## Security Features
- CSRF protection on all forms and actions
- Role-based access control
- Event-specific permission checks
- Confirmation dialogs for destructive actions
- Secure file upload handling

## Technical Implementation

### Controller: `AdminEventController`
- Full CRUD operations
- Advanced filtering and search
- Permission-based event access
- Bulk operations (clone, toggle status)

### Repository Extensions: `EventRepository`
- Event search and filtering methods
- Permission-aware queries
- Statistics and reporting methods

### Form Validation
- Required field validation
- Date validation and constraints
- File upload restrictions
- Unique slug enforcement

## Installation

The event management module is automatically available once you:
1. Have the controller, forms, and templates in place
2. Configure VichUploaderBundle for banner images
3. Set up proper file upload directories
4. Clear the Symfony cache

## Future Enhancements

Potential improvements could include:
- Recurring event templates
- Event categories and tags
- Advanced reporting and analytics
- Email notifications for event changes
- Integration with calendar systems
- Bulk import/export functionality