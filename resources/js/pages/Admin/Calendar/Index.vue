<script setup lang="ts">
    import { show as bookingShowRoute } from '@/actions/App/Http/Controllers/Admin/BookingController';
    import { events as calendarEventsRoute } from '@/actions/App/Http/Controllers/Admin/CalendarController';
    import AppLayout from '@/layouts/AppLayout.vue';
    import type { EventClickArg } from '@fullcalendar/core';
    import dayGridPlugin from '@fullcalendar/daygrid';
    import interactionPlugin from '@fullcalendar/interaction';
    import timeGridPlugin from '@fullcalendar/timegrid';
    import FullCalendar from '@fullcalendar/vue3';
    import { Head, router } from '@inertiajs/vue3';
    import { ref } from 'vue';

    const calendarOptions = ref({
        plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
        initialView: 'timeGridWeek',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay',
        },
        slotMinTime: '06:00:00',
        slotMaxTime: '22:00:00',
        allDaySlot: true,
        nowIndicator: true,
        editable: false,
        selectable: true,
        selectMirror: true,
        dayMaxEvents: true,
        weekends: true,
        height: 'auto',
        events: async (fetchInfo: any, successCallback: any, failureCallback: any) => {
            try {
                const url = calendarEventsRoute.url({
                    query: {
                        start: fetchInfo.startStr,
                        end: fetchInfo.endStr,
                    },
                });

                const response = await fetch(url);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const events = await response.json();
                console.log('Fetched events:', events);
                successCallback(events);
            } catch (error) {
                console.error('Error fetching events:', error);
                failureCallback(error);
            }
        },
        eventClick: handleEventClick,
    });

    function handleEventClick(clickInfo: EventClickArg) {
        const eventType = clickInfo.event.extendedProps.type;
        const eventData = clickInfo.event.extendedProps.data;

        console.log('Event clicked:', eventType, eventData);

        // You can add navigation or modal opening here
        // For example, to navigate to edit pages:
        if (eventType === 'booking') {
            router.visit(bookingShowRoute.url(eventData.id));
        }
    }
</script>

<template>
    <AppLayout>
        <Head title="Calendar" />

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6">
                        <div class="mb-6">
                            <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Calendar</h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                View all your availabilities, restrictions, and bookings in one place
                            </p>
                        </div>

                        <!-- Legend -->
                        <div class="mb-6 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">
                            <h3 class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">Legend</h3>
                            <div class="flex flex-wrap gap-4 text-sm">
                                <div class="flex items-center gap-2">
                                    <div class="h-6 w-6 rounded border border-gray-300 dark:border-gray-600" style="background-color: #86efac"></div>
                                    <span class="text-gray-700 dark:text-gray-300">Available</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="h-6 w-6 rounded border border-gray-300 dark:border-gray-600" style="background-color: #d1d5db"></div>
                                    <span class="text-gray-700 dark:text-gray-300">Restricted</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="h-6 w-6 rounded border border-gray-300 dark:border-gray-600" style="background-color: #3b82f6"></div>
                                    <span class="text-gray-700 dark:text-gray-300">Confirmed Booking</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="h-6 w-6 rounded border border-gray-300 dark:border-gray-600" style="background-color: #f59e0b"></div>
                                    <span class="text-gray-700 dark:text-gray-300">Pending Booking</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="h-6 w-6 rounded border border-gray-300 dark:border-gray-600" style="background-color: #ef4444"></div>
                                    <span class="text-gray-700 dark:text-gray-300">Rejected Booking</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="h-6 w-6 rounded border border-gray-300 dark:border-gray-600" style="background-color: #6b7280"></div>
                                    <span class="text-gray-700 dark:text-gray-300">Cancelled Booking</span>
                                </div>
                            </div>
                        </div>

                        <!-- Calendar -->
                        <div class="calendar-container">
                            <FullCalendar :options="calendarOptions" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
    /* FullCalendar custom styles */
    :root {
        --fc-border-color: hsl(0 0% 92.8%);
        --fc-button-bg-color: #3b82f6;
        --fc-button-border-color: #3b82f6;
        --fc-button-hover-bg-color: #2563eb;
        --fc-button-hover-border-color: #2563eb;
        --fc-button-active-bg-color: #1d4ed8;
        --fc-button-active-border-color: #1d4ed8;
        --fc-today-bg-color: #fef3c7;
        --fc-neutral-bg-color: hsl(0 0% 100%);
        --fc-neutral-text-color: hsl(0 0% 3.9%);
    }

    .dark {
        --fc-border-color: hsl(0 0% 14.9%);
        --fc-today-bg-color: hsl(43 74% 20%);
        --fc-neutral-bg-color: hsl(0 0% 3.9%);
        --fc-neutral-text-color: hsl(0 0% 98%);
    }

    .calendar-container {
        min-height: 600px;
    }

    .fc {
        font-family: inherit;
    }

    /* Calendar title (month/week/day header) */
    .fc .fc-toolbar-title {
        color: var(--fc-neutral-text-color);
    }

    /* Day headers (Mon, Tue, Wed, etc.) */
    .fc .fc-col-header-cell-cushion {
        color: var(--fc-neutral-text-color);
    }

    /* Day numbers in month view */
    .fc .fc-daygrid-day-number {
        color: var(--fc-neutral-text-color);
    }

    /* Time labels in week/day view */
    .fc .fc-timegrid-slot-label-cushion {
        color: var(--fc-neutral-text-color);
    }

    /* Event text in month view (dots view) */
    .fc .fc-daygrid-event-dot {
        border-color: currentColor;
    }

    /* Background color for calendar */
    .fc {
        background-color: var(--fc-neutral-bg-color);
    }

    /* Events should maintain their own colors */
    .fc-event {
        cursor: pointer;
    }

    .fc-event:hover {
        opacity: 0.9;
    }

    /* Ensure buttons text is readable */
    .fc .fc-button {
        color: white;
    }

    /* Today's date in day grid */
    .fc .fc-daygrid-day.fc-day-today {
        background-color: var(--fc-today-bg-color);
    }

    /* All day slot label */
    .fc .fc-timegrid-axis-cushion {
        color: var(--fc-neutral-text-color);
    }

    .fc-theme-standard th {
        background-color: var(--fc-neutral-bg-color);
    }
</style>
