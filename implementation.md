# RobotStaff

This web application is designed to help manage all staff arround events.

It will provide:
- Event creation and management for admin.
- Staff registration and scheduling.
- Staff assignation
- Multiple tools modules

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

#### Staff

- Thier account is identified by a unique username and an email. They do not need a password.
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
 - In the registration page, we can first choose the event from a dropdown list of upcoming events.
 - Staff can update their availability for specific dates and times.
 - If the staff already interested in an event, we can show a message indicating that they have already registered for that event.
 
 - Registration steps:
    1. User is asked if they are a new or returning staff member. 
    1. For new memeber, it asks for username, email, phone number and a free text field for their skills or experience.
    1. After that, they can select the event from a dropdown list of upcoming events.
    1. They can check all roles they are interested in, 


## Pages structure

- Index: 
    - Small custom presentation of the association
    - If the staff is recognized (bases in the cookie), it can show all event they are registered. It shows also all other available events. (they can click to go to the specific event page)
    - If the staff is not recognized, it will invite the staff to enter their email, so they will received a connection link to this index page. It will show also all future events with dates and small description.
- Staff
    - This page is available only if the staff is logged, go back to home if not
    - Here the staff can change their information: First Name, Last name, phone number, city they live (just the city, not the full address), languages they speak, comment, optional photo
- Event/{tagname}:
    - This page is available only if the staff is logged
    - If tagname is wrong, go back to home
    - It show the long description and all available informations
    - If the staff is not yet registered for the event, they can tell they are in. It will directly go to the next part
    - If they are already registered, they can
      - Cancel their participation
      - they can select up to three role by order of preference
      - add a comment
      - select their availability by half day resolution (morning / afternoon)
      - they can tell if they want to help before the contest (checkbox)
      - Tell their affiliation with other teams
      - tell if it is their first participation (checkbox)
      - They will have a flag telling their registration is complete
      - Once the admin has validated thier role, it will be displayed here
- Admin/Index:
    - Page for admin, with login request
    - It will list all events
- Admin/Event/{tagname}
    - Allows to edit all informations of the event
- Admin/Event/{tagname}/staff
    - List all staff with their favorit role or the final assigned role if present, show availability days
