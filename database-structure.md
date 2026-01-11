# Database Structure - RobotStaff

This document describes the complete database structure for the RobotStaff application.

## Overview

The database is designed for a Laravel application using MariaDB, managing staff assignments for events with a focus on flexibility and user-friendly registration processes.

**Security Note:** Tables containing sensitive data or file paths use UUID primary keys instead of auto-incrementing integers to prevent enumeration attacks and protect privacy.

---

## Tables

### 1. `users`
Stores admin user accounts with authentication credentials.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| `name` | VARCHAR(255) | NOT NULL | Admin name |
| `email` | VARCHAR(255) | NOT NULL, UNIQUE | Admin email address |
| `email_verified_at` | TIMESTAMP | NULLABLE | Email verification timestamp |
| `password` | VARCHAR(255) | NOT NULL | Hashed password |
| `remember_token` | VARCHAR(100) | NULLABLE | Laravel remember token |
| `created_at` | TIMESTAMP | NULLABLE | Record creation timestamp |
| `updated_at` | TIMESTAMP | NULLABLE | Record update timestamp |

**Indexes:**
- Primary key on `id`
- Unique index on `email`

---

### 2. `staff`
Stores staff member information and authentication tokens.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | CHAR(36) | PRIMARY KEY | UUID identifier |
| `username` | VARCHAR(255) | NOT NULL, UNIQUE | Unique staff username |
| `email` | VARCHAR(255) | NOT NULL, UNIQUE | Staff email address |
| `first_name` | VARCHAR(255) | NULLABLE | Staff first name |
| `last_name` | VARCHAR(255) | NULLABLE | Staff last name |
| `phone_number` | VARCHAR(50) | NULLABLE | Contact phone number |
| `city` | VARCHAR(255) | NULLABLE | City of residence |
| `languages` | TEXT | NULLABLE | Languages spoken (JSON or comma-separated) |
| `comment` | TEXT | NULLABLE | Free-form comments about skills/experience |
| `photo_path` | VARCHAR(255) | NULLABLE | Path to profile photo |
| `auth_token` | VARCHAR(255) | NOT NULL, UNIQUE | Unique token for passwordless authentication |
| `token_expires_at` | TIMESTAMP | NULLABLE | Token expiration timestamp (60 days) |
| `last_login_at` | TIMESTAMP | NULLABLE | Last login timestamp |
| `created_at` | TIMESTAMP | NULLABLE | Record creation timestamp |
| `updated_at` | TIMESTAMP | NULLABLE | Record update timestamp |

**Indexes:**
- Primary key on `id`
- Unique index on `username`
- Unique index on `email`
- Unique index on `auth_token`

**UUID Usage:** This table uses UUID to prevent enumeration of staff members and protect photo file paths from discovery.

---

### 3. `events`
Stores event information and configuration.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | CHAR(36) | PRIMARY KEY | UUID identifier |
| `name` | VARCHAR(255) | NOT NULL | Event name |
| `tagname` | VARCHAR(255) | NOT NULL, UNIQUE | URL-friendly unique identifier |
| `short_description` | TEXT | NULLABLE | Brief event description |
| `long_description` | TEXT | NULLABLE | Detailed event description |
| `start_date` | DATE | NOT NULL | Event start date |
| `end_date` | DATE | NOT NULL | Event end date |
| `location` | VARCHAR(255) | NULLABLE | Event location |
| `contact_email` | VARCHAR(255) | NULLABLE | Event contact email |
| `logo_path` | VARCHAR(255) | NULLABLE | Path to event logo |
| `whatsapp_link` | TEXT | NULLABLE | WhatsApp group URL |
| `general_documents_links` | TEXT | NULLABLE | Links to general documents (JSON array) |
| `created_at` | TIMESTAMP | NULLABLE | Record creation timestamp |
| `updated_at` | TIMESTAMP | NULLABLE | Record update timestamp |

**Indexes:**
- Primary key on `id`
- Unique index on `tagname`

**UUID Usage:** This table uses UUID to prevent enumeration of events and protect logo file paths from discovery.

---

### 4. `event_days`
Stores daily schedule information for multi-day events.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| `event_id` | CHAR(36) | NOT NULL, FOREIGN KEY | Reference to events table |
| `date` | DATE | NOT NULL | Specific day of the event |
| `schedule` | TEXT | NULLABLE | Hour schedule for this day |
| `created_at` | TIMESTAMP | NULLABLE | Record creation timestamp |
| `updated_at` | TIMESTAMP | NULLABLE | Record update timestamp |

**Indexes:**
- Primary key on `id`
- Foreign key on `event_id` references `events(id)` ON DELETE CASCADE
- Index on `event_id`

---

### 5. `event_roles`
Stores role definitions for each event.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| `event_id` | CHAR(36) | NOT NULL, FOREIGN KEY | Reference to events table |
| `designation` | VARCHAR(255) | NOT NULL | Role name (e.g., judge, referee, volunteer) |
| `number_required` | INT UNSIGNED | NOT NULL, DEFAULT 0 | Number of staff needed for this role |
| `document_links` | TEXT | NULLABLE | Links to role-specific documents (JSON array) |
| `created_at` | TIMESTAMP | NULLABLE | Record creation timestamp |
| `updated_at` | TIMESTAMP | NULLABLE | Record update timestamp |

**Indexes:**
- Primary key on `id`
- Foreign key on `event_id` references `events(id)` ON DELETE CASCADE
- Index on `event_id`

---

### 6. `staff_event_registrations`
Main pivot table linking staff to events with registration details.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | CHAR(36) | PRIMARY KEY | UUID identifier |
| `staff_id` | CHAR(36) | NOT NULL, FOREIGN KEY | Reference to staff table |
| `event_id` | CHAR(36) | NOT NULL, FOREIGN KEY | Reference to events table |
| `comment` | TEXT | NULLABLE | Staff comment for this registration |
| `help_before_event` | BOOLEAN | DEFAULT FALSE | Willing to help before event |
| `team_affiliation` | TEXT | NULLABLE | Teams the staff is affiliated with |
| `is_first_participation` | BOOLEAN | DEFAULT FALSE | First time participating |
| `is_validated` | BOOLEAN | DEFAULT FALSE | Admin has validated participation |
| `assigned_role_id` | BIGINT UNSIGNED | NULLABLE, FOREIGN KEY | Final role assigned by admin |
| `created_at` | TIMESTAMP | NULLABLE | Record creation timestamp |
| `updated_at` | TIMESTAMP | NULLABLE | Record update timestamp |

**Indexes:**
- Primary key on `id`
- Foreign key on `staff_id` references `staff(id)` ON DELETE CASCADE
- Foreign key on `event_id` references `events(id)` ON DELETE CASCADE
- Foreign key on `assigned_role_id` references `event_roles(id)` ON DELETE SET NULL
- Unique composite index on `(staff_id, event_id)`

**UUID Usage:** This table uses UUID to prevent enumeration of registrations and protect privacy of staff-event relationships.

---

### 7. `staff_role_preferences`
Stores staff role preferences for each event (up to 3, ordered).

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| `registration_id` | CHAR(36) | NOT NULL, FOREIGN KEY | Reference to staff_event_registrations |
| `role_id` | BIGINT UNSIGNED | NOT NULL, FOREIGN KEY | Reference to event_roles table |
| `preference_order` | TINYINT UNSIGNED | NOT NULL | Order of preference (1, 2, or 3) |
| `created_at` | TIMESTAMP | NULLABLE | Record creation timestamp |
| `updated_at` | TIMESTAMP | NULLABLE | Record update timestamp |

**Indexes:**
- Primary key on `id`
- Foreign key on `registration_id` references `staff_event_registrations(id)` ON DELETE CASCADE
- Foreign key on `role_id` references `event_roles(id)` ON DELETE CASCADE
- Unique composite index on `(registration_id, preference_order)`
- Index on `registration_id`

**Constraints:**
- `preference_order` must be between 1 and 3
- Maximum 3 preferences per registration

---

### 8. `staff_availability`
Stores staff availability for each day of an event (morning/afternoon).

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| `registration_id` | CHAR(36) | NOT NULL, FOREIGN KEY | Reference to staff_event_registrations |
| `event_day_id` | BIGINT UNSIGNED | NOT NULL, FOREIGN KEY | Reference to event_days table |
| `is_available_morning` | BOOLEAN | DEFAULT FALSE | Available in the morning |
| `is_available_afternoon` | BOOLEAN | DEFAULT FALSE | Available in the afternoon |
| `created_at` | TIMESTAMP | NULLABLE | Record creation timestamp |
| `updated_at` | TIMESTAMP | NULLABLE | Record update timestamp |

**Indexes:**
- Primary key on `id`
- Foreign key on `registration_id` references `staff_event_registrations(id)` ON DELETE CASCADE
- Foreign key on `event_day_id` references `event_days(id)` ON DELETE CASCADE
- Unique composite index on `(registration_id, event_day_id)`

---

### 9. `site_preferences`
Stores global website settings (single row table).

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique identifier (always 1) |
| `association_description` | TEXT | NULLABLE | Description of the association |
| `logo_path` | VARCHAR(255) | NULLABLE | Path to association logo |
| `website_url` | VARCHAR(255) | NULLABLE | Association website URL |
| `general_whatsapp_link` | TEXT | NULLABLE | Link to general WhatsApp channel |
| `created_at` | TIMESTAMP | NULLABLE | Record creation timestamp |
| `updated_at` | TIMESTAMP | NULLABLE | Record update timestamp |

**Indexes:**
- Primary key on `id`

**Note:** This table should only have one row (id = 1).

---

### 10. `email_notifications`
Tracks email notifications sent to prevent spam.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| `recipient_type` | ENUM('admin', 'staff') | NOT NULL | Type of recipient |
| `recipient_id` | VARCHAR(36) | NOT NULL | ID of admin (BIGINT) or staff (UUID) |
| `event_id` | CHAR(36) | NULLABLE, FOREIGN KEY | Related event (if applicable) |
| `notification_type` | VARCHAR(100) | NOT NULL | Type of notification sent |
| `sent_at` | TIMESTAMP | NOT NULL | Timestamp when email was sent |
| `created_at` | TIMESTAMP | NULLABLE | Record creation timestamp |

**Indexes:**
- Primary key on `id`
- Foreign key on `event_id` references `events(id)` ON DELETE CASCADE
- Composite index on `(recipient_type, recipient_id, sent_at)` for cooldown checks

**Note:** `recipient_id` is stored as VARCHAR(36) to accommodate both BIGINT (users) and UUID (staff) identifiers.

**Notification Types:**
- `staff_participation_validated` - Admin validated staff participation
- `staff_role_assigned` - Admin assigned final role to staff
- `event_reminder` - Admin sent event reminder
- `new_staff_registration` - Staff registered for event
- `staff_preferences_changed` - Staff updated role preferences or availability
- `connection_link` - Email with connection link sent to staff

---

## Relationships

### One-to-Many Relationships

1. **events → event_days**
   - One event can have multiple days
   - Cascade delete: Deleting an event removes all its days

2. **events → event_roles**
   - One event can have multiple roles
   - Cascade delete: Deleting an event removes all its roles

3. **events → staff_event_registrations**
   - One event can have multiple staff registrations
   - Cascade delete: Deleting an event removes all registrations

4. **staff → staff_event_registrations**
   - One staff member can register for multiple events
   - Cascade delete: Deleting a staff member removes all their registrations

5. **staff_event_registrations → staff_role_preferences**
   - One registration can have up to 3 role preferences
   - Cascade delete: Deleting a registration removes preferences

6. **staff_event_registrations → staff_availability**
   - One registration can have availability for multiple event days
   - Cascade delete: Deleting a registration removes availability data

7. **event_days → staff_availability**
   - One event day can have availability entries for multiple staff
   - Cascade delete: Deleting an event day removes related availability

8. **event_roles → staff_role_preferences**
   - One role can be preferred by multiple staff members
   - Cascade delete: Deleting a role removes it from preferences

9. **events → email_notifications**
   - One event can trigger multiple notifications
   - Cascade delete: Deleting an event removes notification history

### Many-to-One Relationships

1. **staff_event_registrations → event_roles (assigned_role_id)**
   - Each registration can have one assigned role
   - Set null on delete: Deleting a role clears assignments

---

## Business Logic Constraints

### Registration Completeness
A staff registration is considered "complete" when:
- At least one role preference is selected (1 to 3 preferences allowed)
- Availability is specified for at least one half-day
- All required fields in staff profile are filled (first_name, last_name, phone_number)
- Optional fields (city, languages, comment, photo) do NOT affect completeness

### Profile Completeness
A staff profile is considered "complete" when:
- first_name is filled
- last_name is filled
- phone_number is filled
- All other fields (city, languages, comment, photo) are optional

### Event Visibility Rules
- Staff can see all future events (events that haven't ended yet)
- Staff can only see past events they have participated in (registered for)
- Admins can see all events (past, present, and future)

### Staff Data Deletion
- When staff request data deletion, records are anonymized (not deleted)
- Anonymization removes all personal identifiers while preserving statistical data
- Fields to anonymize: username, email, first_name, last_name, phone_number, city, languages, comment, photo_path
- Event registration records remain with anonymized staff reference for admin reporting

### Availability Defaults
- When staff first register for an event, all half-days default to "available" (checked)
- Staff can then deselect specific half-days they're unavailable

### Email Cooldown
- Maximum 1 email per staff member every 5 minutes
- Implemented by checking `email_notifications` table for recent sends
- Applies to notification types: `staff_preferences_changed`
- Does NOT apply to: `new_staff_registration` (admins receive immediate notifications)

### Token Management
- Staff authentication tokens expire after 60 days
- Token expiration is refreshed on each login
- Expired tokens should be regenerated when staff requests new login link
- Clicking email link immediately logs staff in (counts as email validation)

### Role Assignment
- Admins can over-assign roles (more staff than `number_required`)
- Assigned role must be from the same event as the registration
- Staff can select 1 to 3 role preferences (not required to select exactly 3)

### Event Copy
- When copying an event, admin must provide new tagname before copy completes
- Copied event includes: event details, roles, and event days structure
- Does NOT copy: staff registrations, availability, or preferences

### Image Handling
- Supported formats: JPG, PNG only
- Maximum dimensions: 1000x1000 pixels
- Auto-resize behavior: Maintains aspect ratio (fit within 1000x1000), does NOT crop
- Smaller images are kept at original size

---

## Data Types & Storage Formats

### UUID Fields
The following tables use UUID (CHAR(36)) primary keys for security:
- `staff.id` - Prevents enumeration of staff members and protects photo paths
- `events.id` - Prevents enumeration of events and protects logo paths
- `staff_event_registrations.id` - Prevents enumeration of registrations

UUIDs are stored in standard format: `a8f5f167-9c5d-4f2e-b8d6-5c8e9f3a2b1c`

### JSON Fields
The following fields store JSON arrays:
- `staff.languages` - Array of language codes/names
- `events.general_documents_links` - Array of {title, url} objects
- `event_roles.document_links` - Array of {title, url} objects

### File Storage Paths
All file paths are relative to Laravel's storage directory:
- `staff.photo_path` - e.g., "photos/staff/a8f5f167-9c5d-4f2e-b8d6-5c8e9f3a2b1c/photo.jpg"
- `events.logo_path` - e.g., "logos/events/3d7f8a9b-4c2e-4d1f-9b6a-8e5f7c3d2a1b/logo.png"

Supported formats: JPG, PNG
Maximum dimensions: 1000x1000 pixels (auto-resized)

**Security:** Using UUID in file paths prevents enumeration attacks - attackers cannot iterate through sequential IDs to discover all photos/logos.

### Date/Time Fields
- All dates stored in `DATE` format (YYYY-MM-DD)
- All timestamps stored in `TIMESTAMP` format (UTC)
- Laravel handles timezone conversions

---

## Indexes Strategy

### Performance Optimization
- All foreign keys are indexed
- Unique constraints on natural keys (username, email, tagname, auth_token)
- Composite indexes on frequently joined columns
- Index on `sent_at` in `email_notifications` for cooldown queries

### Query Patterns
Common queries that benefit from these indexes:
1. Find staff by token: `auth_token` unique index
2. Find event by tagname: `tagname` unique index
3. List staff for event: `staff_event_registrations(event_id)` index
4. Check registration exists: `(staff_id, event_id)` composite unique index
5. Email cooldown check: `(recipient_type, recipient_id, sent_at)` composite index

### UUID Index Performance
- UUIDs as primary keys are indexed automatically
- Foreign key indexes on UUID columns perform well with proper configuration
- Composite indexes work efficiently with UUID columns

---

## Migration Sequence

Recommended order for creating migrations:

1. `users` (Laravel default, modify if needed)
2. `staff`
3. `events`
4. `event_days`
5. `event_roles`
6. `staff_event_registrations`
7. `staff_role_preferences`
8. `staff_availability`
9. `site_preferences`
10. `email_notifications`

This order ensures foreign key dependencies are satisfied.

---

## Laravel UUID Implementation Notes

### Model Configuration
For tables using UUID primary keys, Laravel models should:
- Disable auto-incrementing: `public $incrementing = false;`
- Set key type: `protected $keyType = 'string';`
- Use traits or boot methods to auto-generate UUIDs on creation

### Example Model Setup
```php
use Illuminate\Support\Str;

class Staff extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }
}
```

---

## Sample Queries

### Get complete registration data for a staff member
```sql
SELECT 
    e.name, e.tagname, e.start_date, e.end_date,
    ser.is_validated, 
    er_assigned.designation as assigned_role,
    GROUP_CONCAT(er_pref.designation ORDER BY srp.preference_order) as preferred_roles
FROM staff_event_registrations ser
JOIN events e ON ser.event_id = e.id
LEFT JOIN event_roles er_assigned ON ser.assigned_role_id = er_assigned.id
LEFT JOIN staff_role_preferences srp ON ser.id = srp.registration_id
LEFT JOIN event_roles er_pref ON srp.role_id = er_pref.id
WHERE ser.staff_id = ?
GROUP BY ser.id;
```

### Get availability grid for an event
```sql
SELECT 
    s.first_name, s.last_name,
    ed.date,
    sa.is_available_morning, sa.is_available_afternoon
FROM staff_event_registrations ser
JOIN staff s ON ser.staff_id = s.id
JOIN event_days ed ON ed.event_id = ser.event_id
LEFT JOIN staff_availability sa ON sa.registration_id = ser.id AND sa.event_day_id = ed.id
WHERE ser.event_id = ?
ORDER BY s.last_name, s.first_name, ed.date;
```

### Check email cooldown
```sql
SELECT COUNT(*) > 0 as has_recent_email
FROM email_notifications
WHERE recipient_type = 'staff'
  AND recipient_id = ?
  AND notification_type = 'staff_preferences_changed'
  AND sent_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE);
```

---

## Security Considerations

### UUID Benefits
1. **Enumeration Prevention**: Attackers cannot guess valid IDs by incrementing numbers
2. **Privacy Protection**: Staff and event counts cannot be inferred from IDs
3. **File Path Security**: Photo and logo paths use UUIDs, preventing systematic discovery
4. **URL Security**: Registration and staff URLs are non-guessable

### Mixed ID Strategy
- **UUID tables**: `staff`, `events`, `staff_event_registrations` (externally exposed or contain file paths)
- **BIGINT tables**: `users`, `event_days`, `event_roles`, `staff_role_preferences`, `staff_availability` (internal only, never exposed in URLs)

This balanced approach provides security where needed while maintaining simplicity for internal relationships.

---

## Notes

- All tables use Laravel's standard `created_at` and `updated_at` timestamps
- Soft deletes are NOT used - all deletions are hard deletes with cascade where appropriate
- The database supports multi-language UI but stores content in a single language
- All email addresses must be unique across the system
- Staff usernames must be unique and URL-friendly (no special validation specified)
- UUID storage requires 36 characters (standard format with hyphens)
- UUIDs are generated using UUID v4 (random) for maximum unpredictability
