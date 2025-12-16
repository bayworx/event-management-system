# Automatic Event Deactivation

## Overview

The system includes a command to automatically deactivate events that have ended. This helps keep your event listings clean by hiding past events from the public-facing site while preserving all event data and attendee information.

## How It Works

The command `app:deactivate-past-events` finds all active events where:
- The `endDate` has passed (if set), OR
- The `startDate` has passed (if no `endDate` is set)

It then sets the `isActive` flag to `false` for these events, effectively hiding them from public listings while keeping all data intact.

## Command Usage

### Basic Usage
```bash
# Deactivate all past events
bin/console app:deactivate-past-events
```

### Options

**Dry Run Mode** - Preview what would be deactivated without making changes:
```bash
bin/console app:deactivate-past-events --dry-run
```

**Grace Period** - Wait X hours after event ends before deactivating:
```bash
# Wait 24 hours after event ends
bin/console app:deactivate-past-events --grace-period=24

# Wait 48 hours after event ends
bin/console app:deactivate-past-events --grace-period=48
```

### Examples

**Test run before actually deactivating:**
```bash
# See what would be deactivated
bin/console app:deactivate-past-events --dry-run

# If output looks good, run without dry-run
bin/console app:deactivate-past-events
```

**Give events a grace period:**
```bash
# Useful if you want attendees to access event materials for 48 hours after
bin/console app:deactivate-past-events --grace-period=48
```

## Setting Up Automatic Execution

### Option 1: Cron Job (Recommended)

Add this to your crontab to run daily at 2 AM:

```bash
# Open crontab editor
crontab -e

# Add this line (adjust path to your installation)
0 2 * * * cd /home/jhill/event-management-system && php bin/console app:deactivate-past-events >> /var/log/deactivate-events.log 2>&1
```

**With grace period:**
```bash
# Run daily at 2 AM with 24-hour grace period
0 2 * * * cd /home/jhill/event-management-system && php bin/console app:deactivate-past-events --grace-period=24 >> /var/log/deactivate-events.log 2>&1
```

**Run multiple times per day:**
```bash
# Run every 6 hours
0 */6 * * * cd /home/jhill/event-management-system && php bin/console app:deactivate-past-events >> /var/log/deactivate-events.log 2>&1
```

### Option 2: Systemd Timer (Alternative)

Create a systemd service:

**File: `/etc/systemd/system/deactivate-events.service`**
```ini
[Unit]
Description=Deactivate Past Events
After=network.target

[Service]
Type=oneshot
User=www-data
WorkingDirectory=/home/jhill/event-management-system
ExecStart=/usr/bin/php /home/jhill/event-management-system/bin/console app:deactivate-past-events
StandardOutput=journal
StandardError=journal
```

Create a systemd timer:

**File: `/etc/systemd/system/deactivate-events.timer`**
```ini
[Unit]
Description=Run Event Deactivation Daily

[Timer]
OnCalendar=daily
OnCalendar=02:00
Persistent=true

[Install]
WantedBy=timers.target
```

Enable and start the timer:
```bash
sudo systemctl daemon-reload
sudo systemctl enable deactivate-events.timer
sudo systemctl start deactivate-events.timer

# Check status
sudo systemctl status deactivate-events.timer

# View logs
journalctl -u deactivate-events.service
```

### Option 3: Laravel Scheduler Style (Using Cron + Wrapper)

Create a simple wrapper script:

**File: `bin/schedule.sh`**
```bash
#!/bin/bash

# Change to project directory
cd /home/jhill/event-management-system

# Run commands
php bin/console app:deactivate-past-events --grace-period=24
```

Make it executable:
```bash
chmod +x bin/schedule.sh
```

Add single cron entry:
```bash
# Run schedule wrapper every day at 2 AM
0 2 * * * /home/jhill/event-management-system/bin/schedule.sh >> /var/log/event-scheduler.log 2>&1
```

## What Happens When Events Are Deactivated

### For Public Users
- Event no longer appears in public event listings
- Direct links to event pages will show "Event not found" or similar
- Registration is disabled

### For Admins
- Event still appears in admin dashboard (can filter by active/inactive)
- All event data, attendees, files, and agenda are preserved
- Event can be manually reactivated if needed

### For Attendees
- Existing attendees can still access their dashboard (if authenticated)
- File downloads may still work (depends on your access control implementation)

## Logging

Each deactivation is logged with:
- Event ID, title, and slug
- End date/time
- Deactivation reason: `past_event`
- Grace period used (if any)

Logs are written to the event management channel. Check:
```bash
tail -f var/log/dev.log | grep event_auto_deactivated
```

## Manual Reactivation

If you need to reactivate an event that was automatically deactivated:

### Via Admin Panel
1. Go to Admin Dashboard → Events
2. Filter for inactive events
3. Edit the event
4. Check the "Active" checkbox
5. Save

### Via Command Line
```bash
# Direct SQL update (replace ID with your event ID)
php bin/console dbal:run-sql "UPDATE event SET is_active = 1 WHERE id = 123"
```

## Best Practices

1. **Test First**: Always run with `--dry-run` in production first
2. **Use Grace Period**: Consider a 24-48 hour grace period for attendees to download materials
3. **Monitor Logs**: Check logs after setup to ensure it's working correctly
4. **Backup Before First Run**: Backup your database before running the first time
5. **Schedule During Low Traffic**: Run during off-peak hours (e.g., 2-4 AM)

## Troubleshooting

### Command Not Running from Cron

**Check cron logs:**
```bash
grep CRON /var/log/syslog
```

**Ensure full paths are used:**
```bash
# Bad - relies on PATH
0 2 * * * cd /path/to/app && php bin/console app:deactivate-past-events

# Good - full paths specified
0 2 * * * cd /path/to/app && /usr/bin/php bin/console app:deactivate-past-events
```

**Check file permissions:**
```bash
# Console should be executable
chmod +x bin/console
```

### No Events Being Deactivated

**Check if events have end dates:**
```bash
# If events only have start dates, they use start date for deactivation
bin/console app:deactivate-past-events --dry-run
```

**Verify database dates:**
```bash
# Check upcoming events and their end dates
php bin/console dbal:run-sql "SELECT id, title, start_date, end_date, is_active FROM event ORDER BY end_date DESC LIMIT 10"
```

### Events Deactivated Too Early

**Use grace period:**
```bash
# Wait 24 hours after event ends
bin/console app:deactivate-past-events --grace-period=24
```

**Update cron job to include grace period:**
```bash
0 2 * * * cd /path/to/app && php bin/console app:deactivate-past-events --grace-period=24
```

## Related Features

- **Featured Events**: Deactivated events are automatically removed from featured listings
- **Public Listings**: Inactive events don't appear in public event searches
- **Admin Dashboard**: Use status filters to view active vs inactive events
- **Event Archives**: Consider creating an "Archives" page to showcase past events

## Future Enhancements

Potential improvements for this feature:

1. **Archive Status**: Add separate "archived" status vs "inactive"
2. **Auto-Archive After X Days**: Archive events X days after deactivation
3. **Email Notifications**: Notify admins when events are auto-deactivated
4. **Recurring Events**: Special handling for recurring event series
5. **Reactivation Alerts**: Alert if someone tries to access a deactivated event
6. **Statistics Tracking**: Track how long events remain active vs inactive

## Support

For issues or questions:
- Check application logs: `var/log/dev.log`
- Run with `--dry-run` first
- Contact system administrator if events aren't deactivating as expected
