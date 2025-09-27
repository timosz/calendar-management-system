import { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
}

export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
};

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;

// Calendar Management System Types

export type BookingStatus = 'pending' | 'confirmed' | 'rejected' | 'cancelled';

export type RestrictionType = 'holiday' | 'break' | 'meeting' | 'personal' | 'maintenance' | 'other';

export interface Booking {
    id: number;
    user_id: number;
    client_name: string;
    client_email: string;
    client_phone?: string;
    booking_date: string;
    start_time: string;
    end_time: string;
    status: BookingStatus;
    notes?: string;
    google_calendar_event_id?: string;
    created_at: string;
    updated_at: string;
}

export interface Availability {
    id: number;
    user_id: number;
    day_of_week: number; // 0=Sunday, 1=Monday, etc.
    start_time: string;
    end_time: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface Restriction {
    id: number;
    user_id: number;
    start_date: string;
    end_date: string;
    start_time?: string;
    end_time?: string;
    reason?: string;
    type: RestrictionType;
    created_at: string;
    updated_at: string;
}

// Dashboard specific types

export interface DashboardStats {
    total_bookings: number;
    pending_bookings: number;
    confirmed_bookings: number;
    this_week_bookings: number;
    this_month_bookings: number;
    active_availabilities: number;
    current_restrictions: number;
}

export interface StatusDistribution {
    [key: string]: number;
}

export interface TrendData {
    week: string;
    bookings: number;
}

export interface MonthlyData {
    month: string;
    bookings: number;
}

export interface AvailabilityPeriod {
    time_range: string;
    is_active: boolean;
}

export interface AvailabilitySummary {
    [key: string]: {
        day: string;
        availabilities: AvailabilityPeriod[];
    };
}

export interface DashboardPageProps {
    // Base AppPageProps
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;

    // Dashboard specific props
    stats: DashboardStats;
    recentBookings: Booking[];
    upcomingBookings: Booking[];
    todaysBookings: Booking[];
    currentRestrictions: Restriction[];
    statusDistribution: StatusDistribution;
    weeklyTrend: TrendData[];
    monthlyStats: MonthlyData[];
    availabilitySummary: AvailabilitySummary;
    currentDate: string;
    currentTime: string;
}
