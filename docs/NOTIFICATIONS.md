# Email Notification System

## Overview

The application displays toast notifications when emails are sent to users. This provides immediate visual feedback to confirm that email operations have been completed successfully.

## Implementation

### Backend (Laravel)

The backend uses Laravel's session flash messages to communicate success/error states to the frontend:

```php
// Example from AdminEventStaffController.php
return back()->with('success', __('admin.registration_validated'));
return back()->with('success', __('admin.role_assigned'));
return back()->with('success', __('admin.reminders_sent', ['count' => $registrations->count()]));
```

These flash messages are automatically passed to Inertia.js through the `HandleInertiaRequests` middleware:

```php
// app/Http/Middleware/HandleInertiaRequests.php
'flash' => [
    'success' => fn () => $request->session()->get('success'),
    'error' => fn () => $request->session()->get('error'),
],
```

### Frontend (Vue.js + Inertia.js)

The frontend uses [vue-sonner](https://github.com/xiaoluoboding/vue-sonner) for toast notifications.

#### Components

1. **AppShell.vue** - The main layout component that:
   - Includes the `<Toaster>` component for displaying toasts
   - Watches for flash messages from the backend
   - Displays success/error toasts when flash messages are received

2. **useToast.ts** - A composable that provides:
   - `success()` - Display success toast
   - `error()` - Display error toast  
   - `info()` - Display info toast
   - `warning()` - Display warning toast

#### Usage Example

Toasts are displayed automatically when flash messages are received from the backend. No additional frontend code is required for operations that already set flash messages.

To manually trigger a toast:

```vue
<script setup>
import { useToast } from '@/composables/useToast';

const { success, error } = useToast();

// Display a success toast
success('Operation completed successfully');

// Display an error toast
error('Something went wrong');
</script>
```

## Email Operations with Notifications

The following email operations automatically show toast notifications:

### Staff Authentication
- **Registration**: "A connection link has been sent to your email"
- **Login**: "A connection link has been sent to your email"

### Admin Staff Management  
- **Validate Registration**: "Registration validated"
- **Assign Role**: "Role assigned"
- **Send Reminders**: ":count reminders sent"

All messages are internationalized and can be found in the `lang/` directory.

## Customization

### Toast Position
Toasts appear in the top-right corner by default. To change this, modify the `position` prop in `AppShell.vue`:

```vue
<Toaster position="bottom-right" richColors />
```

Available positions: `top-left`, `top-center`, `top-right`, `bottom-left`, `bottom-center`, `bottom-right`

### Toast Styling
The `richColors` prop enables colored toasts based on the type (success = green, error = red, etc.). Remove this prop for neutral styling.

### Toast Duration
By default, toasts auto-dismiss after 4 seconds. To customize:

```typescript
// In useToast.ts
success('Message', {
    description: 'Optional description',
    duration: 5000, // milliseconds
});
```

## Testing

To test the notification system:

1. Log in as an admin
2. Navigate to an event's staff management page
3. Validate a registration or assign a role
4. Observe the toast notification appearing in the top-right corner

The toast will disappear automatically after a few seconds.
