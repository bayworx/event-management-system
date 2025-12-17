# Featured Events Carousel Overlay Fix

## Issue
The featured events carousel had overlapping elements where the text, navigation controls (prev/next arrows), and indicators (dots) were appearing on top of each other, making the carousel difficult to use and read.

## Root Cause
The issue was caused by improper CSS z-index layering. Without explicit z-index values, browser rendering can cause elements to overlap in unpredictable ways, especially with positioned elements (absolute, relative, fixed).

### Specific Problems:
1. **Missing z-index on controls**: Carousel navigation arrows had no z-index, allowing them to be covered by other elements
2. **Missing z-index on indicators**: Indicator dots had no z-index, causing potential overlap
3. **Conflicting inline styles**: Template had inline styles that conflicted with CSS
4. **Unclear element hierarchy**: The layering hierarchy wasn't explicitly defined

## Solution

### 1. Z-Index Hierarchy Established
Created a clear stacking context with proper z-index values:

```
Layer 10: Carousel Controls (prev/next arrows) & Indicators (dots)
Layer 3:  Event Content (title, description, button)
Layer 1:  Overlay (gradient background)
Layer 0:  Image (base layer)
```

### 2. CSS Changes Made

**File: `public/css/featured-events.css`**

#### Added z-index to Carousel Controls
```css
.featured-events-carousel .carousel-control-prev,
.featured-events-carousel .carousel-control-next {
    /* ... existing styles ... */
    z-index: 10;  /* NEW */
}
```

#### Added z-index to Carousel Indicators
```css
.featured-events-carousel .carousel-indicators {
    /* ... existing styles ... */
    z-index: 10;  /* NEW */
}
```

#### Increased Content z-index
```css
.featured-event-content {
    z-index: 3;  /* Changed from 2 */
    position: relative;
    padding: 2rem;
}
```

#### Added z-index to Overlay
```css
.featured-event-overlay {
    /* ... existing styles ... */
    z-index: 1;  /* NEW */
}
```

#### Added Container for Image
```css
.featured-event-slide .featured-event-image {
    position: relative;
    height: 300px;
    overflow: hidden;
}
```

### 3. Template Changes

**File: `templates/components/featured_events_banner.html.twig`**

#### Removed Inline Styles
**Before:**
```html
<img src="" alt="" class="w-100" style="height: 400px; object-fit: cover;">
<div class="featured-event-overlay position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center" 
     style="background: linear-gradient(45deg, rgba(0,0,0,0.7), rgba(0,0,0,0.3));">
```

**After:**
```html
<img src="" alt="" class="w-100">
<div class="featured-event-overlay position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center">
```

Inline styles removed because:
- Height is now controlled by CSS classes (more maintainable)
- Gradient background is defined in CSS (can be themed/customized)

## Visual Hierarchy (Z-Index Map)

```
+-----------------------------------+
|  Carousel Controls (z-index: 10) |  ← Always on top
|  Indicators (z-index: 10)        |
+-----------------------------------+
|  Event Content (z-index: 3)      |  ← Text readable
|    - Title                        |
|    - Description                  |
|    - Button                       |
+-----------------------------------+
|  Overlay (z-index: 1)            |  ← Darkens image
|    Gradient Background            |
+-----------------------------------+
|  Image (z-index: 0)              |  ← Base layer
+-----------------------------------+
```

## Testing

### Visual Testing Checklist
- [ ] Text is clearly readable
- [ ] Prev/Next arrows appear on hover and are clickable
- [ ] Indicator dots are visible at bottom
- [ ] No overlap between text and controls
- [ ] Hover states work correctly
- [ ] Mobile responsive (test at 768px, 576px)
- [ ] Animations smooth
- [ ] Multiple featured events cycle correctly

### Browser Testing
Test in:
- Chrome/Edge (Chromium)
- Firefox
- Safari
- Mobile browsers (iOS Safari, Chrome Mobile)

### Responsive Testing
Check at these breakpoints:
- Desktop: 1200px+
- Tablet: 768px - 1199px
- Mobile: 576px - 767px
- Small Mobile: < 576px

## How It Works Now

### Desktop View (> 768px)
- Full-height carousel at 300px
- Controls appear on hover (60px circles)
- Indicators always visible at bottom
- Text overlays image with gradient

### Tablet View (768px)
- Maintains 300px height
- Smaller controls (45px circles)
- Responsive text sizing
- All features visible

### Mobile View (< 576px)
- Reduced to 250px height
- Smaller text and controls
- Optimized padding
- Indicators slightly smaller

## Maintenance Notes

### Adding New Overlay Elements
If adding new overlay elements, follow this z-index scale:
- 0-5: Background/base layers
- 6-9: Content layers
- 10+: Interactive controls/navigation

### Modifying Colors
The gradient overlay can be adjusted in CSS:
```css
.featured-event-overlay {
    background: linear-gradient(
        135deg, 
        rgba(0, 0, 0, 0.8) 0%,    /* Dark left */
        rgba(0, 0, 0, 0.4) 50%,   /* Medium center */
        transparent 100%           /* Transparent right */
    );
}
```

### Adjusting Height
All height values are in CSS for consistency:
```css
.featured-event-slide {
    height: 300px;  /* Main container */
}

.featured-event-slide .featured-event-image {
    height: 300px;  /* Image container */
}

.featured-event-slide .featured-event-image img {
    height: 300px;  /* Actual image */
}
```

## Related Files

- `public/css/featured-events.css` - Main styles
- `templates/components/featured_events_banner.html.twig` - HTML structure
- `public/js/featured-events-banner.js` - JavaScript logic

## See Also

- [Featured Events Documentation](../README.md#featured-events)
- [Carousel Bootstrap Docs](https://getbootstrap.com/docs/5.3/components/carousel/)
- [CSS Z-Index Guide](https://developer.mozilla.org/en-US/docs/Web/CSS/z-index)

## Troubleshooting

### Controls Still Overlapping?
1. Clear browser cache (Ctrl+Shift+R / Cmd+Shift+R)
2. Check browser dev tools for CSS conflicts
3. Verify z-index values are applied (inspect element)

### Text Not Readable?
1. Check overlay gradient opacity
2. Ensure image isn't too bright
3. Adjust text shadow if needed

### Indicators Not Visible?
1. Check `showIndicators` setting in JavaScript
2. Verify multiple events exist
3. Check z-index is applied
4. Ensure bottom spacing is adequate

## Performance Notes

- Z-index doesn't impact performance
- Transitions remain smooth
- No additional JavaScript needed
- CSS-only solution (no JS overhead)
