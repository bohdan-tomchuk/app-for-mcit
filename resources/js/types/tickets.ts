export type Option = { value: string; label: string };

export type TicketFormValues = {
    title: string;
    description: string;
    priority: string;
};

export type Ticket = {
    id: number;
    title: string;
    description?: string;
    priority: Option;
    status: Option;
    created_at: string | null;
    updated_at?: string | null;
    author?: string;
};

export type StatusChange = {
    id: number;
    from_status: Option;
    to_status: Option;
    actor: { id: number; name: string };
    created_at: string | null;
};

export type Paginated<T> = {
    data: T[];
    links: { first: string | null; last: string | null; prev: string | null; next: string | null };
    meta: { current_page: number; last_page: number; total: number };
};
