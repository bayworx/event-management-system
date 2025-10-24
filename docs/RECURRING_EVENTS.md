# Recurring Events Feature

## Overview

The Event Management System now supports recurring events. This allows you to create a single event that automatically generates multiple instances based on a recurrence pattern.

## Features

- **Recurrence Patterns**: Daily, Weekly, Monthly, Yearly
- **Flexible Configuration**: Define interval (e.g., every 2 weeks) and end condition (date or count)
- **Automatic Instance Generation**: Child events are created automatically with copied settings
- **Parent-Child Relationship**: All instances link back to the parent event
- **Easy Management**: Update parent event to regenerate all instances

## How to Create a Recurring Event

### Via Admin Interface

1. Navigate to **Admin → Events → Create Event**
2. Fill in the event details (title, description, dates, etc.)
3. In the **Settings** section, check **"Recurring Event"**
4. Configure the recurrence settings:
   - **Repeat Pattern**: Choose Daily, Weekly, Monthly, or Yearly
   - **Repeat Every**: Specify the interval (e.g., 1 for weekly, 2 for bi-weekly)
   - **Repeat Until**: Choose either:
     - **End Date**: Specify when to stop creating instances
     - **Number of Occurrences**: Specify how many instances to create
5. Click **Create Event**

The system will automatically:
- Create the parent event
- Generate all recurring instances based on your pattern
- Copy all settings (presenters, agenda, administrators, etc.) to each instance
- Display a success message showing how many instances were created

### Example Scenarios

#### Weekly Meeting for 10 Weeks
- Pattern: Weekly
- Interval: 1
- Occurrences: 10

#### Monthly Webinar for 6 Months
- Pattern: Monthly
- Interval: 1
- Occurrences: 6

#### Bi-Weekly Training Until Year End
- Pattern: Weekly
- Interval: 2
- End Date: December 31, 2025

## Managing Recurring Events

### Editing the Parent Event

When you edit a parent event that already has recurring instances:

1. Navigate to the event in the admin panel
2. Click **Edit**
3. Make your changes
4. Click **Update Event**

**Important**: Changing recurrence settings (pattern, interval, end date, count) will **delete and regenerate all child instances**. This ensures all instances stay in sync with the parent.

### Editing Individual Instances

Each recurring instance can be edited independently:

1. Find the instance in the event list or via the parent event's detail page
2. Click **Edit** on the instance
3. Make your changes (dates, description, etc.)
4. Click **Update Event**

**Note**: Individual instances show an alert indicating they're part of a recurring series. Changes to individual instances won't affect other instances or the parent.

### Viewing Recurring Instances

On the parent event's detail page, you'll see:
- A badge indicating it's a recurring series
- The recurrence pattern details
- A list of all child events (showing first 10 with link to see more)
- Each instance is linked for easy navigation

## Database Structure

### New Fields Added to Event Entity

- `isRecurring` (boolean): Marks if event is a recurring parent
- `recurrencePattern` (string): daily, weekly, monthly, yearly
- `recurrenceInterval` (integer): Number of pattern units (e.g., every 2 weeks)
- `recurrenceEndDate` (datetime): Optional end date for recurrence
- `recurrenceCount` (integer): Optional number of occurrences
- `parentEvent` (relationship): Links child to parent event
- `childEvents` (collection): Collection of all instances

### Relationships

```
Parent Event (isRecurring = true)
  ├─ Child Event Instance 1 (parentEvent → Parent)
  ├─ Child Event Instance 2 (parentEvent → Parent)
  ├─ Child Event Instance 3 (parentEvent → Parent)
  └─ ...
```

## RecurringEventService

The `RecurringEventService` handles all recurring event logic:

### Methods

- `generateRecurringInstances(Event $parentEvent): array`
  - Generates all child event instances based on parent's recurrence settings
  - Returns array of created Event objects

- `deleteRecurringInstances(Event $parentEvent): int`
  - Deletes all child events of a recurring series
  - Returns count of deleted instances

- `regenerateRecurringInstances(Event $parentEvent): array`
  - Deletes existing instances and generates new ones
  - Used when parent event's recurrence settings are updated
  - Returns array of newly created Event objects

### Usage Example

```php
use App\Service\RecurringEventService;

// Inject service
public function __construct(
    private RecurringEventService $recurringEventService
) {}

// Generate instances
if ($event->isRecurring()) {
    $instances = $this->recurringEventService->generateRecurringInstances($event);
    foreach ($instances as $instance) {
        $entityManager->persist($instance);
    }
    $entityManager->flush();
}
```

## What Gets Copied to Instances

When creating recurring instances, the following are copied from the parent:

- Title
- Description
- Location
- Max Attendees
- Active status
- Banner image
- All administrators
- All agenda items (with times adjusted)
- All presenters (with times adjusted)

**Not Copied**:
- Attendees (each instance has its own attendee list)
- Event files (could be added if needed)

## Technical Notes

### Slug Generation

Each recurring instance gets a unique slug:
```
{base-slug}-{date}-{sequence}
Example: weekly-meeting-2025-10-24-1
```

### Safety Limits

- Maximum 100 instances created if using end date (prevents runaway generation)
- Validation ensures either end date OR count is specified (not both)

### Database Migration

Migration file: `migrations/Version20251024165349.php`

To apply:
```bash
bin/console doctrine:migrations:migrate
```

To rollback:
```bash
bin/console doctrine:migrations:execute --down "DoctrineMigrations\\Version20251024165349"
```

## Best Practices

1. **Use Count for Fixed Series**: If you know exactly how many times an event will occur, use count instead of end date
2. **Test First**: Create a test recurring event with a small count (2-3) to verify settings before creating large series
3. **Individual Adjustments**: For exceptions (e.g., one week cancelled), edit that specific instance rather than regenerating the series
4. **Clear Communication**: The parent event shows "Recurring Series" badge, instances show "Instance of Recurring Series" badge

## Troubleshooting

### No Instances Created

Check:
- Pattern is selected
- Either end date OR count is specified (not both)
- End date is after start date
- Interval is at least 1

### Too Many/Too Few Instances

- Verify recurrence count or end date
- Remember: count does not include the parent event itself (parent + count instances)

### Changes Not Reflected in Instances

- Parent event changes to recurrence settings trigger full regeneration
- Changes to other fields (title, description) don't automatically propagate to existing instances
- Consider regenerating if needed by re-saving parent event

## Future Enhancements

Potential features to add:
- [ ] More complex patterns (e.g., every 2nd Tuesday of the month)
- [ ] Exception dates (skip specific occurrences)
- [ ] Bulk edit all instances
- [ ] Copy event files to instances
- [ ] Recurrence preview before saving
- [ ] Weekly patterns with specific days (e.g., every Monday and Wednesday)
