export interface User {
    id: number;
    name: string;
    email: string;
    roles: string[];
    permissions: string[];
    email_verified_at?: string;
    created_at?: string;
}

export interface Paginated<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    meta?: {
        current_page: number;
        from: number | null;
        to: number | null;
        total: number;
        last_page: number;
        per_page: number;
    };
}

export interface ContactList {
    id: number;
    name: string;
    description?: string | null;
    color?: string | null;
    contacts_count?: number;
    created_at?: string;
}

export interface Contact {
    id: number;
    name: string;
    phone_number: string;
    email?: string | null;
    notes?: string | null;
    lists?: ContactList[];
    created_at?: string;
}

export interface Device {
    id: number;
    name: string;
    phone_number: string;
    model?: string | null;
    status: string;
    is_online: boolean;
    battery_level?: number | null;
    charging: boolean;
    signal_strength?: number | null;
    is_active: boolean;
    last_heartbeat_at?: string | null;
    last_heartbeat_human?: string | null;
}

export interface Message {
    id: number;
    uuid: string;
    direction: string;
    from_number?: string | null;
    to_number: string;
    content: string;
    segments: number;
    status: string;
    error?: string | null;
    scheduled_at?: string | null;
    sent_at?: string | null;
    delivered_at?: string | null;
    created_at?: string;
    device?: { id: number; name: string; phone_number: string } | null;
    contact?: { id: number; name: string } | null;
    user?: { id: number; name: string } | null;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
    flash: {
        success: string | null;
        error: string | null;
        new_token: string | null;
    };
};
