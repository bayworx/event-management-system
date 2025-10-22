# Administrator User Management System

## Overview
The Event Management System includes a comprehensive **Administrator User Management** system that allows super administrators to manage other administrator accounts. This system provides full CRUD (Create, Read, Update, Delete) functionality with robust security measures and role-based access control.

## 🔒 Access Control

### Super Administrator Requirements
- Only users with `ROLE_SUPER_ADMIN` can access the user management interface
- The "Administrators" navigation link is only visible to super administrators
- All controller actions are protected by `#[IsGranted('ROLE_SUPER_ADMIN')]`

### Security Features
- **Self-Protection**: Administrators cannot disable or delete their own accounts
- **Last Super Admin Protection**: The last remaining super admin cannot be deleted or demoted
- **CSRF Protection**: All destructive actions require valid CSRF tokens
- **Route-Level Security**: All routes protected at controller level

## 📍 Navigation & Access

### Admin Interface Location
```
Admin Dashboard → Administrators (menu item)
URL: /admin/users
```

### Access Restrictions
- Navigation link only appears if `app.user.isSuperAdmin` is `true`
- Direct URL access blocked for non-super-administrators
- Returns 403 Forbidden for unauthorized users

## 🎛️ Features & Functionality

### 1. Administrator List (`/admin/users`)
**Features:**
- Paginated list of all administrators (20 per page)
- Advanced filtering by:
  - Search (name, email, department)
  - Status (Active/Inactive)
  - Role (Super Admin/Regular Admin)
  - Department selection
- Visual status indicators with badges
- Avatar circles with initials
- Last login information
- Bulk actions and individual controls

**Display Information:**
- Administrator name and email
- Department badge
- Role badge (Super Admin/Administrator)
- Status badge (Active/Inactive)
- Creation date
- Last login date
- Action buttons (View, Edit, Toggle Status, Delete)

### 2. View Administrator (`/admin/users/{id}`)
**Profile Card:**
- Avatar with initials
- Name, email, and status badges
- Quick action buttons (activate/deactivate)

**Detailed Information:**
- Basic information (name, email, department, status, role)
- Account timeline (creation date, last login)
- Managed events list
- Quick statistics (managed events count, days active)

### 3. Create Administrator (`/admin/users/new`)
**Form Fields:**
- **Full Name** (required)
- **Email Address** (required, unique)
- **Department** (dropdown: IT, Marketing, Operations, HR, Finance, Executive, Customer Service, Sales)
- **Password** (required, min 6 characters, with confirmation)
- **Active Status** (checkbox, default: checked)
- **Super Administrator** (checkbox, grants ROLE_SUPER_ADMIN)

**Validation:**
- Email uniqueness validation
- Password strength requirements (minimum 6 characters)
- Password confirmation matching
- Required field validation

### 4. Edit Administrator (`/admin/users/{id}/edit`)
**Same fields as create, with additional features:**
- Password field optional (leave blank to keep current)
- Cannot edit super admin status of yourself
- Form pre-populated with current values

**Special Protections:**
- Self-demotion prevention for super admins
- Last super admin protection

### 5. Quick Actions
**Toggle Status** (`POST /admin/users/{id}/toggle-status`)
- Activate/deactivate administrator accounts
- Protected against self-deactivation
- CSRF token required

**Delete Administrator** (`POST /admin/users/{id}/delete`)
- Permanent account deletion
- Protected against self-deletion
- Last super admin protection
- Confirmation dialog required

**Reset Password** (if implemented)
- Generate temporary password
- Secure password reset functionality

## 🗂️ Entity & Database Structure

### Administrator Entity Features
```php
class Administrator implements UserInterface, PasswordAuthenticatedUserInterface
{
    private ?int $id;
    private ?string $name;
    private ?string $email;           // Unique
    private array $roles;             // ['ROLE_ADMIN'] default
    private ?string $password;
    private bool $isActive = true;
    private ?DateTime $createdAt;
    private ?DateTime $lastLoginAt;
    private bool $isSuperAdmin = false;
    private ?string $department;
    private Collection $managedEvents; // ManyToMany with Event
}
```

### Database Relations
- **Many-to-Many** with `Event` entity (managed events)
- **Unique constraint** on email field
- **JSON field** for roles array
- **Indexed fields** for common queries

## 📋 Form Types

### AdminUserType
**Form Configuration:**
- Supports both create and edit modes
- Dynamic password requirements (required for new, optional for edit)
- Conditional super admin field (can be disabled for self-editing)
- Department dropdown with predefined options
- Bootstrap CSS classes applied

### AdminUserFilterType
**Search & Filter Form:**
- Search field (name, email, department)
- Status dropdown (Active/Inactive/All)
- Role dropdown (Super Admin/Regular Admin/All)
- Department filter dropdown
- GET method, no CSRF protection

## 🎨 Templates & UI

### Professional Design Features
- **Responsive Bootstrap 5** layout
- **Card-based design** for clean organization
- **Badge system** for status and role visualization
- **Avatar circles** with user initials
- **Tooltip integration** for action buttons
- **Form validation feedback** with error display
- **Pagination controls** with item count display

### Template Files
```
templates/admin/user/
├── index.html.twig    # Administrator list with filtering
├── show.html.twig     # Administrator profile view
├── new.html.twig      # Create administrator form
└── edit.html.twig     # Edit administrator form
```

### Consistent UI Elements
- Standard admin layout inheritance (`admin/base.html.twig`)
- Flash message integration
- Confirmation dialogs for destructive actions
- Loading states and form feedback
- Mobile-responsive design

## 🛡️ Security Implementation

### Authentication & Authorization
```php
#[Route('/admin/users')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class AdminUserController extends AbstractController
```

### Protection Mechanisms
1. **Route-Level Protection**: All routes require `ROLE_SUPER_ADMIN`
2. **Controller-Level Checks**: Additional validation in controller methods
3. **Template-Level Security**: Conditional rendering based on permissions
4. **CSRF Protection**: All state-changing operations protected
5. **Input Validation**: Form validation and entity constraints

### Business Logic Protection
```php
// Prevent self-modification
if ($administrator->getId() === $currentUser->getId()) {
    $this->addFlash('error', 'You cannot modify your own account.');
    return $this->redirectToRoute('admin_user_index');
}

// Prevent last super admin deletion
if ($administrator->isSuperAdmin()) {
    $superAdminCount = $this->administratorRepository->countSuperAdmins();
    if ($superAdminCount <= 1) {
        $this->addFlash('error', 'Cannot delete the last super administrator.');
        return $this->redirectToRoute('admin_user_index');
    }
}
```

## 🔍 Repository Methods

### Custom Query Methods
- `findActive()` - Get all active administrators
- `findEventManagers(Event $event)` - Get administrators who can manage specific event
- `findSuperAdmins()` - Get all super administrators
- `findByDepartment(string $department)` - Filter by department
- `search(string $query)` - Search by name or email
- `findRecentlyActive(DateTime $since)` - Get recently active administrators
- `countSuperAdmins()` - Count super administrators
- `findAllDepartments()` - Get unique department list

### Performance Optimization
- Indexed database queries
- Pagination to limit result sets
- Efficient COUNT queries for statistics
- Lazy loading for related entities

## 🚀 Usage Examples

### For Super Administrators

**Creating a New Administrator:**
1. Navigate to `Admin → Administrators`
2. Click "Add Administrator" button
3. Fill out the form with required information
4. Select department and set permissions
5. Choose whether to grant super admin privileges
6. Submit the form

**Managing Existing Administrators:**
1. Use the filter system to find specific administrators
2. Click "View" to see detailed information
3. Click "Edit" to modify account details
4. Use toggle buttons for quick status changes
5. Use confirmation dialogs for deletions

**Bulk Operations:**
- Filter by department to manage team members
- Search by name or email for quick access
- Sort by creation date or last login

### For Regular Administrators
Regular administrators can:
- View their own profile via the Profile menu
- Edit their own account information (not super admin status)
- Cannot access the Administrators management section

## 📊 Statistics & Monitoring

### Dashboard Information Available
- Total administrator count
- Active administrator count
- Super administrator count
- Department distribution
- Recent activity tracking

### Audit Trail Features
- Account creation timestamps
- Last login tracking
- Account status change history
- Password change tracking

## 🔧 Configuration & Customization

### Department Management
Departments are configured in the form types. To add new departments:

1. **Update AdminUserType.php:**
```php
'choices' => [
    'IT' => 'IT',
    'Marketing' => 'Marketing',
    'Your New Department' => 'Your New Department',
    // ... other departments
],
```

2. **Update AdminUserFilterType.php** with the same department list

### Role Management
The system supports:
- `ROLE_ADMIN` - Standard administrator role
- `ROLE_SUPER_ADMIN` - Super administrator with user management privileges

Additional roles can be added by extending the entity and security configuration.

### Customization Options
- **Password Policies**: Modify validation constraints in AdminUserType
- **UI Themes**: Update Bootstrap classes in templates
- **Field Requirements**: Modify form validation rules
- **Permissions**: Extend the authorization checks

## 🧪 Testing Checklist

### Functional Tests
- [ ] Super admin can access all user management features
- [ ] Regular admin cannot access user management
- [ ] User creation with all field combinations
- [ ] User editing with password changes
- [ ] Status toggle functionality
- [ ] User deletion with confirmations
- [ ] Self-protection mechanisms work
- [ ] Last super admin protection works
- [ ] CSRF protection on all forms
- [ ] Form validation displays correctly

### Security Tests  
- [ ] URL access blocked for non-super-admins
- [ ] CSRF tokens prevent unauthorized actions
- [ ] Self-modification protection works
- [ ] Last super admin cannot be deleted/demoted
- [ ] Password hashing works correctly
- [ ] Email uniqueness validation works

### UI/UX Tests
- [ ] Responsive design works on mobile
- [ ] Tooltips display correctly
- [ ] Form validation feedback shows
- [ ] Pagination works correctly
- [ ] Filtering and search functions
- [ ] Badges and status indicators display
- [ ] Confirmation dialogs appear for destructive actions

## 🔄 Future Enhancements

### Potential Improvements
1. **Bulk Operations**: Select multiple administrators for bulk actions
2. **Advanced Permissions**: Role-based permissions beyond super admin
3. **Activity Logging**: Detailed audit trail for all actions
4. **Email Notifications**: Notify administrators of account changes
5. **Password Reset**: Email-based password reset system
6. **Two-Factor Authentication**: Enhanced security with 2FA
7. **API Access**: REST API for external integrations
8. **Advanced Filtering**: Date ranges, advanced search options

### Integration Opportunities
- **LDAP/Active Directory**: External authentication integration
- **Single Sign-On (SSO)**: SAML/OAuth integration
- **Analytics Dashboard**: Usage and activity analytics
- **Notification System**: Real-time notifications for account changes

---

## 📞 Support & Documentation

**Implementation Status**: ✅ **COMPLETE**

**Files Included:**
- Controller: `src/Controller/Admin/AdminUserController.php`
- Forms: `src/Form/AdminUserType.php`, `src/Form/AdminUserFilterType.php`
- Entity: `src/Entity/Administrator.php` (existing)
- Repository: `src/Repository/AdministratorRepository.php` (existing)
- Templates: `templates/admin/user/*.html.twig`
- Navigation: Integrated in `templates/admin/base.html.twig`

**Security Level**: 🔒 **HIGH** (Super Admin Only, CSRF Protected, Self-Protection)

**Last Updated**: October 2024  
**Version**: 1.0  
**Compatibility**: Symfony 6.4+, PHP 8.4+