export type Option = { value: string; label: string };

export type TicketFormValues = {
    title: string;
    description: string;
    priority: string;
};

export type Ticket = {
    id: number;
    title: string;
    priority: Option;
    status: Option;
    created_at: string | null;
    author?: string;
};

export type PaginationLink = { url: string | null; label: string; active: boolean };

export type Paginated<T> = {
    data: T[];
    links: { first: string | null; last: string | null; prev: string | null; next: string | null };
    meta: { current_page: number; last_page: number; total: number; links: PaginationLink[] };
};
