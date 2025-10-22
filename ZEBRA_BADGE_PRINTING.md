# ZEBRA ZD621 Badge Printing System

## Overview
This system provides badge printing support specifically optimized for the **ZEBRA ZD621 4-inch thermal printer**. The system generates both preview templates and ZPL (Zebra Programming Language) files for direct thermal printing.

## 🖨️ Printer Specifications

### ZEBRA ZD621 4" Printer Details:
- **Model**: ZD621 Desktop Thermal Printer
- **Print Width**: 4 inches (102mm)  
- **Resolution**: 208 DPI (dots per inch)
- **Print Technology**: Direct thermal / Thermal transfer
- **Badge Size**: 4" × 3" (832 × 624 dots)
- **Language**: ZPL (Zebra Programming Language)

## 🏷️ Badge Layout

### Badge Dimensions:
- **Total Size**: 4" × 3" (832 × 624 dots at 208 DPI)
- **Printable Area**: 3.9" × 2.9" (with 0.05" margins)
- **Font Sizes**: Scalable from 20pt to 80pt for various sections

### Badge Sections:
1. **Header Section** (120 dots tall)
   - Event title (max 25 characters)
   - Event date
   - "ATTENDEE" type badge

2. **Main Section** (300 dots tall)  
   - Attendee name (large, prominent, max 20 characters)
   - Organization (if present, max 30 characters)
   - Job title (if present, max 35 characters)

3. **Footer Section** (80 dots tall)
   - Attendee ID
   - Event location (if present, max 25 characters)
   - Optional: QR code area

## 📄 File Formats

### 1. ZPL Format (.zpl)
- **Purpose**: Direct printing to ZEBRA printers
- **Content-Type**: `text/plain`
- **Usage**: Send directly to printer via USB, network, or print driver
- **Benefits**: Fastest printing, no conversion needed

### 2. HTML Preview
- **Purpose**: Visual preview and browser printing
- **Layout**: Matches ZPL output exactly
- **Styling**: Monospace font, exact dimensions
- **Benefits**: Preview before printing, fallback printing option

## 🔗 Access Points

### Admin Interface Routes:
```
/admin/events/{id}/badges/attendees/zebra       # HTML Preview
/admin/events/{id}/badges/attendees/zebra?format=zpl  # ZPL Download
```

### UI Integration:
- **Location**: Event management page → Attendees section
- **Button**: "Print Badges" dropdown menu
- **Options**:
  - Standard Badges (A4) - existing format
  - ZEBRA ZD621 (4" Thermal) - new preview format  
  - Download ZPL File - direct printer file

## 🛠️ ZPL Commands Used

### Key ZPL Commands in Template:
```zpl
^XA                 # Start of format
^PW832             # Print width (832 dots for 4")
^LL624             # Label length (624 dots for 3")
^CI28              # Character encoding (UTF-8)
^LH0,0            # Label home position
^FO{x},{y}        # Field origin (positioning)
^GB{w},{h},{t}    # Graphic box (borders/lines)
^CF0,{h},{w}      # Change font (font, height, width)
^FD{text}         # Field data (actual text)
^FS               # Field separator (end field)
^XZ               # End of format
```

### Font Sizes Used:
- **Event Title**: 60×60 dots (large, bold)
- **Event Date**: 30×30 dots (medium)
- **Attendee Name**: 80×80 dots (very large)
- **Organization**: 40×40 dots (medium)
- **Job Title**: 30×30 dots (small-medium)  
- **Footer Info**: 25×25 dots (small)
- **Type Badge**: 20×20 dots (very small)

## 🖥️ Usage Instructions

### For Administrators:

1. **Access Badge Printing**:
   - Go to Event Management → Select Event → Attendees section
   - Click "Print Badges" dropdown button
   - Choose desired format

2. **Preview Format**:
   - Select "ZEBRA ZD621 (4" Thermal)" for HTML preview
   - Review layout and attendee information
   - Use browser print if needed

3. **Direct Printer Format**:
   - Select "Download ZPL File" to get `.zpl` file
   - Send file directly to ZEBRA printer
   - Use printer's network interface or USB connection

### For Print Operators:

1. **Printer Setup**:
   ```
   - Load 4" × 3" thermal labels
   - Set printer to 208 DPI mode
   - Configure for direct thermal printing
   - Ensure proper driver installation
   ```

2. **Printing Methods**:
   
   **Method A: Direct File Transfer**
   ```bash
   # Copy ZPL file to printer (Windows)
   copy event-badges-{id}.zpl \\printer-ip\raw
   
   # Send via network (Linux/Mac)
   cat event-badges-{id}.zpl | nc printer-ip 9100
   ```

   **Method B: Printer Driver**
   ```
   1. Download .zpl file
   2. Open with text editor
   3. Select all content
   4. Send to ZEBRA printer via print driver
   ```

   **Method C: ZEBRA Utilities**
   ```
   - Use ZebraDesigner software
   - Use ZEBRA Browser Print
   - Use manufacturer's printing utilities
   ```

## 🔧 Technical Details

### Template Logic:
```twig
{% if request.query.get('format') == 'zpl' %}
  {# Generate ZPL commands #}
  {{ response.headers.set('Content-Type', 'text/plain') }}
  {% for attendee in event.attendees %}
    {# ZPL badge template #}
  {% endfor %}
{% else %}
  {# Generate HTML preview #}
{% endif %}
```

### Character Limits:
- Event title: 25 characters (prevents text overflow)
- Attendee name: 20 characters (fits in largest font)
- Organization: 30 characters (medium section)
- Job title: 35 characters (smaller font allows more)
- Location: 25 characters (footer space)

### Text Processing:
- All text converted to uppercase for better thermal printing
- Special characters filtered for ZPL compatibility  
- Long text truncated with `|slice()` filter
- Date formatting optimized for space

## 🚀 Features

### Current Features:
- ✅ **Dual Format Output**: HTML preview + ZPL download
- ✅ **Proper Sizing**: Exact 4" × 3" dimensions  
- ✅ **Professional Layout**: Header, body, footer sections
- ✅ **Dynamic Content**: Event details, attendee info
- ✅ **Text Optimization**: Character limits, uppercase
- ✅ **UI Integration**: Dropdown menu in admin interface
- ✅ **Print Controls**: Easy access to both formats

### Optional Enhancements (Commented):
- **QR Codes**: Uncomment QR code section in template
- **Logos**: Add company/event logos with `^GF` command
- **Barcodes**: Add Code 128 or other formats
- **Advanced Graphics**: Custom borders, shapes

## 📋 File Structure

### New Files Created:
```
templates/admin/event/badges/attendees-zpl.html.twig  # ZPL template
ZEBRA_BADGE_PRINTING.md                               # This documentation
```

### Modified Files:
```
src/Controller/Admin/AdminEventController.php         # Added zebra route
templates/admin/event/show.html.twig                  # Added UI dropdown
```

## 🔍 Testing

### Test Checklist:
- [ ] **Route Access**: `/admin/events/{id}/badges/attendees/zebra`
- [ ] **HTML Preview**: Displays badge layout correctly  
- [ ] **ZPL Download**: `?format=zpl` parameter works
- [ ] **Content Display**: All attendee fields populate
- [ ] **Text Limits**: Long text truncates properly
- [ ] **UI Integration**: Dropdown menu functions
- [ ] **File Download**: ZPL file downloads with correct name

### Print Test:
1. Download ZPL file for test event
2. Send to ZEBRA ZD621 printer
3. Verify badge dimensions (4" × 3")  
4. Check text quality and positioning
5. Confirm all attendee information prints correctly

## 🔧 Troubleshooting

### Common Issues:

**Issue**: Badge text appears cut off
**Solution**: Check character limits in template, reduce font sizes if needed

**Issue**: ZPL file won't print  
**Solution**: Verify printer supports ZPL, check network connectivity

**Issue**: Wrong badge dimensions
**Solution**: Confirm printer paper setup matches 4" × 3" labels

**Issue**: Special characters don't print
**Solution**: ZPL has limited character support, use ASCII equivalents

### Print Quality:
- **Darkness**: Adjust printer darkness setting (0-30)
- **Speed**: Slower printing = better quality (2-6 IPS)
- **Media**: Use high-quality thermal labels
- **Cleaning**: Keep printhead clean for optimal output

## 📞 Support

For technical support:
1. Check printer manual for ZPL command reference
2. Verify badge template syntax  
3. Test with simple ZPL commands first
4. Contact ZEBRA support for printer-specific issues

---

**Last Updated**: October 2024
**Printer Model**: ZEBRA ZD621 4-inch Desktop Printer  
**Template Version**: 1.0