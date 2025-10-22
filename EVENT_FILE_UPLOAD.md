# Event File Upload Feature

This document describes the event file upload functionality that has been implemented for the Event Management System.

## Overview

The system now supports uploading and managing files for each event. Event administrators can upload documents, presentations, images, and other files that will be available for download by verified event attendees.

## Features Implemented

### 1. File Upload Management
- **Form**: EventFileType with VichUploader integration
- **Supported formats**: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT, ZIP, JPG, PNG, GIF
- **File size limit**: 50MB per file
- **Automatic file naming**: Uses UniqidNamer for unique filenames

### 2. Admin Interface
- **Upload files**: `/admin/events/{id}/files/new`
- **Edit files**: `/admin/events/{eventId}/files/{fileId}/edit`
- **Delete files**: POST to `/admin/events/{eventId}/files/{fileId}/delete`
- **Toggle status**: POST to `/admin/events/{eventId}/files/{fileId}/toggle`

### 3. File Management Features
- Display name (shown to attendees)
- Optional description
- Sort order (for display ordering)
- Active/Inactive status
- Download tracking
- File information (size, type, upload date)

### 4. Frontend Integration
- Files are displayed on the event show page
- Only active files are visible to attendees  
- Files require attendee verification to download
- Download tracking for analytics

## File Structure

```
├── src/Form/EventFileType.php                     # File upload form
├── src/Controller/Admin/AdminEventController.php  # File management routes
├── src/Repository/EventFileRepository.php         # Database queries
├── templates/admin/event/files/
│   ├── new.html.twig                              # Upload form
│   └── edit.html.twig                             # Edit form
├── templates/admin/event/show.html.twig           # Enhanced file section
└── public/uploads/event_files/                    # Upload directory
```

## Usage Instructions

### For Administrators

1. **Upload a File**:
   - Go to an event's detail page
   - Click "Add File" in the Files section
   - Fill in display name and optional description
   - Select the file to upload
   - Set sort order (lower numbers appear first)
   - Choose active/inactive status
   - Click "Upload File"

2. **Manage Existing Files**:
   - View files in the event detail page
   - Click pencil icon to edit file details
   - Use play/pause button to activate/deactivate files
   - Access full file management via edit page

3. **File Information**:
   - View download statistics
   - See file size and type
   - Track upload and modification dates

### For Attendees

1. **Access Files**:
   - Register for the event
   - Verify email address
   - View available files on the event page
   - Click to download files

## Configuration

### VichUploader Mapping
```yaml
event_files:
    uri_prefix: /uploads/event_files
    upload_destination: '%kernel.project_dir%/public/uploads/event_files'
    namer: Vich\UploaderBundle\Naming\UniqidNamer
```

### File Constraints
- **Max size**: 50MB
- **Allowed types**: Documents, presentations, spreadsheets, text files, archives, images
- **Storage**: Local file system with unique filenames
- **Permissions**: 775 on upload directory

## Database Schema

### New Fields in `event_files` table:
- `name`: Display name for attendees
- `description`: Optional file description  
- `filename`: Generated unique filename
- `original_name`: Original uploaded filename
- `mime_type`: File MIME type
- `file_size`: File size in bytes
- `download_count`: Number of downloads
- `is_active`: Active/inactive status
- `uploaded_at`: Upload timestamp
- `updated_at`: Last modification timestamp
- `sort_order`: Display order

## Security Features

1. **File Type Validation**: Only allowed MIME types can be uploaded
2. **Size Limits**: Files cannot exceed 50MB
3. **Unique Filenames**: Prevents conflicts and direct access
4. **Access Control**: Only verified attendees can download files
5. **Admin Permissions**: Only event administrators can manage files

## Error Handling

- **Upload failures**: Graceful error messages with specific reasons
- **Access denied**: Clear messages for unauthorized access attempts
- **File not found**: Proper 404 handling for missing files
- **CSRF protection**: All state-changing operations protected

## Performance Considerations

1. **File Storage**: Files stored locally in `public/uploads/event_files/`
2. **Download Tracking**: Efficient database updates for analytics
3. **Lazy Loading**: Files loaded only when needed
4. **Proper Indexing**: Database indexes on frequently queried fields

## Future Enhancements

Potential improvements for future versions:
- File versioning system
- Bulk file upload
- File categories/tags
- Advanced file search
- Cloud storage integration (AWS S3, etc.)
- File preview functionality
- Automatic file conversion
- File sharing with external links

## Troubleshooting

### Common Issues

1. **Upload fails with "File too large"**:
   - Check file size is under 50MB
   - Verify PHP upload limits in php.ini

2. **"Permission denied" errors**:
   - Ensure `/public/uploads/event_files/` has 775 permissions
   - Check web server user has write access

3. **File not found on download**:
   - Verify file exists in upload directory
   - Check database filename matches actual file

4. **CSRF token errors**:
   - Ensure forms include proper CSRF tokens
   - Check session configuration

### Debug Commands

```bash
# Check file upload configuration
php bin/console debug:config vich_uploader

# Validate file upload forms
php bin/console debug:form EventFileType

# Check file upload routes
php bin/console debug:router | grep admin_event_file

# Clear cache after changes
php bin/console cache:clear
```

The file upload functionality is now fully operational and ready for production use!