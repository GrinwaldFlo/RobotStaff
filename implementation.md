# RobotStaff

This web application is designed to help manage all staff arround events.

It will provide:
- Event creation and management for admin.
- Staff registration and scheduling.
- Staff assignation
- Multiple tools modules

The full website is for an unique association. Each association wanting to use that will build it's own instance.

## Technologies

It is hosted in a standard PHP 8.4 / MARIADB server. 

A fresh install is provided:
- Framework: Laravel with Breeze (Inertia + Vue)
- Authentification system: Laravel
- Testing framework: Pest
- Laravel Boost is installed

## General behaviour

- When a field is changed, it should be directly updated without passing by a submit button. All changes are live
- Re-usable components should be privilegied
- It should be multi-language (English and French), maybe more in the future
- Should be a modern UI phone friendly

## Features

### User management

- There will be two types of users: Admin and Staff.

#### Admin

- They will have a real account with email and password.
- They can create events, manage staff registrations, and assign staff to events.
- Admin can see all events

#### Staff

- Their account is identified by a unique username and an email. They do not need a password.
- They can register for events and view their assigned events.
- They can update their availability for events.
- They can view their schedule.
- They can receive notifications about event assignments and updates.
- We store a cookie with a unique token to identify the staff user. So they can login without password.

### Event Management

- Admins can create, update, and delete events.
- It will ask for a name, tagname, date (can be multiple continuouse days), location, short description, long description, logo, hour schedule for each day, contact email, and required staff roles.
- We will add all staff roles like (e.g., judge, referee, volunteer, etc.). Also ask how many of each role is needed.

### Staff Registration
 
 - Staff can register for events by selecting the roles they are interested in.
 - In the registration page, we can first choose the event from a list of upcoming events.
 - Staff can update their availability for specific dates and times.
 - If the staff already interested in an event, we can show a message indicating that they have already registered for that event.
 
 - Registration steps:
    1. User is asked if they are a new or returning staff member. 
    1. For new memeber, it asks for username, email, phone number and a free text field for their skills or experience.
    1. After that, they can select the event from a list of upcoming events.
    1. They can check all roles they are interested in, 


## Site structure

- index: Welcom page
- Staff: Edit personal informations
- Event/{tagname}: Staff edit their attendance
- Admin/Index: List all event, edit website preference
- Admin/Event/{tagname}: edit all informations of the event
- Admin/Event/{tagname}/staff: List all staff with their favorit role or the final assigned role if present, show availability days

## Page details

### /index

It shows a small custom presentation of the association, a logo.
It does not propose to log for admin. (They will go specifically to /admin).
If an user is not recognized and goes to other pages, they are redirected here.

#### If the staff is recognized

If the staff is recognized (based in the cookie), it can show all event they are registered. 
It shows also all other available events. (they can click to go to the specific event page).
There is a logout button.
The staff is kept connected for 60 days, reseting each time they come back.
They can go to /staff to edit thier personal informations

#### If the stiff is not recognized

If the staff is not recognized, there are two options

- Propose for new staff to enter their username and email. Then they will receive an email with a connection link and will be treated as a recognized staff
- Propose the staff to enter their username OR email, they will receive an email with a connection link and will be treated as a recognized staff

### /staff

This page is available only if the staff is logged, go back to home if not
Here the staff can change their information: 

- First Name
- Last name
- phone number
- city they live (just the city, not the full address)
- languages they speak
- comment
- optional photo

There is also an action to suppress all their data from the website.

### /event/{tagname}

This page is available only if the staff is logged.
If tagname is wrong, go back to home.
It show the long description and all available informations for the event.
If the staff is not yet registered for the event, they can tell they are in. It will change it's state to registered.

If the staff is registered, they can:

- Cancel their participation
- Select up to three role by order of preference
- add a comment
- select their availability by half day resolution (morning / afternoon)
- they can tell if they want to help before the contest (checkbox)
- Tell their affiliation with other teams
- tell if it is their first participation (checkbox)

They will see:
- A flag telling their registration is complete or not (if everyhing is correctly filled)
- If the admin has validated their participation
- If the admin has validated their final role
- A link to the Whatsapp group
- Links to documents to read before the event

### /admin

Page for admin, it will ask for user/password
It will list all events with agreated informations (registered staff count, dates)
They can edit website preference: Small description of the association, logo, website, ling to general Whatsapp channel.
We can create a new event or copy an event.

### /admin/event/{tagname}

Admin can edit informations about the event

- Title
- Small Description
- Description
- Start date
- End date
- Available roles (Designation, number required, links to specific document)
- Links to WhatsApp group
- Links to all general documents

### /admin/event/{tagname}/staff

List all staff with their favorit role or the final assigned role if present, show availability days.

Allows to validate the staff venue.
Allows to set the final role for each staff. It could be a dropdown with their prefered roles in order, then all other roles.

There is an availability view:
Shows a grid with all staff and their availability. 

And a contact view :
Shows a grid with all staff and their contact information.

## Implementation Clarifications

### Authentication
- **Staff authentication**: Uses Laravel's built-in session/cookie system with custom token-based identification
  - Token is stored in the database linked to each staff member
  - Email with connection links are sent directly without queue system
  - Staff sessions last 60 days, refreshed on each visit
- **Admin authentication**: Uses Laravel's built-in authentication system
  - Simple admin/staff distinction (no role-based permissions for admins)

### Development
- Vue components: Implemented as needed, prioritizing reusable components
- Localization: Laravel's localization system with translation files for English and French
  - Only UI elements are translated
  - Database content (event descriptions, etc.) stored in a single language

### Data Model
- Staff can register for multiple events
- For each event, staff can select multiple preferred roles (up to 3 in order of preference)
- Admins assign the final role for each staff member per event
- **Event Roles**: Each event has custom roles (not predefined globally)
- **Event Copy**: Duplicates event structure and roles, but NOT staff registrations
- **Business Rules**:
  - Staff cannot register for events that have already passed
  - Admins CAN over-assign roles (assign more staff than needed)

### File Management
- Staff photos and event logos stored locally in Laravel storage
- **Image formats**: Only JPG and PNG allowed
- **Image size**: If uploaded images are larger than 1000x1000 pixels, they will be automatically resized to this maximum dimension (maintaining aspect ratio)

### Availability System
- Multi-day events: Staff select availability with half-day resolution (morning/afternoon) for each day separately

### Staff Registration Flow
- When staff first register with email/username, account is created immediately with a token
- After clicking email link, staff can immediately register for events
- Index page shows information banner if staff profile is incomplete, inviting them to fill missing information

### Team Affiliation
- Simple text field for staff to mention which teams they're affiliated with
- No business logic or validation

### Links & Documents
- WhatsApp group links: Simple text fields containing URLs
- Document links: Simple text fields containing URLs
- No file upload functionality for documents

### Notifications
Email notifications are sent in the following scenarios:

#### Staff Notifications:
- Admin validates their participation for an event
- Admin assigns them a final role
- Admin sends an event reminder with all information

#### Admin Notifications:
- New staff registers for an event
- Staff changes their role preferences or availability
- **Cooldown mechanism**: Maximum 1 email every 5 minutes per staff member (to prevent spam from multiple rapid changes)
