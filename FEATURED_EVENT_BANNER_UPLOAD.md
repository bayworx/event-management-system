# Featured Event Banner Image Upload

This document describes the banner image upload feature that was added to the Featured Events system.

## Overview

The Featured Events system now supports uploading banner images directly to the server instead of only using external URLs. This provides better control, performance, and reliability for featured event banners.

## Features Added

### 1. File Upload Support
- Added VichUploaderBundle support for banner image uploads
- Support for JPG, PNG, GIF, and WebP formats
- Maximum file size: 5MB
- Automatic unique filename generation

### 2. Database Changes
- Added `banner_image_name` field to store uploaded filename
- Added `banner_image_size` field to store file size
- Migration: `Version20251013013717`

### 3. Form Updates
- Added banner image upload field to Featured Event forms
- Kept existing imageUrl field as fallback option
- Image upload field includes preview and delete functionality

### 4. Smart Image Resolution
- `getEffectiveBannerImage()` method prioritizes uploaded images over URLs
- Fallback to imageUrl if no banner is uploaded
- Seamless integration with existing system

### 5. Template Updates
- Updated admin show/index templates to use effective banner images
- Updated form templates to include banner upload field
- Updated API endpoint to serve effective banner images

## File Structure

```
├── config/packages/vich_uploader.yaml          # VichUploader configuration
├── public/uploads/featured_events/             # Upload directory
├── src/Entity/FeaturedEvent.php                # Entity with upload fields
├── src/Form/FeaturedEventType.php              # Form with upload field
├── src/Controller/FeaturedEventApiController.php # API serving images
├── templates/admin/featured_events/            # Updated templates
└── migrations/Version20251013013717.php        # Database migration
```

## Usage

### Admin Interface
1. Go to Admin → Events → Featured Events
2. Create or edit a featured event
3. Use the "Banner Image" upload field to select an image
4. The "Image URL (Fallback)" field remains available for external URLs
5. Uploaded images take priority over URL images

### API Integration
The `/api/featured-events/rotation` endpoint automatically serves the effective banner image (uploaded file first, fallback to URL).

### Frontend Display
The featured events carousel and components automatically display uploaded banner images through the existing JavaScript implementation.

## Backwards Compatibility

- Existing featured events with imageUrl continue to work
- No breaking changes to API or frontend
- Smooth migration path from URL-based to upload-based images

## Configuration

### VichUploader Mapping
```yaml
featured_event_banners:
    uri_prefix: /uploads/featured_events
    upload_destination: '%kernel.project_dir%/public/uploads/featured_events'
    namer: Vich\UploaderBundle\Naming\UniqidNamer
```

### File Constraints
- Max size: 5MB
- Allowed types: image/jpeg, image/png, image/gif, image/webp
- Recommended dimensions: 1200x400px for banners

## Technical Details

### Entity Properties
- `$bannerImageFile`: File upload property (not persisted)
- `$bannerImageName`: Stored filename (persisted)
- `$bannerImageSize`: File size in bytes (persisted)

### Key Methods
- `setBannerImageFile()`: Handles file upload
- `getEffectiveBannerImage()`: Returns best available image
- Database fields updated automatically via VichUploader listeners

## Testing

The implementation has been tested for:
- ✅ File upload functionality
- ✅ Database migration
- ✅ Template rendering
- ✅ API endpoint updates
- ✅ Backwards compatibility
- ✅ Form validation

## Future Enhancements

Potential future improvements:
- Image resizing/optimization on upload
- Multiple image sizes (thumbnail, medium, large)
- Image editing tools integration
- Bulk upload functionality
- CDN integration