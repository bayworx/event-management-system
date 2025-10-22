# User Administration Module

This module provides comprehensive user management functionality for the event management system's admin panel.

## Features

### Admin User Management
- **Create administrators**: Add new administrator accounts with customizable permissions
- **Edit administrators**: Update user information, passwords, and permissions
- **View administrator details**: See complete admin profiles with activity statistics
- **Activate/Deactivate accounts**: Control access without deleting accounts
- **Delete administrators**: Remove unnecessary accounts (with safety checks)
- **Filter and search**: Find administrators by name, email, department, status, or role

### Security Features
- **Role-based access**: Only super administrators can access user management
- **Self-protection**: Prevents admins from disabling/deleting their own accounts
- **Last super admin protection**: Prevents deletion of the last super administrator
- **Password validation**: Strong password requirements with confirmation
- **CSRF protection**: All forms include CSRF tokens

### User Interface
- **Responsive design**: Works on desktop and mobile devices
- **Intuitive navigation**: Clear admin navigation with active states
- **Advanced filtering**: Multiple filter options with clear/reset functionality
- **Pagination**: Handles large numbers of administrators efficiently
- **Toast notifications**: Success/error messages for all operations
- **Tooltips**: Helpful hints for actions and form fields

## Access Requirements

- Must be logged in as a Super Administrator
- Regular administrators cannot access user management features
- Protected by `#[IsGranted('ROLE_SUPER_ADMIN')]` attribute

## URLs

- `/admin/users` - List all administrators with filtering
- `/admin/users/new` - Create new administrator
- `/admin/users/{id}` - View administrator details
- `/admin/users/{id}/edit` - Edit administrator
- `/admin/users/{id}/toggle-status` - Activate/deactivate (POST)
- `/admin/users/{id}/delete` - Delete administrator (POST)

## Database Structure

The module uses the existing `Administrator` entity with these key fields:
- `name` - Full name
- `email` - Email address (unique, used for login)
- `department` - Optional department categorization
- `isActive` - Account status (active/inactive)
- `isSuperAdmin` - Super administrator privileges
- `createdAt` - Account creation timestamp
- `lastLoginAt` - Last login tracking
- `managedEvents` - Events assigned to this administrator

## Form Types

### AdminUserType
Main form for creating and editing administrators:
- Name (required)
- Email (required, validated)
- Department (optional, dropdown)
- Password fields (required for new users, optional for editing)
- Active status checkbox
- Super admin checkbox (conditionally shown)

### AdminUserFilterType
Search and filter form:
- Text search (name, email, department)
- Status filter (active/inactive)
- Role filter (super admin/regular admin)
- Department filter

## Templates

- `templates/admin/base.html.twig` - Base template with navigation
- `templates/admin/user/index.html.twig` - User listing with filters
- `templates/admin/user/new.html.twig` - Create new administrator
- `templates/admin/user/edit.html.twig` - Edit administrator
- `templates/admin/user/show.html.twig` - View administrator details

## Usage Examples

### Creating a New Administrator
1. Navigate to `/admin/users`
2. Click "Add Administrator"
3. Fill in required information
4. Set permissions as needed
5. Save to create the account

### Managing Existing Users
1. Use the search/filter options to find specific administrators
2. Use the action buttons to view, edit, activate/deactivate, or delete
3. View detailed profiles to see account statistics and managed events

### Security Considerations
- Only super administrators can create other super administrators
- Users cannot modify their own super admin status
- The system prevents creating orphaned access (no super admins)
- All sensitive operations require CSRF token validation

## Technical Implementation

### Controller: `AdminUserController`
- Full CRUD operations
- Security checks and validations
- Flash message feedback
- Proper error handling

### Repository Extensions: `AdministratorRepository`
- `countSuperAdmins()` - Counts active super administrators
- `findAllDepartments()` - Lists unique departments

### Twig Extensions: `DateTimeExtension`
- `time_diff` filter for human-readable time differences

## Installation

The module is automatically available once you:
1. Have the controller, forms, and templates in place
2. Clear the Symfony cache
3. Ensure you have at least one super administrator account

## Permissions

The module respects the existing role hierarchy:
- `ROLE_SUPER_ADMIN` - Full access to user management
- `ROLE_ADMIN` - No access to user management
- Navigation automatically shows/hides based on permissions