// Staff types
export interface Staff {
    id: string;
    username: string;
    email: string;
    first_name: string | null;
    last_name: string | null;
    phone_number: string | null;
    city: string | null;
    languages: string[] | null;
    comment: string | null;
    photo_path: string | null;
    last_login_at: string | null;
    created_at: string;
    updated_at: string;
}

// Event types
export interface Event {
    id: string;
    name: string;
    tagname: string;
    short_description: string | null;
    long_description: string | null;
    start_date: string;
    end_date: string;
    location: string | null;
    contact_email: string | null;
    logo_path: string | null;
    whatsapp_link: string | null;
    general_documents_links: DocumentLink[] | null;
    days?: EventDay[];
    roles?: EventRole[];
    registrations_count?: number;
    created_at: string;
    updated_at: string;
}

export interface EventDay {
    id: number;
    event_id: string;
    date: string;
    schedule: string | null;
}

export interface EventRole {
    id: number;
    event_id: string;
    designation: string;
    number_required: number;
    document_links: DocumentLink[] | null;
    assigned_count?: number;
}

export interface DocumentLink {
    title: string;
    url: string;
}

// Registration types
export interface StaffEventRegistration {
    id: string;
    staff_id: string;
    event_id: string;
    comment: string | null;
    help_before_event: boolean;
    team_affiliation: string | null;
    is_first_participation: boolean;
    is_validated: boolean;
    assigned_role_id: number | null;
    staff?: Staff;
    event?: Event;
    assigned_role?: EventRole;
    role_preferences?: StaffRolePreference[];
    availability?: StaffAvailability[];
    created_at: string;
    updated_at: string;
}

export interface StaffRolePreference {
    id: number;
    registration_id: string;
    role_id: number;
    preference_order: number;
    role?: EventRole;
}

export interface StaffAvailability {
    id: number;
    registration_id: string;
    event_day_id: number;
    is_available_morning: boolean;
    is_available_afternoon: boolean;
    event_day?: EventDay;
}

// Site preferences
export interface SitePreference {
    id: number;
    association_description: string | null;
    logo_path: string | null;
    website_url: string | null;
    general_whatsapp_link: string | null;
}
